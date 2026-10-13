<?php

namespace App\Http\Controllers;

use App\Exceptions\PickupLinkUnavailableException;
use App\Http\Requests\StorePublicPickupRequest;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Country;
use App\Models\Product;
use App\Models\Receiver;
use App\Models\ShipmentRequest;
use App\Models\ShipmentRequestLink;
use App\Services\ShipmentRequestSubmissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * No-login pickup request form reached through a unique link staff send to a
 * client (see ShipmentRequestLink). Submissions join the normal Shipment
 * Requests queue in the admin panel.
 */
class PublicShipmentRequestController extends Controller
{
    public function show(string $token): Response
    {
        $link = ShipmentRequestLink::with('client')->where('token', $token)->first();

        if (! $link || ! $link->isUsable()) {
            return $this->unavailable($link);
        }

        $oldProductIds = collect(old('receivers', []))
            ->flatMap(fn ($receiver) => $receiver['items'] ?? [])
            ->pluck('product_id')
            ->filter()
            ->unique();

        return response()->view('external.pickup-request-form', [
            'link' => $link,
            'countries' => Country::orderBy('name')->pluck('name'),
            'selectedProducts' => $this->productsQuery($link)
                ->whereIn('id', $oldProductIds)
                ->get()
                ->map(fn (Product $product): array => $this->productOption($product)),
            'previousReceivers' => $link->client ? $this->previousReceivers($link->client) : [],
        ]);
    }

    /**
     * Product search for the form's item picker. Only answers for a usable link.
     */
    public function products(Request $request, string $token): JsonResponse
    {
        $link = ShipmentRequestLink::where('token', $token)->first();

        if (! $link || ! $link->isUsable()) {
            return response()->json([], 404);
        }

        $search = trim((string) $request->query('q', ''));

        $products = $this->productsQuery($link)
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json($products->map(fn (Product $product): array => $this->productOption($product))->values());
    }

    private function productsQuery(ShipmentRequestLink $link): Builder
    {
        return Product::query()
            ->select(['id', 'name', 'category', 'value'])
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $link->branch_id));
    }

    /**
     * @return array{id: string, name: string, category: ?string, value: string}
     */
    private function productOption(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category ? ucfirst($product->category) : null,
            'value' => number_format((float) $product->value, 2, '.', ''),
        ];
    }

    /**
     * Receivers the client has shipped to before (approved shipments and earlier
     * requests), de-duplicated by name and phone.
     *
     * @return list<array{name: string, phone: ?string, country: ?string, state_region: ?string, city: ?string, address: ?string}>
     */
    private function previousReceivers(Client $client): array
    {
        $fromShipments = Receiver::whereHas('shipment', fn ($q) => $q->where('client_id', $client->id))
            ->latest()
            ->limit(200)
            ->get(['receiver_name', 'receiver_phone', 'country', 'state_region', 'city', 'address'])
            ->map(fn (Receiver $receiver): array => [
                'name' => $receiver->receiver_name,
                'phone' => $receiver->receiver_phone,
                'country' => $receiver->country,
                'state_region' => $receiver->state_region,
                'city' => $receiver->city,
                'address' => $receiver->address,
            ]);

        $fromRequests = ShipmentRequest::where('client_id', $client->id)
            ->latest()
            ->limit(50)
            ->pluck('receivers')
            ->flatten(1)
            ->map(fn ($receiver): array => [
                'name' => $receiver['name'] ?? null,
                'phone' => $receiver['phone'] ?? null,
                'country' => $receiver['country'] ?? null,
                'state_region' => $receiver['state_region'] ?? null,
                'city' => $receiver['city'] ?? null,
                'address' => $receiver['address'] ?? null,
            ]);

        return $fromShipments->concat($fromRequests)
            ->filter(fn (array $receiver): bool => filled($receiver['name']))
            ->unique(fn (array $receiver): string => mb_strtolower(trim($receiver['name'])).'|'.preg_replace('/\D/', '', (string) $receiver['phone']))
            ->sortBy(fn (array $receiver): string => mb_strtolower($receiver['name']))
            ->values()
            ->all();
    }

    public function store(
        StorePublicPickupRequest $request,
        string $token,
        ShipmentRequestSubmissionService $submissions,
    ): Response|RedirectResponse {
        $link = $request->link();

        if (! $link || ! $link->isUsable()) {
            return $this->unavailable($link);
        }

        $data = $request->validated();
        $data['receivers'] = ShipmentRequest::normaliseReceivers($data['receivers']);

        try {
            $submissions->submit($this->resolveClient($link, $data), $data, $link);
        } catch (PickupLinkUnavailableException) {
            return $this->unavailable($link->fresh());
        }

        return redirect()->route('public-pickup-request.submitted', $token);
    }

    public function submitted(string $token): Response
    {
        $link = ShipmentRequestLink::with('client')->where('token', $token)->firstOrFail();

        return response()->view('external.pickup-request-submitted', [
            'link' => $link,
            'canSubmitAnother' => $link->isUsable(),
        ]);
    }

    /**
     * The link's own client when it has one; otherwise an existing client with the
     * same phone number, or a new client in the link's branch.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveClient(ShipmentRequestLink $link, array $data): Client
    {
        if ($link->client) {
            return $link->client;
        }

        $phone = preg_replace('/[\s\-()]/', '', $data['sender_phone']);

        return DB::transaction(function () use ($link, $data, $phone) {
            $client = Client::where('phone', $phone)->first();

            if ($client) {
                if (! $client->email && ! empty($data['sender_email'])) {
                    $client->update(['email' => $data['sender_email']]);
                }

                return $client;
            }

            return Client::create([
                'branch_id' => $link->branch_id ?? Branch::query()->value('id'),
                'name' => $data['sender_name'],
                'phone' => $phone,
                'email' => $data['sender_email'] ?? null,
                'address' => $data['pickup_location'],
            ]);
        });
    }

    private function unavailable(?ShipmentRequestLink $link): Response
    {
        return response()->view('external.pickup-request-unavailable', [
            'reason' => $link?->state() ?? 'invalid',
        ], $link ? 410 : 404);
    }
}

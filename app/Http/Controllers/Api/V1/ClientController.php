<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Client;
use App\Models\Shipment;
use App\Models\ClientRating;
use Illuminate\Http\Request;
use App\Models\ClientInteraction;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\ClientResource;
use App\Http\Controllers\Api\V1\BaseApiController;

class ClientController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {        
        abort_unless(auth()->user()->can('view_any_client'), 403);

        $branchId = $this->resolveBranch($request);
        $query    = Client::where('branch_id', $branchId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($c) => new ClientResource($c));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_client'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:30',
            'country'      => 'nullable|string',
            'state_region' => 'nullable|string',
            'city'         => 'nullable|string',
            'address'      => 'nullable|string',
            'id_type'      => 'nullable|string',
            'id_number'    => 'nullable|string',
        ]);

        $client = Client::create(array_merge(
            $request->only(['name', 'email', 'phone', 'country', 'state_region', 'city', 'address', 'id_type', 'id_number']),
            ['branch_id' => $branchId]
        ));

        return $this->success(new ClientResource($client), 'Client created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_client'), 403);

        $branchId = $this->resolveBranch($request);
        $client   = Client::where('branch_id', $branchId)->findOrFail($id);

        return $this->success(new ClientResource($client));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_client'), 403);

        $branchId = $this->resolveBranch($request);
        $client   = Client::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'name'         => 'sometimes|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:30',
            'country'      => 'nullable|string',
            'state_region' => 'nullable|string',
            'city'         => 'nullable|string',
            'address'      => 'nullable|string',
            'id_type'      => 'nullable|string',
            'id_number'    => 'nullable|string',
        ]);

        $client->update($request->only(['name', 'email', 'phone', 'country', 'state_region', 'city', 'address', 'id_type', 'id_number']));

        return $this->success(new ClientResource($client->fresh()));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_client'), 403);

        $branchId = $this->resolveBranch($request);
        $client   = Client::where('branch_id', $branchId)->findOrFail($id);
        $client->delete();

        return $this->success(null, 'Client deleted.');
    }

    public function shipments(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_client'), 403);

        $branchId = $this->resolveBranch($request);
        Client::where('branch_id', $branchId)->findOrFail($id);

        $paginated = Shipment::where('client_id', $id)
            ->with(['originBranch:id,name', 'destinationBranch:id,name'])
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($s) => [
            'id'                => $s->id,
            'shipping_reference'=> $s->shipping_reference,
            'status'            => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
            'payment_status'    => $s->payment_status,
            'total'             => $s->total,
            'paid'              => $s->paid,
            'shipped_at'        => $s->shipped_at,
            'destination_branch'=> $s->destinationBranch?->name,
        ]);
    }

    public function interactions(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_client'), 403);

        $branchId = $this->resolveBranch($request);
        Client::where('branch_id', $branchId)->findOrFail($id);

        $interactions = ClientInteraction::where('client_id', $id)
            ->with('staff:id,name')
            ->latest()
            ->get()
            ->map(fn ($i) => [
                'id'                 => $i->id,
                'interaction_type'   => $i->interaction_type instanceof \BackedEnum ? $i->interaction_type->value : $i->interaction_type,
                'subject'            => $i->subject,
                'notes'              => $i->notes,
                'outcome'            => $i->outcome instanceof \BackedEnum ? $i->outcome->value : $i->outcome,
                'follow_up_date'     => $i->follow_up_date,
                'follow_up_completed'=> $i->follow_up_completed,
                'created_at'         => $i->created_at,
                'staff'              => $i->staff ? ['id' => $i->staff->id, 'name' => $i->staff->name] : null,
            ]);

        return $this->success($interactions);
    }

    public function ratings(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_client'), 403);

        $branchId = $this->resolveBranch($request);
        Client::where('branch_id', $branchId)->findOrFail($id);

        $ratings = ClientRating::where('client_id', $id)
            ->latest()
            ->get(['id', 'client_id', 'payment_reliability', 'shipment_frequency', 'total_lifetime_value', 'total_shipments', 'is_vip', 'internal_notes', 'created_at']);

        return $this->success($ratings);
    }


}

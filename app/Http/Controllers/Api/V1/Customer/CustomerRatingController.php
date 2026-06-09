<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerRatingController extends CustomerBaseController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'shipment_id' => 'required|uuid|exists:shipments,id',
            'rating'      => 'required|integer|min:1|max:5',
            'comment'     => 'nullable|string|max:1000',
        ]);

        $shipment = Shipment::where('client_id', $this->clientId())
            ->where('status', 'delivered')
            ->findOrFail($request->shipment_id);

        // Store rating in contact_messages for now — to be replaced when a ratings table is created
        $user = auth()->user();
        \App\Models\ContactMessage::create([
            'name'    => $user->name,
            'email'   => $user->email,
            'phone'   => $user->phone,
            'subject' => "Rating: {$request->rating}/5 for {$shipment->shipping_reference}",
            'message' => $request->comment ?? "Rating: {$request->rating}/5",
            'status'  => 'read',
        ]);

        return $this->success([
            'shipment_id' => $shipment->id,
            'rating'      => $request->rating,
        ], 'Rating submitted. Thank you!', 201);
    }
}

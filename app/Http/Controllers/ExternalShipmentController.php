<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use App\Models\Country;
use App\Models\Receiver;
use App\Models\Shipment;
use Illuminate\Http\Request;
use App\Models\ShipmentItem;

class ExternalShipmentController extends Controller
{
    /**
     * Show the external shipment form for clients
     */
    public function showForm(string $token)
    {
        $shipment = Shipment::where('external_token', $token)
            ->where('external_form_completed', false)
            ->with(['client', 'receivers.items.product'])
            ->first();

        if (!$shipment) {
            return view('external.shipment-form-expired');
        }

        $countries = Country::orderBy('name')->pluck('name', 'id');

        return view('external.shipment-form', compact('shipment', 'countries'));
    }

    /**
     * Get states for a country (AJAX)
     */
    public function getStates(Request $request)
    {
        $states = State::where('country_id', $request->country_id)
            ->orderBy('name')
            ->pluck('name', 'id');

        return response()->json($states);
    }

    /**
     * Get cities for a state (AJAX)
     */
    public function getCities(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)
            ->orderBy('name')
            ->pluck('name', 'id');

        return response()->json($cities);
    }

    /**
     * Submit the external shipment form
     */
    public function submitForm(Request $request, string $token)
    {
        $shipment = Shipment::where('external_token', $token)
            ->where('external_form_completed', false)
            ->first();

        if (!$shipment) {
            return redirect()->route('external-shipment-expired');
        }

        $validated = $request->validate([
            'receivers' => 'required|array|min:1',
            'receivers.*.receiver_name' => 'required|string|max:255',
            'receivers.*.receiver_phone' => 'nullable|string|max:50',
            'receivers.*.receiver_email' => 'nullable|email|max:255',
            'receivers.*.country' => 'nullable|string',
            'receivers.*.state_region' => 'nullable|string',
            'receivers.*.city' => 'nullable|string',
            'receivers.*.address' => 'nullable|string|max:500',
            'receivers.*.items' => 'required|array|min:1',
            'receivers.*.items.*.description' => 'required|string|max:255',
            'receivers.*.items.*.quantity' => 'required|integer|min:1',
            'receivers.*.items.*.value' => 'required|numeric|min:0',
            'client_note' => 'nullable|string|max:1000',
            'insurance_accepted' => 'nullable|boolean',
        ]);

        // Update shipment with client note and insurance acceptance
        $shipment->update([
            'client_note' => $validated['client_note'] ?? null,
            'insurance_accepted' => $validated['insurance_accepted'] ?? false,
            'external_form_completed' => true,
        ]);

        // Create receivers and their items
        foreach ($validated['receivers'] as $receiverData) {
            $receiver = Receiver::create([
                'shipment_id' => $shipment->id,
                'receiver_name' => $receiverData['receiver_name'],
                'receiver_phone' => $receiverData['receiver_phone'] ?? null,
                'receiver_email' => $receiverData['receiver_email'] ?? null,
                'country' => $receiverData['country'] ?? null,
                'state_region' => $receiverData['state_region'] ?? null,
                'city' => $receiverData['city'] ?? null,
                'address' => $receiverData['address'] ?? null,
            ]);

            // Create items for this receiver
            foreach ($receiverData['items'] as $itemData) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'receiver_id' => $receiver->id,
                    'box_no' => null,
                    'product_id' => null, // Will need to be assigned by admin
                    'quantity' => $itemData['quantity'],
                    'item_cost' => $itemData['value'],
                ]);
            }
        }

        // Calculate totals
        $totalItemCost = $shipment->items()->sum('item_cost');
        $shipment->update([
            'shipping_cost' => $totalItemCost,
            'total' => $totalItemCost + ($shipment->insurance_accepted ? ($shipment->insurance ?? 0) : 0),
        ]);

        return view('external.shipment-form-success', compact('shipment'));
    }
}

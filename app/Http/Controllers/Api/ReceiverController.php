<?php

namespace App\Http\Controllers\Api;

use App\Models\Receiver;
use App\Models\Shipment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReceiverController extends Controller
{
    /**
     * Get previous receivers for a client
     */
    public function getPreviousReceivers(Request $request)
    {
        $clientId = $request->input('client_id');

        if (!$clientId) {
            return response()->json([]);
        }

        // Get unique receivers from previous shipments
        $receivers = Receiver::whereHas('shipment', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })
            ->select([
                'receiver_name',
                'receiver_phone',
                'receiver_email',
                'country',
                'state_region',
                'city',
                'address',
                'receiver_id_type',
                'receiver_id_number',
            ])
            ->distinct()
            ->get()
            ->unique(function ($receiver) {
                return strtolower($receiver->receiver_name) . '-' . $receiver->receiver_phone;
            })
            ->values();

        return response()->json($receivers);
    }
}

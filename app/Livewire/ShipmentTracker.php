<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Shipment;
use App\Models\Branch;

class ShipmentTracker extends Component
{
    public string $query = '';
    public ?array $result = null;
    public bool $searched = false;
    public string $error = '';

    protected $rules = [
        'query' => 'required|string|min:3|max:100',
    ];

    protected $messages = [
        'query.required' => 'Please enter a tracking number or reference.',
        'query.min'      => 'Please enter at least 3 characters.',
    ];

    public function track()
    {
        $this->validate();

        $this->error = '';
        $this->result = null;
        $this->searched = true;

        $shipment = Shipment::where('tracking_number', trim($this->query))
            ->orWhere('shipping_reference', trim($this->query))
            ->with(['originBranch', 'destinationBranch'])
            ->first();

        if (! $shipment) {
            $this->error = 'No shipment found for "' . e($this->query) . '". Please check the number and try again.';
            return;
        }

        $this->result = [
            'reference'         => $shipment->shipping_reference,
            'tracking_number'   => $shipment->tracking_number,
            'status'            => ucfirst($shipment->status->value ?? $shipment->status),
            'status_raw'        => $shipment->status->value ?? $shipment->status,
            'origin'            => $shipment->originBranch?->name ?? 'N/A',
            'destination'       => $shipment->destinationBranch?->name ?? 'N/A',
            'estimated_delivery'=> $shipment->estimated_delivery_date
                ? \Carbon\Carbon::parse($shipment->estimated_delivery_date)->format('M d, Y')
                : null,
            'shipped_at'        => $shipment->shipped_at
                ? \Carbon\Carbon::parse($shipment->shipped_at)->format('M d, Y')
                : null,
            'delivered_at'      => $shipment->delivered_at
                ? \Carbon\Carbon::parse($shipment->delivered_at)->format('M d, Y g:i A')
                : null,
        ];
    }

    public function render()
    {
        return view('livewire.shipment-tracker');
    }
}

<?php

namespace App\Notifications\Ecommerce;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VendorAccountProvisioned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Vendor $vendor) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Vendor Account Approved',
            'body' => "Your vendor account for {$this->vendor->business_name} is active. Check your email for a link to set your password.",
            'type' => 'vendor_provisioned',
            'vendor_id' => $this->vendor->id,
        ];
    }
}

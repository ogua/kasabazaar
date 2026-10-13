<?php

namespace App\Policies;

use App\Models\ShipmentRequestLink;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Pickup request links ride on the Shipment Request permissions: whoever can see
 * the request queue can see the links, and whoever can create requests can issue
 * and revoke links. The resource is excluded from Shield generation so it gets
 * no permissions of its own.
 */
class ShipmentRequestLinkPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_shipment::request');
    }

    public function view(User $user, ShipmentRequestLink $shipmentRequestLink): bool
    {
        return $user->can('view_shipment::request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_shipment::request');
    }

    public function update(User $user, ShipmentRequestLink $shipmentRequestLink): bool
    {
        return $user->can('create_shipment::request');
    }

    public function delete(User $user, ShipmentRequestLink $shipmentRequestLink): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

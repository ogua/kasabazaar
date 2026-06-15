<?php

namespace App\Services;

use App\Enums\EcommerceInventoryLogType;
use App\Exceptions\InsufficientStockException;
use App\Models\EcommerceInventoryLog;
use App\Models\EcommerceOrder;
use App\Models\EcommerceProduct;
use App\Models\User;

class EcommerceInventoryService
{
    public function adjust(
        EcommerceProduct $product,
        int $change,
        EcommerceInventoryLogType $type,
        User $user,
        ?string $reason = null,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): EcommerceInventoryLog {
        $before = $product->stock;
        $after = $before + $change;

        $product->update(['stock' => $after]);

        return EcommerceInventoryLog::create([
            'ecommerce_product_id' => $product->id,
            'branch_id' => $product->branch_id,
            'type' => $type->value,
            'quantity_change' => $change,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $user->id,
        ]);
    }

    public function deductForOrder(EcommerceOrder $order, User $user): void
    {
        foreach ($order->items as $item) {
            $product = EcommerceProduct::find($item->ecommerce_product_id);

            if (! $product) {
                continue;
            }

            if ($product->stock < $item->quantity) {
                throw new InsufficientStockException(
                    "Insufficient stock: {$product->name} has only {$product->stock} units available."
                );
            }

            $this->adjust(
                $product,
                -$item->quantity,
                EcommerceInventoryLogType::Sale,
                $user,
                'Order deduction',
                EcommerceOrder::class,
                $order->id
            );
        }
    }

    public function restoreForOrder(EcommerceOrder $order, User $user): void
    {
        foreach ($order->items as $item) {
            $product = EcommerceProduct::withTrashed()->find($item->ecommerce_product_id);

            if (! $product) {
                continue;
            }

            $this->adjust(
                $product,
                $item->quantity,
                EcommerceInventoryLogType::Return,
                $user,
                'Order cancelled/refunded',
                EcommerceOrder::class,
                $order->id
            );
        }
    }
}

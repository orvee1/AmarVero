<?php

namespace App\Support\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustVariantInventory
{
    public function execute(
        ProductVariant $variant,
        InventoryMovementType $type,
        int $quantity,
        ?User $user = null,
        ?string $reason = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        if ($quantity === 0) {
            throw ValidationException::withMessages([
                'adjustmentForm.quantity' => __('Inventory quantity changes must be greater or less than zero.'),
            ]);
        }

        return DB::transaction(function () use ($variant, $type, $quantity, $user, $reason, $notes, $referenceType, $referenceId): InventoryMovement {
            $lockedVariant = ProductVariant::query()
                ->whereKey($variant->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $nextStockQuantity = $lockedVariant->stock_quantity + $quantity;

            if ($nextStockQuantity < 0) {
                throw ValidationException::withMessages([
                    'adjustmentForm.quantity' => __('Inventory changes cannot reduce stock below zero.'),
                ]);
            }

            $lockedVariant->forceFill([
                'stock_quantity' => $nextStockQuantity,
            ])->save();

            return InventoryMovement::query()->create([
                'product_variant_id' => $lockedVariant->id,
                'user_id' => $user?->id,
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $nextStockQuantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason,
                'notes' => $notes,
            ]);
        });
    }
}

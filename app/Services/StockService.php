<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function adjustStock(
        Product $product,
        StockMovementType $type,
        int $quantity,
        ?string $reference = null,
        ?string $notes = null,
        ?int $userId = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $quantity, $reference, $notes, $userId) {
            $before = $product->quantity;

            $after = match ($type) {
                StockMovementType::Entree, StockMovementType::Inventaire => $before + $quantity,
                StockMovementType::Sortie => max(0, $before - $quantity),
                StockMovementType::Ajustement => $quantity,
            };

            $product->update(['quantity' => $after]);

            return StockMovement::create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => abs($after - $before),
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference' => $reference,
                'notes' => $notes,
                'user_id' => $userId,
            ]);
        });
    }
}

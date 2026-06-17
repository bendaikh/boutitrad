<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OrderStockService
{
    public function __construct(
        private StockService $stockService,
        private AdminNotificationService $adminNotifications,
    ) {}

    /**
     * @param  list<array{product_id: int|string, quantity: int|string}>  $items
     */
    public function assertItemsAvailable(array $items, ?Order $excludeOrder = null): void
    {
        foreach ($this->groupQuantitiesByProduct($items) as $productId => $requestedQty) {
            $available = $this->availableQuantityForProduct((int) $productId, $excludeOrder);

            if ($requestedQty > $available) {
                $product = Product::query()->find($productId);
                $name = $product?->name ?? 'Produit';

                throw ValidationException::withMessages([
                    'items' => sprintf(
                        'Stock insuffisant pour « %s » : %d disponible(s), %d demandée(s).',
                        $name,
                        $available,
                        $requestedQty,
                    ),
                ]);
            }
        }
    }

    public function deductForOrder(Order $order, User $user): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            $this->deductProductQuantity(
                $item->product,
                (int) $item->quantity,
                $order->reference,
                $user->id,
            );
        }
    }

    public function restoreForOrder(Order $order, User $user): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            if (! $item->product) {
                continue;
            }

            $this->restoreProductQuantity(
                $item->product,
                (int) $item->quantity,
                $order->reference,
                $user->id,
            );
        }
    }

    /**
     * @param  Collection<int, OrderItem>  $previousItems
     */
    public function syncOrderItems(Order $order, Collection $previousItems, User $user): void
    {
        $order->loadMissing('items.product');

        $previous = $this->groupOrderItemQuantities($previousItems);
        $current = $this->groupOrderItemQuantities($order->items);

        $productIds = $previous->keys()->merge($current->keys())->unique();

        foreach ($productIds as $productId) {
            $beforeQty = (int) ($previous[$productId] ?? 0);
            $afterQty = (int) ($current[$productId] ?? 0);
            $delta = $afterQty - $beforeQty;

            if ($delta === 0) {
                continue;
            }

            $product = Product::query()->find($productId);

            if (! $product) {
                continue;
            }

            if ($delta > 0) {
                $this->deductProductQuantity($product, $delta, $order->reference, $user->id);
            } else {
                $this->restoreProductQuantity($product, abs($delta), $order->reference, $user->id);
            }
        }
    }

    public function restoreIfReleased(Order $order, OrderStatus $previousStatus, User $user): void
    {
        if ($this->holdsStock($previousStatus) && $this->releasesStock($order->status)) {
            $this->restoreForOrder($order, $user);
        }
    }

    public function holdsStock(OrderStatus $status): bool
    {
        return ! $this->releasesStock($status);
    }

    public function releasesStock(OrderStatus $status): bool
    {
        return in_array($status, [OrderStatus::Annulee, OrderStatus::Retournee], true);
    }

    public function availableQuantityForProduct(int $productId, ?Order $excludeOrder = null): int
    {
        $product = Product::query()->findOrFail($productId);
        $available = (int) $product->quantity;

        if ($excludeOrder) {
            $available += (int) $excludeOrder->items()
                ->where('product_id', $productId)
                ->sum('quantity');
        }

        return max(0, $available);
    }

    /**
     * @param  list<array{product_id: int|string, quantity: int|string}>  $items
     * @return array<int, int>
     */
    private function groupQuantitiesByProduct(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $grouped[$productId] = ($grouped[$productId] ?? 0) + (int) $item['quantity'];
        }

        return $grouped;
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     * @return Collection<int, int>
     */
    private function groupOrderItemQuantities(Collection $items): Collection
    {
        return $items
            ->groupBy('product_id')
            ->map(fn (Collection $rows) => (int) $rows->sum('quantity'));
    }

    private function deductProductQuantity(Product $product, int $quantity, string $reference, ?int $userId): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($product, $quantity, $reference, $userId) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $before = (int) $locked->quantity;

            if ($before < $quantity) {
                throw new InvalidArgumentException(sprintf(
                    'Stock insuffisant pour « %s » (%d disponible(s)).',
                    $locked->name,
                    $before,
                ));
            }

            $this->stockService->adjustStock(
                $locked,
                StockMovementType::Sortie,
                $quantity,
                $reference,
                'Sortie commande',
                $userId,
            );

            $locked->refresh();
            $this->notifyIfLowStock($locked, $before);
        });
    }

    private function restoreProductQuantity(Product $product, int $quantity, string $reference, ?int $userId): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($product, $quantity, $reference, $userId) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $this->stockService->adjustStock(
                $locked,
                StockMovementType::Entree,
                $quantity,
                $reference,
                'Retour stock commande',
                $userId,
            );
        });
    }

    private function notifyIfLowStock(Product $product, int $quantityBefore): void
    {
        $min = (int) ($product->min_quantity ?: 5);
        $after = (int) $product->quantity;

        if ($after <= $min && $quantityBefore > $min) {
            $this->adminNotifications->notifyLowStock($product->fresh());
        }
    }
}

<?php

namespace App\Services;

use App\Enums\ExpenseTreasuryMode;
use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array{
     *     purchases_total: float,
     *     sales_total: float,
     *     charges_total: float,
     *     net_profit: float,
     *     stock_value: float,
     * }
     */
    public function summary(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $purchasesTotal = $this->purchases($dateFrom, $dateTo)->sum('amount');
        $salesTotal = $this->salesQuery($dateFrom, $dateTo)->sum('total');
        $chargesTotal = $this->chargesQuery($dateFrom, $dateTo)->sum('amount');
        $stockValue = (float) Product::query()
            ->selectRaw('COALESCE(SUM(quantity * purchase_price), 0) as value')
            ->value('value');

        return [
            'purchases_total' => round($purchasesTotal, 2),
            'sales_total' => round((float) $salesTotal, 2),
            'charges_total' => round((float) $chargesTotal, 2),
            'net_profit' => round($salesTotal - $purchasesTotal - $chargesTotal, 2),
            'stock_value' => round($stockValue, 2),
        ];
    }

    /**
     * @return Collection<int, array{date: string, reference: string, product: string, supplier: string, amount: float}>
     */
    public function purchases(?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        [$from, $to] = $this->resolveDateRange($dateFrom, $dateTo);

        return Product::query()
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Product $product) => [
                'date' => $product->created_at->format('d/m/Y'),
                'reference' => $product->sku,
                'product' => $product->name,
                'supplier' => filled($product->supplier) ? $product->supplier : '—',
                'amount' => $this->productPurchaseTotal($product),
            ]);
    }

    /**
     * @return Collection<int, array{date: string, reference: string, client: string, commercial: string, amount: float, profit: float}>
     */
    public function sales(?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        return $this->salesQuery($dateFrom, $dateTo)
            ->with(['client:id,name', 'commercial:id,name', 'items.product:id,purchase_price'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Order $order) => [
                'date' => $order->created_at->format('d/m/Y'),
                'reference' => $order->reference,
                'client' => $order->client?->name ?? '—',
                'commercial' => $order->commercial?->name ?? '—',
                'amount' => round((float) $order->total, 2),
                'profit' => $this->orderProfit($order),
            ]);
    }

    /**
     * Bénéfice = prix de vente - coût d'achat, par produit de la commande.
     */
    private function orderProfit(Order $order): float
    {
        $profit = $order->items->sum(function (OrderItem $item) {
            $purchasePrice = (float) ($item->product?->purchase_price ?? 0);

            return ((float) $item->unit_price - $purchasePrice) * (int) $item->quantity;
        });

        return round((float) $profit, 2);
    }

    /**
     * @return Collection<int, array{
     *     category: string,
     *     product: string,
     *     qty_in: int,
     *     qty_out: int,
     *     stock: int,
     *     status: string,
     * }>
     */
    public function stockMovementsSummary(): Collection
    {
        $incomingTypes = [StockMovementType::Entree->value, StockMovementType::Inventaire->value];
        $outgoingTypes = [StockMovementType::Sortie->value];

        $incoming = StockMovement::query()
            ->selectRaw('product_id, COALESCE(SUM(quantity), 0) as total_in')
            ->whereIn('type', $incomingTypes)
            ->groupBy('product_id')
            ->pluck('total_in', 'product_id');

        $outgoing = StockMovement::query()
            ->selectRaw('product_id, COALESCE(SUM(quantity), 0) as total_out')
            ->whereIn('type', $outgoingTypes)
            ->groupBy('product_id')
            ->pluck('total_out', 'product_id');

        return Product::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'category' => $product->category?->name ?? '—',
                'product' => $product->name,
                'qty_in' => (int) ($incoming[$product->id] ?? 0),
                'qty_out' => (int) ($outgoing[$product->id] ?? 0),
                'stock' => (int) $product->quantity,
                'status' => $product->stockStatusLabel(),
            ]);
    }

    /**
     * @return Collection<int, array{date: string, label: string, amount: float, payment_type: string}>
     */
    public function charges(?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        return $this->chargesQuery($dateFrom, $dateTo)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Expense $expense) => [
                'date' => $expense->expense_date->format('d/m/Y'),
                'label' => $expense->title,
                'amount' => round((float) $expense->amount, 2),
                'payment_type' => $expense->treasury_mode?->label() ?? ExpenseTreasuryMode::Caisse->label(),
            ]);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    public function resolveDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $this->parseDate($dateFrom);
        $to = $this->parseDate($dateTo);

        if ($from === null && $to === null) {
            return [null, null];
        }

        $from ??= $to;
        $to ??= $from;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function salesQuery(?string $dateFrom, ?string $dateTo)
    {
        [$from, $to] = $this->resolveDateRange($dateFrom, $dateTo);

        return Order::query()
            ->whereIn('status', $this->confirmedStatuses())
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
    }

    private function productPurchaseTotal(Product $product): float
    {
        $unitPrice = (float) $product->purchase_price;
        $purchasedQty = $this->purchasedQuantity($product);

        return round($unitPrice * $purchasedQty, 2);
    }

    private function purchasedQuantity(Product $product): int
    {
        $outgoing = (int) StockMovement::query()
            ->where('product_id', $product->id)
            ->where('type', StockMovementType::Sortie)
            ->sum('quantity');

        return max(0, (int) $product->quantity + $outgoing);
    }

    private function chargesQuery(?string $dateFrom, ?string $dateTo)
    {
        [$from, $to] = $this->resolveDateRange($dateFrom, $dateTo);

        return Expense::query()
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expense_date', '<=', $to));
    }

    private function parseDate(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function confirmedStatuses(): array
    {
        return [
            OrderStatus::Confirmee->value,
            OrderStatus::EnPreparation->value,
            OrderStatus::Expediee->value,
            OrderStatus::Livree->value,
        ];
    }
}

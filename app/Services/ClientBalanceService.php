<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Collection;

class ClientBalanceService
{
    public function rowsForClient(Client $client): array
    {
        $orders = Order::query()
            ->with('items')
            ->where('client_id', $client->id)
            ->where('status', '!=', OrderStatus::Annulee)
            ->latest()
            ->get();

        $rows = [];

        foreach ($orders as $order) {
            $solde = $order->paymentStatus() === 'paid' ? 0 : $order->orderAmount() - $order->paidAmount();
            $typeRegl = $order->payment_mode?->label() ?? '—';
            $date = $order->created_at->format('d/m/Y');

            if ($order->items->isEmpty()) {
                $rows[] = $this->formatRow($client, $date, $order->reference, $order->orderAmount(), $typeRegl, $solde, $order->paymentStatus());
                continue;
            }

            foreach ($order->items as $item) {
                $rows[] = $this->formatRow($client, $date, $item->product_name, (float) $item->total, $typeRegl, $solde, $order->paymentStatus());
            }
        }

        return $rows;
    }

    public function ordersForClient(Client $client): Collection
    {
        return Order::query()
            ->with('items')
            ->where('client_id', $client->id)
            ->where('status', '!=', OrderStatus::Annulee)
            ->latest()
            ->get();
    }

    private function formatRow(
        Client $client,
        string $date,
        string $designation,
        float $montant,
        string $typeRegl,
        float $solde,
        string $paymentStatus,
    ): array {
        return [
            'id' => $client->formattedId(),
            'nom' => $client->name,
            'date' => $date,
            'designation' => $designation,
            'montant' => $montant,
            'type_regl' => $typeRegl,
            'solde' => $solde,
            'payment_status' => $paymentStatus,
        ];
    }
}

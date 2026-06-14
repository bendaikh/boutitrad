<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMode;
use App\Enums\RegulationStatus;
use App\Enums\SettlementStatus;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function unpaidOrders(User $user): Collection
    {
        return $this->unpaidOrdersQuery($user)
            ->with('client')
            ->orderByDesc('created_at')
            ->get();
    }

    public function unpaidOrdersQuery(User $user): Builder
    {
        return Order::query()
            ->whereNotIn('status', [OrderStatus::Annulee])
            ->whereColumn('amount_paid', '<', 'total')
            ->when($user->isCommercial(), fn ($q) => $q->where('commercial_id', $user->id));
    }

    public function paymentsList(User $user): LengthAwarePaginator
    {
        return OrderPayment::query()
            ->with(['order.client', 'treasury'])
            ->when($user->isCommercial(), function ($q) use ($user) {
                $q->whereHas('order', fn ($oq) => $oq->where('commercial_id', $user->id));
            })
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20);
    }

    public function activeTreasuries(): Collection
    {
        return Treasury::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function record(array $data, User $user): OrderPayment
    {
        return DB::transaction(function () use ($data, $user) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($data['order_id']);

            if ($user->isCommercial() && $order->commercial_id !== $user->id) {
                abort(403);
            }

            $amount = (float) $data['amount'];
            $balance = $order->balanceDue();

            if ($amount <= 0) {
                throw new \InvalidArgumentException('Le montant doit être supérieur à zéro.');
            }

            if ($amount > $balance + 0.01) {
                throw new \InvalidArgumentException('Le montant dépasse le solde du bon.');
            }

            $payment = OrderPayment::create([
                'order_id' => $order->id,
                'payment_date' => $data['payment_date'],
                'payment_mode' => PaymentMode::from($data['payment_mode']),
                'bank' => $data['bank'] ?? null,
                'payment_number' => $data['payment_number'] ?? null,
                'settlement_status' => isset($data['settlement_status'])
                    ? SettlementStatus::from($data['settlement_status'])
                    : null,
                'regulation_status' => RegulationStatus::from($data['regulation_status']),
                'drawer_name' => $data['drawer_name'] ?? null,
                'encashment_date' => $data['encashment_date'] ?? null,
                'treasury_id' => $data['treasury_id'] ?? null,
                'amount' => $amount,
                'created_by' => $user->id,
            ]);

            $order->amount_paid = round($order->paidAmount() + $amount, 2);
            $order->save();

            return $payment;
        });
    }

    public function updateRegulationStatus(OrderPayment $payment, RegulationStatus $status, User $user): void
    {
        $payment->loadMissing('order');

        if ($user->isCommercial() && $payment->order->commercial_id !== $user->id) {
            abort(403);
        }

        $payment->update(['regulation_status' => $status]);
    }
}

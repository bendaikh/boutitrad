<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\CommercialPayroll;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommercialPayrollService
{
    public function __construct(private CommissionService $commissionService) {}

    /**
     * @return list<string>
     */
    public function confirmedStatuses(): array
    {
        return [
            OrderStatus::Confirmee->value,
            OrderStatus::EnPreparation->value,
            OrderStatus::Expediee->value,
            OrderStatus::Livree->value,
        ];
    }

    public function canManagePayrolls(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function commercials(User $user): Collection
    {
        return User::query()
            ->where('role', UserRole::Commercial)
            ->where('is_active', true)
            ->when($user->isCommercial(), fn ($q) => $q->where('id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name', 'commission_rate']);
    }

    /**
     * @return list<string>
     */
    public static function filterKeys(): array
    {
        return [
            'pf_date',
            'pf_reference',
            'pf_pay_month',
            'pf_commercial_id',
            'pf_sales_count',
            'pf_revenue',
            'pf_commission',
            'pf_amount',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function activeFilters(Request $request): array
    {
        return array_filter(
            $request->only(self::filterKeys()),
            fn ($value) => $value !== null && $value !== '',
        );
    }

    public function payrollsList(User $user, Request $request): LengthAwarePaginator
    {
        return $this->filteredQuery($user, $request)
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function allPayrolls(User $user, Request $request): Collection
    {
        return $this->filteredQuery($user, $request)
            ->latest('payment_date')
            ->latest('id')
            ->get();
    }

    private function filteredQuery(User $user, Request $request): Builder
    {
        $query = CommercialPayroll::query()
            ->with('commercial:id,name')
            ->when($user->isCommercial(), fn ($q) => $q->where('commercial_id', $user->id));

        if ($request->filled('pf_date')) {
            $query->whereDate('payment_date', $request->input('pf_date'));
        }

        if ($request->filled('pf_reference')) {
            $query->where('reference', 'like', '%'.$request->input('pf_reference').'%');
        }

        if ($request->filled('pf_pay_month')) {
            $query->where('pay_month', $request->input('pf_pay_month'));
        }

        if ($request->filled('pf_commercial_id')) {
            $commercialId = (int) $request->input('pf_commercial_id');
            if ($user->isCommercial() && $commercialId !== $user->id) {
                abort(403);
            }
            $query->where('commercial_id', $commercialId);
        }

        if ($request->filled('pf_sales_count')) {
            $query->where('sales_count', (int) $request->input('pf_sales_count'));
        }

        if ($request->filled('pf_revenue')) {
            $query->where('revenue', '>=', $this->parseAmount($request->input('pf_revenue')));
        }

        if ($request->filled('pf_commission')) {
            $query->where('commission_amount', '>=', $this->parseAmount($request->input('pf_commission')));
        }

        if ($request->filled('pf_amount')) {
            $query->where('amount_to_pay', '>=', $this->parseAmount($request->input('pf_amount')));
        }

        return $query;
    }

    private function parseAmount(mixed $value): float
    {
        return (float) str_replace(',', '.', preg_replace('/[^\d,.-]/', '', (string) $value));
    }

    public function findForUser(int $id, User $user): CommercialPayroll
    {
        $payroll = CommercialPayroll::query()
            ->with('commercial:id,name,commission_rate')
            ->when($user->isCommercial(), fn ($q) => $q->where('commercial_id', $user->id))
            ->findOrFail($id);

        return $payroll;
    }

    /**
     * @return array{
     *     sales_count: int,
     *     revenue: float,
     *     commission_rate: float,
     *     commission_amount: float,
     *     amount_to_pay: float,
     *     orders: list<array{reference: string, date: string, total: float, status: string, delivery_ref: string|null}>,
     * }
     */
    public function statsForCommercialMonth(int $commercialId, string $payMonth): array
    {
        $commercial = User::query()
            ->where('id', $commercialId)
            ->where('role', UserRole::Commercial)
            ->firstOrFail();

        $month = \Carbon\Carbon::createFromFormat('Y-m', $payMonth)->startOfMonth();
        $from = $month->toDateString();
        $to = $month->copy()->endOfMonth()->toDateString();

        $orders = Order::query()
            ->where('commercial_id', $commercial->id)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->whereIn('status', $this->confirmedStatuses())
            ->orderByDesc('created_at')
            ->get(['id', 'reference', 'created_at', 'total', 'status', 'partner_tracking_ref']);

        $revenue = round((float) $orders->sum('total'), 2);
        $rate = $this->commissionService->rateForCommercial($commercial);
        $commission = round($revenue * ($rate / 100), 2);

        return [
            'sales_count' => $orders->count(),
            'revenue' => $revenue,
            'commission_rate' => $rate,
            'commission_amount' => $commission,
            'amount_to_pay' => $commission,
            'orders' => $orders->map(fn (Order $order) => [
                'reference' => $order->reference,
                'date' => $order->created_at->format('d/m/Y'),
                'total' => round((float) $order->total, 2),
                'status' => $order->status->label(),
                'delivery_ref' => $order->partner_tracking_ref,
            ])->values()->all(),
        ];
    }

    public function duplicateExists(int $commercialId, string $payMonth, ?int $excludeId = null): bool
    {
        return CommercialPayroll::query()
            ->where('commercial_id', $commercialId)
            ->where('pay_month', $payMonth)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function record(array $data, User $user): CommercialPayroll
    {
        if (! $this->canManagePayrolls($user)) {
            abort(403);
        }

        if ($this->duplicateExists((int) $data['commercial_id'], $data['pay_month'])) {
            throw new \InvalidArgumentException('Une paie existe déjà pour ce commercial sur ce mois.');
        }

        $stats = $this->statsForCommercialMonth((int) $data['commercial_id'], $data['pay_month']);

        return DB::transaction(function () use ($data, $user, $stats) {
            return CommercialPayroll::create([
                'reference' => CommercialPayroll::generateReference(),
                'payment_date' => $data['payment_date'],
                'pay_month' => $data['pay_month'],
                'commercial_id' => $data['commercial_id'],
                'sales_count' => $stats['sales_count'],
                'revenue' => $stats['revenue'],
                'commission_rate' => $stats['commission_rate'],
                'commission_amount' => $stats['commission_amount'],
                'amount_to_pay' => $stats['amount_to_pay'],
                'created_by' => $user->id,
            ]);
        });
    }

    public function update(CommercialPayroll $payroll, array $data, User $user): CommercialPayroll
    {
        if (! $this->canManagePayrolls($user)) {
            abort(403);
        }

        if ($this->duplicateExists((int) $data['commercial_id'], $data['pay_month'], $payroll->id)) {
            throw new \InvalidArgumentException('Une paie existe déjà pour ce commercial sur ce mois.');
        }

        $stats = $this->statsForCommercialMonth((int) $data['commercial_id'], $data['pay_month']);

        $payroll->update([
            'payment_date' => $data['payment_date'],
            'pay_month' => $data['pay_month'],
            'commercial_id' => $data['commercial_id'],
            'sales_count' => $stats['sales_count'],
            'revenue' => $stats['revenue'],
            'commission_rate' => $stats['commission_rate'],
            'commission_amount' => $stats['commission_amount'],
            'amount_to_pay' => $stats['amount_to_pay'],
        ]);

        return $payroll->fresh(['commercial:id,name']);
    }

    public function delete(CommercialPayroll $payroll, User $user): void
    {
        if (! $this->canManagePayrolls($user)) {
            abort(403);
        }

        $payroll->delete();
    }
}

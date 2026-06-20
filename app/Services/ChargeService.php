<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ChargeService
{
    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public function resolveMonthRange(?string $month): array
    {
        $parsed = filled($month) && preg_match('/^\d{4}-\d{2}$/', $month)
            ? \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        return [
            $parsed->copy()->startOfMonth()->toDateString(),
            $parsed->copy()->endOfMonth()->toDateString(),
            $parsed->format('Y-m'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function activeFilters(Request $request): array
    {
        [, , $month] = $this->resolveMonthRange($request->input('charge_month'));

        return array_filter([
            'charge_month' => $month,
        ]);
    }

    public function expensesQuery(Request $request)
    {
        [$dateFrom, $dateTo] = array_slice($this->resolveMonthRange($request->input('charge_month')), 0, 2);

        return Expense::query()
            ->with('user:id,name')
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->latest('expense_date')
            ->latest('id');
    }

    public function expensesList(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        return $this->expensesQuery($request)->paginate($perPage)->withQueryString();
    }

    public function allExpenses(Request $request): Collection
    {
        return $this->expensesQuery($request)->get();
    }

    public function find(int $id): Expense
    {
        return Expense::query()->findOrFail($id);
    }
}

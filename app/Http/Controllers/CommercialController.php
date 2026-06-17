<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Commission;
use App\Models\CommercialObjective;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommercialController extends Controller
{
    public function __construct(private CommissionService $commissionService) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        if ($user->isCommercial()) {
            $commercial = $this->commercialRowFor($user);

            return view('commercials.index', [
                'commercials' => collect([$commercial]),
                'commercialView' => true,
            ]);
        }

        $editingCommercial = $request->filled('edit')
            ? User::query()->where('role', UserRole::Commercial)->find($request->edit)
            : null;

        $commercials = $this->commercialRows();

        return view('commercials.index', [
            'commercials' => $commercials,
            'commercialsJson' => $commercials->map(fn (User $commercial) => [
                'id' => $commercial->id,
                'formatted_id' => $commercial->formattedCommercialId(),
                'name' => $commercial->name,
                'phone' => $commercial->phone ?? '',
                'email' => $commercial->email,
                'whatsapp' => $commercial->whatsapp ?? '',
                'prospect_zone' => $commercial->prospect_zone ?? '',
                'commission_rate' => $commercial->commission_rate,
                'effective_commission_rate' => $commercial->effective_commission_rate ?? 0,
                'delivered_orders_count' => $commercial->delivered_orders_count ?? 0,
                'total_sales' => (float) ($commercial->total_sales ?? 0),
                'total_commissions' => (float) ($commercial->total_commissions ?? 0),
            ])->values(),
            'editingCommercial' => $editingCommercial,
            'formActive' => $editingCommercial !== null || $request->boolean('new') || $request->session()->has('errors'),
            'previewCommercialId' => User::previewCommercialId(),
            'defaultCommissionRate' => (float) Setting::get('commission_rate', 5),
            'commercialView' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $this->validateCommercial($request);

        User::create([
            ...$validated,
            'role' => UserRole::Commercial,
            'password' => Hash::make(Str::password(12)),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('commercials.index')->with('success', 'Commercial créé avec succès.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($user->role === UserRole::Commercial, 404);

        $validated = $this->validateCommercial($request, $user);

        $user->update($validated);

        return redirect()->route('commercials.index')->with('success', 'Commercial mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($user->role === UserRole::Commercial, 404);

        if (Order::where('commercial_id', $user->id)->whereIn('status', OrderStatus::activeStatuses())->exists()) {
            return back()->with('error', 'Impossible de supprimer : ce commercial a des commandes en cours.');
        }

        $user->delete();

        return redirect()->route('commercials.index')->with('success', 'Commercial supprimé.');
    }

    public function print(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('commercials.export', [
            'commercials' => $this->commercialRows(),
        ]);
    }

    public function exportExcel(): StreamedResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $commercials = $this->commercialRows();
        $filename = 'commerciaux-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($commercials) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, [
                'ID commercial',
                'Nom commercial',
                'Contact',
                'Email',
                'WhatsApp',
                'Zone prospect',
                'Commission (%)',
                'CA vendu',
                'Cmd. livrées',
                'Total commissions',
            ], ';');

            foreach ($commercials as $commercial) {
                fputcsv($handle, [
                    $commercial->formattedCommercialId(),
                    $commercial->name,
                    $commercial->phone ?? '',
                    $commercial->email,
                    $commercial->whatsapp ?? '',
                    $commercial->prospect_zone ?? '',
                    number_format($commercial->effective_commission_rate ?? 0, 1, ',', ''),
                    number_format($commercial->total_sales ?? 0, 2, ',', ''),
                    $commercial->delivered_orders_count ?? 0,
                    number_format($commercial->total_commissions ?? 0, 2, ',', ''),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(User $user): View
    {
        abort_unless($user->role === UserRole::Commercial, 404);

        if (auth()->user()->isCommercial() && auth()->id() !== $user->id) {
            abort(403);
        }

        return $this->dashboard($this->commercialRowFor($user));
    }

    private function validateCommercial(Request $request, ?User $commercial = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($commercial?->id),
            ],
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'prospect_zone' => 'nullable|string|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);
    }

    private function commercialRows(): Collection
    {
        return $this->commercialStatsQuery()
            ->orderBy('name')
            ->get()
            ->map(fn (User $commercial) => $this->attachCommercialStats($commercial));
    }

    private function commercialRowFor(User $commercial): User
    {
        abort_unless($commercial->role === UserRole::Commercial, 404);

        $row = $this->commercialStatsQuery()
            ->whereKey($commercial->id)
            ->firstOrFail();

        return $this->attachCommercialStats($row);
    }

    private function commercialStatsQuery()
    {
        return User::query()
            ->where('role', UserRole::Commercial)
            ->where('is_active', true)
            ->where('email', '!=', 'commercial@boutitrad.com')
            ->withCount(['commercialOrders as delivered_orders_count' => fn ($q) => $q->where('status', OrderStatus::Livree)])
            ->withSum(
                ['commercialOrders as total_sales' => fn ($q) => $q->where('status', OrderStatus::Livree)],
                'total'
            )
            ->withSum('commissions as total_commissions', 'amount');
    }

    private function attachCommercialStats(User $commercial): User
    {
        $commercial->total_sales = (float) ($commercial->total_sales ?? 0);
        $commercial->total_commissions = (float) ($commercial->total_commissions ?? 0);
        $commercial->effective_commission_rate = $this->commissionService->rateForCommercial($commercial);

        return $commercial;
    }

    private function deliveredOrdersFor(User $commercial): Collection
    {
        return Order::with('client')
            ->where('commercial_id', $commercial->id)
            ->where('status', OrderStatus::Livree)
            ->latest()
            ->get();
    }

    private function commissionsFor(User $commercial): Collection
    {
        return Commission::with('order')
            ->where('user_id', $commercial->id)
            ->latest()
            ->get();
    }

    private function dashboard(User $commercial): View
    {
        $deliveredOrders = $this->deliveredOrdersFor($commercial);
        $totalSales = (float) $commercial->total_sales;
        $ordersCount = Order::where('commercial_id', $commercial->id)->count();
        $objectives = CommercialObjective::where('user_id', $commercial->id)->latest()->limit(3)->get();
        $commissions = $this->commissionsFor($commercial);
        $totalCommissions = (float) $commercial->total_commissions;
        $commissionRate = $commercial->effective_commission_rate;

        return view('commercials.show', compact(
            'commercial', 'deliveredOrders', 'totalSales', 'ordersCount',
            'objectives', 'commissions', 'totalCommissions', 'commissionRate'
        ));
    }
}

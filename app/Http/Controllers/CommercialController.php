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
            return $this->dashboard($user);
        }

        $editingCommercial = $request->filled('edit')
            ? User::query()->where('role', UserRole::Commercial)->find($request->edit)
            : null;

        $commercials = $this->commercialRows();

        return view('commercials.index', [
            'commercials' => $commercials,
            'editingCommercial' => $editingCommercial,
            'formActive' => $editingCommercial !== null || $request->boolean('new') || $request->session()->has('errors'),
            'previewCommercialId' => User::previewCommercialId(),
            'defaultCommissionRate' => (float) Setting::get('commission_rate', 5),
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
            ], ';');

            foreach ($commercials as $commercial) {
                fputcsv($handle, [
                    $commercial->formattedCommercialId(),
                    $commercial->name,
                    $commercial->phone ?? '',
                    $commercial->email,
                    $commercial->whatsapp ?? '',
                    $commercial->prospect_zone ?? '',
                    $commercial->commission_rate !== null ? number_format($commercial->commission_rate, 1, ',', '') : '',
                    number_format($commercial->total_sales ?? 0, 2, ',', ''),
                    $commercial->delivered_orders_count ?? 0,
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

        return $this->dashboard($user);
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
        return User::where('role', UserRole::Commercial)
            ->where('is_active', true)
            ->where('email', '!=', 'commercial@boutitrad.com')
            ->withCount(['commercialOrders as delivered_orders_count' => fn ($q) => $q->where('status', OrderStatus::Livree)])
            ->orderBy('name')
            ->get()
            ->map(function ($commercial) {
                $commercial->total_sales = Order::where('commercial_id', $commercial->id)
                    ->where('status', OrderStatus::Livree)
                    ->sum('total');

                return $commercial;
            });
    }

    private function dashboard(User $commercial): View
    {
        $orders = Order::with('client')
            ->where('commercial_id', $commercial->id)
            ->latest()
            ->limit(10)
            ->get();
        $deliveredOrders = Order::with('client')
            ->where('commercial_id', $commercial->id)
            ->where('status', OrderStatus::Livree)
            ->latest()
            ->limit(15)
            ->get();
        $totalSales = Order::where('commercial_id', $commercial->id)->where('status', OrderStatus::Livree)->sum('total');
        $ordersCount = Order::where('commercial_id', $commercial->id)->count();
        $objectives = CommercialObjective::where('user_id', $commercial->id)->latest()->limit(3)->get();
        $commissions = Commission::with('order')
            ->where('user_id', $commercial->id)
            ->latest()
            ->limit(10)
            ->get();
        $totalCommissions = Commission::where('user_id', $commercial->id)->sum('amount');
        $commissionRate = $this->commissionService->rateForCommercial($commercial);

        return view('commercials.show', compact(
            'commercial', 'orders', 'deliveredOrders', 'totalSales', 'ordersCount',
            'objectives', 'commissions', 'totalCommissions', 'commissionRate'
        ));
    }
}

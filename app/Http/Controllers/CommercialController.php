<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\DashboardService;
use App\Support\CommercialEmail;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommercialController extends Controller
{
    public function __construct(
        private CommissionService $commissionService,
        private DashboardService $dashboard,
    ) {}

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

        $viewingCommercial = $request->filled('view') && ! $editingCommercial
            ? User::query()->where('role', UserRole::Commercial)->find($request->view)
            : null;

        if ($viewingCommercial) {
            $viewingCommercial = $this->commercialRowFor($viewingCommercial);
        }

        if ($editingCommercial) {
            $editingCommercial = $this->commercialRowFor($editingCommercial);
        }

        $commercials = $this->commercialRows();
        $displayCommercial = $editingCommercial ?? $viewingCommercial;
        $selectedCommercialId = $displayCommercial?->id ?? ($request->filled('selected') ? (int) $request->input('selected') : null);

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
                'is_active' => (bool) $commercial->is_active,
            ])->values(),
            'editingCommercial' => $editingCommercial,
            'viewingCommercial' => $viewingCommercial,
            'isViewMode' => $viewingCommercial !== null && $editingCommercial === null,
            'formActive' => $editingCommercial !== null || $viewingCommercial !== null || $request->boolean('new') || $request->session()->has('errors'),
            'previewCommercialId' => User::previewCommercialId(),
            'defaultCommissionRate' => (float) Setting::get('commission_rate', 5),
            'commercialView' => false,
            'permissionGroups' => PermissionCatalog::groupsForRole('commercial'),
            'commercialPermissions' => old(
                'permissions',
                $displayCommercial?->effectivePermissions() ?? PermissionCatalog::defaultsForRole('commercial')
            ),
            'selectedCommercialId' => $selectedCommercialId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $this->validateCommercial($request);

        $permissions = PermissionCatalog::sanitizeForRole(
            UserRole::Commercial->value,
            $validated['permissions'] ?? []
        );

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'prospect_zone' => $validated['prospect_zone'] ?? null,
            'commission_rate' => $validated['commission_rate'] ?? null,
            'password' => $validated['password'],
            'role' => UserRole::Commercial,
            'permissions' => $permissions,
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('commercials.index', ['view' => $user->id])
            ->with('success', 'Commercial créé. Il peut se connecter avec son login et mot de passe.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($user->role === UserRole::Commercial, 404);

        $validated = $this->validateCommercial($request, $user);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'prospect_zone' => $validated['prospect_zone'] ?? null,
            'commission_rate' => $validated['commission_rate'] ?? null,
            'permissions' => PermissionCatalog::sanitizeForRole(
                UserRole::Commercial->value,
                $validated['permissions'] ?? []
            ),
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return redirect()
            ->route('commercials.index', ['view' => $user->id])
            ->with('success', 'Commercial mis à jour.');
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
        if ($request->input('password') === '') {
            $request->merge(['password' => null]);
        }

        if ($request->filled('email')) {
            $request->merge(['email' => CommercialEmail::normalize($request->input('email'))]);
        } elseif ($request->filled('email_local')) {
            $request->merge(['email' => CommercialEmail::fromInput($request->input('email_local'))]);
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => CommercialEmail::rules($commercial),
            'password' => [$commercial ? 'nullable' : 'required', 'string', Password::min(8)],
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'prospect_zone' => 'nullable|string|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(PermissionCatalog::keys())],
            'is_active' => 'nullable|boolean',
        ], [
            'email.required' => 'Le login (email) est obligatoire.',
            'email.email' => 'Le login doit être une adresse email valide.',
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'password.required' => 'Le mot de passe est obligatoire pour un nouveau commercial.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
            ...CommercialEmail::messages(),
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

    private function dashboard(User $commercial): View
    {
        return view('dashboard.commercial', [
            'commercial' => $commercial,
            'stats' => $this->dashboard->commercialStats($commercial),
            'orders' => $this->dashboard->commercialOrders($commercial),
            'stockProducts' => $this->dashboard->commercialStock(),
            'clients' => $this->dashboard->commercialClients($commercial),
        ]);
    }
}

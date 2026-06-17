<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Support\CommercialEmail;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'settings' => [
                'company_name' => Setting::get('company_name', 'BoutiTrad'),
                'company_email' => Setting::get('company_email', ''),
                'company_phone' => Setting::get('company_phone', ''),
                'company_address' => Setting::get('company_address', ''),
                'order_prefix' => Setting::get('order_prefix', 'CMD'),
                'commission_rate' => Setting::get('commission_rate', '5'),
                'delivery_fee' => Setting::get('delivery_fee', '0'),
                'invoice_footer' => Setting::get('invoice_footer', ''),
                'notification_email' => Setting::get('notification_email', '1'),
            ],
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email',
            'company_phone' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:500',
            'order_prefix' => 'nullable|string|max:20',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'delivery_fee' => 'nullable|numeric|min:0',
            'invoice_footer' => 'nullable|string',
            'notification_email' => 'nullable|boolean',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) $value, 'general');
        }

        return back()->with('success', 'Paramètres enregistrés.');
    }

    public function permissions(): View
    {
        return view('settings.permissions', [
            'roles' => UserRole::cases(),
            'users' => User::orderBy('name')->get(),
            'permissionGroups' => config('permissions.groups', []),
            'permissionKeys' => PermissionCatalog::keys(),
        ]);
    }

    public function storePermissionUser(Request $request): RedirectResponse
    {
        $validated = $this->validatePermissionUser($request, null);

        $role = $validated['role'] instanceof UserRole
            ? $validated['role']->value
            : (string) $validated['role'];

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'permissions' => PermissionCatalog::sanitizeForRole($role, $validated['permissions'] ?? []),
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('settings.permissions')
            ->with('success', "Utilisateur « {$user->name} » créé avec ses autorisations.");
    }

    public function updatePermissionUser(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validatePermissionUser($request, $user);

        $role = $validated['role'] instanceof UserRole
            ? $validated['role']->value
            : (string) $validated['role'];

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'permissions' => PermissionCatalog::sanitizeForRole($role, $validated['permissions'] ?? []),
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return redirect()
            ->route('settings.permissions')
            ->with('success', "Autorisations de « {$user->name} » enregistrées.");
    }

    public function destroyPermissionUser(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin() && User::where('role', UserRole::SuperAdmin)->count() <= 1) {
            return back()->with('error', 'Impossible de supprimer le dernier super admin.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('settings.permissions')
            ->with('success', "Utilisateur « {$name} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePermissionUser(Request $request, ?User $user): array
    {
        if ($request->input('password') === '') {
            $request->merge(['password' => null]);
        }

        if ($request->filled('email')) {
            $request->merge(['email' => CommercialEmail::normalize($request->input('email'))]);
        } elseif ($request->filled('email_local')) {
            $request->merge(['email' => CommercialEmail::fromInput($request->input('email_local'))]);
        }

        $role = $request->input('role', $user?->role?->value);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => CommercialEmail::rulesForRole($user, is_string($role) ? $role : $role?->value),
            'password' => [$user ? 'nullable' : 'required', 'string', Password::min(8)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(PermissionCatalog::keys())],
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Le nom utilisateur est obligatoire.',
            'email.required' => 'Le login (email) est obligatoire.',
            'email.email' => 'Le login doit être une adresse email valide.',
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'password.required' => 'Le mot de passe est obligatoire pour un nouvel utilisateur.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
            'role.required' => 'Le profil est obligatoire.',
            ...CommercialEmail::messages(),
        ]);

        if ($user?->isSuperAdmin()) {
            $newRole = $validated['role'] instanceof UserRole
                ? $validated['role']->value
                : (string) $validated['role'];

            if ($newRole !== UserRole::SuperAdmin->value && User::where('role', UserRole::SuperAdmin)->count() <= 1) {
                abort(422, 'Impossible de retirer le rôle super admin au dernier compte administrateur.');
            }
        }

        return $validated;
    }
}

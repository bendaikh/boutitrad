<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }
}

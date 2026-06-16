<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMode;
use App\Enums\ProspectionSource;
use App\Enums\UserRole;
use App\Enums\OrderStatus;
use App\Models\City;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use App\Services\ClientBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function __construct(private ClientBalanceService $clientBalance) {}
    public function index(Request $request): View
    {
        $clients = Client::query()
            ->with('commercial')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $editingClient = $request->filled('edit')
            ? Client::query()->with('commercial')->find($request->edit)
            : null;

        $formActive = $editingClient !== null || $request->boolean('new');

        return view('clients.index', [
            'clients' => $clients,
            'editingClient' => $editingClient,
            'formActive' => $formActive,
            'commercials' => $this->commercials(),
            'prospectionSources' => ProspectionSource::cases(),
            'paymentModes' => PaymentMode::cases(),
            'cities' => $this->cities(),
        ]);
    }

    public function create(): View
    {
        return view('clients.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateClient($request);
        unset($validated['remove_photo'], $validated['photo']);
        $validated = $this->resolveCityFields($validated);
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('client-photos', 'public');
        }

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client créé avec succès.');
    }

    public function print(Client $client): View
    {
        $client->load('commercial');

        return view('clients.print', compact('client'));
    }

    public function show(Client $client): View
    {
        $client->load(['orders' => fn ($q) => $q->latest()->limit(20), 'commercial']);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', array_merge(['client' => $client], $this->formData()));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $this->validateClient($request);
        unset($validated['remove_photo'], $validated['photo']);
        $validated = $this->resolveCityFields($validated);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['photo'] = $this->resolveClientPhoto($request, $client);

        $client->update($validated);

        return redirect()
            ->route('clients.index', request()->only(['search', 'status']))
            ->with('success', 'Client mis à jour.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->photo) {
            Storage::disk('public')->delete($client->photo);
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client supprimé.');
    }

    public function balances(Request $request): View
    {
        $orders = Order::query()
            ->with('client')
            ->where('status', '!=', OrderStatus::Annulee)
            ->when($request->filled('client_id'), function ($query) use ($request) {
                $id = (int) preg_replace('/\D/', '', $request->client_id);
                if ($id > 0) {
                    $query->where('client_id', $id);
                }
            })
            ->when($request->filled('client_name'), fn ($query) => $query->whereHas(
                'client',
                fn ($q) => $q->where('name', 'like', '%'.$request->client_name.'%')
            ))
            ->when($request->filled('order_date'), fn ($query) => $query->whereDate('created_at', $request->order_date))
            ->when($request->filled('amount'), function ($query) use ($request) {
                $amount = (float) str_replace([' ', ','], ['', '.'], $request->amount);
                $query->where('total', $amount);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('clients.balances', compact('orders'));
    }

    public function balancePrint(Client $client): View
    {
        $rows = $this->clientBalance->rowsForClient($client);

        return view('clients.balance-export', compact('client', 'rows'));
    }

    public function balanceExportPdf(Client $client): Response
    {
        $rows = $this->clientBalance->rowsForClient($client);
        $filename = 'balance-'.$client->formattedId().'.pdf';

        $forPdf = true;

        return Pdf::loadView('clients.balance-export', compact('client', 'rows', 'forPdf'))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function balanceExportExcel(Client $client): StreamedResponse
    {
        $rows = $this->clientBalance->rowsForClient($client);
        $filename = 'balance-'.$client->formattedId().'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ID', 'Nom client', 'Date commande', 'Désignation', 'Montant', 'Type règl.', 'Solde'], ';');
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['id'],
                    $row['nom'],
                    $row['date'],
                    $row['designation'],
                    number_format($row['montant'], 2, ',', ' '),
                    $row['type_regl'],
                    number_format($row['solde'], 2, ',', ' '),
                ], ';');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formData(): array
    {
        return [
            'commercials' => $this->commercials(),
            'prospectionSources' => ProspectionSource::cases(),
            'paymentModes' => PaymentMode::cases(),
            'cities' => $this->cities(),
        ];
    }

    private function cities()
    {
        return City::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function resolveCityFields(array $validated): array
    {
        if (! empty($validated['city_id'])) {
            $city = City::find($validated['city_id']);
            $validated['city'] = $city?->name;
        } elseif (! empty($validated['city'])) {
            $matched = City::findByName($validated['city']);
            $validated['city_id'] = $matched?->id;
        } else {
            $validated['city'] = null;
            $validated['city_id'] = null;
        }

        return $validated;
    }

    private function commercials()
    {
        return User::query()
            ->whereIn('role', [UserRole::Commercial, UserRole::SuperAdmin])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function validateClient(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'facebook_page' => 'nullable|string|max:255',
            'instagram_page' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_photo' => 'sometimes|boolean',
            'address' => 'nullable|string|max:500',
            'city_id' => 'nullable|exists:cities,id',
            'city' => 'nullable|string|max:100',
            'prospection' => ['nullable', Rule::enum(ProspectionSource::class)],
            'payment_mode' => ['nullable', Rule::enum(PaymentMode::class)],
            'commercial_id' => 'nullable|exists:users,id',
            'balance' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);
    }

    private function resolveClientPhoto(Request $request, Client $client): ?string
    {
        if ($request->boolean('remove_photo') && $client->photo) {
            Storage::disk('public')->delete($client->photo);

            return null;
        }

        if ($request->hasFile('photo')) {
            if ($client->photo) {
                Storage::disk('public')->delete($client->photo);
            }

            return $request->file('photo')->store('client-photos', 'public');
        }

        return $client->photo;
    }
}

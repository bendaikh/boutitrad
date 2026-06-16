<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\CashTransaction;
use App\Models\Category;
use App\Models\City;
use App\Models\Client;
use App\Models\CommercialObjective;
use App\Models\Commission;
use App\Models\DeliveryPartner;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('role', UserRole::SuperAdmin)->value('id');

        Setting::set('company_name', 'BoutiTrad SARL');
        Setting::set('company_email', 'contact@boutitrad.com');
        Setting::set('company_phone', '+212 600 000 000');
        Setting::set('company_address', 'Casablanca, Maroc');
        Setting::set('commission_rate', '5');

        $livreur = User::updateOrCreate(['email' => 'livreur@boutitrad.com'], [
            'name' => 'Youssef Alami',
            'password' => Hash::make('password'),
            'role' => UserRole::Livreur,
            'phone' => '+212 622 222 222',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'stock@boutitrad.com'], [
            'name' => 'Fatima Zahra',
            'password' => Hash::make('password'),
            'role' => UserRole::GestionnaireStock,
            'phone' => '+212 633 333 333',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $catElectronique = Category::firstOrCreate(['name' => 'Électronique'], ['description' => 'Appareils électroniques']);
        $catMode = Category::firstOrCreate(['name' => 'Mode'], ['description' => 'Vêtements et accessoires']);
        $catMaison = Category::firstOrCreate(['name' => 'Maison'], ['description' => 'Articles maison']);

        $brandSamsung = Brand::firstOrCreate(['name' => 'Samsung']);
        $brandNike = Brand::firstOrCreate(['name' => 'Nike']);
        $brandGeneric = Brand::firstOrCreate(['name' => 'Generic']);

        $products = [
            Product::firstOrCreate(['sku' => 'SAM-S24-001'], [
                'category_id' => $catElectronique->id, 'brand_id' => $brandSamsung->id,
                'name' => 'Samsung Galaxy S24', 'barcode' => '8806095123456', 'supplier' => 'Samsung Maroc', 'city' => 'Casablanca',
                'purchase_price' => 6500, 'sale_price' => 7999,
                'quantity' => 25, 'min_quantity' => 5, 'unit' => 'unité',
            ]),
            Product::firstOrCreate(['sku' => 'NIK-AIR-001'], [
                'category_id' => $catMode->id, 'brand_id' => $brandNike->id,
                'name' => 'Nike Air Max', 'supplier' => 'Nike Distribution', 'city' => 'Rabat',
                'purchase_price' => 800, 'sale_price' => 1299,
                'quantity' => 50, 'min_quantity' => 10, 'unit' => 'paire',
            ]),
            Product::firstOrCreate(['sku' => 'GEN-LMP-001'], [
                'category_id' => $catMaison->id, 'brand_id' => $brandGeneric->id,
                'name' => 'Lampe LED Design', 'supplier' => 'Lumitech', 'city' => 'Marrakech',
                'purchase_price' => 120, 'sale_price' => 249,
                'quantity' => 3, 'min_quantity' => 5, 'unit' => 'unité',
            ]),
            Product::firstOrCreate(['sku' => 'SAM-BUDS-001'], [
                'category_id' => $catElectronique->id, 'brand_id' => $brandSamsung->id,
                'name' => 'Samsung Galaxy Buds', 'supplier' => 'Samsung Maroc', 'city' => 'Casablanca',
                'purchase_price' => 900, 'sale_price' => 1399,
                'quantity' => 0, 'min_quantity' => 8, 'unit' => 'unité',
            ]),
        ];

        $clients = [
            Client::firstOrCreate(['email' => 'client1@email.com'], [
                'name' => 'Karim Mansouri', 'phone' => '+212 644 444 444',
                'address' => '123 Bd Mohammed V', 'city' => 'Casablanca', 'city_id' => City::findByName('Casablanca')?->id, 'balance' => 0,
                'prospection' => 'facebook', 'payment_mode' => 'especes', 'commercial_id' => null,
            ]),
            Client::firstOrCreate(['email' => 'client2@email.com'], [
                'name' => 'Sara Idrissi', 'phone' => '+212 655 555 555',
                'address' => '45 Rue Allal Ben Abdellah', 'city' => 'Rabat', 'city_id' => City::findByName('Rabat')?->id, 'balance' => -500,
                'prospection' => 'instagram', 'payment_mode' => 'credit', 'commercial_id' => null,
            ]),
            Client::firstOrCreate(['email' => 'client3@email.com'], [
                'name' => 'Mohamed Tazi', 'phone' => '+212 666 666 666',
                'address' => '78 Avenue Hassan II', 'city' => 'Marrakech', 'city_id' => City::findByName('Marrakech')?->id, 'balance' => 200,
                'prospection' => 'terrain', 'payment_mode' => 'virement', 'commercial_id' => null,
            ]),
        ];

        Order::where('reference', 'like', 'CMD-%')->delete();
        $this->seedYearlyOrders($adminId, $livreur, $clients, $products);

        Expense::firstOrCreate(['title' => 'Loyer bureau'], [
            'amount' => 8000, 'category' => 'Fixe', 'expense_date' => now()->startOfMonth(), 'user_id' => $adminId,
        ]);
        Expense::firstOrCreate(['title' => 'Marketing digital'], [
            'amount' => 3500, 'category' => 'Marketing', 'expense_date' => now()->subDays(5), 'user_id' => $adminId,
        ]);

        foreach (['Caisse principale', 'Banque Attijariwafa', 'Banque BMCE'] as $treasuryName) {
            Treasury::firstOrCreate(['name' => $treasuryName], ['is_active' => true]);
        }

        DeliveryPartner::firstOrCreate(['code' => 'cathedis'], [
            'name' => 'Cathedis',
            'contact_email' => 'contact@cathedis.ma',
            'contact_phone' => '+212 520 255 255',
            'api_url' => config('cathedis.api_url'),
            'is_active' => true,
            'is_default' => true,
        ]);

        CashTransaction::firstOrCreate(['reference' => 'REC-001'], [
            'type' => 'in', 'amount' => 50000, 'description' => 'Encaissement ventes',
            'transaction_date' => now()->subDays(3), 'user_id' => $adminId,
        ]);
        CashTransaction::firstOrCreate(['reference' => 'DEP-001'], [
            'type' => 'out', 'amount' => 15000, 'description' => 'Paiement fournisseurs',
            'transaction_date' => now()->subDays(2), 'user_id' => $adminId,
        ]);
    }

    private function seedYearlyOrders(
        int $adminId,
        User $livreur,
        array $clients,
        array $products,
    ): void {
        $year = now()->year;
        $pendingStatuses = OrderStatus::activeStatuses();

        $monthlyCounts = [
            1 => ['validated' => 5, 'pending' => 4, 'cancelled' => 2, 'returns' => 1],
            2 => ['validated' => 6, 'pending' => 3, 'cancelled' => 1, 'returns' => 2],
            3 => ['validated' => 4, 'pending' => 5, 'cancelled' => 2, 'returns' => 1],
            4 => ['validated' => 7, 'pending' => 3, 'cancelled' => 1, 'returns' => 2],
            5 => ['validated' => 5, 'pending' => 4, 'cancelled' => 2, 'returns' => 1],
            6 => ['validated' => 6, 'pending' => 5, 'cancelled' => 1, 'returns' => 2],
            7 => ['validated' => 4, 'pending' => 3, 'cancelled' => 1, 'returns' => 1],
            8 => ['validated' => 5, 'pending' => 4, 'cancelled' => 2, 'returns' => 1],
            9 => ['validated' => 6, 'pending' => 3, 'cancelled' => 1, 'returns' => 2],
            10 => ['validated' => 5, 'pending' => 5, 'cancelled' => 2, 'returns' => 1],
            11 => ['validated' => 7, 'pending' => 3, 'cancelled' => 1, 'returns' => 2],
            12 => ['validated' => 4, 'pending' => 4, 'cancelled' => 2, 'returns' => 1],
        ];

        $sequence = 1;

        foreach ($monthlyCounts as $month => $counts) {
            $createdAt = now()->setDate($year, $month, rand(5, 25))->startOfDay()->addHours(rand(9, 18));

            foreach ($this->orderTypesForMonth($counts, $pendingStatuses) as $status) {
                $client = $clients[($sequence - 1) % count($clients)];
                $product = $products[($sequence - 1) % count($products)];
                $qty = rand(1, 3);
                $total = $product->sale_price * $qty;
                $reference = sprintf('CMD-DEMO-%04d-%02d-%04d', $year, $month, $sequence);

                $paymentModes = ['especes', 'cheque', 'virement', 'credit'];
                $paymentMode = $paymentModes[$sequence % count($paymentModes)];
                $amountPaid = match ($sequence % 3) {
                    0 => $total,
                    1 => round($total * 0.5, 2),
                    default => 0,
                };

                $order = Order::create([
                    'reference' => $reference,
                    'client_id' => $client->id,
                    'commercial_id' => null,
                    'livreur_id' => in_array($status, [OrderStatus::Expediee, OrderStatus::Livree], true) ? $livreur->id : null,
                    'status' => $status,
                    'subtotal' => $total,
                    'total' => $total,
                    'amount_paid' => $amountPaid,
                    'payment_mode' => $paymentMode,
                    'validated_at' => in_array($status, [OrderStatus::Confirmee, OrderStatus::EnPreparation, OrderStatus::Expediee, OrderStatus::Livree], true)
                        ? $createdAt->copy()->subDays(rand(1, 3))
                        : null,
                    'delivered_at' => $status === OrderStatus::Livree ? $createdAt->copy()->addDays(rand(1, 4)) : null,
                    'cancelled_at' => $status === OrderStatus::Annulee ? $createdAt->copy()->addDays(rand(1, 2)) : null,
                    'created_by' => $adminId,
                ]);

                $order->created_at = $createdAt;
                $order->updated_at = $createdAt;
                $order->save();

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->sale_price,
                    'total' => $total,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => $status->value,
                    'user_id' => $adminId,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $sequence++;
            }
        }
    }

    private function orderTypesForMonth(array $counts, array $pendingStatuses): array
    {
        $orders = [];

        for ($i = 0; $i < $counts['validated']; $i++) {
            $orders[] = OrderStatus::Livree;
        }

        for ($i = 0; $i < $counts['pending']; $i++) {
            $orders[] = $pendingStatuses[$i % count($pendingStatuses)];
        }

        for ($i = 0; $i < $counts['cancelled']; $i++) {
            $orders[] = OrderStatus::Annulee;
        }

        for ($i = 0; $i < $counts['returns']; $i++) {
            $orders[] = OrderStatus::Retournee;
        }

        return $orders;
    }
}

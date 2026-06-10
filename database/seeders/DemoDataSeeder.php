<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\CashTransaction;
use App\Models\Category;
use App\Models\Client;
use App\Models\CommercialObjective;
use App\Models\Commission;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Setting;
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

        $commercial = User::updateOrCreate(['email' => 'commercial@boutitrad.com'], [
            'name' => 'Ahmed Benali',
            'password' => Hash::make('password'),
            'role' => UserRole::Commercial,
            'phone' => '+212 611 111 111',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

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
                'name' => 'Samsung Galaxy S24', 'purchase_price' => 6500, 'sale_price' => 7999,
                'quantity' => 25, 'min_quantity' => 5, 'unit' => 'unité',
            ]),
            Product::firstOrCreate(['sku' => 'NIK-AIR-001'], [
                'category_id' => $catMode->id, 'brand_id' => $brandNike->id,
                'name' => 'Nike Air Max', 'purchase_price' => 800, 'sale_price' => 1299,
                'quantity' => 50, 'min_quantity' => 10, 'unit' => 'paire',
            ]),
            Product::firstOrCreate(['sku' => 'GEN-LMP-001'], [
                'category_id' => $catMaison->id, 'brand_id' => $brandGeneric->id,
                'name' => 'Lampe LED Design', 'purchase_price' => 120, 'sale_price' => 249,
                'quantity' => 3, 'min_quantity' => 5, 'unit' => 'unité',
            ]),
            Product::firstOrCreate(['sku' => 'SAM-BUDS-001'], [
                'category_id' => $catElectronique->id, 'brand_id' => $brandSamsung->id,
                'name' => 'Samsung Galaxy Buds', 'purchase_price' => 900, 'sale_price' => 1399,
                'quantity' => 40, 'min_quantity' => 8, 'unit' => 'unité',
            ]),
        ];

        $clients = [
            Client::firstOrCreate(['email' => 'client1@email.com'], [
                'name' => 'Karim Mansouri', 'phone' => '+212 644 444 444',
                'address' => '123 Bd Mohammed V', 'city' => 'Casablanca', 'balance' => 0,
            ]),
            Client::firstOrCreate(['email' => 'client2@email.com'], [
                'name' => 'Sara Idrissi', 'phone' => '+212 655 555 555',
                'address' => '45 Rue Allal Ben Abdellah', 'city' => 'Rabat', 'balance' => -500,
            ]),
            Client::firstOrCreate(['email' => 'client3@email.com'], [
                'name' => 'Mohamed Tazi', 'phone' => '+212 666 666 666',
                'address' => '78 Avenue Hassan II', 'city' => 'Marrakech', 'balance' => 200,
            ]),
        ];

        $statuses = [
            OrderStatus::Nouvelle,
            OrderStatus::Confirmee,
            OrderStatus::EnPreparation,
            OrderStatus::Expediee,
            OrderStatus::Livree,
            OrderStatus::Livree,
            OrderStatus::Annulee,
            OrderStatus::Retournee,
        ];

        foreach ($statuses as $i => $status) {
            $client = $clients[$i % count($clients)];
            $product = $products[$i % count($products)];
            $qty = rand(1, 3);
            $total = $product->sale_price * $qty;

            $order = Order::firstOrCreate(['reference' => 'CMD-'.now()->format('Ymd').'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT)], [
                'client_id' => $client->id,
                'commercial_id' => $commercial->id,
                'livreur_id' => in_array($status, [OrderStatus::Expediee, OrderStatus::Livree]) ? $livreur->id : null,
                'status' => $status,
                'subtotal' => $total,
                'total' => $total,
                'validated_at' => in_array($status, [OrderStatus::Confirmee, OrderStatus::EnPreparation, OrderStatus::Expediee, OrderStatus::Livree]) ? now()->subDays(rand(1, 10)) : null,
                'delivered_at' => $status === OrderStatus::Livree ? now()->subDays(rand(1, 5)) : null,
                'cancelled_at' => $status === OrderStatus::Annulee ? now()->subDays(2) : null,
                'created_by' => $adminId,
                'created_at' => now()->subDays(rand(1, 30)),
            ]);

            if ($order->wasRecentlyCreated) {
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
                ]);

                if ($status === OrderStatus::Livree) {
                    Commission::create([
                        'user_id' => $commercial->id,
                        'order_id' => $order->id,
                        'amount' => $total * 0.05,
                        'status' => 'paid',
                    ]);
                }
            }
        }

        CommercialObjective::firstOrCreate([
            'user_id' => $commercial->id,
            'period_start' => now()->startOfMonth(),
        ], [
            'target_amount' => 100000,
            'achieved_amount' => Order::where('commercial_id', $commercial->id)->where('status', OrderStatus::Livree)->sum('total'),
            'period_end' => now()->endOfMonth(),
        ]);

        Expense::firstOrCreate(['title' => 'Loyer bureau'], [
            'amount' => 8000, 'category' => 'Fixe', 'expense_date' => now()->startOfMonth(), 'user_id' => $adminId,
        ]);
        Expense::firstOrCreate(['title' => 'Marketing digital'], [
            'amount' => 3500, 'category' => 'Marketing', 'expense_date' => now()->subDays(5), 'user_id' => $adminId,
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
}

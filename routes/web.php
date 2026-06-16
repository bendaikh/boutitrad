<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommercialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:superadmin,commercial')->group(function () {
        Route::get('clients/balances', [ClientController::class, 'balances'])->name('clients.balances');
        Route::get('clients/{client}/balance-print', [ClientController::class, 'balancePrint'])->name('clients.balance.print');
        Route::get('clients/{client}/balance-export/pdf', [ClientController::class, 'balanceExportPdf'])->name('clients.balance.export.pdf');
        Route::get('clients/{client}/balance-export/excel', [ClientController::class, 'balanceExportExcel'])->name('clients.balance.export.excel');
        Route::get('clients/{client}/print', [ClientController::class, 'print'])->name('clients.print');
        Route::resource('clients', ClientController::class);
        Route::resource('orders', OrderController::class);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('orders/{order}/submit', [OrderController::class, 'submitToAdmin'])->name('orders.submit');
        Route::post('orders/{order}/dispatch', [OrderController::class, 'validateAndDispatch'])->name('orders.dispatch');
        Route::post('orders/{order}/reject', [OrderController::class, 'rejectOrder'])->name('orders.reject');
        Route::get('orders/{order}/bon', [OrderController::class, 'bon'])->name('orders.bon');
        Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
        Route::get('orders/{order}/bon/print', [OrderController::class, 'deliveryNote'])->name('orders.delivery-note');
        Route::patch('orders/{order}/shipping-remark', [OrderController::class, 'updateShippingRemark'])->name('orders.shipping-remark');
        Route::post('orders/{order}/items/{item}/product-image', [OrderController::class, 'uploadItemProductImage'])->name('orders.items.product-image');
        Route::get('sales/balance', [SalesController::class, 'balance'])->name('sales.balance');
        Route::get('sales/balance/print', [SalesController::class, 'balancePrint'])->name('sales.balance.print');
        Route::get('sales/balance/export/pdf', [SalesController::class, 'balanceExportPdf'])->name('sales.balance.export.pdf');
        Route::get('sales/balance/export/excel', [SalesController::class, 'balanceExportExcel'])->name('sales.balance.export.excel');
        Route::get('sales/payments', [SalesController::class, 'payments'])->name('sales.payments');
        Route::post('sales/payments', [SalesController::class, 'storePayment'])->name('sales.payments.store');
        Route::patch('sales/payments/{payment}/status', [SalesController::class, 'updatePaymentStatus'])->name('sales.payments.update-status');
    });

    Route::middleware('role:superadmin,gestionnaire_stock')->group(function () {
        Route::get('products/{product}/print', [ProductController::class, 'print'])->name('products.print');
        Route::resource('products', ProductController::class);
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/print', [StockController::class, 'print'])->name('stock.print');
        Route::get('stock/export/pdf', [StockController::class, 'exportPdf'])->name('stock.export.pdf');
        Route::get('stock/export/excel', [StockController::class, 'exportExcel'])->name('stock.export.excel');
        Route::get('stock/movements', [StockController::class, 'movements'])->name('stock.movements');
        Route::post('stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust');
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoryController::class, 'storeCategory'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'updateCategory'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroyCategory'])->name('categories.destroy');
        Route::post('brands', [CategoryController::class, 'storeBrand'])->name('brands.store');
        Route::put('brands/{brand}', [CategoryController::class, 'updateBrand'])->name('brands.update');
        Route::delete('brands/{brand}', [CategoryController::class, 'destroyBrand'])->name('brands.destroy');
    });

    Route::middleware('role:superadmin,livreur')->group(function () {
        Route::get('deliveries', fn () => redirect()->route('deliveries.transport'))->name('deliveries.index');
        Route::get('deliveries/partners', [DeliveryController::class, 'partners'])->name('deliveries.partners');
        Route::get('deliveries/transport', [DeliveryController::class, 'transport'])->name('deliveries.transport');
        Route::get('deliveries/orders/{order}', [DeliveryController::class, 'showOrder'])->name('deliveries.orders.show');
        Route::post('deliveries/orders/{order}/complete', [DeliveryController::class, 'completeOrder'])->name('deliveries.orders.complete');
        Route::get('deliveries/livreurs', [DeliveryController::class, 'livreurs'])->name('deliveries.livreurs');
    });

    Route::middleware('role:superadmin')->group(function () {
        Route::post('deliveries/partners', [DeliveryController::class, 'storePartner'])->name('deliveries.partners.store');
        Route::post('deliveries/cathedis/sync-cities', [DeliveryController::class, 'syncCathedisCities'])->name('deliveries.cathedis.sync-cities');
        Route::post('deliveries/cathedis/test', [DeliveryController::class, 'testCathedisConnection'])->name('deliveries.cathedis.test');
    });

    Route::middleware('role:superadmin,commercial')->group(function () {
        Route::get('commercials', [CommercialController::class, 'index'])->name('commercials.index');
        Route::get('commercials/print', [CommercialController::class, 'print'])->name('commercials.print');
        Route::get('commercials/export', [CommercialController::class, 'exportExcel'])->name('commercials.export');
        Route::get('commercials/{user}', [CommercialController::class, 'show'])->name('commercials.show');
    });

    Route::middleware('role:superadmin')->group(function () {
        Route::post('commercials', [CommercialController::class, 'store'])->name('commercials.store');
        Route::put('commercials/{user}', [CommercialController::class, 'update'])->name('commercials.update');
        Route::delete('commercials/{user}', [CommercialController::class, 'destroy'])->name('commercials.destroy');
        Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('finance/expenses', [FinanceController::class, 'storeExpense'])->name('finance.expenses.store');
        Route::post('finance/transactions', [FinanceController::class, 'storeTransaction'])->name('finance.transactions.store');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('settings/permissions', [SettingController::class, 'permissions'])->name('settings.permissions');
        Route::post('settings/permissions', [SettingController::class, 'storePermissionUser'])->name('settings.permissions.store');
        Route::put('settings/permissions/{user}', [SettingController::class, 'updatePermissionUser'])->name('settings.permissions.update');
        Route::delete('settings/permissions/{user}', [SettingController::class, 'destroyPermissionUser'])->name('settings.permissions.destroy');
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

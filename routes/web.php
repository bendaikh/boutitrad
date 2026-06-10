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
        Route::resource('clients', ClientController::class);
        Route::resource('orders', OrderController::class)->except(['edit', 'update', 'destroy']);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    });

    Route::middleware('role:superadmin,gestionnaire_stock')->group(function () {
        Route::resource('products', ProductController::class);
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/movements', [StockController::class, 'movements'])->name('stock.movements');
        Route::post('stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust');
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoryController::class, 'storeCategory'])->name('categories.store');
        Route::post('brands', [CategoryController::class, 'storeBrand'])->name('brands.store');
    });

    Route::middleware('role:superadmin,livreur')->group(function () {
        Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    });

    Route::middleware('role:superadmin,commercial')->group(function () {
        Route::get('commercials', [CommercialController::class, 'index'])->name('commercials.index');
        Route::get('commercials/{user}', [CommercialController::class, 'show'])->name('commercials.show');
    });

    Route::middleware('role:superadmin')->group(function () {
        Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('finance/expenses', [FinanceController::class, 'storeExpense'])->name('finance.expenses.store');
        Route::post('finance/transactions', [FinanceController::class, 'storeTransaction'])->name('finance.transactions.store');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::resource('users', UserController::class)->except(['show']);
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

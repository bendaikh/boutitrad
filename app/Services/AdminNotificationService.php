<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderAwaitingValidationNotification;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    public function notifyOrderAwaitingValidation(Order $order): void
    {
        $order->loadMissing(['commercial', 'client']);

        $this->admins()->each(function (User $admin) use ($order) {
            $admin->notify(new OrderAwaitingValidationNotification($order));
        });
    }

    public function admins(): Collection
    {
        return User::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('is_active', true)
            ->get();
    }
}

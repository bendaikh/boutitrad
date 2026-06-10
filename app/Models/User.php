<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isCommercial(): bool
    {
        return $this->role === UserRole::Commercial;
    }

    public function isLivreur(): bool
    {
        return $this->role === UserRole::Livreur;
    }

    public function isGestionnaireStock(): bool
    {
        return $this->role === UserRole::GestionnaireStock;
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function commercialOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'commercial_id');
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'livreur_id');
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(CommercialObjective::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }
}

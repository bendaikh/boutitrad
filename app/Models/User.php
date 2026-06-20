<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
        'phone',
        'whatsapp',
        'prospect_zone',
        'commission_rate',
        'profile_photo',
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
            'permissions' => 'array',
            'is_active' => 'boolean',
            'commission_rate' => 'decimal:2',
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

    /**
     * @return list<string>
     */
    public function effectivePermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return ['*'];
        }

        $permissions = is_array($this->permissions) && $this->permissions !== []
            ? $this->permissions
            : PermissionCatalog::defaultsForRole($this->role->value);

        if ($this->isCommercial()) {
            foreach (['stock.view', 'stock.print'] as $stockPermission) {
                if (! in_array($stockPermission, $permissions, true)) {
                    $permissions[] = $stockPermission;
                }
            }
        }

        return $permissions;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->effectivePermissions();

        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessClientsModule(): bool
    {
        if (! $this->hasRole(UserRole::SuperAdmin, UserRole::Commercial)) {
            return false;
        }

        return $this->hasAnyPermission([
            'clients.create', 'clients.view', 'clients.update', 'clients.delete',
            'clients.balance.view', 'clients.balance.print',
        ]);
    }

    public function canAccessStockModule(): bool
    {
        if (! $this->hasRole(UserRole::SuperAdmin, UserRole::GestionnaireStock, UserRole::Commercial)) {
            return false;
        }

        return $this->hasAnyPermission([
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'stock.view', 'stock.print',
        ]);
    }

    public function canManageStockCatalog(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin, UserRole::GestionnaireStock)
            && $this->hasAnyPermission([
                'products.create', 'products.update', 'products.delete',
                'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            ]);
    }

    public function canAccessVentesModule(): bool
    {
        if (! $this->hasRole(UserRole::SuperAdmin, UserRole::Commercial)) {
            return false;
        }

        return $this->hasAnyPermission([
            'orders.view', 'orders.validate', 'orders.create', 'orders.update', 'orders.delete',
            'sales.balance.view', 'sales.balance.print',
            'payments.view', 'payments.create', 'payments.update', 'payments.delete',
        ]);
    }

    public function canAccessConfigurationModule(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->hasRole(UserRole::Commercial)) {
            return false;
        }

        return $this->hasAnyPermission([
            'commercials.view', 'commercials.create', 'commercials.update', 'commercials.delete',
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions = PermissionCatalog::sanitize($permissions);
        $this->save();
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

    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo ? '/storage/'.$this->profile_photo : null;
    }

    public function initials(): string
    {
        return strtoupper(substr($this->name, 0, 1));
    }

    public function formattedCommercialId(): string
    {
        return 'COM-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public static function previewCommercialId(): string
    {
        $next = static::where('role', UserRole::Commercial)->count() + 1;

        return 'COM-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

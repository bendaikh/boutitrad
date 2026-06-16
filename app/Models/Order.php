<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'reference', 'client_id', 'commercial_id', 'livreur_id', 'delivery_partner_id', 'partner_tracking_ref', 'status',
        'subtotal', 'discount', 'delivery_cost', 'tax', 'total', 'amount_paid', 'payment_mode', 'notes', 'internal_notes', 'shipping_remark',
        'validated_at', 'delivered_at', 'cancelled_at', 'submitted_to_admin_at', 'sent_to_partner_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'delivery_cost' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'payment_mode' => PaymentMode::class,
            'validated_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'submitted_to_admin_at' => 'datetime',
            'sent_to_partner_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function commercial(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }

    public function livreur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'livreur_id');
    }

    public function deliveryPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function commission(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public static function generateReference(): string
    {
        $prefix = 'CMD-'.now()->format('Ymd');
        $last = static::where('reference', 'like', $prefix.'%')->count();

        return $prefix.'-'.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    public function orderAmount(): float
    {
        return round((float) $this->total, 2);
    }

    public function paidAmount(): float
    {
        return round((float) $this->amount_paid, 2);
    }

    public function balanceDue(): float
    {
        $total = $this->orderAmount();
        $paid = $this->paidAmount();

        if ($paid <= 0) {
            return $total;
        }

        return max(0, round($total - $paid, 2));
    }

    public function paymentStatus(): string
    {
        $total = $this->orderAmount();
        $paid = $this->paidAmount();

        if ($total <= 0 || $paid >= $total) {
            return 'paid';
        }

        if ($paid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    public function paymentStatusLabel(): string
    {
        return $this->paymentStatus() === 'paid' ? 'Payé' : 'Impayé';
    }

    public function isEditableByCommercial(): bool
    {
        return $this->status === OrderStatus::Nouvelle;
    }

    public function isAwaitingAdminValidation(): bool
    {
        return $this->status === OrderStatus::EnCours;
    }

    public function hasBeenValidatedByAdmin(): bool
    {
        if ($this->validated_at !== null) {
            return true;
        }

        return in_array($this->status, [
            OrderStatus::Confirmee,
            OrderStatus::EnPreparation,
            OrderStatus::Expediee,
            OrderStatus::Livree,
            OrderStatus::Retournee,
        ], true);
    }

    public function canEditShippingRemark(?User $user = null): bool
    {
        return $this->canManageBonContent($user);
    }

    public function canManageBonContent(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user || $this->status === OrderStatus::Annulee) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isGestionnaireStock()) {
            return true;
        }

        if (! $user->isCommercial()) {
            return false;
        }

        return $this->commercial_id === $user->id
            || $this->created_by === $user->id;
    }

    public function canUploadProductImage(?User $user = null): bool
    {
        return $this->canManageBonContent($user);
    }

    public function canViewBon(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user || $this->status === OrderStatus::Annulee) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isGestionnaireStock()) {
            return true;
        }

        if (! $user->isCommercial()) {
            return false;
        }

        return $this->commercial_id === $user->id
            || $this->created_by === $user->id;
    }

    public function isWithPartner(): bool
    {
        return in_array($this->status, [
            OrderStatus::Confirmee,
            OrderStatus::EnPreparation,
            OrderStatus::Expediee,
        ], true);
    }

    public function isDeliverableByPartner(): bool
    {
        return in_array($this->status, [
            OrderStatus::Expediee,
            OrderStatus::EnPreparation,
            OrderStatus::Confirmee,
        ], true);
    }

    public function canBeModifiedBy(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user || ! $this->isAwaitingAdminValidation()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCommercial() && $this->commercial_id === $user->id) {
            return true;
        }

        return false;
    }

    public function canBeEditedInForm(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if ($this->canBeModifiedBy($user)) {
            return true;
        }

        return $user->isCommercial()
            && $this->commercial_id === $user->id
            && $this->isEditableByCommercial();
    }
}

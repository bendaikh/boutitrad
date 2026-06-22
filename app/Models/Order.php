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
        'reference', 'client_id', 'commercial_id', 'livreur_id', 'delivery_partner_id', 'partner_tracking_ref', 'cathedis_status_code', 'status',
        'subtotal', 'discount', 'delivery_cost', 'tax', 'total', 'amount_paid', 'payment_mode', 'notes', 'internal_notes', 'shipping_remark', 'product_image',
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
            'cathedis_status_synced_at' => 'datetime',
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
        $year = (int) now()->format('Y');
        $prefix = 'BN-'.$year;

        $latestSequence = static::query()
            ->where('reference', 'like', $prefix.'%')
            ->pluck('reference')
            ->map(fn (string $reference) => self::parseBonSequence($reference, $year))
            ->filter()
            ->max();

        $next = ((int) $latestSequence) + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public static function previewReference(): string
    {
        return static::generateReference();
    }

    private static function parseBonSequence(string $reference, int $year): ?int
    {
        if (preg_match('/^BN-'.$year.'(\d{4})$/', $reference, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function deliveryReference(): ?string
    {
        return $this->partner_tracking_ref;
    }

    public function hasCathedisTracking(): bool
    {
        return filled($this->partner_tracking_ref)
            && $this->deliveryPartner?->isCathedis();
    }

    public function cathedisStatusDisplay(): ?string
    {
        return filled($this->cathedis_status_code) ? $this->cathedis_status_code : null;
    }

    public function cathedisStatusColor(): string
    {
        return \App\Support\CathedisStatusMapper::displayColor((string) $this->cathedis_status_code);
    }

    public function orderAmount(): float
    {
        return round((float) $this->total, 2);
    }

    public function itemsSubtotal(): float
    {
        if ($this->relationLoaded('items')) {
            return round((float) $this->items->sum('total'), 2);
        }

        return round((float) $this->items()->sum('total'), 2);
    }

    public function computedGrandTotal(): float
    {
        return max(0, round(
            $this->itemsSubtotal() + (float) $this->delivery_cost - (float) $this->discount,
            2,
        ));
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

    public function canBeValidatedByAdmin(): bool
    {
        if (in_array($this->status, [OrderStatus::Nouvelle, OrderStatus::EnCours], true)) {
            return true;
        }

        return $this->status === OrderStatus::Confirmee
            && blank($this->partner_tracking_ref);
    }

    public function canBeRejectedByAdmin(): bool
    {
        return in_array($this->status, [OrderStatus::Nouvelle, OrderStatus::EnCours], true);
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
            return false;
        }

        if (! $user->isCommercial()) {
            return false;
        }

        return ($this->commercial_id === $user->id || $this->created_by === $user->id)
            && $this->status === OrderStatus::Nouvelle;
    }

    public function canUploadProductImage(?User $user = null): bool
    {
        return $this->canManageBonContent($user);
    }

    public function productImageUrl(): ?string
    {
        return $this->product_image ? '/storage/'.$this->product_image : null;
    }

    public function displayProductImageUrl(): ?string
    {
        $this->loadMissing('items.product');

        foreach ($this->items as $item) {
            if ($url = $item->productImageUrl()) {
                return $url;
            }
        }

        if ($this->productImageUrl()) {
            return $this->productImageUrl();
        }

        return null;
    }

    public function hasProductPhoto(): bool
    {
        $this->loadMissing('items.product');

        if ($this->items->isEmpty()) {
            return filled($this->product_image);
        }

        if ($this->items->count() === 1 && filled($this->product_image) && ! $this->items->first()->hasOrderProductPhoto()) {
            return true;
        }

        return $this->items->every(fn (OrderItem $item) => $item->hasOrderProductPhoto());
    }

    public function hasShippingRemark(): bool
    {
        $this->loadMissing('items');

        if ($this->items->isNotEmpty()) {
            return $this->items->every(fn (OrderItem $item) => filled(trim((string) $item->remark)));
        }

        return filled(trim((string) $this->shipping_remark));
    }

    public function combinedShippingRemark(): string
    {
        $this->loadMissing('items');

        $itemRemarks = $this->items
            ->map(function (OrderItem $item) {
                $remark = trim((string) $item->remark);

                if ($remark === '') {
                    return null;
                }

                return $item->product_name.': '.$remark;
            })
            ->filter()
            ->implode("\n");

        if ($itemRemarks !== '') {
            return $itemRemarks;
        }

        return trim((string) $this->shipping_remark);
    }

    public function clientDetailsComplete(): bool
    {
        $this->loadMissing('client.cityRecord');
        $client = $this->client;

        return $client
            && filled($client->phone)
            && filled($client->address)
            && filled($client->deliveryCityName());
    }

    public function isReadyForAdminSubmission(): bool
    {
        return $this->clientDetailsComplete()
            && $this->hasProductPhoto()
            && $this->hasShippingRemark();
    }

    /**
     * @return list<string>
     */
    public function missingItemsBeforeAdminSubmission(): array
    {
        $missing = [];

        if (! $this->clientDetailsComplete()) {
            $missing[] = 'coordonnées client (téléphone, adresse, ville)';
        }

        if (! $this->hasProductPhoto()) {
            $missing[] = 'photo de chaque produit';
        }

        if (! $this->hasShippingRemark()) {
            $missing[] = 'remarque NB pour chaque produit';
        }

        return $missing;
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

        return false;
    }

    public function canBeEditedInForm(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isCommercial()) {
            if ($this->hasBeenValidatedByAdmin()) {
                return false;
            }

            if (! $this->isEditableByCommercial()) {
                return false;
            }

            return $this->commercial_id === $user->id
                || $this->created_by === $user->id;
        }

        if ($this->canBeModifiedBy($user)) {
            return true;
        }

        return $user->isSuperAdmin()
            && ! $this->hasBeenValidatedByAdmin()
            && $this->status === OrderStatus::Nouvelle;
    }
}

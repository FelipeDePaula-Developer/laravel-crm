<?php

namespace Webkul\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Billing\Contracts\BillingInstallment as BillingInstallmentContract;
use Webkul\Billing\Enums\InstallmentStatus;
use Webkul\Contact\Models\PersonProxy;

class BillingInstallment extends Model implements BillingInstallmentContract
{
    use HasFactory;

    protected $table = 'billing_installments';

    /**
     * Entidade centralizadora — pertence a uma pessoa.
     */
    protected $fillable = [
        'person_id',
        'number',
        'original_value',
        'delinquent_value',
        'paid_value',
        'interest_value',
        'penalty_value',
        'due_date',
        'status',
    ];

    protected $casts = [
        'status' => InstallmentStatus::class,
        'due_date' => 'date',
        'original_value' => 'decimal:2',
        'delinquent_value' => 'decimal:2',
        'paid_value' => 'decimal:2',
        'interest_value' => 'decimal:2',
        'penalty_value' => 'decimal:2',
    ];

    /**
     * O plano de parcelamento pertence a qual pessoa?
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass(), 'person_id');
    }

    /**
     * Um plano de parcelamento tem muitos boletos.
     */
    public function billings(): HasMany
    {
        return $this->hasMany(BillingProxy::modelClass(), 'billing_installment_id');
    }
}

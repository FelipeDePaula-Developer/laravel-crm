<?php

namespace Webkul\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Billing\Contracts\Billing as BillingContract;
use Webkul\Billing\Enums\BillingStatus;

class Billing extends Model implements BillingContract
{
    use HasFactory;

    protected $table = 'billings';

    /**
     * Cada boleto pertence a um plano de parcelamento (BillingInstallment).
     */
    protected $fillable = [
        'billing_installment_id',
        'installment_number',
        'original_value',
        'delinquent_value',
        'paid_value',
        'interest_value',
        'penalty_value',
        'due_date',
        'paid_date',
        'status',
    ];

    protected $casts = [
        'status' => BillingStatus::class,
        'due_date' => 'date',
        'paid_date' => 'date',
        'original_value' => 'decimal:2',
        'delinquent_value' => 'decimal:2',
        'paid_value' => 'decimal:2',
        'interest_value' => 'decimal:2',
        'penalty_value' => 'decimal:2',
    ];

    /**
     * Este boleto pertence a qual plano de parcelamento?
     */
    public function billingInstallment(): BelongsTo
    {
        return $this->belongsTo(BillingInstallmentProxy::modelClass(), 'billing_installment_id');
    }
}

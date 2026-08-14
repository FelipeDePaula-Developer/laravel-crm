<?php

namespace Webkul\Billing\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Billing\Models\Billing;
use Webkul\Billing\Models\BillingInstallment;
use Webkul\Contact\Models\Person;

class BillingProcessService
{
    public function processImportGroup(array $data): void
    {
        DB::transaction(function () use ($data) {
            $person = Person::firstOrCreate(
                ['name' => $data['persons']['name']],
                ['emails' => [$data['persons']['email']]]
            );

            $installment = BillingInstallment::create([
                'person_id'        => $person->id,
                'number'           => $data['installments']['number'],
                'original_value'   => $data['installments']['original_value'],
                'delinquent_value' => $data['installments']['delinquent_value'],
                'paid_value'       => $data['installments']['paid_value'],
                'interest_value'   => $data['installments']['interest_value'],
                'penalty_value'    => $data['installments']['penalty_value'],
                'due_date'         => $data['installments']['due_date'],
                'status'           => $data['installments']['status'],
            ]);

            foreach ($data['billings'] as $parcelNumber => $billingData) {
                Billing::create([
                    'billing_installment_id' => $installment->id,
                    'installment_number'     => $billingData['parcel_number'],
                    'original_value'         => $billingData['original_value'],
                    'delinquent_value'       => $billingData['delinquent_value'],
                    'paid_value'             => $billingData['paid_value'],
                    'interest_value'         => $billingData['interest_value'],
                    'penalty_value'          => $billingData['penalty_value'],
                    'due_date'               => $billingData['due_date'],
                    'paid_date'              => $billingData['paid_date'] ?: null,
                    'status'                 => $billingData['status'],
                ]);
            }
        });
    }
}

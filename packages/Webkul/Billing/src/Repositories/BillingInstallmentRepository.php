<?php

namespace Webkul\Billing\Repositories;

use Webkul\Billing\Contracts\BillingInstallment;
use Webkul\Core\Eloquent\Repository;

class BillingInstallmentRepository extends Repository
{
    /**
     * Searchable fields.
     */
    protected $fieldSearchable = [];

    /**
     * Specify model class name.
     *
     * @return mixed
     */
    public function model()
    {
        return BillingInstallment::class;
    }
}

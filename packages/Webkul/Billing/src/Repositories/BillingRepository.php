<?php

namespace Webkul\Billing\Repositories;

use Webkul\Billing\Contracts\Billing;
use Webkul\Core\Eloquent\Repository;

class BillingRepository extends Repository
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
        return Billing::class;
    }
}

<?php

namespace Webkul\Billing\Providers;

use Webkul\Billing\Models\Billing;
use Webkul\Billing\Models\BillingInstallment;
use Webkul\Core\Providers\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Billing::class,
        BillingInstallment::class,
    ];
}

<?php

namespace Webkul\Billing\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    public function register() {}
}

<?php

namespace Behin\CrmClient;

use Illuminate\Support\ServiceProvider;

class CrmServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CrmClient::class, function () {
            return new CrmClient();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

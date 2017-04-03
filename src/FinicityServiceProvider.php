<?php

namespace Techscope\Finicity;

use Illuminate\Support\ServiceProvider;

class FinicityServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('plaid', function ($app) {
            return new Finic($app);
        });
    }
}
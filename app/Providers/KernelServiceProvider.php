<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class KernelServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton(\App\Kernel\KernelService::class, function ($app) {
            return new \App\Kernel\KernelService($app);
        });
    }

}

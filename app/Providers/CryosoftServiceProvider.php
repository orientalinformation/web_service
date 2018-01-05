<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CryosoftServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\App\Cryosoft\ValueListService::class, function ($app) {
            return new \App\Cryosoft\ValueListService($app);
        });

        $this->app->singleton(\App\Cryosoft\UnitsConverterService::class, function ($app) {
            return new \App\Cryosoft\UnitsConverterService($app);
        });

        $this->app->singleton(\App\Cryosoft\EquipmentsService::class, function ($app) {
            return new \App\Cryosoft\EquipmentsService($app);
        });

        $this->app->singleton(\App\Cryosoft\DimaResultsService::class, function ($app) {
            return new \App\Cryosoft\DimaResultsService($app);
        });

        $this->app->singleton(\App\Cryosoft\EconomicResultsService::class, function ($app) {
            return new \App\Cryosoft\EconomicResultsService($app);
        });

        $this->app->singleton(\App\Cryosoft\StudyService::class, function ($app) {
            return new \App\Cryosoft\StudyService($app);
        });

        $this->app->singleton(\App\Cryosoft\CheckControlService::class, function ($app) {
            return new \App\Cryosoft\CheckControlService($app);
        });

        $this->app->singleton(\App\Cryosoft\CalculateService::class, function ($app) {
            return new \App\Cryosoft\CalculateService($app);
        });

        $this->app->singleton(\App\Cryosoft\OutputService::class, function ($app) {
            return new \App\Cryosoft\OutputService($app);
        });
    }

}

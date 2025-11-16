<?php

namespace JessyLedama\Kompressor;

use Illuminate\Support\ServiceProvider;
use JessyLedama\Kompressor\Services\KompressorService;

class KompressorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kompressor.php', 'kompressor');

        $this->app->singleton('kompressor', function () {
            return new KompressorService();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/kompressor.php' => config_path('kompressor.php')
        ]);
    }
}

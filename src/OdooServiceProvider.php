<?php

declare(strict_types=1);

namespace Marshmallow\Odoo;

use Illuminate\Support\ServiceProvider;
use Marshmallow\Odoo\Console\Commands\OdooCommand;

class OdooServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-odoo.php', 'laravel-odoo');

        $this->app->singleton(Odoo::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-odoo.php' => config_path('laravel-odoo.php'),
        ], ['laravel-odoo', 'laravel-odoo-config']);

        $this->commands([
            OdooCommand::class,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Marshmallow\Odoo;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use Marshmallow\Odoo\Client\OdooClient;
use Marshmallow\Odoo\Console\Commands\DoctorCommand;
use Marshmallow\Odoo\Console\Commands\ModulesCommand;
use Marshmallow\Odoo\Console\Commands\PingCommand;
use Marshmallow\Odoo\Contracts\Client;

class OdooServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/odoo.php', 'odoo');

        $this->app->singleton(Client::class, function (Application $app): Client {
            /** @var array<string, mixed> $config */
            $config = $app->make(Repository::class)->get('odoo', []);

            return new OdooClient($app->make(Factory::class), $config);
        });

        $this->app->singleton(Odoo::class, function (Application $app): Odoo {
            /** @var array<string, class-string<Resources\Resource>> $resources */
            $resources = $app->make(Repository::class)->get('odoo.resources', []);

            return new Odoo($app->make(Client::class), $resources);
        });
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
            __DIR__.'/../config/odoo.php' => config_path('odoo.php'),
        ], ['odoo', 'odoo-config']);

        $this->commands([
            PingCommand::class,
            ModulesCommand::class,
            DoctorCommand::class,
        ]);
    }
}

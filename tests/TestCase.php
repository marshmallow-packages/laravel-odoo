<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Tests;

use Marshmallow\Odoo\OdooServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            OdooServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('odoo.url', 'https://odoo.test');
        $app['config']->set('odoo.api_key', 'test-key');
        $app['config']->set('odoo.retry.sleep', 0);
    }
}

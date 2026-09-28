<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Facades;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Facade;
use Marshmallow\Odoo\Contracts\Client;
use Marshmallow\Odoo\Odoo as OdooManager;
use Marshmallow\Odoo\Resources\Invoices;
use Marshmallow\Odoo\Resources\Modules;
use Marshmallow\Odoo\Resources\Partners;
use Marshmallow\Odoo\Resources\Products;
use Marshmallow\Odoo\Resources\ProductTemplates;
use Marshmallow\Odoo\Resources\Resource;
use Marshmallow\Odoo\Resources\Taxes;
use Marshmallow\Odoo\Testing\FakeClient;
use Marshmallow\Odoo\Testing\OdooFake;

/**
 * @method static Client api()
 * @method static bool enabled()
 * @method static Resource model(string $model)
 * @method static Partners partners()
 * @method static Products products()
 * @method static ProductTemplates templates()
 * @method static Invoices invoices()
 * @method static Taxes taxes()
 * @method static Modules modules()
 * @method static void macro(string $name, object|callable $macro)
 *
 * @see OdooManager
 */
class Odoo extends Facade
{
    /**
     * Replace the client with an in-memory fake that answers from scripted
     * responses (keyed "model/method") and records every call for assertions.
     *
     * @param  array<string, mixed>  $responses
     */
    public static function fake(array $responses = []): OdooFake
    {
        /** @var array<string, class-string<resource>> $resources */
        $resources = app(Repository::class)->get('odoo.resources', []);

        $client = new FakeClient($responses);
        $fake = new OdooFake($client, $resources);

        app()->instance(Client::class, $client);
        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return OdooManager::class;
    }
}

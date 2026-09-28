<?php

declare(strict_types=1);

namespace Marshmallow\Odoo;

use BadMethodCallException;
use Illuminate\Support\Traits\Macroable;
use Marshmallow\Odoo\Contracts\Client;
use Marshmallow\Odoo\Resources\Invoices;
use Marshmallow\Odoo\Resources\Modules;
use Marshmallow\Odoo\Resources\Partners;
use Marshmallow\Odoo\Resources\Products;
use Marshmallow\Odoo\Resources\ProductTemplates;
use Marshmallow\Odoo\Resources\Resource;
use Marshmallow\Odoo\Resources\Taxes;

class Odoo
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * @param  array<string, class-string<resource>>  $resources  Custom resources from config('odoo.resources')
     */
    public function __construct(
        protected readonly Client $client,
        protected readonly array $resources = [],
    ) {}

    /**
     * The low-level client, for calls the resources do not cover.
     */
    public function api(): Client
    {
        return $this->client;
    }

    public function enabled(): bool
    {
        return $this->client->enabled();
    }

    /**
     * A generic resource for any Odoo model.
     */
    public function model(string $model): Resource
    {
        return new Resource($this->client, $model);
    }

    public function partners(): Partners
    {
        return new Partners($this->client);
    }

    public function products(): Products
    {
        return new Products($this->client);
    }

    public function templates(): ProductTemplates
    {
        return new ProductTemplates($this->client);
    }

    public function invoices(): Invoices
    {
        return new Invoices($this->client);
    }

    public function taxes(): Taxes
    {
        return new Taxes($this->client);
    }

    public function modules(): Modules
    {
        return new Modules($this->client);
    }

    /**
     * Resolve macros first, then the custom resources registered in config.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (isset($this->resources[$method])) {
            return new $this->resources[$method]($this->client);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist. Register it under config("odoo.resources") or as a macro.',
            static::class,
            $method,
        ));
    }
}

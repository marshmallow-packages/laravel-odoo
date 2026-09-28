<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Marshmallow\Odoo\Odoo
 */
class Odoo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Marshmallow\Odoo\Odoo::class;
    }
}

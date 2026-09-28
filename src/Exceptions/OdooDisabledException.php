<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Exceptions;

/**
 * Thrown when config('odoo.enabled') is false; check Odoo::enabled() to skip work up front.
 */
class OdooDisabledException extends OdooException
{
    public function __construct()
    {
        parent::__construct('Odoo is disabled. Enable it with ODOO_ENABLED=true.');
    }
}

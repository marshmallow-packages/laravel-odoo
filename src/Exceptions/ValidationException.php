<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Exceptions;

/**
 * Odoo rejected the values (odoo ValidationError or UserError).
 */
class ValidationException extends OdooException {}

<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Exceptions;

/**
 * The API key's user lacks the access rights for this operation (odoo AccessError, HTTP 403).
 */
class AccessDeniedException extends OdooException {}

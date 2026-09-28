<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Exceptions;

/**
 * The request itself is wrong: unknown model or method (werkzeug NotFound),
 * or an invalid field or value in a domain (builtins.ValueError and friends).
 * Never retried; fix the code.
 */
class InvalidRequestException extends OdooException {}

<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Exceptions;

/**
 * The API key is missing or invalid (HTTP 401, werkzeug Unauthorized, odoo AccessDenied).
 */
class AuthenticationException extends OdooException {}

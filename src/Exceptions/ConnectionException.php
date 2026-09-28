<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Exceptions;

/**
 * The Odoo instance could not be reached within the configured timeout and retries.
 */
class ConnectionException extends OdooException {}

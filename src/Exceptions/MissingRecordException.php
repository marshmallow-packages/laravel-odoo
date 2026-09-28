<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Exceptions;

/**
 * The requested record does not exist or is not visible to the API key's user.
 */
class MissingRecordException extends OdooException {}

<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Client;

use JsonSerializable;

/**
 * Encodes the request body as a JSON object, so no params still sends {}
 * rather than [] (Odoo rejects a list where it expects named params).
 */
final class JsonBody implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(private readonly array $params) {}

    public function jsonSerialize(): object
    {
        return (object) $this->params;
    }
}

<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Contracts;

interface Client
{
    /**
     * Call a method on an Odoo model over the JSON-2 API.
     *
     * @param  array<string, mixed>  $params  Named parameters of the method (domain, fields, vals_list, context, ...)
     * @param  array<int, int>  $ids  Record ids the method acts on; omit for @api.model methods
     */
    public function call(string $model, string $method, array $params = [], array $ids = []): mixed;

    /**
     * The raw /web/version payload (version, version_info, ...).
     *
     * @return array<string, mixed>
     */
    public function version(): array;

    /**
     * The server version label, e.g. "19.0+e".
     */
    public function versionLabel(): string;

    /**
     * The server major version, e.g. 19, or 0 when unknown.
     */
    public function majorVersion(): int;

    /**
     * The context of the user behind the API key (uid, lang, tz).
     *
     * @return array<string, mixed>
     */
    public function contextGet(): array;

    public function enabled(): bool;
}

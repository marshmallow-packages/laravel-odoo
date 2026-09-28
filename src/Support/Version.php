<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Support;

/**
 * Reads the /web/version payload, which uses "version" and "version_info"
 * (the legacy common.version call used server_version and server_version_info).
 */
final class Version
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function label(array $payload): string
    {
        $label = $payload['version'] ?? $payload['server_version'] ?? null;

        return is_string($label) && $label !== '' ? $label : 'unknown';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function major(array $payload): int
    {
        $info = $payload['version_info'] ?? $payload['server_version_info'] ?? null;

        if (is_array($info)) {
            return (int) ($info[0] ?? 0);
        }

        return (int) self::label($payload);
    }
}

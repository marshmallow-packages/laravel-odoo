<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Exceptions;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OdooException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $payload  The decoded Odoo error body (name, message, arguments, context, debug)
     */
    public function __construct(
        string $message = '',
        public readonly ?Response $response = null,
        public readonly ?array $payload = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $response?->status() ?? 0, $previous);
    }

    /**
     * Map a failed Odoo response onto the matching typed exception.
     */
    public static function fromResponse(Response $response): self
    {
        $payload = $response->json();
        $payload = is_array($payload) ? $payload : null;

        $name = isset($payload['name']) && is_string($payload['name']) ? $payload['name'] : null;

        $message = isset($payload['message']) && is_string($payload['message']) && $payload['message'] !== ''
            ? $payload['message']
            : "Odoo responded with HTTP {$response->status()}.";

        $class = self::classFor($name, $response->status());

        return new $class($message, $response, $payload);
    }

    /**
     * The fully qualified Odoo exception name, e.g. odoo.exceptions.ValidationError.
     */
    public function odooName(): ?string
    {
        $name = $this->payload['name'] ?? null;

        return is_string($name) ? $name : null;
    }

    /**
     * @return array<int, mixed>
     */
    public function arguments(): array
    {
        $arguments = $this->payload['arguments'] ?? [];

        return is_array($arguments) ? array_values($arguments) : [];
    }

    /**
     * The server-side traceback, when Odoo includes one.
     */
    public function debug(): ?string
    {
        $debug = $this->payload['debug'] ?? null;

        return is_string($debug) ? $debug : null;
    }

    /**
     * @return class-string<self>
     */
    private static function classFor(?string $name, int $status): string
    {
        $short = $name === null ? '' : Str::afterLast($name, '.');

        return match (true) {
            in_array($short, ['Unauthorized', 'AccessDenied'], true) => AuthenticationException::class,
            in_array($short, ['AccessError', 'Forbidden'], true) => AccessDeniedException::class,
            in_array($short, ['ValidationError', 'UserError'], true) => ValidationException::class,
            $short === 'MissingError' => MissingRecordException::class,
            $status === 401 => AuthenticationException::class,
            $status === 403 => AccessDeniedException::class,
            $status === 404 => MissingRecordException::class,
            default => ServerException::class,
        };
    }
}

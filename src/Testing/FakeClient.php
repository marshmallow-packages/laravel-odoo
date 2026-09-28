<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Testing;

use Closure;
use Marshmallow\Odoo\Contracts\Client;
use RuntimeException;

/**
 * Answers calls from scripted responses and records every call made.
 */
class FakeClient implements Client
{
    /** @var array<string, mixed> */
    private array $responses = [];

    /** @var array<int, RecordedCall> */
    private array $calls = [];

    /**
     * @param  array<string, mixed>  $responses  Keyed "model/method", values are the return value or a Closure(RecordedCall): mixed
     */
    public function __construct(array $responses = [], private bool $enabled = true)
    {
        foreach ($responses as $key => $response) {
            $this->responses[$key] = $response;
        }
    }

    /**
     * Script the response for a model method. A Closure receives the RecordedCall.
     */
    public function respond(string $model, string $method, mixed $response): static
    {
        $this->responses["{$model}/{$method}"] = $response;

        return $this;
    }

    public function call(string $model, string $method, array $params = [], array $ids = []): mixed
    {
        $call = new RecordedCall($model, $method, $params, $ids);
        $this->calls[] = $call;

        $key = "{$model}/{$method}";

        if (! array_key_exists($key, $this->responses)) {
            throw new RuntimeException("No fake response registered for {$key}. Script one with Odoo::fake(['{$key}' => ...]).");
        }

        $response = $this->responses[$key];

        return $response instanceof Closure ? $response($call) : $response;
    }

    public function version(): array
    {
        return (array) ($this->responses['web/version'] ?? [
            'server_version' => '19.0',
            'server_version_info' => [19, 0, 0, 'final', 0, ''],
            'server_serie' => '19.0',
            'protocol_version' => 1,
        ]);
    }

    public function contextGet(): array
    {
        if (array_key_exists('res.users/context_get', $this->responses)) {
            return (array) $this->call('res.users', 'context_get');
        }

        $this->calls[] = new RecordedCall('res.users', 'context_get');

        return ['uid' => 1, 'lang' => 'en_US', 'tz' => 'UTC'];
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<int, RecordedCall>
     */
    public function calls(?string $model = null, ?string $method = null): array
    {
        return array_values(array_filter(
            $this->calls,
            static fn (RecordedCall $call): bool => ($model === null || $call->model === $model)
                && ($method === null || $call->method === $method),
        ));
    }
}

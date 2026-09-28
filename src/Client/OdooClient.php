<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Client;

use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Marshmallow\Odoo\Contracts\Client;
use Marshmallow\Odoo\Exceptions\ConnectionException;
use Marshmallow\Odoo\Exceptions\InvalidConfigurationException;
use Marshmallow\Odoo\Exceptions\OdooDisabledException;
use Marshmallow\Odoo\Exceptions\OdooException;
use Marshmallow\Odoo\Support\Version;
use Throwable;

class OdooClient implements Client
{
    /**
     * @param  array<string, mixed>  $config  The odoo config array
     */
    public function __construct(
        private readonly Factory $http,
        private readonly array $config,
    ) {}

    public function call(string $model, string $method, array $params = [], array $ids = []): mixed
    {
        $body = $params;

        if ($ids !== []) {
            $body['ids'] = array_values($ids);
        }

        return $this->send('post', "json/2/{$model}/{$method}", $body)->json();
    }

    public function version(): array
    {
        return (array) $this->send('get', 'web/version')->json();
    }

    public function versionLabel(): string
    {
        return Version::label($this->version());
    }

    public function majorVersion(): int
    {
        return Version::major($this->version());
    }

    public function contextGet(): array
    {
        return (array) $this->call('res.users', 'context_get');
    }

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? true);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function send(string $method, string $path, array $body = []): Response
    {
        if (! $this->enabled()) {
            throw new OdooDisabledException;
        }

        $request = $this->request();

        try {
            $response = $method === 'get'
                ? $request->get($path)
                : $request->post($path, new JsonBody($body));
        } catch (HttpConnectionException $exception) {
            throw new ConnectionException($exception->getMessage(), previous: $exception);
        }

        if (! $response->successful()) {
            throw OdooException::fromResponse($response);
        }

        return $response;
    }

    private function request(): PendingRequest
    {
        $url = rtrim((string) ($this->config['url'] ?? ''), '/');

        if ($url === '') {
            throw new InvalidConfigurationException('No Odoo URL configured. Set ODOO_URL or config("odoo.url").');
        }

        $apiKey = (string) ($this->config['api_key'] ?? '');

        if ($apiKey === '') {
            throw new InvalidConfigurationException('No Odoo API key configured. Set ODOO_API_KEY or config("odoo.api_key").');
        }

        $request = $this->http
            ->baseUrl($url)
            ->acceptJson()
            ->asJson()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withToken($apiKey, 'bearer')
            ->withUserAgent((string) ($this->config['user_agent'] ?? 'marshmallow/laravel-odoo'))
            ->timeout((int) ($this->config['timeout'] ?? 30))
            ->retry(
                times: (int) ($this->config['retry']['times'] ?? 1),
                sleepMilliseconds: (int) ($this->config['retry']['sleep'] ?? 0),
                when: fn (Throwable $exception): bool => $this->shouldRetry($exception),
                throw: false,
            );

        $database = (string) ($this->config['database'] ?? '');

        return $database === ''
            ? $request
            : $request->withHeader('X-Odoo-Database', $database);
    }

    private function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof HttpConnectionException) {
            return true;
        }

        if (! $exception instanceof RequestException) {
            return false;
        }

        if (! $exception->response->serverError()) {
            return false;
        }

        // A 5xx carrying an Odoo error body (builtins.ValueError for a bad
        // domain, for instance) is deterministic; only proxy-level 502/503
        // without one are worth another attempt.
        return ! is_string($exception->response->json('name'));
    }
}

<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Marshmallow\Odoo\Contracts\Client;
use Marshmallow\Odoo\Exceptions\AccessDeniedException;
use Marshmallow\Odoo\Exceptions\AuthenticationException;
use Marshmallow\Odoo\Exceptions\ConnectionException;
use Marshmallow\Odoo\Exceptions\InvalidConfigurationException;
use Marshmallow\Odoo\Exceptions\MissingRecordException;
use Marshmallow\Odoo\Exceptions\OdooDisabledException;
use Marshmallow\Odoo\Exceptions\ServerException;
use Marshmallow\Odoo\Exceptions\ValidationException;

it('posts named params and ids to the json-2 endpoint with the expected headers', function () {
    Http::fake(['odoo.test/json/2/res.partner/read' => Http::response([['id' => 7, 'name' => 'Acme']])]);
    config()->set('odoo.database', 'company');

    $result = app(Client::class)->call('res.partner', 'read', ['fields' => ['name']], [7]);

    expect($result)->toBe([['id' => 7, 'name' => 'Acme']]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://odoo.test/json/2/res.partner/read'
        && $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'bearer test-key')
        && $request->hasHeader('X-Odoo-Database', 'company')
        && $request->hasHeader('User-Agent', 'marshmallow/laravel-odoo')
        && $request->hasHeader('Content-Type', 'application/json; charset=utf-8')
        && $request->data() === ['fields' => ['name'], 'ids' => [7]]);
});

it('sends an empty json object when there are no params', function () {
    Http::fake(['odoo.test/json/2/res.users/context_get' => Http::response(['uid' => 2])]);

    app(Client::class)->contextGet();

    Http::assertSent(fn (Request $request): bool => $request->body() === '{}'
        && ! $request->hasHeader('X-Odoo-Database'));
});

it('reads the server version', function () {
    Http::fake(['odoo.test/web/version' => Http::response(['server_version' => '19.0'])]);

    expect(app(Client::class)->version())->toBe(['server_version' => '19.0']);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET');
});

it('maps odoo errors onto typed exceptions', function (string $name, int $status, string $class) {
    Http::fake(['odoo.test/*' => Http::response(['name' => $name, 'message' => 'Nope', 'arguments' => ['Nope'], 'debug' => 'Traceback'], $status)]);

    try {
        app(Client::class)->call('res.partner', 'create', ['vals_list' => [[]]]);
    } catch (Throwable $exception) {
        expect($exception)->toBeInstanceOf($class);
        expect($exception->getMessage())->toBe('Nope');
        expect($exception->getCode())->toBe($status);
        expect($exception->odooName())->toBe($name);
        expect($exception->arguments())->toBe(['Nope']);
        expect($exception->debug())->toBe('Traceback');
        expect($exception->payload['name'])->toBe($name);
        expect($exception->response?->status())->toBe($status);

        return;
    }

    $this->fail('No exception thrown.');
})->with([
    'unauthorized' => ['werkzeug.exceptions.Unauthorized', 401, AuthenticationException::class],
    'access denied' => ['odoo.exceptions.AccessDenied', 403, AuthenticationException::class],
    'access error' => ['odoo.exceptions.AccessError', 403, AccessDeniedException::class],
    'validation' => ['odoo.exceptions.ValidationError', 422, ValidationException::class],
    'user error' => ['odoo.exceptions.UserError', 422, ValidationException::class],
    'missing' => ['odoo.exceptions.MissingError', 404, MissingRecordException::class],
    'other' => ['builtins.TypeError', 500, ServerException::class],
]);

it('falls back to the http status when the error body is not json', function () {
    Http::fake(['odoo.test/*' => Http::response('<html>Bad gateway</html>', 502)]);

    try {
        app(Client::class)->call('res.partner', 'read', [], [1]);
    } catch (ServerException $exception) {
        expect($exception->getMessage())->toBe('Odoo responded with HTTP 502.');
        expect($exception->payload)->toBeNull();

        return;
    }

    $this->fail('No exception thrown.');
});

it('maps 401 without a body to an authentication exception', function () {
    Http::fake(['odoo.test/*' => Http::response('', 401)]);

    app(Client::class)->call('res.partner', 'read', [], [1]);
})->throws(AuthenticationException::class);

it('retries connection failures and server errors but not client errors', function () {
    config()->set('odoo.retry.times', 3);

    Http::fake([
        'odoo.test/json/2/a/b' => Http::sequence()
            ->push('', 503)
            ->push('', 500)
            ->push(['ok' => true]),
        'odoo.test/json/2/c/d' => Http::sequence()
            ->push(['name' => 'odoo.exceptions.ValidationError', 'message' => 'Bad'], 422)
            ->push(['ok' => true]),
    ]);

    expect(app(Client::class)->call('a', 'b'))->toBe(['ok' => true]);

    expect(fn () => app(Client::class)->call('c', 'd'))->toThrow(ValidationException::class);

    Http::assertSentCount(4);
});

it('wraps transport failures in the package connection exception', function () {
    Http::fake(['odoo.test/*' => fn () => throw new Illuminate\Http\Client\ConnectionException('cURL error 28')]);
    config()->set('odoo.retry.times', 1);

    app(Client::class)->call('res.partner', 'read', [], [1]);
})->throws(ConnectionException::class, 'cURL error 28');

it('throws when disabled instead of calling out', function () {
    Http::fake();
    config()->set('odoo.enabled', false);

    expect(app(Client::class)->enabled())->toBeFalse();
    expect(fn () => app(Client::class)->call('res.partner', 'read', [], [1]))->toThrow(OdooDisabledException::class);

    Http::assertNothingSent();
});

it('throws a configuration exception without a url or api key', function (string $key, string $env) {
    Http::fake();
    config()->set("odoo.{$key}", null);

    expect(fn () => app(Client::class)->version())->toThrow(InvalidConfigurationException::class, $env);

    Http::assertNothingSent();
})->with([
    'url' => ['url', 'ODOO_URL'],
    'api key' => ['api_key', 'ODOO_API_KEY'],
]);

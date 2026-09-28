<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Marshmallow\Odoo\Facades\Odoo;

it('passes on a healthy setup with the required modules', function () {
    Odoo::fake(['ir.module.module/search_read' => [['name' => 'account', 'shortdesc' => 'Invoicing']]]);

    $this->artisan('odoo:doctor', ['--module' => ['account']])
        ->expectsOutputToContain('https://odoo.test')
        ->expectsOutputToContain('19.0')
        ->expectsOutputToContain('valid (uid 1)')
        ->expectsOutputToContain('installed')
        ->expectsOutputToContain('Everything checks out.')
        ->assertSuccessful();
});

it('reports missing config', function () {
    config()->set('odoo.url', null);
    config()->set('odoo.api_key', '');

    $this->artisan('odoo:doctor')
        ->expectsOutputToContain('missing, set ODOO_URL')
        ->expectsOutputToContain('missing, set ODOO_API_KEY')
        ->expectsOutputToContain('2 problem(s)')
        ->assertFailed();
});

it('warns when disabled', function () {
    config()->set('odoo.enabled', false);

    $this->artisan('odoo:doctor')
        ->expectsOutputToContain('ODOO_ENABLED is false')
        ->assertSuccessful();
});

it('reports an unreachable instance', function () {
    config()->set('odoo.retry.times', 1);
    Http::fake(['odoo.test/*' => fn () => throw new ConnectionException('cURL error 7')]);

    $this->artisan('odoo:doctor')
        ->expectsOutputToContain('cURL error 7')
        ->assertFailed();
});

it('reports an unsupported version', function () {
    Odoo::fake(['web/version' => ['server_version' => '17.0', 'server_version_info' => [17, 0, 0, 'final', 0, '']]]);

    $this->artisan('odoo:doctor')
        ->expectsOutputToContain('17.0, the JSON-2 API needs Odoo 19+')
        ->assertFailed();
});

it('reports a rejected api key', function () {
    Http::fake([
        'odoo.test/web/version' => Http::response(['server_version' => '19.0', 'server_version_info' => [19, 0, 0, 'final', 0, '']]),
        'odoo.test/json/2/*' => Http::response(['name' => 'werkzeug.exceptions.Unauthorized', 'message' => 'Invalid apikey'], 401),
    ]);

    $this->artisan('odoo:doctor')
        ->expectsOutputToContain('rejected: Invalid apikey')
        ->assertFailed();
});

it('reports missing modules', function () {
    Odoo::fake(['ir.module.module/search_read' => []]);

    $this->artisan('odoo:doctor', ['--module' => ['sale']])
        ->expectsOutputToContain('not installed')
        ->expectsOutputToContain('1 problem(s)')
        ->assertFailed();
});

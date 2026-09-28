<?php

declare(strict_types=1);

use Marshmallow\Odoo\Contracts\Client;
use Marshmallow\Odoo\Facades\Odoo;
use Marshmallow\Odoo\Odoo as OdooManager;
use Marshmallow\Odoo\Resources\Partners;
use Marshmallow\Odoo\Testing\FakeClient;
use Marshmallow\Odoo\Testing\OdooFake;
use Marshmallow\Odoo\Testing\RecordedCall;
use PHPUnit\Framework\AssertionFailedError;

it('swaps the facade root and the client binding', function () {
    $fake = Odoo::fake();

    expect($fake)->toBeInstanceOf(OdooFake::class);
    expect(app(OdooManager::class))->toBe($fake);
    expect(app(Client::class))->toBeInstanceOf(FakeClient::class)->toBe($fake->api());
});

it('answers from scripted responses and closures', function () {
    $fake = Odoo::fake(['res.partner/search' => [1]]);
    $fake->respond('res.partner', 'read', fn (RecordedCall $call): array => array_map(fn (int $id): array => ['id' => $id], $call->ids));

    expect(Odoo::partners()->search())->toBe([1]);
    expect(Odoo::partners()->get([3, 4]))->toBe([['id' => 3], ['id' => 4]]);
    expect(Odoo::api()->version()['server_version'])->toBe('19.0');
    expect(Odoo::api()->contextGet()['uid'])->toBe(1);
    expect(Odoo::enabled())->toBeTrue();
});

it('lets tests override version and context', function () {
    Odoo::fake([
        'web/version' => ['server_version' => '18.0', 'server_version_info' => [18, 0, 0, 'final', 0, '']],
        'res.users/context_get' => ['uid' => 42],
    ]);

    expect(Odoo::api()->version()['server_version'])->toBe('18.0');
    expect(Odoo::api()->contextGet())->toBe(['uid' => 42]);
});

it('fails loudly for calls without a scripted response', function () {
    Odoo::fake();

    Odoo::partners()->search();
})->throws(RuntimeException::class, 'res.partner/search');

it('keeps custom resources from config', function () {
    config()->set('odoo.resources', ['contacts' => Partners::class]);

    expect(Odoo::fake()->contacts())->toBeInstanceOf(Partners::class);
});

it('records calls for assertions', function () {
    $fake = Odoo::fake(['res.partner/search' => [], 'res.partner/write' => true]);

    Odoo::partners()->search([['a', '=', 1]]);
    Odoo::partners()->update(2, ['name' => 'x']);

    $fake->assertCalled('res.partner', 'search');
    $fake->assertCalled('res.partner', 'write', fn (RecordedCall $call): bool => $call->is('res.partner', 'write') && $call->ids === [2]);
    $fake->assertCalledTimes('res.partner', 'search', 1);
    $fake->assertNotCalled('res.partner', 'unlink');
    $fake->assertNotCalled('res.partner', 'write', fn (RecordedCall $call): bool => $call->ids === [3]);

    expect($fake->recorded())->toHaveCount(2);
    expect($fake->recorded('res.partner', 'write'))->toHaveCount(1);

    expect(fn () => $fake->assertNothingCalled())->toThrow(AssertionFailedError::class, '2 unexpected Odoo call(s)');
    expect(fn () => $fake->assertCalled('res.partner', 'unlink'))->toThrow(AssertionFailedError::class, '[res.partner/unlink]');
    expect(fn () => $fake->assertCalledTimes('res.partner', 'search', 2))->toThrow(AssertionFailedError::class, '1 times instead of 2');
    expect(fn () => $fake->assertNotCalled('res.partner', 'search'))->toThrow(AssertionFailedError::class);
});

it('asserts nothing was called', function () {
    Odoo::fake()->assertNothingCalled();
});

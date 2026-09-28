<?php

declare(strict_types=1);

use Marshmallow\Odoo\Exceptions\MissingRecordException;
use Marshmallow\Odoo\Facades\Odoo;
use Marshmallow\Odoo\Resources\Invoices;
use Marshmallow\Odoo\Resources\Resource;
use Marshmallow\Odoo\Support\Domain;
use Marshmallow\Odoo\Testing\RecordedCall;

it('exposes the built-in resources with their odoo models', function (string $accessor, string $model) {
    expect(Odoo::$accessor()->model())->toBe($model);
})->with([
    ['partners', 'res.partner'],
    ['products', 'product.product'],
    ['templates', 'product.template'],
    ['invoices', 'account.move'],
    ['taxes', 'account.tax'],
    ['modules', 'ir.module.module'],
]);

it('builds a generic resource for any model', function () {
    expect(Odoo::model('crm.lead'))->toBeInstanceOf(Resource::class);
    expect(Odoo::model('crm.lead')->model())->toBe('crm.lead');
});

it('requires a model name for the generic resource', function () {
    new Resource(Odoo::fake()->api());
})->throws(InvalidArgumentException::class);

it('finds one record', function () {
    $fake = Odoo::fake(['res.partner/read' => [['id' => 7, 'name' => 'Acme']]]);

    expect(Odoo::partners()->find(7, ['name']))->toBe(['id' => 7, 'name' => 'Acme']);

    $fake->assertCalled('res.partner', 'read', fn (RecordedCall $call): bool => $call->ids === [7]
        && $call->params === ['fields' => ['name']]);
});

it('throws when the record is missing', function () {
    Odoo::fake(['res.partner/read' => []]);

    Odoo::partners()->find(7);
})->throws(MissingRecordException::class, 'No res.partner record with id 7.');

it('gets several records and skips the call for no ids', function () {
    $fake = Odoo::fake(['res.partner/read' => [['id' => 1], ['id' => 2]]]);

    expect(Odoo::partners()->get([1, 2]))->toBe([['id' => 1], ['id' => 2]]);
    expect(Odoo::partners()->get([]))->toBe([]);

    $fake->assertCalledTimes('res.partner', 'read', 1);
});

it('searches ids with pagination and order', function () {
    $fake = Odoo::fake(['res.partner/search' => [3, 4]]);

    $ids = Odoo::partners()->search(Domain::make()->where('is_company', true), limit: 10, offset: 5, order: 'name asc');

    expect($ids)->toBe([3, 4]);

    $fake->assertCalled('res.partner', 'search', fn (RecordedCall $call): bool => $call->params === [
        'domain' => [['is_company', '=', true]],
        'offset' => 5,
        'limit' => 10,
        'order' => 'name asc',
    ]);
});

it('search-reads with a raw domain and no optional params', function () {
    $fake = Odoo::fake(['res.partner/search_read' => [['id' => 1, 'name' => 'A']]]);

    expect(Odoo::partners()->searchRead([['name', '=', 'A']], ['name']))->toBe([['id' => 1, 'name' => 'A']]);

    $fake->assertCalled('res.partner', 'search_read', fn (RecordedCall $call): bool => $call->params === [
        'domain' => [['name', '=', 'A']],
        'fields' => ['name'],
        'offset' => 0,
    ]);
});

it('returns the first match or null', function () {
    $fake = Odoo::fake(['res.partner/search_read' => [['id' => 1]]]);

    expect(Odoo::partners()->first(Domain::make()->where('email', 'a@b.c')))->toBe(['id' => 1]);

    $fake->assertCalled('res.partner', 'search_read', fn (RecordedCall $call): bool => $call->params['limit'] === 1);

    $fake->respond('res.partner', 'search_read', []);

    expect(Odoo::partners()->first([]))->toBeNull();
});

it('counts and checks existence', function () {
    $fake = Odoo::fake(['res.partner/search_count' => 2]);

    expect(Odoo::partners()->searchCount([]))->toBe(2);
    expect(Odoo::partners()->exists(9))->toBeTrue();

    $fake->assertCalled('res.partner', 'search_count', fn (RecordedCall $call): bool => $call->params === ['domain' => [['id', '=', 9]]]);
});

it('creates one or many records', function () {
    $fake = Odoo::fake(['res.partner/create' => [11, 12]]);

    expect(Odoo::partners()->createMany([['name' => 'A'], ['name' => 'B']]))->toBe([11, 12]);

    $fake->respond('res.partner', 'create', [13]);

    expect(Odoo::partners()->create(['name' => 'C']))->toBe(13);

    $fake->assertCalled('res.partner', 'create', fn (RecordedCall $call): bool => $call->params === ['vals_list' => [['name' => 'C']]]
        && $call->ids === []);
});

it('updates and deletes by id or ids', function () {
    $fake = Odoo::fake(['res.partner/write' => true, 'res.partner/unlink' => true]);

    expect(Odoo::partners()->update(1, ['name' => 'Z']))->toBeTrue();
    expect(Odoo::partners()->delete([1, 2]))->toBeTrue();

    $fake->assertCalled('res.partner', 'write', fn (RecordedCall $call): bool => $call->ids === [1] && $call->params === ['vals' => ['name' => 'Z']]);
    $fake->assertCalled('res.partner', 'unlink', fn (RecordedCall $call): bool => $call->ids === [1, 2] && $call->params === []);
});

it('reads field definitions', function () {
    $fake = Odoo::fake(['res.partner/fields_get' => ['name' => ['type' => 'char']]]);

    expect(Odoo::partners()->fields(['type']))->toBe(['name' => ['type' => 'char']]);

    $fake->assertCalled('res.partner', 'fields_get', fn (RecordedCall $call): bool => $call->params === ['attributes' => ['type']]);
});

it('sends the context with every call of a contextual copy', function () {
    $fake = Odoo::fake(['res.partner/read' => [['id' => 1]]]);

    $dutch = Odoo::partners()->withContext(['lang' => 'nl_NL']);
    $dutch->get([1]);
    Odoo::partners()->get([1]);

    $fake->assertCalled('res.partner', 'read', fn (RecordedCall $call): bool => ($call->params['context'] ?? null) === ['lang' => 'nl_NL']);
    $fake->assertCalled('res.partner', 'read', fn (RecordedCall $call): bool => ! isset($call->params['context']));
});

it('posts invoices and reads the payment state', function () {
    $fake = Odoo::fake([
        'account.move/action_post' => true,
        'account.move/read' => [['id' => 5, 'payment_state' => 'paid']],
    ]);

    Odoo::invoices()->post(5);

    expect(Odoo::invoices()->paymentState(5))->toBe('paid');
    expect(Invoices::customerInvoices()->toArray())->toBe([['move_type', '=', 'out_invoice']]);
    expect(Invoices::creditNotes()->toArray())->toBe([['move_type', '=', 'out_refund']]);

    $fake->assertCalled('account.move', 'action_post', fn (RecordedCall $call): bool => $call->ids === [5]);
});

it('lists installed modules and checks one', function () {
    $fake = Odoo::fake([
        'ir.module.module/search_read' => [['name' => 'account', 'shortdesc' => 'Invoicing']],
        'ir.module.module/search_count' => 0,
    ]);

    expect(Odoo::modules()->installed())->toBe([['name' => 'account', 'shortdesc' => 'Invoicing']]);
    expect(Odoo::modules()->isInstalled('sale'))->toBeFalse();

    $fake->assertCalled('ir.module.module', 'search_read', fn (RecordedCall $call): bool => $call->params['domain'] === [['state', '=', 'installed']]
        && $call->params['fields'] === ['name', 'shortdesc']);
    $fake->assertCalled('ir.module.module', 'search_count', fn (RecordedCall $call): bool => $call->params['domain'] === [['name', '=', 'sale'], ['state', '=', 'installed']]);
});

<?php

declare(strict_types=1);

use Marshmallow\Odoo\Support\Domain;

it('builds and-ed terms', function () {
    $domain = Domain::make()
        ->where('state', 'installed')
        ->where('id', '>', 5)
        ->whereIn('type', ['a', 'b'])
        ->whereNotIn('id', [1])
        ->whereNull('parent_id')
        ->whereNotNull('email')
        ->whereLike('name', 'Acme%')
        ->whereILike('ref', 'acme');

    expect($domain->toArray())->toBe([
        ['state', '=', 'installed'],
        ['id', '>', 5],
        ['type', 'in', ['a', 'b']],
        ['id', 'not in', [1]],
        ['parent_id', '=', false],
        ['email', '!=', false],
        ['name', 'like', 'Acme%'],
        ['ref', 'ilike', 'acme'],
    ]);
});

it('or-combines everything before with the new term', function () {
    $domain = Domain::make()
        ->where('a', 1)
        ->where('b', 2)
        ->orWhere('c', 3);

    expect($domain->toArray())->toBe(['|', '&', ['a', '=', 1], ['b', '=', 2], ['c', '=', 3]]);
});

it('chains or terms', function () {
    $domain = Domain::make()
        ->where('a', 1)
        ->orWhere('b', 2)
        ->orWhere('c', '!=', 3);

    expect($domain->toArray())->toBe(['|', '|', ['a', '=', 1], ['b', '=', 2], ['c', '!=', 3]]);
});

it('starts with or when nothing came before', function () {
    expect(Domain::make()->orWhere('a', 1)->toArray())->toBe([['a', '=', 1]]);
});

it('nests domains as single operands', function () {
    $inner = Domain::make()->where('x', 1)->where('y', 2);

    $domain = Domain::make()
        ->where('a', 1)
        ->whereDomain($inner)
        ->orWhereDomain([['z', '=', 3], ['w', '=', 4]])
        ->whereDomain([]);

    expect($domain->toArray())->toBe([
        '|', '&', ['a', '=', 1], '&', ['x', '=', 1], ['y', '=', 2], '&', ['z', '=', 3], ['w', '=', 4],
    ]);
});

it('wraps raw terms and reports emptiness', function () {
    expect(Domain::fromArray([['a', '=', 1]])->toArray())->toBe([['a', '=', 1]]);
    expect(Domain::make()->isEmpty())->toBeTrue();
    expect(Domain::make()->where('a', 1)->isEmpty())->toBeFalse();
});

it('nests closures as and-ed and or-ed groups', function () {
    $domain = Domain::make()
        ->where('is_company', true)
        ->where(fn (Domain $d) => $d->where('a', 1)->orWhere('b', 2))
        ->orWhere(fn (Domain $d) => $d->where('c', 3)->where('d', 4));

    expect($domain->toArray())->toBe([
        '|', '&', ['is_company', '=', true], '|', ['a', '=', 1], ['b', '=', 2], '&', ['c', '=', 3], ['d', '=', 4],
    ]);
});

it('negates a group', function () {
    expect(Domain::make()->where('x', 1)->whereNot(fn (Domain $d) => $d->where('a', 1)->where('b', 2))->toArray())
        ->toBe([['x', '=', 1], '!', '&', ['a', '=', 1], ['b', '=', 2]]);

    expect(Domain::make()->whereNot([['a', '=', 1]])->toArray())->toBe(['!', ['a', '=', 1]]);
    expect(Domain::make()->whereNot([])->toArray())->toBe([]);
});

it('builds between and hierarchy terms', function () {
    expect(Domain::make()->whereBetween('date', '2026-01-01', '2026-01-31')->toArray())
        ->toBe([['date', '>=', '2026-01-01'], ['date', '<=', '2026-01-31']]);

    expect(Domain::make()->where('x', 1)->whereNotBetween('qty', 1, 9)->toArray())
        ->toBe([['x', '=', 1], '|', ['qty', '<', 1], ['qty', '>', 9]]);

    expect(Domain::make()->whereChildOf('parent_id', 5)->whereParentOf('id', [6, 7])->toArray())
        ->toBe([['parent_id', 'child_of', [5]], ['id', 'parent_of', [6, 7]]]);
});

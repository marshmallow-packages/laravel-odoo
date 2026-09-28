<?php

declare(strict_types=1);

use Marshmallow\Odoo\Odoo;

it('resolves the singleton', function () {
    expect(app(Odoo::class))->toBeInstanceOf(Odoo::class);
});

it('returns the same instance from the container', function () {
    expect(app(Odoo::class))->toBe(app(Odoo::class));
});

it('merges the package config', function () {
    expect(config('laravel-odoo.placeholder'))->toBe('default');
});

it('registers the artisan command', function () {
    $this->artisan('laravel-odoo:placeholder')
        ->expectsOutputToContain('Odoo placeholder command executed.')
        ->assertSuccessful();
});

<?php

declare(strict_types=1);

use Marshmallow\Odoo\Facades\Odoo;

it('lists installed modules', function () {
    Odoo::fake(['ir.module.module/search_read' => [
        ['name' => 'account', 'shortdesc' => 'Invoicing'],
        ['name' => 'sale', 'shortdesc' => 'Sales'],
    ]]);

    $this->artisan('odoo:modules')
        ->expectsTable(['Module', 'Title'], [['account', 'Invoicing'], ['sale', 'Sales']])
        ->expectsOutputToContain('2 installed module(s).')
        ->assertSuccessful();
});

it('filters by name or title', function () {
    Odoo::fake(['ir.module.module/search_read' => [
        ['name' => 'account', 'shortdesc' => 'Invoicing'],
        ['name' => 'sale', 'shortdesc' => 'Sales'],
    ]]);

    $this->artisan('odoo:modules', ['--filter' => 'invoic'])
        ->expectsTable(['Module', 'Title'], [['account', 'Invoicing']])
        ->assertSuccessful();
});

it('warns when nothing matches', function () {
    Odoo::fake(['ir.module.module/search_read' => []]);

    $this->artisan('odoo:modules')
        ->expectsOutputToContain('No installed modules found.')
        ->assertSuccessful();
});

it('fails with the odoo error message', function () {
    config()->set('odoo.enabled', false);

    $this->artisan('odoo:modules')
        ->expectsOutputToContain('Odoo is disabled')
        ->assertFailed();
});

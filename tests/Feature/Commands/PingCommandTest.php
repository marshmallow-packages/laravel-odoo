<?php

declare(strict_types=1);

use Marshmallow\Odoo\Facades\Odoo;

it('prints the version and the api key user', function () {
    Odoo::fake([
        'res.users/context_get' => ['uid' => 2, 'lang' => 'nl_NL', 'tz' => 'Europe/Amsterdam'],
        'res.users/read' => [['id' => 2, 'name' => 'Integration', 'login' => 'api@example.com']],
    ]);

    $this->artisan('odoo:ping')
        ->expectsOutputToContain('19.0')
        ->expectsOutputToContain('Integration (api@example.com, uid 2)')
        ->expectsOutputToContain('nl_NL')
        ->assertSuccessful();
});

it('fails with the odoo error message', function () {
    config()->set('odoo.enabled', false);

    $this->artisan('odoo:ping')
        ->expectsOutputToContain('Odoo is disabled')
        ->assertFailed();
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Marshmallow\Odoo\Client\OdooClient;
use Marshmallow\Odoo\Contracts\Client;
use Marshmallow\Odoo\Facades\Odoo as OdooFacade;
use Marshmallow\Odoo\Odoo;
use Marshmallow\Odoo\Resources\Partners;
use Marshmallow\Odoo\Resources\Resource;

it('binds the client contract to the http client as a singleton', function () {
    expect(app(Client::class))
        ->toBeInstanceOf(OdooClient::class)
        ->toBe(app(Client::class));
});

it('binds the manager as a singleton behind the facade', function () {
    expect(app(Odoo::class))->toBe(app(Odoo::class));
    expect(OdooFacade::getFacadeRoot())->toBe(app(Odoo::class));
    expect(OdooFacade::partners())->toBeInstanceOf(Partners::class);
});

it('merges the package config', function () {
    expect(config('odoo.enabled'))->toBeTrue();
    expect(config('odoo.timeout'))->toBe(30);
    expect(config('odoo.retry.times'))->toBe(3);
    expect(config('odoo.resources'))->toBe([]);
});

it('publishes the config under the odoo-config tag', function () {
    $target = config_path('odoo.php');
    File::delete($target);

    $this->artisan('vendor:publish', ['--tag' => 'odoo-config'])->assertSuccessful();

    expect(File::exists($target))->toBeTrue();

    File::delete($target);
});

it('registers the artisan commands', function () {
    $commands = array_keys(app('Illuminate\Contracts\Console\Kernel')->all());

    expect($commands)->toContain('odoo:ping', 'odoo:modules', 'odoo:doctor');
});

it('resolves custom resources from config', function () {
    config()->set('odoo.resources', ['contacts' => Partners::class]);

    expect(app(Odoo::class)->contacts())->toBeInstanceOf(Partners::class);
});

it('resolves resources registered as macros', function () {
    Odoo::macro('leads', fn (): Resource => $this->model('crm.lead'));

    expect(OdooFacade::leads()->model())->toBe('crm.lead');
});

it('throws for unknown resource accessors', function () {
    app(Odoo::class)->nope();
})->throws(BadMethodCallException::class, 'config("odoo.resources")');

<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | When disabled every call throws an OdooDisabledException instead of
    | hitting the network. Check Odoo::enabled() to skip work up front.
    |
    */

    'enabled' => (bool) env('ODOO_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Instance
    |--------------------------------------------------------------------------
    |
    | The base URL of the Odoo instance (https://company.odoo.com) and the API
    | key of the user the package acts as. The database name is only needed
    | when one server hosts several databases without a host-based dbfilter;
    | it is sent as the X-Odoo-Database header when set.
    |
    */

    'url' => env('ODOO_URL'),

    'database' => env('ODOO_DATABASE'),

    'api_key' => env('ODOO_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds per request. Retries only cover connection failures
    | and 5xx responses; 4xx errors are never retried because each call is its
    | own transaction and a validation error will not fix itself.
    |
    */

    'timeout' => (int) env('ODOO_TIMEOUT', 30),

    'retry' => [
        'times' => 3,
        'sleep' => 250,
    ],

    'user_agent' => 'marshmallow/laravel-odoo',

    /*
    |--------------------------------------------------------------------------
    | Custom resources
    |--------------------------------------------------------------------------
    |
    | Register your own resource classes (extending
    | Marshmallow\Odoo\Resources\Resource) under an accessor name, so that
    | Odoo::contacts() resolves App\Odoo\Contacts.
    |
    */

    'resources' => [
        // 'contacts' => App\Odoo\Contacts::class,
    ],

];

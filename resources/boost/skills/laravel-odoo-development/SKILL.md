---
name: laravel-odoo-development
description: >
  Configure and apply the Laravel Odoo package in Laravel applications:
  the Odoo 19+ External JSON-2 client, per-model resources, domain
  builder, typed exceptions, Odoo::fake() for tests, and the
  odoo:doctor, odoo:ping and odoo:modules commands.
license: MIT
metadata:
  author: Marshmallow
---

# Laravel Odoo

Use this skill when a Laravel application needs to talk to Odoo 19+ through `marshmallow/laravel-odoo`.

## Primary Goal

- apply the package's public API in the smallest correct way
- keep sync logic, local `odoo_*_id` columns and mappings in the application; the package is a transport, not an ORM

## Workflow

### 1. Inspect the app context

- confirm the app is a Laravel project on PHP 8.3+ and Laravel 12 or 13
- confirm the target is Odoo 19+ (the package speaks JSON-2 only, never `/jsonrpc` or `/xmlrpc`)
- find which Odoo models the app needs; verify field names against the instance with `Odoo::model('x.y')->fields(['type', 'string'])` rather than from memory

### 2. Install and configure

```bash
composer require marshmallow/laravel-odoo
php artisan vendor:publish --tag="odoo-config"   # optional
php artisan odoo:doctor --module=account
```

```dotenv
ODOO_URL=https://company.odoo.com
ODOO_API_KEY=...
ODOO_DATABASE=          # only for multi-database hosts
ODOO_ENABLED=true
ODOO_TIMEOUT=30
```

Config keys in `config/odoo.php`: `enabled`, `url`, `database`, `api_key`, `timeout`, `retry.times`, `retry.sleep`, `user_agent`, `resources`.

### 3. Use the resources

```php
use Marshmallow\Odoo\Facades\Odoo;
use Marshmallow\Odoo\Support\Domain;

$rows = Odoo::partners()->searchRead(Domain::make()->where('is_company', true), ['name', 'email'], limit: 50);
$id = Odoo::partners()->create(['name' => 'Acme']);
Odoo::partners()->update($id, ['phone' => '...']);
$partner = Odoo::partners()->find($id, ['name']);   // MissingRecordException when absent
Odoo::invoices()->post($invoiceId);                  // action_post, a separate transaction from create()
Odoo::model('crm.lead')->call('name_search', ['name' => 'acme']);
Odoo::products()->withContext(['lang' => 'nl_NL'])->get($ids, ['name']);
```

Accessors: `partners()` (res.partner), `products()` (product.product), `templates()` (product.template), `invoices()` (account.move), `taxes()` (account.tax), `modules()` (ir.module.module), `model('any.model')`, `api()` (raw client: `call`, `version`, `contextGet`).

Resource methods: `find`, `get`, `exists`, `search`, `searchRead`, `first`, `searchCount`, `create`, `createMany`, `update`, `delete`, `fields`, `call`, `withContext`.

### 4. Handle errors

Catch `Marshmallow\Odoo\Exceptions\OdooException` or a subclass: `AuthenticationException`, `AccessDeniedException`, `ValidationException`, `MissingRecordException`, `ServerException`, `ConnectionException`, `OdooDisabledException`, `InvalidConfigurationException`. Each keeps `payload`, `response`, `odooName()`, `arguments()`, `debug()`.

Every JSON-2 call is its own transaction. Store remote ids immediately after `create()` and make follow-up calls (like `post()`) idempotent and retryable.

### 5. Test the application

```php
$odoo = Odoo::fake([
    'res.partner/search_read' => [['id' => 7, 'name' => 'Acme']],
    'res.partner/create' => [8],
]);

$odoo->assertCalled('res.partner', 'create', fn ($call) => $call->params['vals_list'][0]['name'] === 'Acme');
$odoo->assertNotCalled('res.partner', 'unlink');
```

Unscripted calls throw. Use `Http::fake()` on `{ODOO_URL}/json/2/{model}/{method}` when the test is about the wire format.

### 6. Extend

Extend `Marshmallow\Odoo\Resources\Resource`, set `protected string $model`, and register it under `config('odoo.resources')` (`'leads' => App\Odoo\Leads::class`) or as `Odoo::macro('leads', ...)`.

## Rules, References, and Templates

- README.md of the package for the full API and examples
- https://www.odoo.com/documentation/19.0/developer/reference/external_api.html for the JSON-2 request format
- https://www.odoo.com/documentation/19.0/developer/reference/backend/orm.html for ORM method parameters and domain syntax

## Examples

- Sync a customer: `first()` by email, `create()` when absent, store the id on the local model, `update()` afterwards.
- Invoice a paid order: `create()` an `account.move` with `move_type` `out_invoice` and `invoice_line_ids` commands, store the id, then `post()`; poll `paymentState()` later.
- Guard a feature on an app: `Odoo::modules()->isInstalled('sale')`.

## Anti-patterns

- Do not build XML-RPC or `/jsonrpc` calls next to this package; they are removed in Odoo Online 21.1 and Odoo 22.
- Do not hardcode field lists as typed DTOs; fields differ per instance and version.
- Do not wrap several calls in a pretend transaction; Odoo commits each call on its own.
- Do not put credentials, hostnames or record ids in code or tests; use env and `Odoo::fake()`.

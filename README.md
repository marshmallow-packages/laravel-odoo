![marshmallow.](https://marshmallow.dev/cdn/media/logo-red-237x46.png "marshmallow.")

# Laravel Odoo

[![Latest Version on Packagist](https://img.shields.io/packagist/v/marshmallow/laravel-odoo.svg?style=flat-square)](https://packagist.org/packages/marshmallow/laravel-odoo)
[![Tests](https://img.shields.io/github/actions/workflow/status/marshmallow-packages/laravel-odoo/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/marshmallow-packages/laravel-odoo/actions/workflows/tests.yml)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/marshmallow/laravel-odoo.svg?style=flat-square)](https://packagist.org/packages/marshmallow/laravel-odoo)
[![Total Downloads](https://img.shields.io/packagist/dt/marshmallow/laravel-odoo.svg?style=flat-square)](https://packagist.org/packages/marshmallow/laravel-odoo)

Laravel client for the Odoo 19+ External JSON-2 API, with per-model resources for partners, products, invoices and more.

Odoo 19 introduced the [External JSON-2 API](https://www.odoo.com/documentation/19.0/developer/reference/external_api.html): plain HTTPS, a bearer API key and one `POST /json/2/{model}/{method}` call per ORM method. The legacy `/xmlrpc` and `/jsonrpc` endpoints are being removed (Odoo Online 21.1, Odoo 22 on-premise), so this package speaks JSON-2 only.

It is built on Laravel's HTTP client, so `Http::fake()` works, and it ships an `Odoo::fake()` for tests that never touch the network. Records come back as plain arrays: Odoo's field set is instance-specific, and creating or changing records stays an explicit call.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Odoo 19+ (Online or on-premise) with an API key

## Installation

Install the package via Composer:

```bash
composer require marshmallow/laravel-odoo
```

Add the instance to your `.env`:

```dotenv
ODOO_URL=https://company.odoo.com
ODOO_API_KEY=your-api-key
```

Then check the connection:

```bash
php artisan odoo:doctor
```

Publishing the config is optional:

```bash
php artisan vendor:publish --tag="odoo-config"
```

### API keys

Generate one under *Preferences > Account Security > API Keys* in Odoo, for the user the integration should act as. The key carries that user's access rights, so give it an integration user with only the apps it needs. `ODOO_DATABASE` is only required when one server hosts several databases without a host-based dbfilter.

## Getting started

```php
use Marshmallow\Odoo\Facades\Odoo;

$partners = Odoo::partners()->searchRead(
    [['is_company', '=', true]],
    ['name', 'email', 'vat'],
    limit: 50,
);

$id = Odoo::partners()->create([
    'name' => 'Acme BV',
    'email' => 'finance@acme.example',
    'is_company' => true,
]);

Odoo::partners()->update($id, ['phone' => '+31 20 123 4567']);

$partner = Odoo::partners()->find($id, ['name', 'phone']);
```

## Resources

Every resource is a thin gateway to one Odoo model:

| Accessor | Odoo model | Extras |
| --- | --- | --- |
| `Odoo::partners()` | `res.partner` | |
| `Odoo::products()` | `product.product` | |
| `Odoo::templates()` | `product.template` | |
| `Odoo::invoices()` | `account.move` | `post()`, `paymentState()`, `customerInvoices()`, `creditNotes()` |
| `Odoo::taxes()` | `account.tax` | |
| `Odoo::modules()` | `ir.module.module` | `installed()`, `isInstalled()` |
| `Odoo::model('crm.lead')` | any model | generic resource |

All resources share the same methods:

```php
$resource = Odoo::model('crm.lead');

$resource->find(int $id, array $fields = []): array;              // throws MissingRecordException
$resource->get(array $ids, array $fields = []): array;
$resource->exists(int $id): bool;
$resource->search(Domain|array $domain = [], ?int $limit = null, int $offset = 0, ?string $order = null): array; // ids
$resource->searchRead(Domain|array $domain = [], array $fields = [], ?int $limit = null, int $offset = 0, ?string $order = null): array;
$resource->first(Domain|array $domain = [], array $fields = [], ?string $order = null): ?array;
$resource->searchCount(Domain|array $domain = []): int;
$resource->create(array $values): int;
$resource->createMany(array $valuesList): array;                    // ids
$resource->update(int|array $ids, array $values): bool;             // write
$resource->delete(int|array $ids): bool;                            // unlink
$resource->fields(array $attributes = []): array;                   // fields_get
$resource->call(string $method, array $params = [], array $ids = []): mixed;
```

Anything the resource does not cover goes through `call()`, with Odoo's own parameter names:

```php
Odoo::invoices()->call('action_post', ids: [$invoiceId]);
Odoo::partners()->call('name_search', ['name' => 'acme', 'limit' => 5]);
```

Send a context (language, company, timezone) with every call of a resource:

```php
$dutch = Odoo::products()->withContext(['lang' => 'nl_NL']);

$dutch->find($id, ['name', 'description_sale']);
```

Field names differ per Odoo version and per installed app. When in doubt, ask the instance instead of guessing:

```php
Odoo::partners()->fields(['type', 'string', 'required']);
```

### Invoices

Creating and confirming an invoice are two transactions in Odoo. Store the id right after `create()`, then post it; if posting fails the invoice still exists as a draft and the call can be retried.

```php
use Marshmallow\Odoo\Resources\Invoices;
use Marshmallow\Odoo\Support\Commands;

$invoiceId = Odoo::invoices()->create([
    'move_type' => 'out_invoice',
    'partner_id' => $partnerId,
    'invoice_line_ids' => [
        Commands::create(['product_id' => $productId, 'quantity' => 2, 'price_unit' => 19.95, 'tax_ids' => Commands::set([$taxId])]),
    ],
]);

Odoo::invoices()->post($invoiceId);

Odoo::invoices()->paymentState($invoiceId); // not_paid, in_payment, paid, partial, reversed

$open = Odoo::invoices()->searchRead(
    Invoices::customerInvoices()->where('payment_state', 'not_paid'),
    ['name', 'amount_residual'],
);
```

### Relations

One2many and many2many values are written with Odoo's command tuples. `Marshmallow\Odoo\Support\Commands` builds them:

| Helper | Command | Effect |
| --- | --- | --- |
| `Commands::create($values)` | `[0, 0, vals]` | Create a related record |
| `Commands::update($id, $values)` | `[1, id, vals]` | Update a related record |
| `Commands::delete($id)` | `[2, id]` | Unlink and delete |
| `Commands::unlink($id)` | `[3, id]` | Unlink, keep the record |
| `Commands::link($id)` | `[4, id]` | Link an existing record |
| `Commands::clear()` | `[5]` | Unlink all |
| `Commands::set($ids)` | `[6, 0, ids]` | Replace all links |

```php
Odoo::products()->update($productId, ['taxes_id' => Commands::set([$taxId])]);
```

### Domains

Domains can be passed as raw Odoo arrays or built with `Marshmallow\Odoo\Support\Domain`. Terms added with `where*()` are ANDed; `orWhere()` ORs everything before it with the new term, the same precedence as Laravel's query builder. The builder emits Odoo's prefix notation.

```php
use Marshmallow\Odoo\Support\Domain;

$domain = Domain::make()
    ->where('is_company', true)
    ->where('country_id.code', '=', 'NL')
    ->whereIn('id', [1, 2, 3])
    ->whereNotNull('email')
    ->whereILike('name', 'acme')
    ->orWhere('ref', 'ACME');

$domain->toArray();
// ['|', '&', '&', '&', '&', ['is_company', '=', true], ['country_id.code', '=', 'NL'], ['id', 'in', [1, 2, 3]], ['email', '!=', false], ['name', 'ilike', 'acme'], ['ref', '=', 'ACME']]
```

Also available: `whereNotIn()`, `whereNull()`, `whereLike()`, `whereDomain()` and `orWhereDomain()` to nest a whole domain as one operand, and `Domain::fromArray()` to wrap raw terms.

## The client

`Odoo::api()` returns the low-level `Marshmallow\Odoo\Contracts\Client`, for calls that do not belong to a model:

```php
Odoo::api()->call('res.partner', 'search_read', ['domain' => [], 'fields' => ['name'], 'limit' => 10]);
Odoo::api()->version();     // GET /web/version, raw payload
Odoo::api()->versionLabel(); // "19.0+e"
Odoo::api()->majorVersion(); // 19
Odoo::api()->contextGet();  // uid, lang, tz of the API key user
Odoo::enabled();            // config('odoo.enabled')
```

Every request carries the bearer key, a `User-Agent`, and `X-Odoo-Database` when configured. Connection failures and 5xx responses without an Odoo error body (a 502/503 from the proxy) are retried (`odoo.retry`). Anything Odoo itself answered, including a 500 with an error name such as `builtins.ValueError`, is deterministic and never retried: every JSON-2 call is its own transaction and the same request will fail the same way.

## Errors

All exceptions extend `Marshmallow\Odoo\Exceptions\OdooException`. The subclass is picked from Odoo's error name first and the HTTP status second, and the full error body stays on the exception.

| Exception | When |
| --- | --- |
| `AuthenticationException` | Invalid or missing API key (`werkzeug.exceptions.Unauthorized`, `odoo.exceptions.AccessDenied`, HTTP 401) |
| `AccessDeniedException` | The key's user lacks access rights (`odoo.exceptions.AccessError`, HTTP 403) |
| `ValidationException` | Odoo rejected the values (`ValidationError`, `UserError`) |
| `MissingRecordException` | The record does not exist (`MissingError`, or `find()` on an empty `read`; Odoo returns `[]` for unknown ids rather than an error) |
| `InvalidRequestException` | The request is wrong: unknown model or method (`werkzeug.exceptions.NotFound`), invalid field or value in a domain (`builtins.ValueError` and friends) |
| `ServerException` | Any other non-2xx response |
| `ConnectionException` | The instance could not be reached within the timeout and retries |
| `OdooDisabledException` | `ODOO_ENABLED=false` |
| `InvalidConfigurationException` | `ODOO_URL` or `ODOO_API_KEY` is missing |

```php
use Marshmallow\Odoo\Exceptions\ValidationException;

try {
    Odoo::partners()->create(['email' => 'not-an-email']);
} catch (ValidationException $exception) {
    $exception->getMessage();  // Odoo's message
    $exception->odooName();    // odoo.exceptions.ValidationError
    $exception->arguments();   // Odoo's arguments list
    $exception->debug();       // server traceback, when Odoo sends one
    $exception->payload;       // the full decoded error body
    $exception->response;      // the Illuminate HTTP response
}
```

## Configuration

Full documentation lives in the published `config/odoo.php`.

| Key | Env | Default | Description |
| --- | --- | --- | --- |
| `enabled` | `ODOO_ENABLED` | `true` | Master switch. When off every call throws `OdooDisabledException`. |
| `url` | `ODOO_URL` | `null` | Base URL of the instance. |
| `database` | `ODOO_DATABASE` | `null` | Sent as `X-Odoo-Database`; only for multi-database hosts. |
| `api_key` | `ODOO_API_KEY` | `null` | API key of the integration user. |
| `timeout` | `ODOO_TIMEOUT` | `30` | Seconds per request. |
| `retry.times` | | `3` | Attempts for connection failures and 5xx responses without an Odoo error body. |
| `retry.sleep` | | `250` | Milliseconds between attempts. |
| `user_agent` | | `marshmallow/laravel-odoo` | `User-Agent` header. |
| `resources` | | `[]` | Custom resource classes, see Extending. |

## Artisan commands

| Command | Purpose |
| --- | --- |
| `odoo:doctor` | Config present, instance reachable, API key valid, version 19+. Add `--module=account --module=sale` to require modules. |
| `odoo:ping` | Server version and the user behind the API key. |
| `odoo:modules` | Installed modules with their titles. `--filter=` narrows the list. |

## Testing your own application

`Odoo::fake()` swaps the client for an in-memory one. Responses are scripted per `model/method`; every call is recorded for assertions and nothing reaches the network.

```php
use Marshmallow\Odoo\Facades\Odoo;
use Marshmallow\Odoo\Testing\RecordedCall;

$odoo = Odoo::fake([
    'res.partner/search_read' => [['id' => 7, 'name' => 'Acme']],
    'res.partner/create' => [8],
]);

// ... run the code under test ...

$odoo->assertCalled('res.partner', 'create', fn (RecordedCall $call): bool => $call->params['vals_list'][0]['name'] === 'Acme');
$odoo->assertCalledTimes('res.partner', 'search_read', 1);
$odoo->assertNotCalled('res.partner', 'unlink');
$odoo->assertNothingCalled();
```

A closure response receives the `RecordedCall` (`model`, `method`, `params`, `ids`) so it can answer dynamically, and `respond()` adds responses after the fake was created:

```php
$odoo->respond('res.partner', 'read', fn (RecordedCall $call): array => array_map(
    fn (int $id): array => ['id' => $id, 'name' => "Partner {$id}"],
    $call->ids,
));
```

A call without a scripted response throws, so a test cannot silently pass on an empty answer. `version()` and `contextGet()` have defaults (Odoo 19, uid 1) and can be overridden under the keys `web/version` (`version`, `version_info`) and `res.users/context_get`.

Prefer `Http::fake()` when you want to test the wire format itself. URLs follow `{ODOO_URL}/json/2/{model}/{method}`:

```php
Http::fake([
    'company.odoo.com/json/2/res.partner/search_read' => Http::response([['id' => 7, 'name' => 'Acme']]),
    'company.odoo.com/json/2/*' => Http::response(['name' => 'odoo.exceptions.AccessError', 'message' => 'No access'], 403),
]);
```

## Extending

Add a resource for a model your project uses a lot. Extend `Resource`, set the model, and register the accessor in config or as a macro:

```php
namespace App\Odoo;

use Marshmallow\Odoo\Resources\Resource;

class Leads extends Resource
{
    protected string $model = 'crm.lead';

    public function won(): array
    {
        return $this->searchRead([['stage_id.is_won', '=', true]], ['name', 'partner_id']);
    }
}
```

```php
// config/odoo.php
'resources' => [
    'leads' => App\Odoo\Leads::class,
],
```

```php
Odoo::leads()->won();

// or, without touching the config:
Odoo::macro('leads', fn () => new App\Odoo\Leads($this->api()));
```

Custom resources work under `Odoo::fake()` as well.

## Testing

```bash
composer test
```

That runs PHPStan, Pint, a 100% type coverage check and the Pest suite. Individual steps are available as `composer analyse`, `composer lint:check`, `composer test:types` and `composer test:unit`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please report security vulnerabilities by email to [lars@marshmallow.dev](mailto:lars@marshmallow.dev) rather than via the public issue tracker.

## Credits

- [Marshmallow](https://github.com/marshmallow-packages)
- [All Contributors](https://github.com/marshmallow-packages/laravel-odoo/contributors)

## License

The MIT License. Please see the [License File](LICENSE.md) for more information.

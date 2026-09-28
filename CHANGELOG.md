# Release Notes

## [Unreleased](https://github.com/marshmallow-packages/laravel-odoo/compare/v0.1.0...HEAD)

## [v0.1.0](https://github.com/marshmallow-packages/laravel-odoo/releases/tag/v0.1.0) - unreleased

Initial pre-release: a generic client for the Odoo 19+ External JSON-2 API.

### Added

- `Odoo::api()` client over Laravel's HTTP client: bearer API key, `X-Odoo-Database`, timeout, retries on connection failures and 5xx only.
- Typed exceptions mapped from Odoo's error name and HTTP status, with the full error payload kept on the exception.
- `Resource` base with `find`, `get`, `exists`, `search`, `searchRead`, `first`, `searchCount`, `create`, `createMany`, `update`, `delete`, `fields`, `call` and `withContext`.
- Built-in resources: partners, products, templates, invoices (`post`, `paymentState`), taxes and modules (`installed`, `isInstalled`); `Odoo::model()` for any other model.
- Custom resources via `config('odoo.resources')` or `Odoo::macro()`.
- `Domain` builder with query-builder precedence, emitting Odoo prefix notation.
- `Commands` helpers for x2many command tuples (`create`, `update`, `delete`, `unlink`, `link`, `clear`, `set`).
- `Odoo::fake()` with scripted responses and `assertCalled`, `assertCalledTimes`, `assertNotCalled`, `assertNothingCalled`.
- `odoo:doctor`, `odoo:ping` and `odoo:modules` commands.
- `config/odoo.php` (publish tag `odoo-config`) with `ODOO_URL`, `ODOO_DATABASE`, `ODOO_API_KEY`, `ODOO_ENABLED` and `ODOO_TIMEOUT`.

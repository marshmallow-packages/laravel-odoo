# Release Notes

## [Unreleased](https://github.com/marshmallow-packages/laravel-odoo/compare/v0.2.0...HEAD)

### Fixed

- `ODOO_COMPANY_ID` also fills `allowed_company_ids`, which is where Odoo reads the active company from on multi-company databases.

## [v0.2.0](https://github.com/marshmallow-packages/laravel-odoo/compare/v0.1.0...v0.2.0) - 2026-09-28

<!-- Release notes generated using configuration in .github/release.yml at main -->

### Added

- Resource paging: `collect()`, `chunk()` and `lazy()`, ordered by id unless an order is given.
- `readGroup()` over `formatted_read_group`, `nameSearch()`, `displayNames()`, `hasAccess()`, `archive()` and `unarchive()`.
- `loadNames: false` on `find()` and `get()` to receive plain many2one ids.
- Context shortcuts `withLang()`, `withTimezone()`, `withCompany()` and `withArchived()`, plus a default context in `config('odoo.context')` (`ODOO_LANG`, `ODOO_TIMEZONE`, `ODOO_COMPANY_ID`).
- Domain builder: closure nesting in `where()` and `orWhere()`, `whereNot()`, `whereBetween()`, `whereNotBetween()`, `whereChildOf()` and `whereParentOf()`.

### Fixed

- 4xx responses without a recognised Odoo error name (such as 422) now map to `InvalidRequestException` instead of `ServerException`.

### What's Changed

#### Enhancements

* feat(resources): paging, grouping, access checks and context defaults by @LTKort in https://github.com/marshmallow-packages/laravel-odoo/pull/1

### New Contributors

* @LTKort made their first contribution in https://github.com/marshmallow-packages/laravel-odoo/pull/1

**Full Changelog**: https://github.com/marshmallow-packages/laravel-odoo/compare/v0.1.0...v0.2.0

## [v0.1.0](https://github.com/marshmallow-packages/laravel-odoo/releases/tag/v0.1.0) - 2026-09-28

Initial pre-release: a generic client for the Odoo 19+ External JSON-2 API.

### Added

- `Odoo::api()` client over Laravel's HTTP client: bearer API key, `X-Odoo-Database`, timeout, retries on connection failures and 5xx responses without an Odoo error body only; `versionLabel()` and `majorVersion()` read `/web/version`.
- Typed exceptions mapped from Odoo's error name and HTTP status, with the full error payload kept on the exception; `InvalidRequestException` for unknown models, methods and fields.
- `Resource` base with `find`, `get`, `exists`, `search`, `searchRead`, `first`, `searchCount`, `create`, `createMany`, `update`, `delete`, `fields`, `call` and `withContext`.
- Built-in resources: partners, products, templates, invoices (`post`, `paymentState`), taxes and modules (`installed`, `isInstalled`); `Odoo::model()` for any other model.
- Custom resources via `config('odoo.resources')` or `Odoo::macro()`.
- `Domain` builder with query-builder precedence, emitting Odoo prefix notation.
- `Commands` helpers for x2many command tuples (`create`, `update`, `delete`, `unlink`, `link`, `clear`, `set`).
- `Odoo::fake()` with scripted responses and `assertCalled`, `assertCalledTimes`, `assertNotCalled`, `assertNothingCalled`.
- `odoo:doctor`, `odoo:ping` and `odoo:modules` commands.
- `config/odoo.php` (publish tag `odoo-config`) with `ODOO_URL`, `ODOO_DATABASE`, `ODOO_API_KEY`, `ODOO_ENABLED` and `ODOO_TIMEOUT`.

---
name: laravel-odoo-overview
description: Orient in the marshmallow/laravel-odoo repo - what it is, how the pieces fit, and the invariants. Invoke first when starting work here.
---

# laravel-odoo overview

`marshmallow/laravel-odoo` is a standalone, public Laravel package (Packagist, MIT): a generic client for the Odoo 19+ External JSON-2 API (`POST {url}/json/2/{model}/{method}` with a bearer API key). It ships a client, typed exceptions, per-model resources, a domain builder, a test fake and a few Artisan commands. It is **not** an application: no Laravel app, no deploy target, no `.env`. Runtime code lives in `src/`; a Testbench workbench app under `workbench/` stands in for a host application during development. Customer-specific business logic (sync jobs, ID columns, tax mappings, credentials) belongs in consuming projects, never here.

## How the pieces fit

- **`src/`** - the package. `OdooServiceProvider` is the single wiring point (config merge, publish tag, container bindings, commands). `Odoo` is the manager behind the `Odoo` facade: `api()` returns the client, the resource accessors (`partners()`, `products()`, `invoices()`, ...) return `Resources\*` classes, `model('x.y')` returns a generic resource.
- **`src/Client/`** - `OdooClient` (Laravel `Http` under the hood, so `Http::fake()` works) implements `Contracts\Client`. All JSON-2 traffic goes through `call(model, method, params, ids)`.
- **`src/Exceptions/`** - `OdooException` base plus typed subclasses mapped from Odoo's error `name` and the HTTP status. Every exception keeps the original error payload.
- **`src/Resources/`** - `Resource` base bound to an Odoo model name (find/get/search/searchRead/searchCount/create/update/delete/call) and thin per-model subclasses with model-specific helpers.
- **`src/Support/Domain.php`** - fluent builder for Odoo search domains.
- **`src/Testing/`** - `Odoo::fake()` swaps the facade root with `OdooFake` (scripted responses + assertions).
- **`config/odoo.php`** - publish tag `odoo-config`; env keys `ODOO_URL`, `ODOO_DATABASE`, `ODOO_API_KEY`, `ODOO_ENABLED`, `ODOO_TIMEOUT`.
- **`tests/`** - Pest on Orchestra Testbench. `TestCase` boots the provider; `ArchTest` enforces architectural rules; type coverage is gated at 100%. No test may hit a real Odoo instance.
- **`workbench/`** - throwaway host app for `composer build` / `composer serve`. Never ship behavior that only works because of workbench wiring.
- **`resources/boost/skills/laravel-odoo-development/SKILL.md`** - the Boost skill shipped to consumers. Regenerate with `package-generate-skill` whenever public APIs, config, commands, tags or README promises change.
- **`.agents/`** - agent config, with `.claude` and `CLAUDE.md` symlinked to `.agents` and `AGENTS.md`. Local skills: `package-scaffold`, `package-testing`, `package-release`, `package-compatibility`, `package-generate-skill`.
- **CI** - `.github/workflows/tests.yml` runs the matrix; `update-changelog.yml` maintains `CHANGELOG.md`.

## Invariants (do not violate)

- **This repo is public.** Never commit customer names, Odoo hostnames, database names, API keys, record IDs, Linear/Sentry identifiers or internal playbooks. `docs/BRIEF.md` and `docs/AI-COST-OPTIMIZATION-PORTABLE.md` are deliberately gitignored - keep them that way.
- **JSON-2 only.** Never add the legacy `/jsonrpc` or `/xmlrpc` transports; they are removed in Odoo Online 21.1 and Odoo 22.
- **One call, one transaction.** Odoo has no multi-call transactions, so the package never pretends otherwise: no batching abstractions that hide partial failure.
- **Support matrix**: PHP `^8.3`, `illuminate/support` `^12.0||^13.0`, Testbench `^10.0||^11.0`. Any code, dependency or CI change must hold across the whole matrix - use the `package-compatibility` skill.
- **`composer test` is the gate**: `analyse` (Larastan) + `lint:check` (Pint) + `test:types` (100% type coverage) + `test:unit`. All four must pass before anything is considered done.
- **Provider-first wiring**: add capabilities through `OdooServiceProvider` using Laravel-native package APIs. No new abstraction layers unless the extension point is real.
- **Naming stays aligned** across composer name, namespace `Marshmallow\Odoo\`, config key `odoo`, publish tag `odoo-config`, command prefix `odoo:` and docs.
- **Return plain arrays**, no ORM. Odoo field sets are instance-specific; do not hardcode field lists as typed DTOs.
- **Test observable behavior** through public APIs, provider wiring, commands and documented promises - not internals.
- **No em-dashes** in any generated text: code, docs, commits, PR bodies.
- **Never release autonomously** - tags, releases and changelog publishing are explicitly requested only (`package-release`).

## Gotchas (stable)

- `composer.lock` is gitignored on purpose (library, not app) - do not commit it or rely on locked versions.
- `post-autoload-dump` runs `clear` (`testbench package:purge-skeleton`) then `prepare` (`package:discover`); purge-skeleton deletes generated workbench skeleton files, so do not hand-edit anything it regenerates.
- `.claude` -> `.agents` and `CLAUDE.md` -> `AGENTS.md` are symlinks. Write to the real paths (`.agents/`, `AGENTS.md`).
- Odoo model and field names shift between versions; verify against a real instance with `fields_get` rather than trusting memory.
- Type coverage is `--min=100`, so every new symbol needs full type annotations or CI fails.

For live structure use `fd`/`rg`/`ast-grep`, not a hardcoded file tree.

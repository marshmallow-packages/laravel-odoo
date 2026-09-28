<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Odoo\Exceptions\AuthenticationException;
use Marshmallow\Odoo\Exceptions\ConnectionException;
use Marshmallow\Odoo\Exceptions\OdooException;
use Marshmallow\Odoo\Odoo;
use Marshmallow\Odoo\Support\Version;

class DoctorCommand extends Command
{
    protected $signature = 'odoo:doctor {--module=* : Module(s) that must be installed, e.g. --module=account --module=sale}';

    protected $description = 'Check that Odoo is configured, reachable, the API key works and the version is supported.';

    private const int MINIMUM_VERSION = 19;

    private int $failures = 0;

    public function handle(Odoo $odoo): int
    {
        $this->components->info('Odoo setup check.');

        if (! $this->checkConfig()) {
            return $this->summary();
        }

        if (! $odoo->enabled()) {
            $this->components->warn('ODOO_ENABLED is false, so calls will throw until it is switched on.');

            return $this->summary();
        }

        if (! $this->checkVersion($odoo)) {
            return $this->summary();
        }

        if (! $this->checkApiKey($odoo)) {
            return $this->summary();
        }

        $this->checkModules($odoo);

        return $this->summary();
    }

    private function checkConfig(): bool
    {
        $missing = 0;

        foreach (['url' => 'ODOO_URL', 'api_key' => 'ODOO_API_KEY'] as $key => $env) {
            $value = (string) config("odoo.{$key}", '');
            $label = ucfirst(str_replace('_', ' ', $key));

            if ($value === '') {
                $this->problem($label, "missing, set {$env}");
                $missing++;

                continue;
            }

            $this->pass($label, $key === 'api_key' ? 'set' : $value);
        }

        $database = (string) config('odoo.database', '');

        $this->pass('Database', $database === '' ? 'not set (single-database host)' : $database);

        return $missing === 0;
    }

    private function checkVersion(Odoo $odoo): bool
    {
        try {
            $version = $odoo->api()->version();
        } catch (ConnectionException $exception) {
            $this->problem('Reachable', $exception->getMessage());

            return false;
        } catch (OdooException $exception) {
            $this->problem('Reachable', $exception->getMessage());

            return false;
        }

        $this->pass('Reachable', 'yes');

        $major = Version::major($version);
        $label = Version::label($version);

        if ($major < self::MINIMUM_VERSION) {
            $this->problem('Version', "{$label}, the JSON-2 API needs Odoo ".self::MINIMUM_VERSION.'+');

            return false;
        }

        $this->pass('Version', $label);

        return true;
    }

    private function checkApiKey(Odoo $odoo): bool
    {
        try {
            $context = $odoo->api()->contextGet();
        } catch (AuthenticationException $exception) {
            $this->problem('API key', "rejected: {$exception->getMessage()}");

            return false;
        } catch (OdooException $exception) {
            $this->problem('API key', $exception->getMessage());

            return false;
        }

        $this->pass('API key', 'valid (uid '.(int) ($context['uid'] ?? 0).')');

        return true;
    }

    private function checkModules(Odoo $odoo): void
    {
        /** @var array<int, string> $required */
        $required = (array) $this->option('module');

        if ($required === []) {
            return;
        }

        try {
            $installed = array_map(
                static fn (array $module): string => (string) ($module['name'] ?? ''),
                $odoo->modules()->installed(),
            );
        } catch (OdooException $exception) {
            $this->problem('Modules', $exception->getMessage());

            return;
        }

        foreach ($required as $module) {
            in_array($module, $installed, true)
                ? $this->pass("Module {$module}", 'installed')
                : $this->problem("Module {$module}", 'not installed');
        }
    }

    private function pass(string $check, string $detail): void
    {
        $this->components->twoColumnDetail($check, "<fg=green>{$detail}</>");
    }

    private function problem(string $check, string $detail): void
    {
        $this->failures++;

        $this->components->twoColumnDetail($check, "<fg=red>{$detail}</>");
    }

    private function summary(): int
    {
        $this->newLine();

        if ($this->failures > 0) {
            $this->components->error("{$this->failures} problem(s) need attention.");

            return self::FAILURE;
        }

        $this->components->info('Everything checks out.');

        return self::SUCCESS;
    }
}

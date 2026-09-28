<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Marshmallow\Odoo\Exceptions\OdooException;
use Marshmallow\Odoo\Odoo;

class ModulesCommand extends Command
{
    protected $signature = 'odoo:modules {--filter= : Only show modules whose name or title contains this text}';

    protected $description = 'List the modules installed on the Odoo instance.';

    public function handle(Odoo $odoo): int
    {
        try {
            $modules = $odoo->modules()->installed();
        } catch (OdooException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $filter = $this->option('filter');
        $filter = is_string($filter) ? $filter : '';

        if ($filter !== '') {
            $modules = array_values(array_filter(
                $modules,
                static fn (array $module): bool => Str::contains((string) ($module['name'] ?? ''), $filter, ignoreCase: true)
                    || Str::contains((string) ($module['shortdesc'] ?? ''), $filter, ignoreCase: true),
            ));
        }

        if ($modules === []) {
            $this->components->warn('No installed modules found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Module', 'Title'],
            array_map(static fn (array $module): array => [
                (string) ($module['name'] ?? ''),
                (string) ($module['shortdesc'] ?? ''),
            ], $modules),
        );

        $this->components->info(count($modules).' installed module(s).');

        return self::SUCCESS;
    }
}

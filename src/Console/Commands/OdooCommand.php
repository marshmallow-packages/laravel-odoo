<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Console\Commands;

use Illuminate\Console\Command;

class OdooCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'laravel-odoo:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package laravel-odoo.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Odoo placeholder command executed.');

        return self::SUCCESS;
    }
}

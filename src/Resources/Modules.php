<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Resources;

use Marshmallow\Odoo\Support\Domain;

class Modules extends Resource
{
    protected string $model = 'ir.module.module';

    /**
     * Installed modules with their technical name and title.
     *
     * @return array<int, array<string, mixed>>
     */
    public function installed(): array
    {
        return $this->searchRead(
            Domain::make()->where('state', 'installed'),
            ['name', 'shortdesc'],
            order: 'name asc',
        );
    }

    public function isInstalled(string $name): bool
    {
        $domain = Domain::make()
            ->where('name', $name)
            ->where('state', 'installed');

        return $this->searchCount($domain) > 0;
    }
}

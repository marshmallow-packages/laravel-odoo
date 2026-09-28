<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Odoo\Exceptions\OdooException;
use Marshmallow\Odoo\Odoo;

class PingCommand extends Command
{
    protected $signature = 'odoo:ping';

    protected $description = 'Check the connection to Odoo and show the server version and the API key user.';

    public function handle(Odoo $odoo): int
    {
        try {
            $version = $odoo->api()->versionLabel();
            $context = $odoo->api()->contextGet();

            $uid = (int) ($context['uid'] ?? 0);
            $user = $uid > 0
                ? $odoo->model('res.users')->find($uid, ['name', 'login'])
                : [];
        } catch (OdooException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Server version', $version);
        $this->components->twoColumnDetail('User', sprintf('%s (%s, uid %d)', $user['name'] ?? 'unknown', $user['login'] ?? '-', $uid));
        $this->components->twoColumnDetail('Language', (string) ($context['lang'] ?? '-'));
        $this->components->twoColumnDetail('Timezone', (string) ($context['tz'] ?? '-'));

        return self::SUCCESS;
    }
}

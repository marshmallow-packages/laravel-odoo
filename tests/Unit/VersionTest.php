<?php

declare(strict_types=1);

use Marshmallow\Odoo\Support\Version;

it('reads the /web/version payload', function () {
    $payload = ['version' => '19.0+e', 'version_info' => [19, 0, 0, 'final', 0, 'e']];

    expect(Version::label($payload))->toBe('19.0+e');
    expect(Version::major($payload))->toBe(19);
});

it('falls back to the legacy keys and to parsing the label', function () {
    expect(Version::label(['server_version' => '18.0']))->toBe('18.0');
    expect(Version::major(['server_version_info' => [18, 0]]))->toBe(18);
    expect(Version::major(['version' => '17.0']))->toBe(17);
    expect(Version::label([]))->toBe('unknown');
    expect(Version::major([]))->toBe(0);
});

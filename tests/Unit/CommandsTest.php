<?php

declare(strict_types=1);

use Marshmallow\Odoo\Support\Commands;

it('builds odoo x2many commands', function () {
    expect(Commands::create(['name' => 'Line']))->toBe([0, 0, ['name' => 'Line']]);
    expect(Commands::update(3, ['quantity' => 2]))->toBe([1, 3, ['quantity' => 2]]);
    expect(Commands::delete(3))->toBe([2, 3]);
    expect(Commands::unlink(3))->toBe([3, 3]);
    expect(Commands::link(3))->toBe([4, 3]);
    expect(Commands::clear())->toBe([5]);
    expect(Commands::set([5 => 7, 9]))->toBe([6, 0, [7, 9]]);
});

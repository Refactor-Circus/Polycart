<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Support;

use RefactorCircus\Keystone\Database\Tables as KeystoneTables;

/**
 * The table each of Polycart's records lives in, from the `polycart.tables`
 * config key. Models, migrations, validation rules and joins all ask here,
 * so renaming a table is one config change made before the migrations run.
 *
 * A published config that lists only some tables keeps the defaults for the
 * rest.
 */
final class Tables extends KeystoneTables
{
    protected const string CONFIG = 'polycart.tables';

    /**
     * @var array<string, string>
     */
    public const array DEFAULTS = [
        'carts' => 'polycart_carts',
        'cart_paths' => 'polycart_cart_paths',
        'cart_members' => 'polycart_cart_members',
        'cart_lines' => 'polycart_cart_lines',
    ];
}

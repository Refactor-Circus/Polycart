<?php

declare(strict_types=1);

use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Scope\Models\CartPathModel;
use RefactorCircus\Polycart\Support\Tables;

it('ships the default table names in its config', function (): void {
    $config = require dirname(__DIR__, 2).'/config/polycart.php';

    expect($config['tables'])->toBe(Tables::DEFAULTS);
});

it('keeps the default for tables a published config leaves out', function (): void {
    config()->set('polycart.tables', ['carts' => 'crm_carts']);

    expect(Tables::name('carts'))->toBe('crm_carts')
        ->and(Tables::name('cart_lines'))->toBe('polycart_cart_lines')
        ->and((new CartModel)->getTable())->toBe('crm_carts')
        ->and((new CartPathModel)->getTable())->toBe('polycart_cart_paths');
});

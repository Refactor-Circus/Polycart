<?php

declare(strict_types=1);

use RefactorCircus\Polycart\Atrium\Data\CartTypeRow;
use RefactorCircus\Polycart\Tests\Fixtures\Types\QuoteCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\RetailCart;

/*
 * A cart type as a row of the `polycart.types` screen: an unsaved stand-in
 * model keyed by the type's key, so row sections receive a model.
 */

it('keys the row by its type\'s key and carries the type', function (): void {
    $type = (new RetailCart)->withKey('cart');
    $row = CartTypeRow::for($type);

    expect($row->getKey())->toBe('cart')
        ->and($row->getKeyName())->toBe('key')
        ->and($row->type)->toBe($type);
});

it('is an unsaved model with a string key, no incrementing and no timestamps', function (): void {
    $row = CartTypeRow::for((new QuoteCart)->withKey('quote'));

    expect($row->getKeyType())->toBe('string')
        ->and($row->getIncrementing())->toBeFalse()
        ->and($row->timestamps)->toBeFalse()
        ->and($row->exists)->toBeFalse()
        ->and((new ReflectionClass(CartTypeRow::class))->isFinal())->toBeTrue();
});

it('refuses to have its type replaced from outside', function (): void {
    $row = CartTypeRow::for((new RetailCart)->withKey('cart'));

    expect(fn () => $row->type = (new QuoteCart)->withKey('quote'))->toThrow(Error::class);
    expect($row->type->key())->toBe('cart');
});

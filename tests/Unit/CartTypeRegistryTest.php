<?php

declare(strict_types=1);

use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Exceptions\CartTypeCollisionException;
use RefactorCircus\Polycart\Domains\CartType\Exceptions\UnknownCartTypeException;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Product;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Quote;
use RefactorCircus\Polycart\Tests\Fixtures\Types\OrderCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\QuoteCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\SavedCart;

it('lets a package register a type at runtime', function (): void {
    $registry = app(CartTypeRegistry::class);

    $registry->register('wishlist', SavedCart::class);

    expect($registry->has('wishlist'))->toBeTrue()
        ->and($registry->get('wishlist'))->toBeInstanceOf(SavedCart::class)
        ->and($registry->get('wishlist')->key())->toBe('wishlist');
});

it('derives a key from the class when none is given', function (): void {
    $registry = app(CartTypeRegistry::class);

    $registry->registerMany([OrderCart::class]);

    expect($registry->class('order_cart'))->toBe(OrderCart::class);
});

it('gives the application config precedence over a package registration', function (): void {
    $registry = app(CartTypeRegistry::class);

    $registry->register('quote', OrderCart::class);

    expect($registry->get('quote'))->toBeInstanceOf(QuoteCart::class);
});

it('refuses two classes for one key', function (): void {
    $registry = app(CartTypeRegistry::class);

    $registry->register('wishlist', SavedCart::class);
    $registry->register('wishlist', SavedCart::class);
    $registry->register('wishlist', OrderCart::class);
})->throws(CartTypeCollisionException::class);

it('refuses a class that is not a cart type', function (): void {
    /** @phpstan-ignore argument.type */
    app(CartTypeRegistry::class)->register('product', Product::class);
})->throws(UnknownCartTypeException::class, 'must extend');

it('throws for an unknown key', function (): void {
    app(CartTypeRegistry::class)->get('nope');
})->throws(UnknownCartTypeException::class, '[nope]');

it('names the model a type hydrates as', function (): void {
    $registry = app(CartTypeRegistry::class);

    expect($registry->model('quote'))->toBe(Quote::class)
        ->and($registry->model('order'))->toBe(CartModel::class)
        ->and($registry->model('nope'))->toBeNull();
});

it('keeps its catalogue until a type is registered or the config changes', function (): void {
    $registry = app(CartTypeRegistry::class);

    expect($registry->has('wishlist'))->toBeFalse();

    $registry->register('wishlist', SavedCart::class);
    expect($registry->has('wishlist'))->toBeTrue();

    config()->set('polycart.types', [...config('polycart.types'), 'later' => OrderCart::class]);
    expect($registry->class('later'))->toBe(OrderCart::class);

    config()->set('polycart.types', ['quote' => QuoteCart::class]);
    expect($registry->has('later'))->toBeFalse()
        ->and($registry->has('wishlist'))->toBeTrue();
});

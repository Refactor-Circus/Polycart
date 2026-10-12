<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;
use RefactorCircus\Polycart\Domains\CartType\Support\CartType;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;

/**
 * @method static CartTypeRegistry types()
 * @method static CartType type(string $key)
 * @method static CartModel create(string $type, Model|string|null $owner = null, array{label?: string|null, meta?: array<string, mixed>|null, parent_id?: string|null} $attributes = [], Model|null $scope = null)
 * @method static CartModel active(string $type, Model|string $owner, Model|null $scope = null)
 * @method static CartMemberModel share(CartModel $cart, Model $member, string $role)
 * @method static void unshare(CartModel $cart, Model $member)
 * @method static mixed usingSource(\BackedEnum|string $source, \Closure $callback)
 * @method static CartModel|null find(string $id)
 * @method static CartModel convert(CartModel $cart, string $to, bool $copy = true)
 * @method static CartModel merge(CartModel $from, CartModel $into)
 *
 * @see \RefactorCircus\Polycart\Polycart
 */
class Polycart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RefactorCircus\Polycart\Polycart::class;
    }
}

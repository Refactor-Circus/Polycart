<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Support;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Exceptions\LineRejectedException;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;

/**
 * Finds a batch's lines within one cart, so a batch can never reach another
 * cart's. Every line the batch names is read and locked in one query, and
 * each comes back holding the cart it was looked up in.
 *
 * @internal
 */
final class LineLookup
{
    /**
     * @param  array<string, CartLineModel>  $lines
     */
    private function __construct(private readonly array $lines) {}

    /**
     * Read and lock the named lines of a cart.
     *
     * @param  array<int, CartLineModel|string>  $lines  Lines, or their ids.
     */
    public static function lock(CartModel $cart, array $lines): self
    {
        $ids = array_values(array_unique(array_map(self::id(...), $lines)));

        /** @var Collection<int, CartLineModel> $found */
        $found = $cart->lines()->whereKey($ids)->lockForUpdate()->get();

        return new self($found->keyBy(fn (CartLineModel $line): string => (string) $line->getKey())->all());
    }

    /**
     * A line of the cart, unless the batch has removed it already.
     */
    public function in(CartLineModel|string $line): CartLineModel
    {
        $id = self::id($line);
        $found = $this->lines[$id] ?? null;

        if (! $found instanceof CartLineModel || ! $found->exists) {
            throw LineRejectedException::because(sprintf('Line [%s] is not in this cart.', $id), 'line_not_found');
        }

        return $found;
    }

    private static function id(CartLineModel|string $line): string
    {
        return $line instanceof CartLineModel ? (string) $line->getKey() : $line;
    }
}

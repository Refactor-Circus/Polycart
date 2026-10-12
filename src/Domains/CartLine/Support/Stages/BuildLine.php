<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Support\Stages;

use Closure;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\CartLine\Contracts\AddLineStage;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartLine\Support\PendingLine;

/**
 * Build the CartLineModel that will be written, without saving it.
 *
 * Stages after this one see the final quantity and price on `$line->line`,
 * which is the place to check stock or credit limits.
 */
final class BuildLine implements AddLineStage
{
    public function handle(PendingLine $line, Closure $next): PendingLine
    {
        if ($line->existing instanceof CartLineModel) {
            $line->existing->quantity += $line->quantity;
            $line->existing->unit_price = $line->unitPrice ?? $line->existing->unit_price;
            $line->line = $line->existing;

            return $next($line);
        }

        $built = new CartLineModel([
            'purchasable_type' => $line->purchasable?->getMorphClass(),
            'purchasable_id' => $line->purchasable instanceof Model ? (string) $line->purchasable->getKey() : null,
            'fingerprint' => $line->fingerprint,
            'quantity' => $line->quantity,
            'unit_price' => $line->unitPrice,
            'options' => $line->options,
            'meta' => $line->meta,
            'position' => $line->cart->nextLinePosition(),
        ]);

        $built->cart()->associate($line->cart);
        $line->line = $built;

        return $next($line);
    }
}

<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Data\UpdateLineData;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;

/**
 * A line's quantity or price is about to change.
 */
final class LineUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CartModel $cart,
        public CartLineModel $line,
        public UpdateLineData $data,
    ) {}
}

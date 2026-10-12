<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Data\UpdateLinesData;

/**
 * A batch of lines is about to change.
 */
final class LinesUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CartModel $cart,
        public UpdateLinesData $data,
    ) {}
}

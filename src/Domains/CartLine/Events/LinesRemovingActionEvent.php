<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Data\RemoveLinesData;

/**
 * A batch of lines is about to be removed.
 */
final class LinesRemovingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CartModel $cart,
        public RemoveLinesData $data,
    ) {}
}

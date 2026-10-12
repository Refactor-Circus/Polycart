<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Sharing\Data\SetVisibilityData;

/**
 * A cart's visibility is about to change.
 */
final class VisibilityChangingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CartModel $cart,
        public SetVisibilityData $data,
    ) {}
}

<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Polycart\Domains\Cart\Data\ListCartsData;

/**
 * Carts are about to be listed.
 */
final class CartsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListCartsData $filters,
        public ?Model $viewer,
    ) {}
}

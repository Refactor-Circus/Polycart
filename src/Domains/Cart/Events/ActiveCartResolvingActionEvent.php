<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Polycart\Domains\Cart\Data\ActiveCartData;

/**
 * An owner's active cart is being looked up, or started.
 */
final class ActiveCartResolvingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ActiveCartData $data,
        public Model|string $owner,
        public ?Model $scope,
    ) {}
}

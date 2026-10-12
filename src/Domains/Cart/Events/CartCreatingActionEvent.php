<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Polycart\Domains\Cart\Data\CreateCartData;

/**
 * A cart is about to be created.
 */
final class CartCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CreateCartData $data,
        public Model|string|null $owner,
        public ?Model $scope,
    ) {}
}

<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartType\Actions;

use RefactorCircus\Polycart\Domains\CartType\Events\CartTypesListedActionEvent;
use RefactorCircus\Polycart\Domains\CartType\Events\CartTypesListingActionEvent;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;
use RefactorCircus\Polycart\Domains\CartType\Support\CartType;

final class ListCartTypesAction
{
    public function __construct(private readonly CartTypeRegistry $types) {}

    /**
     * @return array<int, CartType>
     */
    public function execute(): array
    {
        CartTypesListingActionEvent::dispatch();

        $result = $this->perform();

        CartTypesListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return array<int, CartType>
     */
    private function perform(): array
    {
        return array_map(
            fn (string $key): CartType => $this->types->get($key),
            array_keys($this->types->all()),
        );
    }
}

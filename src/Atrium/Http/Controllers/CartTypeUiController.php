<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Polycart\Atrium\Data\CartTypeRow;
use RefactorCircus\Polycart\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Polycart\Atrium\Support\Screens;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Actions\ListCartTypesAction;

/**
 * The cart types page, behind the JSON API's own check: `viewAny` on CartModel.
 * It declares itself as the `polycart.types` screen, each type a CartTypeRow
 * row.
 */
final class CartTypeUiController
{
    use AuthorizesScreens;

    public function __invoke(Request $request): View
    {
        $this->authorizeScreen('viewAny', CartModel::class);

        $types = app(ListCartTypesAction::class)->execute();

        /** @var view-string $view */
        $view = 'polycart::ui.types.index';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::list('polycart.types'), $request),
            'types' => $types,
            // Each type as the model its `row.actions` sections receive.
            'rows' => array_map(CartTypeRow::for(...), $types),
        ]);
    }
}

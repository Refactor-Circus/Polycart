<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Atrium;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavGroup;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Plugins\Support\Plugin;
use RefactorCircus\Atrium\Domains\Search\Data\SearchResult;
use RefactorCircus\Atrium\Domains\Search\Data\SearchSource;
use RefactorCircus\Atrium\Domains\Widgets\Data\WidgetDefinition;
use RefactorCircus\Atrium\Support\Icons;
use RefactorCircus\Polycart\Atrium\Http\Controllers\CartTypeUiController;
use RefactorCircus\Polycart\Atrium\Http\Controllers\CartUiController;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;

/**
 * Registers Polycart inside the Atrium dashboard.
 *
 * Widgets declared here are offered in Atrium's picker. None is ever placed
 * on a dashboard automatically; that is always a user's choice. Navigation,
 * widgets and search are shown only to users the cart policies let list
 * carts, and list only the carts they can access, as the JSON API does,
 * or every cart for an operator (`polycart.atrium.show_all`).
 */
class PolycartPlugin extends Plugin
{
    /**
     * Features from `polycart.atrium.features` that switch Polycart in Atrium
     * on and off as a whole. A feature class that cannot be loaded, such as
     * PolycartSupportFeature without refactor-circus/pennantplus, is skipped.
     *
     * @return array<int, string>
     */
    public function features(): array
    {
        return $this->featuresFromConfig('polycart.atrium.features');
    }

    /**
     * The package's section in the sidebar rail: its icon and its place.
     */
    public function navigationGroups(): array
    {
        return [
            NavGroup::make('Polycart')->icon(Icons::svg('shopping-cart'))->sort(30),
        ];
    }

    public function navigation(): array
    {
        return [
            NavItem::make(__('polycart::polycart.carts'))
                ->icon(Icons::svg('shopping-cart'))
                ->route('atrium.polycart.carts.index')
                ->group('Polycart')
                ->sort(10)
                ->authorize(fn (Request $request): bool => self::mayList($request->user())),

            NavItem::make(__('polycart::polycart.types'))
                ->icon(Icons::svg('rectangle-stack'))
                ->route('atrium.polycart.types.index')
                ->group('Polycart')
                ->sort(20)
                ->authorize(fn (Request $request): bool => self::mayList($request->user())),

            // The package's own audit log, while an audit log is installed.
            $this->historyNavItem('polycart')->group('Polycart')->sort(90),
        ];
    }

    public function routes(): void
    {
        Route::name('polycart.')->group(function (): void {
            Route::get('polycart/carts', [CartUiController::class, 'index'])->name('carts.index');
            Route::get('polycart/carts/{cart}', [CartUiController::class, 'show'])->name('carts.show');
            Route::patch('polycart/carts/{cart}', [CartUiController::class, 'update'])->name('carts.update');
            Route::delete('polycart/carts/{cart}', [CartUiController::class, 'destroy'])->name('carts.destroy');
            Route::post('polycart/carts/{cart}/clear', [CartUiController::class, 'clear'])->name('carts.clear');
            Route::post('polycart/carts/{cart}/status', [CartUiController::class, 'transition'])->name('carts.transition');
            Route::post('polycart/carts/{cart}/convert', [CartUiController::class, 'convert'])->name('carts.convert');

            Route::put('polycart/carts/{cart}/visibility', [CartUiController::class, 'visibility'])->name('carts.visibility');
            Route::post('polycart/carts/{cart}/members', [CartUiController::class, 'share'])->name('carts.members.store');

            Route::scopeBindings()->group(function (): void {
                Route::delete('polycart/carts/{cart}/members/{member}', [CartUiController::class, 'unshare'])->name('carts.members.destroy');
                Route::patch('polycart/carts/{cart}/lines/{line}', [CartUiController::class, 'updateLine'])->name('carts.lines.update');
                Route::delete('polycart/carts/{cart}/lines/{line}', [CartUiController::class, 'removeLine'])->name('carts.lines.destroy');
            });

            Route::get('polycart/types', CartTypeUiController::class)->name('types.index');
        });
    }

    /**
     * Widget types Polycart makes available.
     *
     * Returning a definition offers the widget in the picker; it does not
     * place it on anyone's dashboard.
     */
    public function widgets(): array
    {
        return [
            WidgetDefinition::make('polycart.carts-by-type')
                ->label(__('polycart::polycart.widget_carts_by_type'))
                ->description(__('polycart::polycart.widget_carts_by_type_description'))
                ->defaultSize(6, 2)
                ->view('polycart::ui.widgets.carts-by-type')
                ->authorize(fn (Request $request): bool => self::mayList($request->user()))
                ->resolve(fn (): array => [
                    'counts' => self::countsByType(),
                ]),

            WidgetDefinition::make('polycart.recent-carts')
                ->label(__('polycart::polycart.widget_recent_carts'))
                ->description(__('polycart::polycart.widget_recent_carts_description'))
                ->defaultSize(6, 2)
                ->view('polycart::ui.widgets.recent-carts')
                ->authorize(fn (Request $request): bool => self::mayList($request->user()))
                ->resolve(fn (): array => [
                    'carts' => self::visible()->withCount('lines')->latest('updated_at')->limit(5)->get(),
                ]),
        ];
    }

    /**
     * The closures are static and resolve what they need when they run,
     * because Atrium may serialize them into a child process.
     */
    public function search(): ?SearchSource
    {
        return SearchSource::make('polycart')
            ->label('Polycart')
            ->authorize(static fn (Request $request): bool => self::mayList($request->user()))
            ->using(static fn (string $query): array => self::visible()
                ->where(fn (Builder $builder): Builder => $builder
                    ->where('label', 'like', '%'.$query.'%')
                    ->orWhere('id', 'like', $query.'%'))
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (CartModel $cart): SearchResult => SearchResult::make(
                    $cart->label ?? $cart->id,
                    route('atrium.polycart.carts.show', $cart),
                )->subtitle($cart->type.($cart->status === null ? '' : ' · '.$cart->status))->group(__('polycart::polycart.carts')))
                ->all());
    }

    /**
     * The unexpired carts the user can access, counted per known type in
     * one grouped query.
     *
     * @return array<string, int>
     */
    private static function countsByType(): array
    {
        $types = array_keys(app(CartTypeRegistry::class)->all());

        $counts = self::visible()
            ->ofType(...$types)
            ->unexpired()
            ->toBase()
            ->select('type')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->all();

        return array_combine($types, array_map(fn (string $type): int => (int) ($counts[$type] ?? 0), $types));
    }

    /**
     * Whether a user may list carts: the JSON API's `viewAny` check.
     */
    private static function mayList(mixed $user): bool
    {
        return ScreenAccess::allowsUser($user instanceof Authenticatable ? $user : null, 'viewAny', CartModel::class);
    }

    /**
     * Carts the signed-in user can access; every cart for an operator or
     * with authorization off.
     *
     * @return Builder<CartModel>
     */
    private static function visible(): Builder
    {
        $user = auth()->user();
        $actor = ScreenAccess::actor($user instanceof Authenticatable ? $user : null);

        return CartModel::query()->when($actor, fn (Builder $query, Model $actor): Builder => $query->accessibleBy($actor));
    }
}

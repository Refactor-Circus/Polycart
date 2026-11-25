<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\Cart\Events\CartStatsShowingActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartStatsShownActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Support\CartStats;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;

/**
 * The carts at a glance: how many unexpired carts each registered type has,
 * and the carts updated most recently. Two queries, however many carts and
 * types there are.
 */
final class ShowCartStatsAction
{
    /**
     * How many recent carts the stats hold.
     */
    private const int RECENT = 5;

    public function __construct(private readonly CartTypeRegistry $types) {}

    /**
     * @param  Model|null  $viewer  When given, only carts they can access.
     */
    public function execute(?Model $viewer = null): CartStats
    {
        CartStatsShowingActionEvent::dispatch($viewer);

        $result = $this->perform($viewer);

        CartStatsShownActionEvent::dispatch($result, $viewer);

        return $result;
    }

    private function perform(?Model $viewer): CartStats
    {
        $types = array_map(strval(...), array_keys($this->types->all()));

        $counts = $this->carts($viewer)
            ->ofType(...$types)
            ->unexpired()
            ->toBase()
            ->select('type')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->all();

        $recent = $this->carts($viewer)
            ->withCount('lines')
            ->latest('updated_at')
            ->latest('id')
            ->limit(self::RECENT)
            ->get();

        return new CartStats(
            byType: array_combine($types, array_map(fn (string $type): int => (int) ($counts[$type] ?? 0), $types)),
            recent: $recent,
        );
    }

    /**
     * Every cart, or only those the viewer can access.
     *
     * @return Builder<CartModel>
     */
    private function carts(?Model $viewer): Builder
    {
        $query = CartModel::query();

        if ($viewer !== null) {
            $query->accessibleBy($viewer);
        }

        return $query;
    }
}

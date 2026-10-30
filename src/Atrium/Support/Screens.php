<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Atrium\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Polycart\Atrium\Data\CartTypeRow;
use RefactorCircus\Polycart\Atrium\ScreenAccess;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

/**
 * Polycart's pages as screens on Atrium's extension registry: each with
 * Atrium's standard slots, and an `access` closure that decides extensions'
 * `permission()`s the way the pages decide their own controls, through
 * Polycart's ScreenAccess. So a permission is granted to dashboard operators
 * on Polycart's models, and to everyone while `polycart.authorization` is
 * off; registrants wanting a check of their own use `authorize()`.
 *
 * The permission is asked about the screen's record, or a row's; a screen
 * without a record, and a cart type row, are asked about as the CartModel
 * class.
 */
final class Screens
{
    public static function detail(string $key, Model $record): Screen
    {
        return self::access(Screen::standard($key, Screen::DETAIL)->record($record));
    }

    public static function list(string $key): Screen
    {
        return self::access(Screen::standard($key, Screen::LIST));
    }

    private static function access(Screen $screen): Screen
    {
        return $screen->access(static fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allows(
            $permission,
            $record instanceof CartTypeRow || $record === null ? CartModel::class : $record,
        ));
    }
}

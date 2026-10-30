<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Atrium\Data;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\CartType\Support\CartType;

/**
 * One cart type as a row of the `polycart.types` screen.
 *
 * A row slot's row is a model, and a cart type is not one, so each type is
 * wrapped in this unsaved stand-in, as Atrium's `SettingsPanelRow` wraps a
 * settings panel: keyed by the type's key, so screen extensions remember
 * their answers per type, and carrying the type for registrants to read as
 * `$record->type`. It has no table and is never saved, so it lives here
 * rather than in a domain's `Models/` and needs no policy or lifecycle
 * events. Extensions' `permission()`s ask about it as the CartModel class.
 */
final class CartTypeRow extends Model
{
    /** @var string */
    protected $primaryKey = 'key';

    /** @var string */
    protected $keyType = 'string';

    /** @var bool */
    public $incrementing = false;

    /** @var bool */
    public $timestamps = false;

    public private(set) CartType $type;

    public static function for(CartType $type): self
    {
        $row = new self;
        $row->forceFill(['key' => $type->key()]);
        $row->type = $type;

        return $row;
    }
}

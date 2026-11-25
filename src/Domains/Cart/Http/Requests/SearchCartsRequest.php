<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Polycart\Domains\Cart\Actions\ListCartsAction;
use RefactorCircus\Polycart\Domains\Cart\Data\ListCartsData;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

/**
 * The cart list's `search` filter on its own, with `q` required: carts whose
 * label contains it or whose id starts with it, paged as the list is.
 */
final class SearchCartsRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', CartModel::class);
    }

    public function rules(): array
    {
        $search = array_values(array_filter(
            ListCartsData::rules()['search'],
            fn (mixed $rule): bool => ! in_array($rule, ['sometimes', 'nullable'], true),
        ));

        return ['q' => ['required', ...$search]] + ListCartsData::pagination()->rules();
    }

    public function persist(): JsonResponse
    {
        $filters = ListCartsData::fromValidated([
            'search' => $this->validated('q'),
            ...$this->safe()->only(['per_page', 'cursor']),
        ]);

        return CartResource::collection(app(ListCartsAction::class)->execute($filters, $this->actor()))->response();
    }
}

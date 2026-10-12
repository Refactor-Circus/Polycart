<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Polycart\Domains\Cart\Actions\CreateCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\CreateCartData;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Support\Morphs;

final class StoreCartRequest extends Request
{
    public function authorize(): bool
    {
        if (! parent::authorize() || ! $this->allows('create', CartModel::class)) {
            return false;
        }

        // Nesting under a cart changes that cart's tree.
        $parent = $this->filled('parent_id') ? CartModel::query()->find($this->string('parent_id')->toString()) : null;

        return ! $parent instanceof CartModel || $this->allows('update', $parent);
    }

    public function rules(): array
    {
        return Morphs::ownerRules() + Morphs::scopeRules() + CreateCartData::rules();
    }

    public function persist(): JsonResponse
    {
        $validated = $this->validated();
        $morphs = app(Morphs::class);

        // Acting as a user, the user is always the owner.
        $owner = $this->actor() ?? $morphs->ownerFrom($validated);

        $cart = app(CreateCartAction::class)->execute(CreateCartData::fromValidated($validated), $owner, $morphs->scopeFrom($validated));

        return (new CartResource($cart->load('lines')))->response()->setStatusCode(201);
    }
}

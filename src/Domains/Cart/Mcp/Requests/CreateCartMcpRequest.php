<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Actions\CreateCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\CreateCartData;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Mcp\Request;
use RefactorCircus\Polycart\Support\Morphs;

final class CreateCartMcpRequest extends Request
{
    protected function authorize(): bool
    {
        if (! parent::authorize() || ! $this->allows('create', CartModel::class)) {
            return false;
        }

        // Nesting under a cart changes that cart's tree.
        $parent = is_string($this->get('parent_id')) ? CartModel::query()->find($this->get('parent_id')) : null;

        return ! $parent instanceof CartModel || $this->allows('update', $parent);
    }

    protected function rules(): array
    {
        return Morphs::ownerRules() + Morphs::scopeRules() + CreateCartData::rules();
    }

    protected function respond(array $validated): ResponseFactory
    {
        $morphs = app(Morphs::class);

        // Acting as a user, the user is always the owner.
        $owner = $this->actor() ?? $morphs->ownerFrom($validated);

        $cart = app(CreateCartAction::class)->execute(CreateCartData::fromValidated($validated), $owner, $morphs->scopeFrom($validated));

        return (new CartResource($cart->load('lines')))->toStructuredResponse();
    }
}

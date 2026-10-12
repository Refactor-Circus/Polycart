<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Actions\ActiveCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\ActiveCartData;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Mcp\Request;
use RefactorCircus\Polycart\Support\Morphs;

final class ActiveCartMcpRequest extends Request
{
    protected function authorize(): bool
    {
        // Resolving the active cart starts one when there is none.
        return parent::authorize() && $this->allows('create', CartModel::class);
    }

    protected function rules(): array
    {
        // Acting as a user, the owner comes from the session, not the call.
        $owner = $this->actor() === null ? Morphs::ownerRules(required: true) : [];

        return $owner + Morphs::scopeRules() + ActiveCartData::rules();
    }

    protected function respond(array $validated): ResponseFactory
    {
        $morphs = app(Morphs::class);

        // The owner rules require one when no user is acting.
        /** @var Model|string $owner */
        $owner = $this->actor() ?? $morphs->ownerFrom($validated);

        $cart = app(ActiveCartAction::class)->execute(ActiveCartData::fromValidated($validated), $owner, $morphs->scopeFrom($validated));

        return (new CartResource($cart->load('lines')))->toStructuredResponse();
    }
}

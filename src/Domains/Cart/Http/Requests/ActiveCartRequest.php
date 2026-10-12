<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Polycart\Domains\Cart\Actions\ActiveCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\ActiveCartData;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Support\Morphs;

final class ActiveCartRequest extends Request
{
    public function authorize(): bool
    {
        // Resolving the active cart starts one when there is none.
        return parent::authorize() && $this->allows('create', CartModel::class);
    }

    public function rules(): array
    {
        // Acting as a user, the owner comes from the session, not the body.
        $owner = $this->actor() === null ? Morphs::ownerRules(required: true) : [];

        return $owner + Morphs::scopeRules() + ActiveCartData::rules();
    }

    public function persist(): JsonResponse
    {
        $validated = $this->validated();
        $morphs = app(Morphs::class);

        // The owner rules require one when no user is acting.
        /** @var Model|string $owner */
        $owner = $this->actor() ?? $morphs->ownerFrom($validated);

        $cart = app(ActiveCartAction::class)->execute(ActiveCartData::fromValidated($validated), $owner, $morphs->scopeFrom($validated));

        return (new CartResource($cart->load('lines')))->response();
    }
}

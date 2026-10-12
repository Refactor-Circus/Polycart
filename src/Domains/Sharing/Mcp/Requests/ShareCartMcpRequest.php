<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\Sharing\Actions\ShareCartAction;
use RefactorCircus\Polycart\Domains\Sharing\Data\ShareCartData;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;
use RefactorCircus\Polycart\Domains\Sharing\Resources\CartMemberResource;
use RefactorCircus\Polycart\Support\Morphs;

final class ShareCartMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allows('create', CartMemberModel::class, [$this->cart()]);
    }

    protected function rules(): array
    {
        return $this->cartRules() + Morphs::memberRules() + ShareCartData::rules();
    }

    protected function respond(array $validated): ResponseFactory
    {
        $member = app(ShareCartAction::class)->execute($this->cart(), app(Morphs::class)->memberFrom($validated), ShareCartData::fromValidated($validated));

        return (new CartMemberResource($member))->toStructuredResponse();
    }
}

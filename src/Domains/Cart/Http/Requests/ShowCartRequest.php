<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Polycart\Domains\Cart\Actions\ShowCartAction;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class ShowCartRequest extends CartRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->cart());
    }

    public function persist(): JsonResponse
    {
        return (new CartResource(app(ShowCartAction::class)->execute($this->cart())))->response();
    }
}

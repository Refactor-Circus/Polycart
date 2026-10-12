<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Polycart\Domains\Cart\Actions\DeleteCartAction;

final class DestroyCartRequest extends CartRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->cart());
    }

    public function persist(): Response
    {
        app(DeleteCartAction::class)->execute($this->cart());

        return response()->noContent();
    }
}

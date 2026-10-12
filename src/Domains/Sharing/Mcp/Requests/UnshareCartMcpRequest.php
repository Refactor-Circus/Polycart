<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\Sharing\Actions\UnshareCartAction;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;

final class UnshareCartMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->member());
    }

    protected function rules(): array
    {
        return $this->cartRules() + [
            'member' => ['required', 'string', 'max:26'],
        ];
    }

    protected function respond(array $validated): ResponseFactory
    {
        $member = $this->member();

        app(UnshareCartAction::class)->execute($this->cart(), $member);

        return Response::structured(['removed' => $member->id]);
    }

    private function member(): CartMemberModel
    {
        /** @var string $id */
        $id = $this->get('member');

        return $this->cart()->members()->findOrFail($id);
    }
}

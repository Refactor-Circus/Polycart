<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Actions\ShowCartStatsAction;
use RefactorCircus\Polycart\Domains\Cart\Http\Resources\CartStatsResource;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Mcp\Request;

final class ShowCartStatsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', CartModel::class);
    }

    protected function respond(array $validated): ResponseFactory
    {
        return (new CartStatsResource(app(ShowCartStatsAction::class)->execute($this->actor())))->toStructuredResponse();
    }
}

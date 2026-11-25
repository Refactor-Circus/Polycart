<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Polycart\Domains\Cart\Actions\ShowCartStatsAction;
use RefactorCircus\Polycart\Domains\Cart\Http\Resources\CartStatsResource;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

final class ShowCartStatsRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', CartModel::class);
    }

    public function persist(): JsonResponse
    {
        return (new CartStatsResource(app(ShowCartStatsAction::class)->execute($this->actor())))->response();
    }
}

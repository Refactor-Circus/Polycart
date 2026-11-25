<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\ActiveCartRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\ClearCartRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\ConvertCartRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\DestroyCartRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\IndexCartsRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\MergeCartsRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\SearchCartsRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\ShowCartRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\ShowCartStatsRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\StoreCartRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\TransitionCartRequest;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\UpdateCartRequest;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

final class CartController
{
    public function index(IndexCartsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function stats(ShowCartStatsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function search(SearchCartsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreCartRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function active(ActiveCartRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowCartRequest $request, CartModel $cart): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateCartRequest $request, CartModel $cart): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyCartRequest $request, CartModel $cart): Response
    {
        return $request->persist();
    }

    public function clear(ClearCartRequest $request, CartModel $cart): JsonResponse
    {
        return $request->persist();
    }

    public function transition(TransitionCartRequest $request, CartModel $cart): JsonResponse
    {
        return $request->persist();
    }

    public function convert(ConvertCartRequest $request, CartModel $cart): JsonResponse
    {
        return $request->persist();
    }

    public function merge(MergeCartsRequest $request, CartModel $cart): JsonResponse
    {
        return $request->persist();
    }
}

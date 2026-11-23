<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Polycart\Domains\Cart\Http\Controllers\CartController;

Route::get('stats', [CartController::class, 'stats'])->name('stats.show');
Route::get('search', [CartController::class, 'search'])->name('search.index');

Route::get('carts', [CartController::class, 'index'])->name('carts.index');
Route::post('carts', [CartController::class, 'store'])->name('carts.store');
Route::post('carts/active', [CartController::class, 'active'])->name('carts.active');
Route::get('carts/{cart}', [CartController::class, 'show'])->name('carts.show');
Route::patch('carts/{cart}', [CartController::class, 'update'])->name('carts.update');
Route::delete('carts/{cart}', [CartController::class, 'destroy'])->name('carts.destroy');
Route::post('carts/{cart}/clear', [CartController::class, 'clear'])->name('carts.clear');
Route::post('carts/{cart}/status', [CartController::class, 'transition'])->name('carts.transition');
Route::post('carts/{cart}/convert', [CartController::class, 'convert'])->name('carts.convert');
Route::post('carts/{cart}/merge', [CartController::class, 'merge'])->name('carts.merge');

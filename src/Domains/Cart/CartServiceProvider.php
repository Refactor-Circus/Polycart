<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart;

use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

class CartServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // How the audit log (refactor-circus/keen) names a cart: its label, else
        // its id. Only while Polycart audits, known once every provider booted.
        $this->app->booted(function (): void {
            if (! $this->package()->audits()) {
                return;
            }

            $this->app->make(AuditHooks::class)
                ->label(CartModel::class, fn (CartModel $cart): string => $cart->label ?? $cart->id);
        });

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}

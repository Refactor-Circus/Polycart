<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine;

use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Polycart\Domains\CartLine\Contracts\PriceResolver;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartLine\Services\PurchasablePriceResolver;

class CartLineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind your own to price lines from an ERP or a price book.
        $this->app->singleton(PriceResolver::class, PurchasablePriceResolver::class);
    }

    public function boot(): void
    {
        // How the audit log (refactor-circus/keen) names a line: a custom line by its
        // description, any other by what it is for. Only while Polycart audits,
        // known once every provider booted.
        $this->app->booted(function (): void {
            if (! $this->package()->audits()) {
                return;
            }

            $this->app->make(AuditHooks::class)->label(CartLineModel::class, fn (CartLineModel $line): string => match (true) {
                ! $line->isCustom() => class_basename((string) $line->purchasable_type).' #'.$line->purchasable_id,
                is_string($line->meta['description'] ?? null) => $line->meta['description'],
                default => $line->id,
            });
        });

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}

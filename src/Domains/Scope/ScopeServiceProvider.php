<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Scope;

use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Polycart\Domains\Scope\Services\ScopeTree;

class ScopeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // What each actor reaches is read once per request or job.
        $this->app->scoped(ScopeTree::class);
    }

    public function boot(): void {}
}

<?php

namespace Workbench\App\Providers;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Carts\OrderCart;
use Workbench\App\Carts\QuoteCart;
use Workbench\App\Carts\ShopCart;
use Workbench\App\Carts\WishlistCart;
use Workbench\App\Http\Middleware\SignInWorkbenchUser;
use Workbench\App\Models\Organization;
use Workbench\App\Models\Product;
use Workbench\App\Models\Team;
use Workbench\App\Models\User;

/**
 * Turns the workbench into a demo store: Polycart on the workbench User, with
 * its own cart types, teams and catalogue, served through the JSON API (on
 * here, off by default in a host) and the MCP server.
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        config([
            'auth.providers.users.model' => User::class,
            'polycart.routes.enabled' => true,
            // Workbench only: run the API on the 'web' group so SignInWorkbenchUser signs the
            // demo user in (D-016). Hosts keep the package default ['api'] plus their own guard.
            'polycart.routes.middleware' => ['web'],
            'polycart.types' => [
                'cart' => ShopCart::class,
                'quote' => QuoteCart::class,
                'order' => OrderCart::class,
                'wishlist' => WishlistCart::class,
            ],
            'polycart.owners' => ['user' => User::class],
            'polycart.scopes' => ['team' => Team::class, 'organization' => Organization::class],
            'polycart.purchasables' => ['product' => Product::class],
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Keep the workbench user signed in whatever URL is opened first.
        $this->callAfterResolving(HttpKernel::class, function (HttpKernel $kernel): void {
            if ($kernel instanceof Kernel) {
                $kernel->appendMiddlewareToGroup('web', SignInWorkbenchUser::class);
            }
        });

        //
    }
}

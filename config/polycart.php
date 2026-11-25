<?php

declare(strict_types=1);

use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Policies\CartPolicy;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartLine\Policies\CartLinePolicy;
use RefactorCircus\Polycart\Domains\CartType\Support\ShoppingCart;
use RefactorCircus\Polycart\Domains\Scope\Models\CartPathModel;
use RefactorCircus\Polycart\Domains\Scope\Policies\CartPathPolicy;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;
use RefactorCircus\Polycart\Domains\Sharing\Policies\CartMemberPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | CartModel Types
    |--------------------------------------------------------------------------
    |
    | Every kind of cart your application keeps — a shopping cart, a saved
    | cart, a quote, an order, a project — keyed by the string stored in the
    | carts table. Each class extends RefactorCircus\Polycart\Domains\CartType\Support\CartType and holds
    | that kind's rules. Entries here win over types a package registers.
    |
    */

    'types' => [
        'cart' => ShoppingCart::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Type
    |--------------------------------------------------------------------------
    |
    | The type $user->cart() returns when no type is given.
    |
    */

    'default_type' => 'cart',

    /*
    |--------------------------------------------------------------------------
    | Pruning
    |--------------------------------------------------------------------------
    |
    | Carts whose type gives them a lifetime are pruned by `model:prune` this
    | many days after they expire.
    |
    | prune_deleted_after_days: Deleted carts of every type, including ones
    |                           that never expire such as quotes and orders,
    |                           are pruned for good this many days after
    |                           they were deleted.
    |
    */

    'prune_after_days' => 30,

    'prune_deleted_after_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Owners, Scopes and Purchasables
    |--------------------------------------------------------------------------
    |
    | The models the JSON API and MCP tools may name, keyed by the alias a
    | caller uses. Owners are people (users); scopes are the levels of your
    | tree (teams, organizations) and implement CartScope. Nothing outside
    | these lists can be reached through them, so a caller can never make
    | the package load an arbitrary class. Code that calls the package
    | directly is not limited by them.
    |
    */

    'owners' => [
        // 'user' => App\Models\User::class,
    ],

    'scopes' => [
        // 'team' => App\Models\Team::class,
        // 'organization' => App\Models\Organization::class,
    ],

    'purchasables' => [
        // 'product' => App\Models\Product::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | When on, the JSON API and MCP tools act as the authenticated user: they
    | list only the carts that user can access, make them the owner of carts
    | they start, and check every change against the role the cart's type
    | gives them. Turn it off only for trusted server-to-server use.
    |
    */

    'authorization' => true,

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | The policy the Gate uses for each model. The JSON API and MCP tools
    | check every call against these. By default a cart's owner may do
    | anything, everyone else gets what their role grants, lines and members
    | follow their cart, and a cart's tree paths can be seen by whoever sees
    | the cart but never changed. Every model has an entry: point one at your
    | own class to replace its policy; the CartModel entry covers every cart
    | type's subclass.
    |
    */

    'policies' => [
        CartModel::class => CartPolicy::class,
        CartLineModel::class => CartLinePolicy::class,
        CartMemberModel::class => CartMemberPolicy::class,
        CartPathModel::class => CartPathPolicy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | The table each of Polycart's records is stored in. Set these before
    | running the migrations: the migrations, models and queries all read
    | them. A published config that lists only some tables keeps the defaults
    | for the rest.
    |
    | cart_paths: every level of the tree a cart lives in, written by the
    |             cart when it is created.
    |
    */

    'tables' => [
        'carts' => 'polycart_carts',
        'cart_paths' => 'polycart_cart_paths',
        'cart_members' => 'polycart_cart_members',
        'cart_lines' => 'polycart_cart_lines',
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON API
    |--------------------------------------------------------------------------
    |
    | Every cart operation over HTTP, including the cart stats and search
    | endpoints. The package's openapi.json and its TypeScript client
    | (@refactor-circus/polycart-client) describe these routes. It is off by
    | default because it can read and change every cart. A front end built
    | on the API, such as an admin UI, needs it on: enable it behind your
    | own auth middleware.
    |
    */

    'routes' => [
        'enabled' => false,
        'prefix' => 'polycart',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Server
    |--------------------------------------------------------------------------
    |
    | The same operations as the JSON API, as MCP tools for agents. Expose it
    | over HTTP, locally over stdio, or both.
    |
    */

    'mcp' => [
        'web' => [
            'enabled' => false,
            'route' => 'mcp/polycart',
            'middleware' => [],
        ],
        'local' => [
            'enabled' => false,
            'handle' => 'polycart',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cortex
    |--------------------------------------------------------------------------
    |
    | When refactor-circus/cortex is installed, the MCP server is registered with it, so
    | its instructions can be overridden, and the tools join its registry,
    | so Cortex agents can manage carts. Set `tools` to a list of tool names,
    | such as ['list-carts', 'show-cart'], to offer only some of them. The
    | tools are grouped in Cortex under `tags`.
    |
    */

    'cortex' => [
        'enabled' => true,
        'server' => 'polycart',
        'tools' => null,
        'tags' => ['polycart'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    |
    | Polycart keeps no history of its own. With refactor-circus/keen
    | installed, Keen records every change Polycart makes, and Polycart serves
    | the history through its `GET /polycart/history` route and its history
    | MCP tool. Without Keen neither exists.
    |
    | enabled: Set to false to keep Polycart out of the audit log even while
    |          Keen is installed: Keen records nothing of Polycart's, and
    |          Polycart offers no history.
    |
    */

    'audit' => [
        'enabled' => true,
    ],

];

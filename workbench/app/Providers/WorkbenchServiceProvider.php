<?php

namespace Workbench\App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Server;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Polycart\Domains\Cart\Http\Resources\CartStatsResource;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Domains\CartLine\Resources\CartLineResource;
use RefactorCircus\Polycart\Domains\CartType\Http\Resources\CartTypeResource;
use RefactorCircus\Polycart\Domains\Sharing\Resources\CartMemberResource;
use Workbench\App\Carts\OrderCart;
use Workbench\App\Carts\QuoteCart;
use Workbench\App\Carts\ShopCart;
use Workbench\App\Carts\WishlistCart;
use Workbench\App\Console\Commands\ExportOpenApiCommand;
use Workbench\App\Http\Middleware\SignInWorkbenchUser;
use Workbench\App\Models\Organization;
use Workbench\App\Models\Product;
use Workbench\App\Models\Team;
use Workbench\App\Models\User;

/**
 * Turns the workbench into a demo store: Polycart on the workbench User, with
 * its own cart types, teams and catalogue, served through the JSON API (on
 * here, off by default in a host) and the MCP server.
 *
 * It also holds the one Scramble configuration `workbench:openapi` exports
 * openapi.json with (`composer client:generate`). Nothing in src/ knows about
 * Scramble: it is a dev dependency.
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * The server every operation's path is relative to: the default
     * `polycart.routes.prefix`, without a host, so the document holds nothing
     * machine-specific.
     */
    private const string SERVER = '/polycart';

    /**
     * The API resources whose component schema is their own `schema()`.
     *
     * @var list<class-string<JsonResource>>
     */
    private const array RESOURCES = [
        CartResource::class,
        CartStatsResource::class,
        CartLineResource::class,
        CartMemberResource::class,
        CartTypeResource::class,
    ];

    /**
     * What each route's success response holds: one resource (`item`), a list
     * of them (`list`) or a cursor page of them (`page`).
     *
     * @var array<string, array{0: 'item'|'list'|'page', 1: class-string<JsonResource>}>
     */
    private const array RESPONSES = [
        'polycart.stats.show' => ['item', CartStatsResource::class],
        'polycart.search.index' => ['page', CartResource::class],
        'polycart.carts.index' => ['page', CartResource::class],
        'polycart.carts.store' => ['item', CartResource::class],
        'polycart.carts.active' => ['item', CartResource::class],
        'polycart.carts.show' => ['item', CartResource::class],
        'polycart.carts.update' => ['item', CartResource::class],
        'polycart.carts.clear' => ['item', CartResource::class],
        'polycart.carts.transition' => ['item', CartResource::class],
        'polycart.carts.convert' => ['item', CartResource::class],
        'polycart.carts.merge' => ['item', CartResource::class],
        'polycart.carts.lines.store' => ['list', CartLineResource::class],
        'polycart.carts.lines.update' => ['item', CartResource::class],
        'polycart.carts.lines.destroy' => ['item', CartResource::class],
        'polycart.carts.visibility' => ['item', CartResource::class],
        'polycart.carts.members.index' => ['list', CartMemberResource::class],
        'polycart.carts.members.store' => ['item', CartMemberResource::class],
        'polycart.types.index' => ['list', CartTypeResource::class],
    ];

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

            // The OpenAPI document. Every value is fixed so the export is the
            // same on every machine and every run.
            'scramble.api_path' => 'polycart',
            'scramble.info.version' => '2.0.0',
            'scramble.info.description' => 'The JSON API of Polycart: typed carts, their lines, sharing, stats and search.',
            'scramble.ui.title' => 'Polycart',
            // Always generate: a cached document could be stale.
            'scramble.cache.store' => null,
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

        if ($this->app->runningInConsole()) {
            $this->commands([ExportOpenApiCommand::class]);
        }

        $this->configureOpenApi();
    }

    /**
     * Document Polycart's own `polycart.*` routes, each under its route name
     * as operationId, with one relative server.
     */
    private function configureOpenApi(): void
    {
        Scramble::configure()
            ->routes(fn (Route $route): bool => str_starts_with((string) $route->getName(), 'polycart.'))
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo): void {
                $name = (string) $routeInfo->route->getName();

                $operation->setOperationId($name);

                if (isset(self::RESPONSES[$name])) {
                    $this->describeSuccess($operation, ...self::RESPONSES[$name]);
                }
            })
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->servers = [Server::make(self::SERVER)];

                $this->useResourceSchemas($openApi);
            });
    }

    /**
     * Scramble infers a resource's fields from `toArray()` and the model, and
     * leaves nested lists untyped. Each resource already declares its payload
     * as JSON Schema for its MCP tools: use that, so the document matches the
     * tools' output schemas. Every resource gets its component, named by its
     * class basename as Scramble names them, whatever Scramble inferred.
     */
    private function useResourceSchemas(OpenApi $openApi): void
    {
        $factory = new JsonSchemaTypeFactory;

        foreach (self::RESOURCES as $resource) {
            $openApi->components->addSchema(class_basename($resource), Schema::fromType(self::raw($factory->object($resource::schema($factory))->toArray())));
        }
    }

    /**
     * The controllers answer through `$request->persist()`, which hides the
     * resource from Scramble: what it infers for a collection, and whether it
     * sees the cursor page around it, depends on the framework version. State
     * each success body from the resource instead, so the export is the same
     * on every install. Status codes stay as Scramble found them.
     *
     * @param  'item'|'list'|'page'  $kind
     * @param  class-string<JsonResource>  $resource
     */
    private function describeSuccess(Operation $operation, string $kind, string $resource): void
    {
        $name = class_basename($resource);
        $ref = ['$ref' => '#/components/schemas/'.$name];
        $nullableString = ['type' => ['string', 'null']];
        $object = fn (array $properties): array => ['type' => 'object', 'properties' => $properties, 'required' => array_keys($properties)];

        [$description, $body] = match ($kind) {
            'item' => ["`{$name}`", $object(['data' => $ref])],
            'list' => ["Array of `{$name}`", $object(['data' => ['type' => 'array', 'items' => $ref]])],
            'page' => ["A cursor page of `{$name}`", $object([
                'data' => ['type' => 'array', 'items' => $ref],
                'links' => $object(['first' => $nullableString, 'last' => $nullableString, 'prev' => $nullableString, 'next' => $nullableString]),
                'meta' => $object([
                    'path' => ['type' => 'string'],
                    'per_page' => ['type' => 'integer'],
                    'next_cursor' => $nullableString + ['description' => 'Pass as cursor for the next page; null on the last.'],
                    'prev_cursor' => $nullableString,
                ]),
            ])],
        };

        foreach ($operation->responses ?? [] as $response) {
            if ($response instanceof Response && (int) $response->code >= 200 && (int) $response->code < 300) {
                $response->setDescription($description)->setContent('application/json', Schema::fromType(self::raw($body)));
            }
        }
    }

    /**
     * A JSON Schema written as it is: OpenAPI 3.1 schemas are JSON Schema.
     *
     * @param  array<string, mixed>  $schema
     */
    private static function raw(array $schema): Type
    {
        return new class($schema) extends Type
        {
            /**
             * @param  array<string, mixed>  $schema
             */
            public function __construct(private readonly array $schema)
            {
                parent::__construct('object');
            }

            public function serializeAs31(): mixed
            {
                return $this->schema;
            }

            public function serializeAs32(): mixed
            {
                return $this->schema;
            }
        };
    }
}

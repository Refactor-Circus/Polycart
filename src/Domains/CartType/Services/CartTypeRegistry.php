<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartType\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Exceptions\CartTypeCollisionException;
use RefactorCircus\Polycart\Domains\CartType\Exceptions\UnknownCartTypeException;
use RefactorCircus\Polycart\Domains\CartType\Support\CartType;

/**
 * The catalogue of cart types.
 *
 * Types come from two places: `polycart.types` in the application's config,
 * and runtime registration by a package's service provider. **The
 * application's config always wins**, so an application can point a key a
 * package ships at its own subclass. Two packages claiming the same key is an
 * error rather than a silent shadowing.
 *
 * Keys are strings, not enum cases, so a new type needs no migration, no
 * backfill, and no coordination over which integer is free.
 */
final class CartTypeRegistry
{
    /** @var array<string, class-string<CartType>> */
    private array $registered = [];

    /** @var array<string, CartType> */
    private array $resolved = [];

    /**
     * The merged catalogue, and the `polycart.types` value it was built from.
     *
     * @var array{config: mixed, types: array<string, class-string<CartType>>}|null
     */
    private ?array $catalogue = null;

    public function __construct(
        private readonly Container $container,
        private readonly Config $config,
    ) {}

    /**
     * Register a type under a key.
     *
     * Safe to call from any service provider's `boot()`, in any order.
     * Registering the same class under the same key twice is a no-op.
     *
     * @param  class-string<CartType>  $class
     */
    public function register(string $key, string $class): void
    {
        if (! is_subclass_of($class, CartType::class)) {
            throw UnknownCartTypeException::notACartType($class);
        }

        $existing = $this->registered[$key] ?? null;

        if ($existing !== null && $existing !== $class) {
            throw CartTypeCollisionException::key($key, $existing, $class);
        }

        $this->registered[$key] = $class;
        $this->catalogue = null;
    }

    /**
     * Register several types at once.
     *
     * String keys set the key; unkeyed entries derive one from the class.
     *
     * @param  array<int|string, class-string<CartType>>  $types
     */
    public function registerMany(array $types): void
    {
        foreach ($types as $key => $class) {
            $this->register(is_string($key) ? $key : $this->derive($class), $class);
        }
    }

    /**
     * Every known type, keyed by key, with config taking precedence.
     *
     * @return array<string, class-string<CartType>>
     */
    public function all(): array
    {
        // Merged on read, and kept only until a package registers a type or
        // the config changes: precedence must not depend on which happened
        // first.
        $config = $this->config->get('polycart.types', []);

        if ($this->catalogue !== null && $this->catalogue['config'] === $config) {
            return $this->catalogue['types'];
        }

        $types = array_merge($this->registered, $this->configured($config));
        $this->catalogue = ['config' => $config, 'types' => $types];

        return $types;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * The class registered under a key.
     *
     * @return class-string<CartType>
     */
    public function class(string $key): string
    {
        $types = $this->all();

        if (! array_key_exists($key, $types)) {
            throw UnknownCartTypeException::key($key);
        }

        return $types[$key];
    }

    /**
     * Resolve the type registered under a key.
     */
    public function get(string $key): CartType
    {
        $class = $this->class($key);

        $cached = $this->resolved[$key] ?? null;

        if ($cached instanceof $class) {
            return $cached;
        }

        $type = $this->container->make($class);

        if (! $type instanceof CartType) {
            throw UnknownCartTypeException::notACartType($class);
        }

        return $this->resolved[$key] = $type->withKey($key);
    }

    /**
     * The model class rows of a type hydrate as, or null for an unknown key.
     *
     * Reads never throw on an unknown key: a row written by a type that has
     * since been removed still loads, as a plain CartModel.
     *
     * @return class-string<CartModel>|null
     */
    public function model(string $key): ?string
    {
        return $this->has($key) ? $this->get($key)->model() : null;
    }

    /**
     * The types declared in the application's config.
     *
     * @return array<string, class-string<CartType>>
     */
    private function configured(mixed $configured): array
    {
        /** @var array<int|string, class-string<CartType>> $configured */
        $configured = is_array($configured) ? $configured : [];

        $types = [];

        foreach ($configured as $key => $class) {
            if (! is_subclass_of($class, CartType::class)) {
                throw UnknownCartTypeException::notACartType($class);
            }

            $types[is_string($key) ? $key : $this->derive($class)] = $class;
        }

        return $types;
    }

    private function derive(string $class): string
    {
        return Str::snake(class_basename($class));
    }
}

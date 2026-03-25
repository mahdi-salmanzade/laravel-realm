<?php

namespace Realm\Integrations;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Realm\Context\RealmContext;

/**
 * @mixin \Illuminate\Cache\Repository
 */
class RealmCacheRepository implements Repository
{
    public function __construct(
        private readonly Repository $inner,
        private readonly RealmContext $context,
    ) {}

    // -------------------------------------------------------
    // Key resolution
    // -------------------------------------------------------

    private function resolveKey(mixed $key): string
    {
        $stringKey = match (true) {
            $key instanceof \BackedEnum => (string) $key->value,
            $key instanceof \UnitEnum => $key->name,
            default => (string) $key,
        };

        if ($this->context->isTenancyDisabled() || $this->context->id() === null) {
            return $stringKey;
        }

        return 'realm:'.$this->context->key().':'.$stringKey;
    }

    // -------------------------------------------------------
    // PSR-16 CacheInterface
    // -------------------------------------------------------

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->inner->get($this->resolveKey($key), $default);
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->inner->set($this->resolveKey($key), $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->inner->delete($this->resolveKey($key));
    }

    public function clear(): bool
    {
        if ($this->context->isTenancyDisabled() || $this->context->id() === null) {
            return $this->inner->clear();
        }

        throw new \RuntimeException(
            'Cache::clear() would wipe ALL tenants\' data. '
            .'Use Realm::withoutTenancy(fn () => Cache::clear()) to clear the entire store intentionally.'
        );
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $prefixedKeys = [];
        $keyMap = [];

        foreach ($keys as $key) {
            $prefixed = $this->resolveKey($key);
            $prefixedKeys[] = $prefixed;
            $keyMap[$prefixed] = $key;
        }

        $results = $this->inner->getMultiple($prefixedKeys, $default);

        $output = [];
        foreach ($results as $prefixedKey => $value) {
            $originalKey = $keyMap[$prefixedKey] ?? $prefixedKey;
            $output[$originalKey] = $value;
        }

        return $output;
    }

    /** @param iterable<string, mixed> $values */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $prefixed = [];
        foreach ($values as $key => $value) {
            $prefixed[$this->resolveKey($key)] = $value;
        }

        return $this->inner->setMultiple($prefixed, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $prefixed = [];
        foreach ($keys as $key) {
            $prefixed[] = $this->resolveKey($key);
        }

        return $this->inner->deleteMultiple($prefixed);
    }

    public function has(string $key): bool
    {
        return $this->inner->has($this->resolveKey($key));
    }

    // -------------------------------------------------------
    // Illuminate\Contracts\Cache\Repository
    // -------------------------------------------------------

    /** @param \UnitEnum|string $key */
    public function pull($key, $default = null)
    {
        return $this->inner->pull($this->resolveKey($key), $default);
    }

    public function put($key, $value, $ttl = null)
    {
        return $this->inner->put($this->resolveKey($key), $value, $ttl);
    }

    public function add($key, $value, $ttl = null)
    {
        return $this->inner->add($this->resolveKey($key), $value, $ttl);
    }

    public function increment($key, $value = 1)
    {
        return $this->inner->increment($this->resolveKey($key), $value);
    }

    public function decrement($key, $value = 1)
    {
        return $this->inner->decrement($this->resolveKey($key), $value);
    }

    public function forever($key, $value)
    {
        return $this->inner->forever($this->resolveKey($key), $value);
    }

    public function remember($key, $ttl, Closure $callback)
    {
        return $this->inner->remember($this->resolveKey($key), $ttl, $callback);
    }

    public function sear($key, Closure $callback)
    {
        return $this->inner->sear($this->resolveKey($key), $callback);
    }

    public function rememberForever($key, Closure $callback)
    {
        return $this->inner->rememberForever($this->resolveKey($key), $callback);
    }

    public function touch($key, $ttl)
    {
        return $this->inner->touch($this->resolveKey($key), $ttl);
    }

    public function forget($key)
    {
        return $this->inner->forget($this->resolveKey($key));
    }

    public function getStore(): Store
    {
        return $this->inner->getStore();
    }

    // -------------------------------------------------------
    // Non-interface methods (tags, lock, etc.)
    // -------------------------------------------------------

    /**
     * @param  array<mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        // Methods that wipe the entire store — same protection as clear()
        if ($method === 'flush' && ! $this->context->isTenancyDisabled() && $this->context->id() !== null) {
            throw new \RuntimeException(
                'Cache::flush() would wipe ALL tenants\' data. '
                .'Use Realm::withoutTenancy(fn () => Cache::flush()) to flush the entire store intentionally.'
            );
        }

        // Methods known to NOT take a cache key as first argument
        $noKeyMethods = ['tags', 'lock', 'restoreLock', 'getStore', 'supportsTags', 'getPrefix', 'flush', 'getDefaultCacheTime', 'setDefaultCacheTime'];

        if (! in_array($method, $noKeyMethods, true) && isset($parameters[0]) && (is_string($parameters[0]) || $parameters[0] instanceof \UnitEnum)) {
            $parameters[0] = $this->resolveKey($parameters[0]);
        }

        return $this->inner->$method(...$parameters);
    }
}

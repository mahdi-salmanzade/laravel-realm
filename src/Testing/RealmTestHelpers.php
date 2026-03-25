<?php

namespace Realm\Testing;

use Closure;
use Realm\Context\RealmContext;
use Realm\Facades\Realm;
use Realm\Models\Tenant;

trait RealmTestHelpers
{
    protected function createRealm(string $key, array $attributes = []): Tenant
    {
        return Tenant::create(array_merge([
            'key' => $key,
            'name' => ucfirst($key),
            'active' => true,
        ], $attributes));
    }

    protected function actingAsRealm(Tenant $tenant, ?Closure $callback = null): static
    {
        app(RealmContext::class)->set($tenant);

        if ($callback !== null) {
            return $this->actingAsRealmCallback($tenant, $callback);
        }

        return $this;
    }

    private function actingAsRealmCallback(Tenant $tenant, Closure $callback): static
    {
        Realm::run($tenant, $callback);

        return $this;
    }

    /**
     * Run a callback with tenancy disabled.
     *
     * This is a test-context alias for Realm::withoutTenancy().
     */
    protected function withoutRealm(Closure $callback): mixed
    {
        return Realm::withoutTenancy($callback);
    }

    protected function assertRealmCount(Tenant $tenant, string $modelClass, int $expectedCount): void
    {
        $count = Realm::run($tenant, fn () => $modelClass::count());

        $this->assertEquals($expectedCount, $count, "Expected {$expectedCount} records for realm {$tenant->key}, got {$count}.");
    }

    protected function assertRealmHas(Tenant $tenant, string $modelClass, array $attributes): void
    {
        $exists = Realm::run($tenant, fn () => $modelClass::where($attributes)->exists());

        $this->assertTrue($exists, "Expected realm {$tenant->key} to have a {$modelClass} record with given attributes.");
    }

    protected function assertRealmMissing(Tenant $tenant, string $modelClass, array $attributes): void
    {
        $exists = Realm::run($tenant, fn () => $modelClass::where($attributes)->exists());

        $this->assertFalse($exists, "Expected realm {$tenant->key} NOT to have a {$modelClass} record with given attributes.");
    }

    protected function attachUserToRealm(Tenant $tenant, mixed $user, string $role = 'member'): void
    {
        $tenant->users()->attach($user, ['role' => $role]);
    }

    protected function assertTenantHasSecret(Tenant $tenant, string $key): void
    {
        $this->assertNotNull(
            $tenant->getSecret($key),
            "Expected tenant '{$tenant->key}' to have secret '{$key}'."
        );
    }

    protected function assertTenantMissingSecret(Tenant $tenant, string $key): void
    {
        $this->assertNull(
            $tenant->getSecret($key),
            "Expected tenant '{$tenant->key}' NOT to have secret '{$key}'."
        );
    }

    protected function resetRealmContext(): void
    {
        app(RealmContext::class)->reset();
    }
}

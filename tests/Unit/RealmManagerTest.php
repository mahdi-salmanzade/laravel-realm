<?php

namespace Realm\Tests\Unit;

use Realm\Context\RealmContext;
use Realm\Facades\Realm;
use Realm\Models\Tenant;
use Realm\RealmManager;
use Realm\Scopes\RealmScope;
use Realm\Tests\TestCase;
use ReflectionClass;

class RealmManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RealmScope::resetWarning();
    }

    public function test_current_proxies_to_context(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        app(RealmContext::class)->set($tenant);

        $this->assertSame($tenant->id, Realm::current()->id);

        app(RealmContext::class)->reset();
    }

    public function test_id_proxies_to_context(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        app(RealmContext::class)->set($tenant);

        $this->assertEquals($tenant->id, Realm::id());

        app(RealmContext::class)->reset();
    }

    public function test_key_proxies_to_context(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        app(RealmContext::class)->set($tenant);

        $this->assertEquals('acme', Realm::key());

        app(RealmContext::class)->reset();
    }

    public function test_check_proxies_to_context(): void
    {
        $this->assertFalse(Realm::check());

        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        app(RealmContext::class)->set($tenant);

        $this->assertTrue(Realm::check());

        app(RealmContext::class)->reset();
    }

    public function test_is_tenancy_disabled_proxies_to_context(): void
    {
        $this->assertFalse(Realm::isTenancyDisabled());

        Realm::withoutTenancy(function () {
            $this->assertTrue(Realm::isTenancyDisabled());
        });
    }

    public function test_run_proxies_to_context(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        Realm::run($tenant, function () {
            $this->assertEquals('acme', Realm::key());
        });
    }

    public function test_without_tenancy_proxies_to_context(): void
    {
        Realm::withoutTenancy(function () {
            $this->assertTrue(Realm::isTenancyDisabled());
        });
    }

    public function test_no_eloquent_methods_on_manager(): void
    {
        $reflection = new ReflectionClass(RealmManager::class);
        $methods = array_map(
            fn ($m) => $m->getName(),
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        // These Eloquent methods should NOT exist
        $eloquentMethods = [
            'find', 'findOrFail', 'all', 'where', 'create',
            'update', 'delete', 'first', 'get', 'query',
            'save', 'destroy', 'pluck', 'count',
        ];

        foreach ($eloquentMethods as $method) {
            $this->assertNotContains(
                $method,
                $methods,
                "RealmManager should not have Eloquent method '{$method}'"
            );
        }
    }

    public function test_no_call_magic_method(): void
    {
        $reflection = new ReflectionClass(RealmManager::class);
        $this->assertFalse(
            $reflection->hasMethod('__call'),
            'RealmManager should not have __call magic method'
        );
    }

    public function test_no_call_static_magic_method(): void
    {
        $reflection = new ReflectionClass(RealmManager::class);
        $this->assertFalse(
            $reflection->hasMethod('__callStatic'),
            'RealmManager should not have __callStatic magic method'
        );
    }
}

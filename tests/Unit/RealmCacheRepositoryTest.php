<?php

namespace Realm\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Realm\Context\RealmContext;
use Realm\Facades\Realm;
use Realm\Integrations\RealmCacheRepository;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class RealmCacheRepositoryTest extends TestCase
{
    use RealmTestHelpers;

    private RealmContext $context;

    private Repository $inner;

    private RealmCacheRepository $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = app(RealmContext::class);
        $this->inner = new Repository(new ArrayStore);
        $this->cache = new RealmCacheRepository($this->inner, $this->context);
    }

    public function test_key_prefixed_when_realm_active(): void
    {
        $tenant = $this->createRealm('acme');
        $this->context->set($tenant);

        $this->cache->put('settings', 'v');

        $this->assertTrue($this->inner->has('realm:acme:settings'));
    }

    public function test_key_not_prefixed_when_no_realm(): void
    {
        $this->cache->put('settings', 'v');

        $this->assertTrue($this->inner->has('settings'));
    }

    public function test_key_not_prefixed_inside_without_tenancy(): void
    {
        $tenant = $this->createRealm('acme');
        $this->context->set($tenant);

        $this->context->withoutTenancy(function () {
            $this->cache->put('settings', 'v');
        });

        $this->assertTrue($this->inner->has('settings'));
        $this->assertFalse($this->inner->has('realm:acme:settings'));
    }

    public function test_key_prefixed_inside_run_within_without_tenancy(): void
    {
        $tenant = $this->createRealm('acme');
        $this->context->set($tenant);

        $this->context->withoutTenancy(function () use ($tenant) {
            Realm::run($tenant, function () {
                $this->cache->put('settings', 'v');
            });
        });

        $this->assertTrue($this->inner->has('realm:acme:settings'));
    }

    public function test_get_retrieves_prefixed_key_correctly(): void
    {
        $tenant = $this->createRealm('acme');
        $this->context->set($tenant);

        $this->inner->put('realm:acme:settings', 'tenant-value');

        $this->assertEquals('tenant-value', $this->cache->get('settings'));
    }

    public function test_forget_removes_prefixed_key_correctly(): void
    {
        $tenant = $this->createRealm('acme');
        $this->context->set($tenant);

        $this->inner->put('realm:acme:settings', 'tenant-value');

        $this->cache->forget('settings');

        $this->assertFalse($this->inner->has('realm:acme:settings'));
    }

    public function test_clear_throws_when_tenant_active(): void
    {
        $tenant = $this->createRealm('acme');
        $context = app(RealmContext::class);
        $context->set($tenant);

        $inner = new Repository(new ArrayStore);
        $cache = new RealmCacheRepository($inner, $context);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ALL tenants');
        $cache->clear();
    }

    public function test_clear_works_when_no_tenant(): void
    {
        $inner = new Repository(new ArrayStore);
        $context = app(RealmContext::class);
        $cache = new RealmCacheRepository($inner, $context);

        $inner->put('key', 'value');
        $this->assertTrue($cache->clear());
    }
}

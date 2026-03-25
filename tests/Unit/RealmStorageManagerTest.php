<?php

namespace Realm\Tests\Unit;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Realm\Context\RealmContext;
use Realm\Facades\Realm;
use Realm\Integrations\RealmStorageManager;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class RealmStorageManagerTest extends TestCase
{
    use RealmTestHelpers;

    private RealmContext $context;

    private Filesystem $inner;

    private RealmStorageManager $storage;

    protected function setUp(): void
    {
        parent::setUp();

        config(['realm.storage.root' => 'tenants']);

        $this->context = app(RealmContext::class);
        $this->inner = Storage::fake('testing');
        $this->storage = new RealmStorageManager($this->inner, $this->context);
    }

    public function test_path_prefixed_when_realm_active(): void
    {
        $tenant = $this->createRealm('acme');
        $this->context->set($tenant);

        $this->storage->put('file.txt', 'data');

        $this->assertTrue($this->inner->exists('tenants/acme/file.txt'));
    }

    public function test_path_not_prefixed_when_no_realm(): void
    {
        $this->storage->put('file.txt', 'data');

        $this->assertTrue($this->inner->exists('file.txt'));
    }

    public function test_path_not_prefixed_inside_without_tenancy(): void
    {
        $tenant = $this->createRealm('acme');
        $this->context->set($tenant);

        $this->context->withoutTenancy(function () {
            $this->storage->put('file.txt', 'data');
        });

        $this->assertTrue($this->inner->exists('file.txt'));
        $this->assertFalse($this->inner->exists('tenants/acme/file.txt'));
    }

    public function test_path_prefixed_inside_run_within_without_tenancy(): void
    {
        $tenant = $this->createRealm('acme');
        $this->context->set($tenant);

        $this->context->withoutTenancy(function () use ($tenant) {
            Realm::run($tenant, function () {
                $this->storage->put('file.txt', 'data');
            });
        });

        $this->assertTrue($this->inner->exists('tenants/acme/file.txt'));
    }

    public function test_custom_storage_root_via_config(): void
    {
        config(['realm.storage.root' => 'custom-root']);

        $tenant = $this->createRealm('acme');
        $this->context->set($tenant);

        $this->storage->put('file.txt', 'data');

        $this->assertTrue($this->inner->exists('custom-root/acme/file.txt'));
    }
}

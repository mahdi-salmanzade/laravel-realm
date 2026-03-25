<?php

namespace Realm\Tests\Unit;

use Realm\Models\TenantSecret;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class TenantSecretTest extends TestCase
{
    use RealmTestHelpers;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
    }

    public function test_set_secret_encrypts_and_stores(): void
    {
        $tenant = $this->createRealm('acme');

        $tenant->setSecret('api_key', 'my-secret-value');

        $raw = TenantSecret::withoutGlobalScope('realm')
            ->where('tenant_id', $tenant->id)
            ->where('key', 'api_key')
            ->first();

        $this->assertNotNull($raw);
        $this->assertNotEquals('my-secret-value', $raw->value);
    }

    public function test_get_secret_decrypts_and_returns_correct_value(): void
    {
        $tenant = $this->createRealm('acme');

        $tenant->setSecret('api_key', 'my-secret-value');

        $this->assertEquals('my-secret-value', $tenant->getSecret('api_key'));
    }

    public function test_get_secret_returns_null_for_missing_key(): void
    {
        $tenant = $this->createRealm('acme');

        $this->assertNull($tenant->getSecret('nonexistent'));
    }

    public function test_delete_secret_removes_the_record(): void
    {
        $tenant = $this->createRealm('acme');

        $tenant->setSecret('api_key', 'my-secret-value');
        $this->assertTrue($tenant->deleteSecret('api_key'));

        $this->assertNull($tenant->getSecret('api_key'));
        $this->assertDatabaseMissing('tenant_secrets', [
            'tenant_id' => $tenant->id,
            'key' => 'api_key',
        ]);
    }

    public function test_value_is_hidden_from_to_array(): void
    {
        $tenant = $this->createRealm('acme');

        $tenant->setSecret('api_key', 'my-secret-value');

        $secret = TenantSecret::withoutGlobalScope('realm')
            ->where('tenant_id', $tenant->id)
            ->where('key', 'api_key')
            ->first();

        $array = $secret->toArray();

        $this->assertArrayNotHasKey('value', $array);
    }

    public function test_secrets_cascade_deleted_when_tenant_deleted(): void
    {
        $tenant = $this->createRealm('acme');

        $tenant->setSecret('key1', 'value1');
        $tenant->setSecret('key2', 'value2');

        $tenantId = $tenant->id;
        $tenant->delete();

        $this->assertDatabaseMissing('tenant_secrets', ['tenant_id' => $tenantId]);
    }

    public function test_set_secret_with_same_key_updates_existing_record(): void
    {
        $tenant = $this->createRealm('acme');

        $tenant->setSecret('api_key', 'original-value');
        $tenant->setSecret('api_key', 'updated-value');

        $count = TenantSecret::withoutGlobalScope('realm')
            ->where('tenant_id', $tenant->id)
            ->where('key', 'api_key')
            ->count();

        $this->assertEquals(1, $count);
        $this->assertEquals('updated-value', $tenant->getSecret('api_key'));
    }
}

<?php

namespace Realm\Tests\Unit;

use Realm\Exceptions\InvalidRealmKeyException;
use Realm\Models\Tenant;
use Realm\Tests\TestCase;

class TenantModelTest extends TestCase
{
    public function test_valid_key_acme_accepted(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        $this->assertDatabaseHas('tenants', ['key' => 'acme']);
    }

    public function test_valid_key_with_hyphen_accepted(): void
    {
        $tenant = Tenant::create(['key' => 'my-app', 'name' => 'My App']);
        $this->assertDatabaseHas('tenants', ['key' => 'my-app']);
    }

    public function test_valid_key_alphanumeric_accepted(): void
    {
        $tenant = Tenant::create(['key' => 'a1', 'name' => 'A1']);
        $this->assertDatabaseHas('tenants', ['key' => 'a1']);
    }

    public function test_valid_key_with_numbers_and_hyphens_accepted(): void
    {
        $tenant = Tenant::create(['key' => 'test-123', 'name' => 'Test 123']);
        $this->assertDatabaseHas('tenants', ['key' => 'test-123']);
    }

    public function test_uppercase_key_rejected(): void
    {
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => 'Acme', 'name' => 'Acme']);
    }

    public function test_underscore_key_rejected(): void
    {
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => 'my_app', 'name' => 'My App']);
    }

    public function test_space_in_key_rejected(): void
    {
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => 'my app', 'name' => 'My App']);
    }

    public function test_path_traversal_key_rejected(): void
    {
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => '../../etc', 'name' => 'Hack']);
    }

    public function test_hyphen_start_key_rejected(): void
    {
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => '-start', 'name' => 'Start']);
    }

    public function test_empty_key_rejected(): void
    {
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => '', 'name' => 'Empty']);
    }

    public function test_reserved_word_www_rejected(): void
    {
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => 'www', 'name' => 'WWW']);
    }

    public function test_reserved_word_api_rejected(): void
    {
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => 'api', 'name' => 'API']);
    }

    public function test_reserved_word_admin_rejected(): void
    {
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => 'admin', 'name' => 'Admin']);
    }

    public function test_reserved_word_localhost_rejected(): void
    {
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => 'localhost', 'name' => 'Localhost']);
    }

    public function test_max_length_63_enforced(): void
    {
        $longKey = str_repeat('a', 64);
        $this->expectException(InvalidRealmKeyException::class);
        Tenant::create(['key' => $longKey, 'name' => 'Long']);
    }

    public function test_exactly_63_chars_accepted(): void
    {
        $key = str_repeat('a', 63);
        $tenant = Tenant::create(['key' => $key, 'name' => 'Max Length']);
        $this->assertDatabaseHas('tenants', ['key' => $key]);
    }

    public function test_key_validation_runs_on_update(): void
    {
        $tenant = Tenant::create(['key' => 'valid', 'name' => 'Valid']);

        $this->expectException(InvalidRealmKeyException::class);
        $tenant->update(['key' => 'INVALID']);
    }

    public function test_key_validation_skipped_on_update_if_key_unchanged(): void
    {
        $tenant = Tenant::create(['key' => 'valid', 'name' => 'Valid']);
        $tenant->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('tenants', ['key' => 'valid', 'name' => 'Updated Name']);
    }

    public function test_active_defaults_to_true(): void
    {
        $tenant = Tenant::create(['key' => 'active-test', 'name' => 'Test']);
        $tenant->refresh();
        $this->assertTrue($tenant->active);
    }

    public function test_data_cast_to_array(): void
    {
        $tenant = Tenant::create([
            'key' => 'data-test',
            'name' => 'Test',
            'data' => ['plan' => 'pro', 'timezone' => 'UTC'],
        ]);

        $tenant->refresh();
        $this->assertIsArray($tenant->data);
        $this->assertEquals('pro', $tenant->data['plan']);
    }
}

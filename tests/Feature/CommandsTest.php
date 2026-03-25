<?php

namespace Realm\Tests\Feature;

use Realm\Models\Tenant;
use Realm\Tests\TestCase;

class CommandsTest extends TestCase
{
    public function test_create_command_validates_key_format(): void
    {
        $this->artisan('realm:create', ['key' => 'INVALID', 'name' => 'Test'])
            ->assertFailed();
    }

    public function test_create_command_creates_tenant(): void
    {
        $this->artisan('realm:create', ['key' => 'acme', 'name' => 'Acme Corp'])
            ->assertSuccessful();

        $this->assertDatabaseHas('tenants', ['key' => 'acme', 'name' => 'Acme Corp']);
    }

    public function test_create_command_rejects_duplicate_keys(): void
    {
        Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $this->artisan('realm:create', ['key' => 'acme', 'name' => 'Acme Again'])
            ->assertFailed();
    }

    public function test_create_command_rejects_reserved_words(): void
    {
        $this->artisan('realm:create', ['key' => 'www', 'name' => 'WWW'])
            ->assertFailed();

        $this->artisan('realm:create', ['key' => 'admin', 'name' => 'Admin'])
            ->assertFailed();
    }

    public function test_list_command_shows_tenants(): void
    {
        Tenant::create(['key' => 'acme', 'name' => 'Acme Corp']);
        Tenant::create(['key' => 'globex', 'name' => 'Globex Inc']);

        $this->artisan('realm:list')
            ->expectsOutputToContain('acme')
            ->expectsOutputToContain('globex')
            ->assertSuccessful();

        $this->assertDatabaseHas('tenants', ['key' => 'acme']);
        $this->assertDatabaseHas('tenants', ['key' => 'globex']);
    }

    public function test_list_command_shows_message_when_empty(): void
    {
        $this->artisan('realm:list')
            ->expectsOutputToContain('No realms found')
            ->assertSuccessful();
    }

    public function test_check_command_outputs_diagnostics(): void
    {
        Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $this->artisan('realm:check')
            ->expectsOutputToContain('Strategy: column')
            ->expectsOutputToContain('Strict mode: ON')
            ->expectsOutputToContain('Active tenants: 1/1')
            ->assertSuccessful();
    }

    public function test_check_command_inspects_specific_tenant(): void
    {
        Tenant::create(['key' => 'acme', 'name' => 'Acme Corp']);

        $this->artisan('realm:check', ['--realm' => 'acme'])
            ->expectsOutputToContain('acme')
            ->assertSuccessful();

        // Verify the tenant exists and has the right name
        $this->assertDatabaseHas('tenants', ['key' => 'acme', 'name' => 'Acme Corp']);
    }

    public function test_check_command_fails_for_nonexistent_tenant(): void
    {
        $this->artisan('realm:check', ['--realm' => 'nonexistent'])
            ->assertFailed();
    }

    public function test_create_command_rejects_key_starting_with_hyphen(): void
    {
        $this->artisan('realm:create', ['key' => '-bad', 'name' => 'Bad'])
            ->assertFailed();
    }

    public function test_create_command_rejects_key_with_spaces(): void
    {
        $this->artisan('realm:create', ['key' => 'has space', 'name' => 'Space'])
            ->assertFailed();
    }
}

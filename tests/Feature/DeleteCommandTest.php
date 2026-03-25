<?php

namespace Realm\Tests\Feature;

use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class DeleteCommandTest extends TestCase
{
    use RealmTestHelpers;

    public function test_requires_confirmation_non_matching_input_aborts(): void
    {
        $this->createRealm('acme');

        $this->artisan('realm:delete', ['key' => 'acme'])
            ->expectsQuestion('Type the tenant key to confirm deletion', 'wrong-input')
            ->assertFailed();

        $this->assertDatabaseHas('tenants', ['key' => 'acme']);
    }

    public function test_force_skips_prompt_and_deletes(): void
    {
        $this->createRealm('acme');

        $this->artisan('realm:delete', ['key' => 'acme', '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('tenants', ['key' => 'acme']);
    }

    public function test_error_when_tenant_not_found(): void
    {
        $this->artisan('realm:delete', ['key' => 'nonexistent'])
            ->assertFailed();
    }

    public function test_success_tenant_deleted_and_confirmed(): void
    {
        $this->createRealm('acme');

        $this->artisan('realm:delete', ['key' => 'acme'])
            ->expectsQuestion('Type the tenant key to confirm deletion', 'acme')
            ->expectsOutputToContain("Tenant 'acme' deleted successfully.")
            ->assertSuccessful();

        $this->assertDatabaseMissing('tenants', ['key' => 'acme']);
    }
}

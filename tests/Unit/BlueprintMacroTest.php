<?php

namespace Realm\Tests\Unit;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Realm\Models\Tenant;
use Realm\Tests\TestCase;

class BlueprintMacroTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Enable foreign key constraints for SQLite
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
    }

    public function test_realm_macro_creates_column(): void
    {
        Schema::create('test_blueprint', function (Blueprint $table) {
            $table->id();
            $table->realm();
            $table->timestamps();
        });

        $this->assertTrue(Schema::hasColumn('test_blueprint', 'realm_id'));
    }

    public function test_realm_macro_default_is_restrict_on_delete(): void
    {
        Schema::create('test_restrict', function (Blueprint $table) {
            $table->id();
            $table->realm();
            $table->timestamps();
        });

        // Insert a tenant and a record
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        DB::table('test_restrict')->insert([
            'realm_id' => $tenant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Try to delete the tenant - should fail due to restrict
        $this->expectException(QueryException::class);
        $tenant->delete();
    }

    public function test_realm_macro_cascade_deletes_records(): void
    {
        Schema::create('test_cascade', function (Blueprint $table) {
            $table->id();
            $table->realm(cascade: true);
            $table->timestamps();
        });

        $tenant = Tenant::create(['key' => 'cascade-test', 'name' => 'Cascade']);

        DB::table('test_cascade')->insert([
            'realm_id' => $tenant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant->delete();

        $this->assertDatabaseMissing('test_cascade', ['realm_id' => $tenant->id]);
    }
}

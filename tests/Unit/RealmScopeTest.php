<?php

namespace Realm\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Realm\Context\RealmContext;
use Realm\Facades\Realm;
use Realm\Models\Tenant;
use Realm\Scopes\RealmScope;
use Realm\Tests\TestCase;
use Realm\Traits\BelongsToRealm;

class ScopedModel extends Model
{
    use BelongsToRealm;

    protected $table = 'scoped_models';

    protected $fillable = ['name', 'realm_id'];
}

class RealmScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('scoped_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('realm_id')->nullable()->constrained('tenants');
            $table->string('name');
            $table->timestamps();
        });

        RealmScope::resetWarning();
    }

    public function test_with_active_realm_adds_where_clause(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        // Insert directly via DB to bypass both scope and creating hook
        DB::table('scoped_models')->insert([
            'name' => 'Acme Item',
            'realm_id' => $tenant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('scoped_models')->insert([
            'name' => 'Other Item',
            'realm_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(RealmContext::class)->set($tenant);

        $results = ScopedModel::all();
        $this->assertCount(1, $results);
        $this->assertEquals('Acme Item', $results->first()->name);

        app(RealmContext::class)->reset();
    }

    public function test_without_realm_strict_on_returns_empty(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        // Insert directly
        ScopedModel::withoutGlobalScope(RealmScope::class)->create([
            'name' => 'Item',
            'realm_id' => $tenant->id,
        ]);

        // No realm set, strict mode ON
        $results = ScopedModel::all();
        $this->assertCount(0, $results);
    }

    public function test_without_realm_strict_off_returns_all(): void
    {
        config(['realm.strict' => false]);

        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        ScopedModel::withoutGlobalScope(RealmScope::class)->create([
            'name' => 'Item 1',
            'realm_id' => $tenant->id,
        ]);
        ScopedModel::withoutGlobalScope(RealmScope::class)->create([
            'name' => 'Item 2',
            'realm_id' => $tenant->id,
        ]);

        // No realm set, strict OFF - should return all
        $results = ScopedModel::all();
        $this->assertCount(2, $results);
    }

    public function test_inside_without_tenancy_scope_skipped(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        ScopedModel::withoutGlobalScope(RealmScope::class)->create([
            'name' => 'Item',
            'realm_id' => $tenant->id,
        ]);

        Realm::withoutTenancy(function () {
            $results = ScopedModel::all();
            $this->assertCount(1, $results);
        });
    }

    public function test_warning_logged_once_per_request(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'RealmScope: Query attempted with no active realm');
            });

        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        ScopedModel::withoutGlobalScope(RealmScope::class)->create([
            'name' => 'Item',
            'realm_id' => $tenant->id,
        ]);

        // Multiple queries without realm - should only log once
        ScopedModel::all();
        ScopedModel::all();
        ScopedModel::all();
    }

    public function test_reset_warning_allows_logging_again(): void
    {
        Log::shouldReceive('warning')
            ->twice()
            ->withArgs(function ($message) {
                return str_contains($message, 'RealmScope: Query attempted with no active realm');
            });

        ScopedModel::all(); // First warning
        RealmScope::resetWarning();
        ScopedModel::all(); // Second warning (after reset)
    }
}

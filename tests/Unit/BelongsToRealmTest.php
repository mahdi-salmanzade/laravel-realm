<?php

namespace Realm\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Realm\Exceptions\NoActiveRealmException;
use Realm\Facades\Realm;
use Realm\Models\Tenant;
use Realm\Scopes\RealmScope;
use Realm\Tests\TestCase;
use Realm\Traits\BelongsToRealm;

class TenantModel extends Model
{
    use BelongsToRealm;

    protected $table = 'tenant_models';

    protected $fillable = ['name', 'realm_id'];
}

class BelongsToRealmTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenant_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('realm_id')->nullable()->constrained('tenants');
            $table->string('name');
            $table->timestamps();
        });

        RealmScope::resetWarning();
    }

    public function test_auto_sets_realm_id_from_context(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        Realm::run($tenant, function () use ($tenant) {
            $model = TenantModel::create(['name' => 'Test']);
            $this->assertEquals($tenant->id, $model->realm_id);
        });
    }

    public function test_throws_on_create_without_context_strict_on(): void
    {
        $this->expectException(NoActiveRealmException::class);
        TenantModel::create(['name' => 'No Realm']);
    }

    public function test_allows_create_without_context_strict_off(): void
    {
        config(['realm.strict' => false]);

        $model = TenantModel::create(['name' => 'Orphan']);
        $this->assertNull($model->realm_id);
    }

    public function test_allows_create_inside_without_tenancy(): void
    {
        Realm::withoutTenancy(function () {
            $model = TenantModel::create(['name' => 'System Record']);
            $this->assertNull($model->realm_id);
        });
    }

    public function test_without_realm_scope_returns_all(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        Realm::run($tenant, function () {
            TenantModel::create(['name' => 'Scoped Item']);
        });

        // Without realm scope - should return all records
        $results = TenantModel::withoutRealm()->get();
        $this->assertCount(1, $results);
    }

    public function test_for_realm_with_tenant_model(): void
    {
        $acme = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        $globex = Tenant::create(['key' => 'globex', 'name' => 'Globex']);

        Realm::run($acme, fn () => TenantModel::create(['name' => 'Acme Item']));
        Realm::run($globex, fn () => TenantModel::create(['name' => 'Globex Item']));

        $results = TenantModel::forRealm($acme)->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Acme Item', $results->first()->name);
    }

    public function test_for_realm_with_integer_id(): void
    {
        $acme = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        $globex = Tenant::create(['key' => 'globex', 'name' => 'Globex']);

        Realm::run($acme, fn () => TenantModel::create(['name' => 'Acme Item']));
        Realm::run($globex, fn () => TenantModel::create(['name' => 'Globex Item']));

        $results = TenantModel::forRealm($acme->id)->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Acme Item', $results->first()->name);
    }

    public function test_for_realm_does_not_accept_string(): void
    {
        $this->expectException(\TypeError::class);
        TenantModel::forRealm('acme')->get();
    }

    public function test_has_realm_relationship(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $model = Realm::run($tenant, fn () => TenantModel::create(['name' => 'Test']));

        // Need to load without scope to access
        $loaded = TenantModel::withoutRealm()->find($model->id);
        $this->assertInstanceOf(Tenant::class, $loaded->realm);
        $this->assertEquals('acme', $loaded->realm->key);
    }

    public function test_does_not_overwrite_explicitly_set_realm_id(): void
    {
        $acme = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        $globex = Tenant::create(['key' => 'globex', 'name' => 'Globex']);

        // Set context to acme but explicitly assign globex's id
        Realm::run($acme, function () use ($globex) {
            $model = TenantModel::create([
                'name' => 'Cross-assigned',
                'realm_id' => $globex->id,
            ]);
            $this->assertEquals($globex->id, $model->realm_id);
        });
    }
}

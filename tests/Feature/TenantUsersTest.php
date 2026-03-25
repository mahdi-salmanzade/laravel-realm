<?php

namespace Realm\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class User extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}

class TenantUsersTest extends TestCase
{
    use RealmTestHelpers;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('realm.user_model', User::class);

        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
    }

    public function test_attach_user_to_tenant_relationship_returns_user(): void
    {
        $tenant = $this->createRealm('acme');
        $userId = DB::table('users')->insertGetId([
            'name' => 'John',
            'email' => 'john@test.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant->users()->attach($userId);

        $users = $tenant->users;

        $this->assertCount(1, $users);
        $this->assertEquals($userId, $users->first()->id);
        $this->assertEquals('John', $users->first()->name);
    }

    public function test_detach_user_relationship_empty(): void
    {
        $tenant = $this->createRealm('acme');
        $userId = DB::table('users')->insertGetId([
            'name' => 'John',
            'email' => 'john@test.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant->users()->attach($userId);
        $tenant->users()->detach($userId);

        $this->assertCount(0, $tenant->fresh()->users);
    }

    public function test_pivot_role_stored_and_accessible(): void
    {
        $tenant = $this->createRealm('acme');
        $userId = DB::table('users')->insertGetId([
            'name' => 'John',
            'email' => 'john@test.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant->users()->attach($userId, ['role' => 'admin']);

        $user = $tenant->users->first();

        $this->assertEquals('admin', $user->pivot->role);
    }

    public function test_users_cascade_deleted_when_tenant_deleted(): void
    {
        $tenant = $this->createRealm('acme');
        $userId = DB::table('users')->insertGetId([
            'name' => 'John',
            'email' => 'john@test.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant->users()->attach($userId, ['role' => 'member']);

        $tenantId = $tenant->id;
        $tenant->delete();

        $this->assertDatabaseMissing('tenant_users', ['tenant_id' => $tenantId]);
        // User record itself should still exist
        $this->assertDatabaseHas('users', ['id' => $userId]);
    }
}

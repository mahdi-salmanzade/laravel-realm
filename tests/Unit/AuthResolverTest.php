<?php

namespace Realm\Tests\Unit;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Realm\Resolution\AuthResolver;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class AuthResolverTestUser extends Authenticatable
{
    protected $table = 'users';

    protected $fillable = ['name', 'email'];
}

class AuthResolverTest extends TestCase
{
    use RealmTestHelpers;

    private AuthResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new AuthResolver;
    }

    public function test_user_with_one_tenant_returns_tenant_key(): void
    {
        $tenant = $this->createRealm('acme');
        $user = AuthResolverTestUser::create([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        DB::table('tenant_users')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('http://example.com/dashboard');
        $request->setUserResolver(fn () => $user);

        $this->assertEquals('acme', $this->resolver->resolve($request));
    }

    public function test_user_with_multiple_tenants_returns_owner_tenant_key(): void
    {
        $memberTenant = $this->createRealm('member-org');
        $ownerTenant = $this->createRealm('owner-org');

        $user = AuthResolverTestUser::create([
            'name' => 'Jane',
            'email' => 'jane@example.com',
        ]);

        DB::table('tenant_users')->insert([
            'tenant_id' => $memberTenant->id,
            'user_id' => $user->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tenant_users')->insert([
            'tenant_id' => $ownerTenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('http://example.com/dashboard');
        $request->setUserResolver(fn () => $user);

        $this->assertEquals('owner-org', $this->resolver->resolve($request));
    }

    public function test_user_with_no_tenants_returns_null(): void
    {
        $user = AuthResolverTestUser::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);

        $request = Request::create('http://example.com/dashboard');
        $request->setUserResolver(fn () => $user);

        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_unauthenticated_returns_null(): void
    {
        $request = Request::create('http://example.com/dashboard');
        $request->setUserResolver(fn () => null);

        $this->assertNull($this->resolver->resolve($request));
    }
}

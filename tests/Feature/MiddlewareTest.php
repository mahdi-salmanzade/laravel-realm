<?php

namespace Realm\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Realm\Context\RealmContext;
use Realm\Facades\Realm;
use Realm\Http\Middleware\EnsureCentralRoute;
use Realm\Http\Middleware\ResolveRealm;
use Realm\Models\Tenant;
use Realm\Resolution\HeaderResolver;
use Realm\Scopes\RealmScope;
use Realm\Tests\TestCase;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RealmScope::resetWarning();
    }

    protected function defineRoutes($router): void
    {
        $router->middleware(ResolveRealm::class)->get('/tenant/dashboard', function () {
            return response()->json([
                'realm_key' => Realm::key(),
                'realm_id' => Realm::id(),
            ]);
        });

        $router->middleware(EnsureCentralRoute::class)->get('/central/home', function () {
            return response()->json([
                'has_realm' => Realm::check(),
            ]);
        });
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Use header resolver for easier testing
        $app['config']->set('realm.resolvers', [
            HeaderResolver::class,
        ]);
        // Clear central domains so localhost doesn't trigger the guard
        $app['config']->set('realm.central_domains', []);
    }

    public function test_resolve_realm_sets_context_for_valid_tenant(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $response = $this->get('/tenant/dashboard', ['X-Realm' => 'acme']);

        $response->assertOk();
        $response->assertJson([
            'realm_key' => 'acme',
        ]);
    }

    public function test_resolve_realm_returns_404_for_unknown_key(): void
    {
        $response = $this->get('/tenant/dashboard', ['X-Realm' => 'nonexistent']);

        $response->assertNotFound();
    }

    public function test_resolve_realm_returns_404_for_inactive_tenant(): void
    {
        Tenant::create(['key' => 'inactive', 'name' => 'Inactive', 'active' => false]);

        $response = $this->get('/tenant/dashboard', ['X-Realm' => 'inactive']);

        $response->assertNotFound();
    }

    public function test_resolve_realm_returns_404_when_no_header(): void
    {
        $response = $this->get('/tenant/dashboard');

        $response->assertNotFound();
    }

    public function test_ensure_central_route_passes_without_context(): void
    {
        $response = $this->get('/central/home');

        $response->assertOk();
        $response->assertJson(['has_realm' => false]);
    }

    public function test_ensure_central_route_resets_context_if_active(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'EnsureCentralRoute: Realm context was active');
            });

        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        // Manually set context before hitting central route
        app(RealmContext::class)->set($tenant);

        $response = $this->get('/central/home');

        $response->assertOk();
        $response->assertJson(['has_realm' => false]);
    }
}

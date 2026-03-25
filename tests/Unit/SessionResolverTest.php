<?php

namespace Realm\Tests\Unit;

use Illuminate\Http\Request;
use Realm\Resolution\SessionResolver;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class SessionResolverTest extends TestCase
{
    use RealmTestHelpers;

    private SessionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new SessionResolver;
    }

    public function test_resolves_from_session_value(): void
    {
        $request = Request::create('http://example.com/dashboard');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('realm_key', 'acme');

        $this->assertEquals('acme', $this->resolver->resolve($request));
    }

    public function test_session_not_set_returns_null(): void
    {
        $request = Request::create('http://example.com/dashboard');
        $request->setLaravelSession(app('session.store'));

        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_no_session_on_request_returns_null(): void
    {
        $request = Request::create('http://example.com/dashboard');

        $this->assertNull($this->resolver->resolve($request));
    }
}

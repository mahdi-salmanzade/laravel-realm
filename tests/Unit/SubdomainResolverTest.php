<?php

namespace Realm\Tests\Unit;

use Illuminate\Http\Request;
use Realm\Resolution\SubdomainResolver;
use Realm\Tests\TestCase;

class SubdomainResolverTest extends TestCase
{
    private SubdomainResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new SubdomainResolver;
        config(['realm.subdomain.domain' => 'myapp.com']);
        config(['realm.central_domains' => ['myapp.com']]);
    }

    public function test_resolves_subdomain(): void
    {
        $request = Request::create('http://acme.myapp.com/dashboard');
        $this->assertEquals('acme', $this->resolver->resolve($request));
    }

    public function test_central_domain_returns_null(): void
    {
        $request = Request::create('http://myapp.com/');
        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_www_in_central_domains_returns_null(): void
    {
        config(['realm.central_domains' => ['myapp.com', 'www.myapp.com']]);

        $request = Request::create('http://www.myapp.com/');
        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_multi_level_subdomain_returns_null(): void
    {
        $request = Request::create('http://a.b.myapp.com/');
        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_empty_subdomain_returns_null(): void
    {
        $request = Request::create('http://myapp.com/');
        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_different_domain_returns_null(): void
    {
        $request = Request::create('http://other.com/');
        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_subdomain_with_numbers(): void
    {
        $request = Request::create('http://tenant123.myapp.com/');
        $this->assertEquals('tenant123', $this->resolver->resolve($request));
    }
}

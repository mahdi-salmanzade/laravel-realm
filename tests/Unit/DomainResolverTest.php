<?php

namespace Realm\Tests\Unit;

use Illuminate\Http\Request;
use Realm\Resolution\DomainResolver;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class DomainResolverTest extends TestCase
{
    use RealmTestHelpers;

    private DomainResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DomainResolver;
    }

    public function test_resolves_custom_domain_match(): void
    {
        $this->createRealm('acme', ['domain' => 'acme.example.com']);

        $request = Request::create('http://acme.example.com/dashboard');

        $this->assertEquals('acme', $this->resolver->resolve($request));
    }

    public function test_no_match_returns_null(): void
    {
        $request = Request::create('http://unknown.example.com/');

        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_inactive_tenant_domain_returns_null(): void
    {
        $this->createRealm('acme', [
            'domain' => 'acme.example.com',
            'active' => false,
        ]);

        $request = Request::create('http://acme.example.com/dashboard');

        $this->assertNull($this->resolver->resolve($request));
    }
}

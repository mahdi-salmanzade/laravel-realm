<?php

namespace Realm\Tests\Unit;

use Illuminate\Http\Request;
use Realm\Resolution\HeaderResolver;
use Realm\Tests\TestCase;

class HeaderResolverTest extends TestCase
{
    private HeaderResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new HeaderResolver;
    }

    public function test_resolves_from_header(): void
    {
        $request = Request::create('/api/test');
        $request->headers->set('X-Realm', 'acme');

        $this->assertEquals('acme', $this->resolver->resolve($request));
    }

    public function test_returns_null_without_header(): void
    {
        $request = Request::create('/api/test');
        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_returns_null_for_empty_header(): void
    {
        $request = Request::create('/api/test');
        $request->headers->set('X-Realm', '');

        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_custom_header_name_from_config(): void
    {
        config(['realm.header.name' => 'X-Tenant-ID']);

        $resolver = new HeaderResolver;
        $request = Request::create('/api/test');
        $request->headers->set('X-Tenant-ID', 'globex');

        $this->assertEquals('globex', $resolver->resolve($request));
    }
}

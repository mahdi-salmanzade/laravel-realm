<?php

namespace Realm\Tests\Unit;

use Illuminate\Http\Request;
use Realm\Resolution\QueryResolver;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class QueryResolverTest extends TestCase
{
    use RealmTestHelpers;

    private QueryResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new QueryResolver;
    }

    public function test_resolves_from_query_param(): void
    {
        $request = Request::create('http://example.com/dashboard?realm=acme');

        $this->assertEquals('acme', $this->resolver->resolve($request));
    }

    public function test_missing_query_param_returns_null(): void
    {
        $request = Request::create('http://example.com/dashboard');

        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_custom_param_name_via_config(): void
    {
        config(['realm.query.parameter' => 'tenant']);
        $resolver = new QueryResolver;

        $request = Request::create('http://example.com/dashboard?tenant=globex');

        $this->assertEquals('globex', $resolver->resolve($request));
    }
}

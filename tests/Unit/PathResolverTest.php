<?php

namespace Realm\Tests\Unit;

use Illuminate\Http\Request;
use Realm\Resolution\PathResolver;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class PathResolverTest extends TestCase
{
    use RealmTestHelpers;

    private PathResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PathResolver;
    }

    public function test_resolves_from_valid_path_segment(): void
    {
        $request = Request::create('http://example.com/acme/dashboard');

        $this->assertEquals('acme', $this->resolver->resolve($request));
    }

    public function test_wrong_segment_index_returns_null(): void
    {
        config(['realm.path.segment' => 3]);
        $resolver = new PathResolver;

        $request = Request::create('http://example.com/acme/dashboard');

        $this->assertNull($resolver->resolve($request));
    }

    public function test_empty_path_returns_null(): void
    {
        $request = Request::create('http://example.com/');

        $this->assertNull($this->resolver->resolve($request));
    }

    public function test_configurable_segment_number(): void
    {
        config(['realm.path.segment' => 2]);
        $resolver = new PathResolver;

        $request = Request::create('http://example.com/app/globex/settings');

        $this->assertEquals('globex', $resolver->resolve($request));
    }
}

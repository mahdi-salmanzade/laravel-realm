<?php

namespace Realm\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Realm\Facades\Realm;
use Realm\RealmServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            RealmServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Realm' => Realm::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('realm.strict', true);
        $app['config']->set('realm.debug', false);
    }
}

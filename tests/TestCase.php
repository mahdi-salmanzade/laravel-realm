<?php

namespace Realm\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Realm\Context\RealmContext;
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
        $app['config']->set('realm.cache.prefix', false);
        $app['config']->set('realm.storage.prefix_path', false);
    }

    protected function afterRefreshingDatabase(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        app(RealmContext::class)->reset();
        parent::tearDown();
    }
}

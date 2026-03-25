<?php

namespace Realm;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;
use Realm\Console\RealmCheckCommand;
use Realm\Console\RealmCreateCommand;
use Realm\Console\RealmInstallCommand;
use Realm\Console\RealmListCommand;
use Realm\Console\RealmTestCommand;
use Realm\Context\RealmContext;
use Realm\Http\Middleware\EnsureCentralRoute;
use Realm\Http\Middleware\ResolveRealm;
use Realm\Listeners\ResetRealmContext;
use Realm\Strategies\ColumnStrategy;
use Realm\Strategies\TenancyStrategyInterface;
use Realm\Support\RealmBlueprint;

class RealmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/realm.php', 'realm');

        $this->app->singleton(RealmContext::class);

        $this->app->singleton(TenancyStrategyInterface::class, function () {
            return match (config('realm.strategy', 'column')) {
                default => new ColumnStrategy,
            };
        });

        $this->app->singleton(RealmManager::class, function ($app) {
            return new RealmManager($app->make(RealmContext::class));
        });
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerBlueprintMacro();
        $this->registerRouteMacros();
        $this->registerOctaneListener();
    }

    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/realm.php' => config_path('realm.php'),
            ], 'realm-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'realm-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RealmInstallCommand::class,
                RealmCreateCommand::class,
                RealmListCommand::class,
                RealmCheckCommand::class,
                RealmTestCommand::class,
            ]);
        }
    }

    private function registerBlueprintMacro(): void
    {
        RealmBlueprint::register();
    }

    private function registerRouteMacros(): void
    {
        Route::macro('realm', function ($callback) {
            return Route::middleware(ResolveRealm::class)->group($callback);
        });

        Route::macro('central', function ($callback) {
            return Route::middleware(EnsureCentralRoute::class)->group($callback);
        });
    }

    private function registerOctaneListener(): void
    {
        if (class_exists(RequestReceived::class)) {
            $this->app['events']->listen(
                RequestReceived::class,
                ResetRealmContext::class,
            );
        }
    }
}

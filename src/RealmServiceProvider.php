<?php

namespace Realm;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;
use Realm\Console\RealmCheckCommand;
use Realm\Console\RealmCreateCommand;
use Realm\Console\RealmDeleteCommand;
use Realm\Console\RealmExecCommand;
use Realm\Console\RealmInstallCommand;
use Realm\Console\RealmListCommand;
use Realm\Console\RealmRunForEachCommand;
use Realm\Console\RealmTestCommand;
use Realm\Context\RealmContext;
use Realm\Http\Middleware\EnsureCentralRoute;
use Realm\Http\Middleware\ResolveRealm;
use Realm\Integrations\RealmCacheManager;
use Realm\Integrations\RealmConfigManager;
use Realm\Integrations\RealmStorageManager;
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
            $strategy = config('realm.strategy', 'column');

            return match ($strategy) {
                'column' => new ColumnStrategy,
                default => throw new \InvalidArgumentException(
                    "Unknown realm strategy '{$strategy}'. Supported strategies: 'column'."
                ),
            };
        });

        $this->app->singleton(RealmManager::class, function ($app) {
            return new RealmManager($app->make(RealmContext::class));
        });

        $this->app->singleton(RealmConfigManager::class);

        $this->registerCacheIntegration();
        $this->registerStorageIntegration();
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerBlueprintMacro();
        $this->registerRouteMacros();
        $this->registerOctaneListener();
    }

    private function registerCacheIntegration(): void
    {
        if (config('realm.cache.prefix', true)) {
            $this->app->singleton('cache', function ($app) {
                return new RealmCacheManager($app);
            });
        }
    }

    private function registerStorageIntegration(): void
    {
        if (config('realm.storage.prefix_path', true)) {
            $this->app->extend('filesystem.disk', function ($disk, $app) {
                return new RealmStorageManager(
                    $disk,
                    $app->make(RealmContext::class),
                );
            });
        }
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
                RealmDeleteCommand::class,
                RealmExecCommand::class,
                RealmRunForEachCommand::class,
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

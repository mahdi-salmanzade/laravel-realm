<?php

namespace Realm\Tests\Unit;

use Realm\Integrations\RealmConfigManager;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class RealmConfigManagerTest extends TestCase
{
    use RealmTestHelpers;

    private RealmConfigManager $configManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configManager = app(RealmConfigManager::class);
    }

    public function test_config_overrides_applied_when_entering_realm(): void
    {
        $tenant = $this->createRealm('acme', [
            'data' => ['config' => ['app.name' => 'Acme']],
        ]);

        $this->configManager->apply($tenant);

        $this->assertEquals('Acme', config('app.name'));

        $this->configManager->restore();
    }

    public function test_config_restored_when_leaving_realm(): void
    {
        $original = config('app.name');

        $tenant = $this->createRealm('acme', [
            'data' => ['config' => ['app.name' => 'Acme']],
        ]);

        $this->configManager->apply($tenant);
        $this->assertEquals('Acme', config('app.name'));

        $this->configManager->restore();
        $this->assertEquals($original, config('app.name'));
    }

    public function test_nested_run_calls_restore_correctly(): void
    {
        $original = config('app.name');

        $acme = $this->createRealm('acme', [
            'data' => ['config' => ['app.name' => 'Acme']],
        ]);

        $globex = $this->createRealm('globex', [
            'data' => ['config' => ['app.name' => 'Globex']],
        ]);

        $this->configManager->apply($acme);
        $this->assertEquals('Acme', config('app.name'));

        $this->configManager->apply($globex);
        $this->assertEquals('Globex', config('app.name'));

        $this->configManager->restore();
        $this->assertEquals('Acme', config('app.name'));

        $this->configManager->restore();
        $this->assertEquals($original, config('app.name'));
    }

    public function test_empty_config_data_is_noop(): void
    {
        $original = config('app.name');

        $tenant = $this->createRealm('acme', [
            'data' => [],
        ]);

        $this->configManager->apply($tenant);

        $this->assertEquals($original, config('app.name'));

        $this->configManager->restore();

        $this->assertEquals($original, config('app.name'));
    }

    public function test_apply_only_modifies_specified_keys(): void
    {
        $originalTimezone = config('app.timezone');

        $tenant = $this->createRealm('acme', [
            'data' => ['config' => ['app.name' => 'Acme']],
        ]);

        $this->configManager->apply($tenant);

        $this->assertEquals('Acme', config('app.name'));
        $this->assertEquals($originalTimezone, config('app.timezone'));

        $this->configManager->restore();
    }
}

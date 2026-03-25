<?php

namespace Realm\Tests\Unit;

use Realm\Context\RealmContext;
use Realm\Models\Tenant;
use Realm\Scopes\RealmScope;
use Realm\Tests\TestCase;

class RealmContextTest extends TestCase
{
    private RealmContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = app(RealmContext::class);
        RealmScope::resetWarning();
    }

    public function test_set_and_get(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $this->context->set($tenant);
        $this->assertSame($tenant->id, $this->context->get()->id);
    }

    public function test_id_returns_tenant_id(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $this->context->set($tenant);
        $this->assertEquals($tenant->id, $this->context->id());
    }

    public function test_key_returns_tenant_key(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $this->context->set($tenant);
        $this->assertEquals('acme', $this->context->key());
    }

    public function test_check_returns_true_when_tenant_set(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $this->context->set($tenant);
        $this->assertTrue($this->context->check());
    }

    public function test_check_returns_false_when_no_tenant(): void
    {
        $this->assertFalse($this->context->check());
    }

    public function test_id_returns_null_when_no_tenant(): void
    {
        $this->assertNull($this->context->id());
    }

    public function test_key_returns_null_when_no_tenant(): void
    {
        $this->assertNull($this->context->key());
    }

    public function test_get_returns_null_when_no_tenant(): void
    {
        $this->assertNull($this->context->get());
    }

    public function test_reset_clears_tenant(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $this->context->set($tenant);
        $this->context->reset();

        $this->assertNull($this->context->get());
        $this->assertFalse($this->context->check());
        $this->assertFalse($this->context->isTenancyDisabled());
    }

    public function test_run_switches_context_and_restores(): void
    {
        $acme = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        $globex = Tenant::create(['key' => 'globex', 'name' => 'Globex']);

        $this->context->set($acme);

        $this->context->run($globex, function () use ($globex) {
            $this->assertEquals($globex->id, $this->context->id());
        });

        $this->assertEquals($acme->id, $this->context->id());
    }

    public function test_run_restores_on_exception(): void
    {
        $acme = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        $globex = Tenant::create(['key' => 'globex', 'name' => 'Globex']);

        $this->context->set($acme);

        try {
            $this->context->run($globex, function () {
                throw new \RuntimeException('Test error');
            });
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertEquals($acme->id, $this->context->id());
    }

    public function test_run_with_string_key_resolves_from_database(): void
    {
        $acme = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $this->context->run('acme', function () {
            $this->assertEquals('acme', $this->context->key());
        });
    }

    public function test_run_inside_without_tenancy_re_enables_tenancy(): void
    {
        $acme = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $this->context->withoutTenancy(function () use ($acme) {
            $this->assertTrue($this->context->isTenancyDisabled());

            $this->context->run($acme, function () {
                // run() should re-enable tenancy
                $this->assertFalse($this->context->isTenancyDisabled());
                $this->assertTrue($this->context->check());
            });

            // After run(), withoutTenancy should be restored
            $this->assertTrue($this->context->isTenancyDisabled());
        });
    }

    public function test_without_tenancy_sets_disabled(): void
    {
        $this->context->withoutTenancy(function () {
            $this->assertTrue($this->context->isTenancyDisabled());
        });
    }

    public function test_without_tenancy_restores_previous_state(): void
    {
        $this->assertFalse($this->context->isTenancyDisabled());

        $this->context->withoutTenancy(function () {
            $this->assertTrue($this->context->isTenancyDisabled());
        });

        $this->assertFalse($this->context->isTenancyDisabled());
    }

    public function test_nested_without_tenancy_restores_correctly(): void
    {
        $this->context->withoutTenancy(function () {
            $this->assertTrue($this->context->isTenancyDisabled());

            $this->context->withoutTenancy(function () {
                $this->assertTrue($this->context->isTenancyDisabled());
            });

            $this->assertTrue($this->context->isTenancyDisabled());
        });

        $this->assertFalse($this->context->isTenancyDisabled());
    }

    public function test_check_returns_false_when_tenancy_disabled(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme']);
        $this->context->set($tenant);

        $this->context->withoutTenancy(function () {
            // Tenant is set but tenancy is disabled
            $this->assertFalse($this->context->check());
            $this->assertNotNull($this->context->get()); // Tenant still there
        });
    }

    public function test_run_returns_callback_value(): void
    {
        $acme = Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        $result = $this->context->run($acme, fn () => 42);
        $this->assertEquals(42, $result);
    }

    public function test_without_tenancy_returns_callback_value(): void
    {
        $result = $this->context->withoutTenancy(fn () => 'hello');
        $this->assertEquals('hello', $result);
    }
}

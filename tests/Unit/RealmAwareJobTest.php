<?php

namespace Realm\Tests\Unit;

use Realm\Exceptions\NoActiveRealmException;
use Realm\Facades\Realm;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;
use Realm\Traits\RealmAwareJob;

class TestRealmJob
{
    use RealmAwareJob;

    public function __construct()
    {
        $this->initializeRealmAwareJob();
    }
}

class RealmAwareJobTest extends TestCase
{
    use RealmTestHelpers;

    public function test_job_captures_realm_id_when_realm_active(): void
    {
        $tenant = $this->createRealm('acme');
        $this->actingAsRealm($tenant);

        $job = new TestRealmJob;

        $this->assertEquals($tenant->id, $job->realmId);
    }

    public function test_throws_no_active_realm_exception_in_strict_mode_without_context(): void
    {
        $this->app['config']->set('realm.strict', true);

        $this->expectException(NoActiveRealmException::class);

        new TestRealmJob;
    }

    public function test_does_not_throw_inside_without_tenancy(): void
    {
        $this->app['config']->set('realm.strict', true);

        $job = Realm::withoutTenancy(function () {
            return new TestRealmJob;
        });

        $this->assertNull($job->realmId);
    }

    public function test_realm_id_is_null_when_no_context_and_strict_false(): void
    {
        $this->app['config']->set('realm.strict', false);

        $job = new TestRealmJob;

        $this->assertNull($job->realmId);
    }

    public function test_realm_id_captures_correct_tenant_when_switching_context(): void
    {
        $acme = $this->createRealm('acme');
        $globex = $this->createRealm('globex');

        $this->actingAsRealm($acme);
        $jobA = new TestRealmJob;

        $jobB = Realm::run($globex, function () {
            return new TestRealmJob;
        });

        $this->assertEquals($acme->id, $jobA->realmId);
        $this->assertEquals($globex->id, $jobB->realmId);
    }
}

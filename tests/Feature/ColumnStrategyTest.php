<?php

namespace Realm\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Realm\Facades\Realm;
use Realm\Scopes\RealmScope;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;
use Realm\Traits\BelongsToRealm;

class Project extends Model
{
    use BelongsToRealm;

    protected $table = 'projects';

    protected $fillable = ['name', 'realm_id'];
}

class ColumnStrategyTest extends TestCase
{
    use RealmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('realm_id')->nullable()->constrained('tenants');
            $table->string('name');
            $table->timestamps();
        });

        RealmScope::resetWarning();
    }

    public function test_records_isolated_between_tenants(): void
    {
        $acme = $this->createRealm('acme');
        $globex = $this->createRealm('globex');

        Realm::run($acme, fn () => Project::create(['name' => 'Acme Project']));
        Realm::run($globex, fn () => Project::create(['name' => 'Globex Project']));

        // Verify isolation
        Realm::run($acme, function () {
            $projects = Project::all();
            $this->assertCount(1, $projects);
            $this->assertEquals('Acme Project', $projects->first()->name);
        });

        Realm::run($globex, function () {
            $projects = Project::all();
            $this->assertCount(1, $projects);
            $this->assertEquals('Globex Project', $projects->first()->name);
        });
    }

    public function test_tenant_a_cannot_see_tenant_b_records(): void
    {
        $acme = $this->createRealm('acme');
        $globex = $this->createRealm('globex');

        Realm::run($acme, fn () => Project::create(['name' => 'Secret Project']));

        Realm::run($globex, function () {
            $this->assertCount(0, Project::where('name', 'Secret Project')->get());
        });
    }

    public function test_without_realm_returns_all_records(): void
    {
        $acme = $this->createRealm('acme');
        $globex = $this->createRealm('globex');

        Realm::run($acme, fn () => Project::create(['name' => 'Acme Project']));
        Realm::run($globex, fn () => Project::create(['name' => 'Globex Project']));

        $allProjects = Project::withoutRealm()->get();
        $this->assertCount(2, $allProjects);
    }

    public function test_for_realm_returns_correct_subset(): void
    {
        $acme = $this->createRealm('acme');
        $globex = $this->createRealm('globex');

        Realm::run($acme, fn () => Project::create(['name' => 'A1']));
        Realm::run($acme, fn () => Project::create(['name' => 'A2']));
        Realm::run($globex, fn () => Project::create(['name' => 'G1']));

        $acmeProjects = Project::forRealm($acme)->get();
        $this->assertCount(2, $acmeProjects);

        $globexProjects = Project::forRealm($globex)->get();
        $this->assertCount(1, $globexProjects);
    }

    public function test_run_properly_switches_and_restores_context(): void
    {
        $acme = $this->createRealm('acme');
        $globex = $this->createRealm('globex');

        Realm::run($acme, function () use ($acme) {
            $this->assertEquals($acme->id, Realm::id());
        });

        // No context should be active after run()
        $this->assertNull(Realm::current());
    }

    public function test_without_tenancy_disables_scoping(): void
    {
        $acme = $this->createRealm('acme');

        Realm::run($acme, fn () => Project::create(['name' => 'Scoped']));

        Realm::withoutTenancy(function () {
            $projects = Project::all();
            $this->assertCount(1, $projects);
        });
    }

    public function test_run_inside_without_tenancy_re_enables_scoping(): void
    {
        $acme = $this->createRealm('acme');
        $globex = $this->createRealm('globex');

        Realm::run($acme, fn () => Project::create(['name' => 'Acme Only']));
        Realm::run($globex, fn () => Project::create(['name' => 'Globex Only']));

        Realm::withoutTenancy(function () use ($acme) {
            // Without tenancy - sees all
            $this->assertCount(2, Project::all());

            // run() inside withoutTenancy re-enables scoping
            Realm::run($acme, function () {
                $projects = Project::all();
                $this->assertCount(1, $projects);
                $this->assertEquals('Acme Only', $projects->first()->name);
            });

            // Back to withoutTenancy
            $this->assertCount(2, Project::all());
        });
    }

    public function test_strict_mode_returns_empty_without_context(): void
    {
        $acme = $this->createRealm('acme');

        Realm::run($acme, fn () => Project::create(['name' => 'Test']));

        // No context, strict mode on
        $this->assertCount(0, Project::all());
    }

    public function test_realm_id_auto_set_on_create(): void
    {
        $acme = $this->createRealm('acme');

        $project = Realm::run($acme, fn () => Project::create(['name' => 'Auto ID']));

        $this->assertEquals($acme->id, $project->realm_id);
    }

    public function test_test_helpers_work(): void
    {
        $acme = $this->createRealm('acme');
        $globex = $this->createRealm('globex');

        $this->actingAsRealm($acme, fn () => Project::create(['name' => 'Acme Project']));
        $this->actingAsRealm($globex, fn () => Project::create(['name' => 'Globex Project']));

        $this->assertRealmCount($acme, Project::class, 1);
        $this->assertRealmHas($acme, Project::class, ['name' => 'Acme Project']);
        $this->assertRealmMissing($acme, Project::class, ['name' => 'Globex Project']);
    }

    public function test_multiple_records_per_tenant(): void
    {
        $acme = $this->createRealm('acme');

        Realm::run($acme, function () {
            Project::create(['name' => 'P1']);
            Project::create(['name' => 'P2']);
            Project::create(['name' => 'P3']);
        });

        $this->assertRealmCount($acme, Project::class, 3);
    }
}

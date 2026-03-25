<?php

namespace Realm\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Realm\Context\RealmContext;
use Realm\Events\RealmActivated;
use Realm\Events\RealmCreated;
use Realm\Events\RealmCreating;
use Realm\Events\RealmDeactivated;
use Realm\Events\RealmDeleted;
use Realm\Events\RealmDeleting;
use Realm\Events\RealmIdentified;
use Realm\Events\RealmNotFound;
use Realm\Events\RealmSwitched;
use Realm\Facades\Realm;
use Realm\Models\Tenant;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class EventWiringTest extends TestCase
{
    use RealmTestHelpers;

    public function test_creating_event_fires_when_tenant_is_created(): void
    {
        Event::fake([RealmCreating::class]);

        Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        Event::assertDispatched(RealmCreating::class, function (RealmCreating $event) {
            return $event->tenant->key === 'acme';
        });
    }

    public function test_created_event_fires_after_tenant_is_saved(): void
    {
        Event::fake([RealmCreated::class]);

        Tenant::create(['key' => 'acme', 'name' => 'Acme']);

        Event::assertDispatched(RealmCreated::class, function (RealmCreated $event) {
            return $event->tenant->key === 'acme' && $event->tenant->exists;
        });
    }

    public function test_deleting_and_deleted_events_fire_when_tenant_is_destroyed(): void
    {
        $tenant = $this->createRealm('acme');

        Event::fake([RealmDeleting::class, RealmDeleted::class]);

        $tenant->delete();

        Event::assertDispatched(RealmDeleting::class, function (RealmDeleting $event) {
            return $event->tenant->key === 'acme';
        });

        Event::assertDispatched(RealmDeleted::class, function (RealmDeleted $event) {
            return $event->tenant->key === 'acme';
        });
    }

    public function test_activated_event_fires_when_tenant_becomes_active(): void
    {
        $tenant = Tenant::create(['key' => 'acme', 'name' => 'Acme', 'active' => false]);

        Event::fake([RealmActivated::class]);

        $tenant->update(['active' => true]);

        Event::assertDispatched(RealmActivated::class, function (RealmActivated $event) {
            return $event->tenant->key === 'acme' && $event->tenant->active === true;
        });
    }

    public function test_deactivated_event_fires_when_tenant_becomes_inactive(): void
    {
        $tenant = $this->createRealm('acme');

        Event::fake([RealmDeactivated::class]);

        $tenant->update(['active' => false]);

        Event::assertDispatched(RealmDeactivated::class, function (RealmDeactivated $event) {
            return $event->tenant->key === 'acme' && $event->tenant->active === false;
        });
    }

    public function test_switched_event_fires_when_context_changes_to_different_tenant(): void
    {
        $acme = $this->createRealm('acme');
        $globex = $this->createRealm('globex');

        $context = app(RealmContext::class);

        Event::fake([RealmSwitched::class]);

        $context->set($acme);
        $context->set($globex);

        Event::assertDispatched(RealmSwitched::class, fn (RealmSwitched $event) => $event->previous === null && $event->current->id === $acme->id);

        Event::assertDispatched(RealmSwitched::class, fn (RealmSwitched $event) => $event->previous?->id === $acme->id && $event->current->id === $globex->id);
    }

    public function test_switched_event_does_not_fire_when_setting_same_tenant(): void
    {
        $acme = $this->createRealm('acme');
        $context = app(RealmContext::class);

        $context->set($acme);

        Event::fake([RealmSwitched::class]);

        $context->set($acme);

        Event::assertNotDispatched(RealmSwitched::class);
    }

    public function test_identified_and_not_found_events_can_be_dispatched(): void
    {
        $tenant = $this->createRealm('acme');

        Event::fake([RealmIdentified::class, RealmNotFound::class]);

        RealmIdentified::dispatch($tenant);
        RealmNotFound::dispatch('missing-key');

        Event::assertDispatched(RealmIdentified::class, function (RealmIdentified $event) {
            return $event->tenant->key === 'acme';
        });

        Event::assertDispatched(RealmNotFound::class, function (RealmNotFound $event) {
            return $event->key === 'missing-key';
        });
    }

    public function test_switched_event_fires_when_run_exits(): void
    {
        Event::fake([RealmSwitched::class]);

        $alpha = $this->createRealm('alpha');
        $beta = $this->createRealm('beta');
        $context = app(RealmContext::class);

        $context->set($alpha); // fires once (null -> alpha)

        Realm::run($beta, function () {
            // fires on entry (alpha -> beta)
        });
        // fires on exit (beta -> alpha)

        Event::assertDispatched(RealmSwitched::class, 3);
    }
}

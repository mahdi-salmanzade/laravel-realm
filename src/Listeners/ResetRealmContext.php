<?php

namespace Realm\Listeners;

use Realm\Context\RealmContext;
use Realm\Integrations\RealmConfigManager;
use Realm\Scopes\RealmScope;
use Realm\Strategies\TenancyStrategyInterface;

class ResetRealmContext
{
    public function handle(object $event): void
    {
        app(RealmConfigManager::class)->reset();
        app(RealmContext::class)->reset();
        app(TenancyStrategyInterface::class)->disconnect();
        RealmScope::resetWarning();
    }
}

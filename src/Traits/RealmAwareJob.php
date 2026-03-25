<?php

namespace Realm\Traits;

use Realm\Exceptions\NoActiveRealmException;
use Realm\Facades\Realm;
use Realm\Integrations\RealmQueueMiddleware;

trait RealmAwareJob
{
    public ?int $realmId = null;

    public int $realmRetries = 0;

    public function initializeRealmAwareJob(): void
    {
        $currentId = Realm::id();

        if ($currentId !== null) {
            $this->realmId = $currentId;
        } elseif (config('realm.strict', true) && ! Realm::isTenancyDisabled()) {
            throw new NoActiveRealmException(
                'Cannot dispatch '.class_basename($this).' without an active realm. '
                .'Use Realm::run() to set context, or dispatch inside Realm::withoutTenancy().'
            );
        }
    }

    /**
     * @return array<int, object>
     */
    public function realmMiddleware(): array
    {
        return [new RealmQueueMiddleware];
    }
}

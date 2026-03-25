<?php

namespace Realm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Realm\Models\Tenant;

class RealmCreating
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
    ) {}
}

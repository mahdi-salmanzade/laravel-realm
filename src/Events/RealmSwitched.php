<?php

namespace Realm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Realm\Models\Tenant;

class RealmSwitched
{
    use Dispatchable;

    public function __construct(
        public readonly ?Tenant $previous,
        public readonly ?Tenant $current,
    ) {}
}

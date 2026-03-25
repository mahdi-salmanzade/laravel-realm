<?php

namespace Realm\Events;

use Illuminate\Foundation\Events\Dispatchable;

class RealmNotFound
{
    use Dispatchable;

    public function __construct(
        public readonly ?string $key,
    ) {}
}

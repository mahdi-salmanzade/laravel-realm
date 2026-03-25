<?php

namespace Realm\Strategies;

use Realm\Models\Tenant;

class ColumnStrategy implements TenancyStrategyInterface
{
    public function switch(Tenant $tenant): void
    {
        // No-op for column strategy — no connection switching needed
    }

    public function disconnect(): void
    {
        // No-op for column strategy
    }
}

<?php

namespace Realm\Strategies;

use Realm\Models\Tenant;

interface TenancyStrategyInterface
{
    public function switch(Tenant $tenant): void;

    public function disconnect(): void;
}

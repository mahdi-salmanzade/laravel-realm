<?php

namespace Realm;

use Closure;
use Realm\Context\RealmContext;
use Realm\Models\Tenant;

class RealmManager
{
    public function __construct(
        private readonly RealmContext $context,
    ) {}

    public function current(): ?Tenant
    {
        return $this->context->get();
    }

    public function id(): ?int
    {
        return $this->context->id();
    }

    public function key(): ?string
    {
        return $this->context->key();
    }

    public function check(): bool
    {
        return $this->context->check();
    }

    public function isTenancyDisabled(): bool
    {
        return $this->context->isTenancyDisabled();
    }

    public function run(string|Tenant $tenant, Closure $callback): mixed
    {
        return $this->context->run($tenant, $callback);
    }

    public function withoutTenancy(Closure $callback): mixed
    {
        return $this->context->withoutTenancy($callback);
    }
}

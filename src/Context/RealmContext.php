<?php

namespace Realm\Context;

use Closure;
use Realm\Models\Tenant;
use Realm\Scopes\RealmScope;
use Realm\Strategies\TenancyStrategyInterface;

class RealmContext
{
    private ?Tenant $current = null;

    private bool $tenancyDisabled = false;

    public function set(Tenant $tenant): void
    {
        $this->current = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->current;
    }

    public function id(): ?int
    {
        return $this->current?->id;
    }

    public function key(): ?string
    {
        return $this->current?->key;
    }

    public function check(): bool
    {
        return $this->current !== null && ! $this->tenancyDisabled;
    }

    public function isTenancyDisabled(): bool
    {
        return $this->tenancyDisabled;
    }

    public function reset(): void
    {
        $this->current = null;
        $this->tenancyDisabled = false;
        RealmScope::resetWarning();
    }

    public function run(string|Tenant $tenant, Closure $callback): mixed
    {
        $tenant = $tenant instanceof Tenant
            ? $tenant
            : Tenant::where('key', $tenant)->firstOrFail();

        $previous = $this->current;
        $previousDisabled = $this->tenancyDisabled;

        $this->set($tenant);
        $this->tenancyDisabled = false;

        $strategy = app(TenancyStrategyInterface::class);
        $strategy->switch($tenant);

        try {
            return $callback();
        } finally {
            $this->current = $previous;
            $this->tenancyDisabled = $previousDisabled;
            $previous ? $strategy->switch($previous) : $strategy->disconnect();
        }
    }

    public function withoutTenancy(Closure $callback): mixed
    {
        $previous = $this->tenancyDisabled;
        $this->tenancyDisabled = true;

        try {
            return $callback();
        } finally {
            $this->tenancyDisabled = $previous;
        }
    }
}

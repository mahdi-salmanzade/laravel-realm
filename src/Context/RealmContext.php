<?php

namespace Realm\Context;

use Closure;
use Realm\Events\RealmSwitched;
use Realm\Integrations\RealmConfigManager;
use Realm\Models\Tenant;
use Realm\Scopes\RealmScope;
use Realm\Strategies\TenancyStrategyInterface;

class RealmContext
{
    private ?Tenant $current = null;

    private bool $tenancyDisabled = false;

    public function set(Tenant $tenant): void
    {
        $previous = $this->current;
        $this->current = $tenant;

        if ($previous?->id !== $tenant->id) {
            RealmSwitched::dispatch($previous, $tenant);
        }
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

    public function forget(): void
    {
        $this->current = null;
    }

    public function run(string|Tenant $tenant, Closure $callback): mixed
    {
        $tenantModel = $tenant instanceof Tenant
            ? $tenant
            : Tenant::where('key', $tenant)->where('active', true)->firstOrFail();

        $previous = $this->current;
        $previousDisabled = $this->tenancyDisabled;

        $this->set($tenantModel);
        $this->tenancyDisabled = false;

        $strategy = app(TenancyStrategyInterface::class);
        $strategy->switch($tenantModel);

        $configManager = app(RealmConfigManager::class);
        $configManager->apply($tenantModel);

        try {
            return $callback();
        } finally {
            $configManager->restore();
            $exitingTenant = $this->current;
            $this->current = $previous;
            $this->tenancyDisabled = $previousDisabled;

            if ($exitingTenant?->id !== $previous?->id) {
                RealmSwitched::dispatch($exitingTenant, $previous);
            }

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

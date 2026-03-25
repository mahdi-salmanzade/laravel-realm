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

    public function forget(): void
    {
        $this->context->forget();
    }

    // -------------------------------------------------------
    // Secrets (facade proxies)
    // -------------------------------------------------------

    public function getSecret(string|Tenant $tenant, string $key): ?string
    {
        return $this->resolveTenant($tenant)->getSecret($key);
    }

    public function setSecret(string|Tenant $tenant, string $key, string $value): void
    {
        $this->resolveTenant($tenant)->setSecret($key, $value);
    }

    public function deleteSecret(string|Tenant $tenant, string $key): bool
    {
        return $this->resolveTenant($tenant)->deleteSecret($key);
    }

    private function resolveTenant(string|Tenant $tenant): Tenant
    {
        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        return Tenant::where('key', $tenant)->firstOrFail();
    }
}

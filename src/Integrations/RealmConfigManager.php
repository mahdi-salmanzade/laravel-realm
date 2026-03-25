<?php

namespace Realm\Integrations;

use Illuminate\Support\Facades\Log;
use Realm\Models\Tenant;

class RealmConfigManager
{
    /** @var array<int, array<string, mixed>> */
    private array $configStack = [];

    public function apply(Tenant $tenant): void
    {
        $overrides = $tenant->data['config'] ?? [];

        if (empty($overrides)) {
            $this->configStack[] = [];

            return;
        }

        $snapshot = [];

        foreach ($overrides as $key => $value) {
            $snapshot[$key] = config($key);
            config([$key => $value]);
        }

        $this->configStack[] = $snapshot;
    }

    public function restore(): void
    {
        if (empty($this->configStack)) {
            Log::warning('RealmConfigManager::restore() called with empty config stack. This indicates a mismatched apply()/restore() pair.');

            return;
        }

        $snapshot = array_pop($this->configStack);

        if ($snapshot) {
            foreach ($snapshot as $key => $value) {
                config([$key => $value]);
            }
        }
    }

    public function reset(): void
    {
        while (! empty($this->configStack)) {
            $this->restore();
        }
    }
}

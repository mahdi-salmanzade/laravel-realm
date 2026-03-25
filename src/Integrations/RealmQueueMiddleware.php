<?php

namespace Realm\Integrations;

use Closure;
use Illuminate\Support\Facades\Log;
use Realm\Context\RealmContext;
use Realm\Exceptions\RealmNotFoundException;
use Realm\Models\Tenant;
use Realm\Strategies\TenancyStrategyInterface;

class RealmQueueMiddleware
{
    public function handle(object $job, Closure $next): void
    {
        if (! property_exists($job, 'realmId') || $job->realmId === null) {
            $next($job);

            return;
        }

        $tenant = Tenant::find($job->realmId);

        if ($tenant === null) {
            Log::warning('RealmQueueMiddleware: Tenant not found. Failing job.', [
                'tenant_id' => $job->realmId,
                'job' => get_class($job),
            ]);

            $job->fail(new RealmNotFoundException(
                "Tenant #{$job->realmId} no longer exists."
            ));

            return;
        }

        if (! $tenant->active) {
            $maxRetries = (int) config('realm.queue.max_inactive_retries', 3);
            $retries = property_exists($job, 'realmRetries') ? $job->realmRetries : 0;

            if ($retries >= $maxRetries) {
                Log::warning('RealmQueueMiddleware: Max inactive retries reached. Failing job.', [
                    'tenant_key' => $tenant->key,
                    'job' => get_class($job),
                    'retries' => $retries,
                ]);

                $job->fail(new RealmNotFoundException(
                    "Tenant '{$tenant->key}' still inactive after {$retries} retries."
                ));

                return;
            }

            if (property_exists($job, 'realmRetries')) {
                $job->realmRetries = $retries + 1;
            }

            $delay = (int) config('realm.queue.inactive_delay', 300);

            Log::info('RealmQueueMiddleware: Tenant inactive. Releasing job.', [
                'tenant_key' => $tenant->key,
                'job' => get_class($job),
                'delay' => $delay,
                'retry' => $retries + 1,
                'max_retries' => $maxRetries,
            ]);

            $job->release($delay);

            return;
        }

        $context = app(RealmContext::class);
        $strategy = app(TenancyStrategyInterface::class);
        $configManager = app(RealmConfigManager::class);

        $context->set($tenant);
        $strategy->switch($tenant);
        $configManager->apply($tenant);

        try {
            $next($job);
        } finally {
            $configManager->restore();
            $strategy->disconnect();
            $context->reset();
        }
    }
}

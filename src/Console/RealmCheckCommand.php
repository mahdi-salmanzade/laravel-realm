<?php

namespace Realm\Console;

use Illuminate\Console\Command;
use Laravel\Octane\Events\RequestReceived;
use Realm\Models\Tenant;

class RealmCheckCommand extends Command
{
    protected $signature = 'realm:check {--realm= : Inspect a specific tenant by key}';

    protected $description = 'Run diagnostic checks on Realm configuration';

    public function handle(): int
    {
        if ($realmKey = $this->option('realm')) {
            return $this->inspectTenant($realmKey);
        }

        return $this->runDiagnostics();
    }

    private function runDiagnostics(): int
    {
        $this->info('Realm Diagnostics');
        $this->info('=================');
        $this->newLine();

        // Strategy
        $strategy = config('realm.strategy', 'column');
        $this->line("  Strategy: {$strategy}");

        // Resolvers
        $resolvers = config('realm.resolvers', []);
        foreach ($resolvers as $resolver) {
            $this->line('  Resolver: '.class_basename($resolver));
        }

        // Strict mode
        $strict = config('realm.strict', true) ? 'ON' : 'OFF';
        $this->line("  Strict mode: {$strict}");

        // Tenant count
        $activeCount = Tenant::where('active', true)->count();
        $totalCount = Tenant::count();
        $this->line("  Active tenants: {$activeCount}/{$totalCount}");

        // Octane listener
        $octaneAvailable = class_exists(RequestReceived::class);
        $this->line('  Octane listener: '.($octaneAvailable ? 'available' : 'not needed (Octane not installed)'));

        $this->newLine();

        return self::SUCCESS;
    }

    private function inspectTenant(string $key): int
    {
        $tenant = Tenant::where('key', $key)->first();

        if (! $tenant) {
            $this->error("Tenant '{$key}' not found.");

            return self::FAILURE;
        }

        $this->info("Tenant: {$tenant->key} ({$tenant->name})");
        $this->line("  ID: {$tenant->id}");
        $this->line('  Active: '.($tenant->active ? 'Yes' : 'No'));
        $this->line('  Domain: '.($tenant->domain ?? '-'));
        $this->line("  Created: {$tenant->created_at}");

        if ($tenant->data) {
            $this->line('  Data keys: '.implode(', ', array_keys($tenant->data)));
        }

        return self::SUCCESS;
    }
}

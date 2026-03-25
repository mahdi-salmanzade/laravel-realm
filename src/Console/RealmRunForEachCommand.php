<?php

namespace Realm\Console;

use Illuminate\Console\Command;
use Realm\Facades\Realm;
use Realm\Models\Tenant;

class RealmRunForEachCommand extends Command
{
    protected $signature = 'realm:run-for-each {artisan_command} {--only=* : Only run for specific tenant keys} {--except=* : Skip specific tenant keys}';

    protected $description = 'Run an artisan command for each active tenant';

    public function handle(): int
    {
        $command = $this->argument('artisan_command');
        $only = $this->option('only');
        $except = $this->option('except');

        $query = Tenant::where('active', true)->orderBy('key');

        if (! empty($only)) {
            $query->whereIn('key', $only);
        }

        if (! empty($except)) {
            $query->whereNotIn('key', $except);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');

            return self::SUCCESS;
        }

        $this->info("Running '{$command}' for {$tenants->count()} tenant(s)...");
        $this->newLine();

        $failed = 0;

        /** @var Tenant $tenant */
        foreach ($tenants as $tenant) {
            $this->line("  [{$tenant->key}] Running...");

            try {
                $exitCode = Realm::run($tenant, fn () => $this->call($command));

                if ($exitCode !== self::SUCCESS) {
                    $this->error("  [{$tenant->key}] Command returned non-zero exit code: {$exitCode}");
                    $failed++;
                } else {
                    $this->info("  [{$tenant->key}] Done.");
                }
            } catch (\Throwable $e) {
                $this->error("  [{$tenant->key}] Failed: {$e->getMessage()}");
                $failed++;
            }

            $this->newLine();
        }

        if ($failed > 0) {
            $this->error("{$failed} tenant(s) failed.");

            return self::FAILURE;
        }

        $this->info('All tenants completed successfully.');

        return self::SUCCESS;
    }
}

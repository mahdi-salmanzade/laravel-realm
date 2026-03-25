<?php

namespace Realm\Console;

use Illuminate\Console\Command;
use Realm\Facades\Realm;
use Realm\Models\Tenant;

class RealmExecCommand extends Command
{
    protected $signature = 'realm:exec {key} {command}';

    protected $description = 'Run an artisan command in the context of a specific tenant';

    public function handle(): int
    {
        $key = $this->argument('key');
        $tenant = Tenant::where('key', $key)->first();

        if (! $tenant) {
            $this->error("Tenant '{$key}' not found.");

            return self::FAILURE;
        }

        if (! $tenant->active) {
            $this->error("Tenant '{$key}' is not active.");

            return self::FAILURE;
        }

        $command = $this->argument('command');

        $this->info("Running '{$command}' for tenant '{$tenant->key}'...");

        $exitCode = Realm::run($tenant, fn () => $this->call($command));

        if ($exitCode !== self::SUCCESS) {
            $this->error("Command '{$command}' exited with code {$exitCode}.");
        }

        return $exitCode;
    }
}

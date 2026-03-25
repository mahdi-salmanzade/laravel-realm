<?php

namespace Realm\Console;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Realm\Models\Tenant;

class RealmDeleteCommand extends Command
{
    protected $signature = 'realm:delete {key} {--force : Skip confirmation prompt}';

    protected $description = 'Delete a tenant realm and its associated data';

    public function handle(): int
    {
        $key = $this->argument('key');
        $tenant = Tenant::where('key', $key)->first();

        if (! $tenant) {
            $this->error("Tenant '{$key}' not found.");

            return self::FAILURE;
        }

        $this->showDestructionSummary($tenant);

        if (! $this->option('force')) {
            $confirm = $this->ask('Type the tenant key to confirm deletion');

            if ($confirm !== $tenant->key) {
                $this->error('Confirmation did not match. Aborting.');

                return self::FAILURE;
            }
        }

        try {
            $tenant->delete();
        } catch (\Throwable $e) {
            if ($this->isForeignKeyViolation($e)) {
                $this->error('Cannot delete: child records exist in other tables.');
                $this->error("Clean up records with realm_id = {$tenant->id} first.");

                return self::FAILURE;
            }

            throw $e;
        }

        // Clean up storage directory if it exists
        $storageRoot = config('realm.storage.root', 'tenants');
        $tenantPath = $storageRoot.'/'.$tenant->key;

        if (Storage::exists($tenantPath)) {
            Storage::deleteDirectory($tenantPath);
            $this->line("  Deleted storage directory: {$tenantPath}");
        }

        $this->info("Tenant '{$tenant->key}' deleted successfully.");

        return self::SUCCESS;
    }

    private function showDestructionSummary(Tenant $tenant): void
    {
        $userCount = $this->getTenantUserCount($tenant);
        $secretCount = $this->getTenantSecretCount($tenant);
        $storageRoot = config('realm.storage.root', 'tenants');
        $storagePath = $storageRoot.'/'.$tenant->key;
        $storageExists = Storage::exists($storagePath);

        $this->newLine();
        $this->warn('  This will PERMANENTLY destroy:');
        $this->line("  - Tenant record: {$tenant->key} ({$tenant->name})");

        if ($userCount > 0) {
            $this->line("  - {$userCount} tenant_users pivot entries (cascade)");
        }

        if ($secretCount > 0) {
            $this->line("  - {$secretCount} tenant_secrets entries (cascade)");
        }

        if ($storageExists) {
            $this->line("  - Storage directory: {$storagePath}");
        }

        $this->newLine();
        $this->warn('  Other tables with realm_id columns may also have data for');
        $this->warn('  this tenant. If using restrictOnDelete (the default), the');
        $this->warn('  DELETE will fail if child records exist in those tables.');
        $this->warn('  Clean up tenant data in your app tables first.');
        $this->newLine();
        $this->error('  This action CANNOT be undone.');
        $this->newLine();
    }

    private function getTenantUserCount(Tenant $tenant): int
    {
        try {
            return DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function getTenantSecretCount(Tenant $tenant): int
    {
        try {
            return DB::table('tenant_secrets')
                ->where('tenant_id', $tenant->id)
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function isForeignKeyViolation(\Throwable $e): bool
    {
        if (! $e instanceof QueryException) {
            return false;
        }

        $message = strtolower($e->getMessage());
        $code = (string) $e->getCode();

        // String-based detection (SQLite, general)
        if (str_contains($message, 'foreign key') || str_contains($message, 'constraint')) {
            return true;
        }

        // Error code detection
        return in_array($code, [
            '23503', // PostgreSQL foreign key violation
            '23000', // MySQL/MariaDB integrity constraint
            '547',   // SQL Server foreign key constraint
        ], true);
    }
}

<?php

namespace Realm\Console;

use Illuminate\Console\Command;
use Realm\Models\Tenant;

class RealmListCommand extends Command
{
    protected $signature = 'realm:list';

    protected $description = 'List all tenant realms';

    public function handle(): int
    {
        $tenants = Tenant::orderBy('key')->get();

        if ($tenants->isEmpty()) {
            $this->info('No realms found. Create one with: php artisan realm:create {key} {name}');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Key', 'Name', 'Domain', 'Active', 'Created'],
            $tenants->map(fn (Tenant $tenant) => [
                $tenant->id,
                $tenant->key,
                $tenant->name,
                $tenant->domain ?? '-',
                $tenant->active ? 'Yes' : 'No',
                $tenant->created_at?->format('Y-m-d'),
            ]),
        );

        return self::SUCCESS;
    }
}

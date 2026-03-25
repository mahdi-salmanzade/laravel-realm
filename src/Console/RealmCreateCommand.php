<?php

namespace Realm\Console;

use Illuminate\Console\Command;
use Realm\Exceptions\InvalidRealmKeyException;
use Realm\Models\Tenant;

class RealmCreateCommand extends Command
{
    protected $signature = 'realm:create {key} {name}';

    protected $description = 'Create a new tenant realm';

    public function handle(): int
    {
        $key = $this->argument('key');
        $name = $this->argument('name');

        try {
            Tenant::validateKey($key);
        } catch (InvalidRealmKeyException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (Tenant::where('key', $key)->exists()) {
            $this->error("A realm with key '{$key}' already exists.");

            return self::FAILURE;
        }

        $tenant = Tenant::create([
            'key' => $key,
            'name' => $name,
        ]);

        $this->info("Realm '{$tenant->key}' ({$tenant->name}) created successfully.");

        return self::SUCCESS;
    }
}

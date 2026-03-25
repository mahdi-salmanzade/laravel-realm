<?php

namespace Realm\Console;

use Illuminate\Console\Command;

class RealmInstallCommand extends Command
{
    protected $signature = 'realm:install';

    protected $description = 'Install the Realm multi-tenancy package';

    public function handle(): int
    {
        $this->info('Installing Laravel Realm...');

        $this->call('vendor:publish', [
            '--tag' => 'realm-config',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'realm-migrations',
        ]);

        $this->call('migrate');

        $this->info('');
        $this->info('  Realm installed!');
        $this->info('');
        $this->info('  Strategy: column');
        $strict = config('realm.strict', true) ? 'ON' : 'OFF';
        $cache = config('realm.cache.prefix', true) ? 'ON' : 'OFF';
        $storage = config('realm.storage.prefix_path', true) ? 'ON' : 'OFF';
        $queue = config('realm.queue.context', true) ? 'ON' : 'OFF';

        $this->info("  Strict mode: {$strict}");
        $this->info("  Cache prefixing: {$cache}");
        $this->info("  Storage prefixing: {$storage}");
        $this->info("  Queue context: {$queue}");
        $this->info('');
        $this->info('  Next steps:');
        $this->info('  1. Add BelongsToRealm to your models');
        $this->info('  2. Add $table->realm() to migrations');
        $this->info('  3. Wrap tenant routes in Route::realm()');
        $this->info('  4. Add RealmAwareJob trait to queued jobs');
        $this->info('');
        $this->info('  php artisan realm:create acme "Acme Corp"');
        $this->info('  php artisan realm:check');

        return self::SUCCESS;
    }
}

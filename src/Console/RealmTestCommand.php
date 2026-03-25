<?php

namespace Realm\Console;

use Illuminate\Console\Command;
use Realm\Context\RealmContext;
use Realm\Facades\Realm;
use Realm\Models\Tenant;

class RealmTestCommand extends Command
{
    protected $signature = 'realm:test';

    protected $description = 'Verify that realm isolation is working correctly';

    public function handle(): int
    {
        $this->info('Realm Isolation Test');
        $this->info('====================');
        $this->newLine();

        $passed = 0;
        $failed = 0;

        // Create test tenants
        try {
            $alpha = Tenant::create(['key' => 'realm-test-alpha', 'name' => 'Test Alpha']);
            $this->line('  ✓ Created tenant: realm-test-alpha');
            $passed++;
        } catch (\Throwable $e) {
            $this->error('  ✗ Failed to create tenant alpha: '.$e->getMessage());
            $failed++;

            return self::FAILURE;
        }

        try {
            $beta = Tenant::create(['key' => 'realm-test-beta', 'name' => 'Test Beta']);
            $this->line('  ✓ Created tenant: realm-test-beta');
            $passed++;
        } catch (\Throwable $e) {
            $this->error('  ✗ Failed to create tenant beta: '.$e->getMessage());
            $alpha->delete();
            $failed++;

            return self::FAILURE;
        }

        // Test context switching
        try {
            $context = app(RealmContext::class);

            $context->set($alpha);
            if (Realm::key() === 'realm-test-alpha') {
                $this->line('  ✓ Context set to alpha');
                $passed++;
            } else {
                $this->error('  ✗ Context not set correctly to alpha');
                $failed++;
            }

            $context->set($beta);
            if (Realm::key() === 'realm-test-beta') {
                $this->line('  ✓ Context switched to beta');
                $passed++;
            } else {
                $this->error('  ✗ Context not switched correctly to beta');
                $failed++;
            }

            // Test Realm::run()
            Realm::run($alpha, function () use (&$passed, &$failed) {
                if (Realm::key() === 'realm-test-alpha') {
                    $this->line('  ✓ Realm::run() switches context');
                    $passed++;
                } else {
                    $this->error('  ✗ Realm::run() did not switch context');
                    $failed++;
                }
            });

            if (Realm::key() === 'realm-test-beta') {
                $this->line('  ✓ Context restored after Realm::run()');
                $passed++;
            } else {
                $this->error('  ✗ Context not restored after Realm::run()');
                $failed++;
            }

            // Test withoutTenancy
            Realm::withoutTenancy(function () use (&$passed, &$failed) {
                if (Realm::isTenancyDisabled()) {
                    $this->line('  ✓ withoutTenancy disables scoping');
                    $passed++;
                } else {
                    $this->error('  ✗ withoutTenancy did not disable scoping');
                    $failed++;
                }
            });

            $context->reset();
        } catch (\Throwable $e) {
            $this->error('  ✗ Context test failed: '.$e->getMessage());
            $failed++;
        }

        // Cleanup
        try {
            $alpha->delete();
            $beta->delete();
            $this->line('  ✓ Cleanup: test tenants removed');
            $passed++;
        } catch (\Throwable $e) {
            $this->error('  ✗ Cleanup failed: '.$e->getMessage());
            $failed++;
        }

        $this->newLine();
        if ($failed === 0) {
            $this->info("  All {$passed} checks passed. Tenancy is working.");
        } else {
            $this->error("  {$failed} check(s) failed out of ".($passed + $failed).'.');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}

<?php

namespace Realm\Tests\Feature;

use Realm\Context\RealmContext;
use Realm\Facades\Realm;
use Realm\Integrations\RealmQueueMiddleware;
use Realm\Testing\RealmTestHelpers;
use Realm\Tests\TestCase;

class QueueMiddlewareTest extends TestCase
{
    use RealmTestHelpers;

    public function test_context_restored_when_job_has_valid_tenant(): void
    {
        $tenant = $this->createRealm('acme');

        $job = new class
        {
            public ?int $realmId = null;

            public bool $failed = false;

            public bool $released = false;

            public int $releaseDelay = 0;

            public function fail(\Throwable $e): void
            {
                $this->failed = true;
            }

            public function release(int $delay = 0): void
            {
                $this->released = true;
                $this->releaseDelay = $delay;
            }
        };

        $job->realmId = $tenant->id;

        $capturedKey = null;

        (new RealmQueueMiddleware)->handle($job, function ($job) use (&$capturedKey) {
            $capturedKey = Realm::key();
        });

        $this->assertEquals('acme', $capturedKey);
    }

    public function test_job_failed_when_tenant_deleted(): void
    {
        $job = new class
        {
            public ?int $realmId = null;

            public bool $failed = false;

            public bool $released = false;

            public int $releaseDelay = 0;

            public function fail(\Throwable $e): void
            {
                $this->failed = true;
            }

            public function release(int $delay = 0): void
            {
                $this->released = true;
                $this->releaseDelay = $delay;
            }
        };

        $job->realmId = 99999;

        (new RealmQueueMiddleware)->handle($job, function ($job) {
            $this->fail('Next callback should not have been called');
        });

        $this->assertTrue($job->failed);
    }

    public function test_job_released_when_tenant_inactive(): void
    {
        $tenant = $this->createRealm('acme', ['active' => false]);

        $job = new class
        {
            public ?int $realmId = null;

            public bool $failed = false;

            public bool $released = false;

            public int $releaseDelay = 0;

            public function fail(\Throwable $e): void
            {
                $this->failed = true;
            }

            public function release(int $delay = 0): void
            {
                $this->released = true;
                $this->releaseDelay = $delay;
            }
        };

        $job->realmId = $tenant->id;

        (new RealmQueueMiddleware)->handle($job, function ($job) {
            $this->fail('Next callback should not have been called');
        });

        $this->assertTrue($job->released);
    }

    public function test_context_cleaned_up_in_finally_block(): void
    {
        $tenant = $this->createRealm('acme');

        $job = new class
        {
            public ?int $realmId = null;

            public bool $failed = false;

            public bool $released = false;

            public int $releaseDelay = 0;

            public function fail(\Throwable $e): void
            {
                $this->failed = true;
            }

            public function release(int $delay = 0): void
            {
                $this->released = true;
                $this->releaseDelay = $delay;
            }
        };

        $job->realmId = $tenant->id;

        try {
            (new RealmQueueMiddleware)->handle($job, function ($job) {
                throw new \RuntimeException('Job processing error');
            });
        } catch (\RuntimeException) {
            // Expected
        }

        $context = app(RealmContext::class);
        $this->assertNull($context->get());
    }

    public function test_no_context_change_when_job_has_no_realm_id(): void
    {
        $job = new class
        {
            public ?int $realmId = null;

            public bool $failed = false;

            public bool $released = false;

            public int $releaseDelay = 0;

            public function fail(\Throwable $e): void
            {
                $this->failed = true;
            }

            public function release(int $delay = 0): void
            {
                $this->released = true;
                $this->releaseDelay = $delay;
            }
        };

        $nextCalled = false;

        (new RealmQueueMiddleware)->handle($job, function ($job) use (&$nextCalled) {
            $nextCalled = true;
            $this->assertNull(Realm::id());
        });

        $this->assertTrue($nextCalled);
        $this->assertFalse($job->failed);
        $this->assertFalse($job->released);
    }

    public function test_middleware_applies_config_overrides(): void
    {
        $tenant = $this->createRealm('acme', [
            'data' => ['config' => ['app.name' => 'Acme Queue']],
        ]);

        $job = new class
        {
            public ?int $realmId = null;

            public int $realmRetries = 0;

            public function fail(\Throwable $e): void {}

            public function release(int $delay = 0): void {}
        };
        $job->realmId = $tenant->id;

        $configDuringJob = null;
        $middleware = new RealmQueueMiddleware;
        $middleware->handle($job, function () use (&$configDuringJob) {
            $configDuringJob = config('app.name');
        });

        $this->assertEquals('Acme Queue', $configDuringJob);
        // Config should be restored after middleware
        $this->assertNotEquals('Acme Queue', config('app.name'));
    }

    public function test_middleware_fails_after_max_inactive_retries(): void
    {
        config(['realm.queue.max_inactive_retries' => 2]);
        $tenant = $this->createRealm('acme', ['active' => false]);

        $job = new class
        {
            public ?int $realmId = null;

            public int $realmRetries = 2; // Already at max

            public bool $failed = false;

            public bool $released = false;

            public function fail(\Throwable $e): void
            {
                $this->failed = true;
            }

            public function release(int $delay = 0): void
            {
                $this->released = true;
            }
        };
        $job->realmId = $tenant->id;

        $middleware = new RealmQueueMiddleware;
        $middleware->handle($job, function () {
            $this->fail('Should not reach next middleware');
        });

        $this->assertTrue($job->failed);
        $this->assertFalse($job->released);
    }
}

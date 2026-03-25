<?php

namespace Realm\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Log;
use Realm\Context\RealmContext;
use Realm\Facades\Realm;

class RealmScope implements Scope
{
    /**
     * Tracks whether the "no active realm" warning has been logged this request.
     *
     * Note: This static flag is safe in FPM and Octane with process-based workers
     * (each process has its own static state, and the Octane RequestReceived listener
     * resets it between requests). It is NOT safe with coroutine-based servers
     * (Swoole coroutines, Roadrunner fibers) where concurrent requests share statics.
     */
    private static bool $warnedThisRequest = false;

    public function __construct(
        private readonly string $column = 'realm_id',
    ) {}

    public function apply(Builder $builder, Model $model): void
    {
        if (app(RealmContext::class)->isTenancyDisabled()) {
            return;
        }

        $realmId = Realm::id();

        if ($realmId !== null) {
            $builder->where($model->getTable().'.'.$this->column, $realmId);
        } elseif (config('realm.strict', true)) {
            $builder->whereRaw('0 = 1');

            if (! self::$warnedThisRequest) {
                self::$warnedThisRequest = true;

                $context = ['model' => get_class($model)];

                if (config('realm.debug', false)) {
                    $context['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
                }

                Log::warning(
                    'RealmScope: Query attempted with no active realm. '
                    .'Returning empty results (strict mode). '
                    .'Use ->withoutRealm() or Realm::withoutTenancy() if intentional.',
                    $context
                );
            }
        }
    }

    public static function resetWarning(): void
    {
        self::$warnedThisRequest = false;
    }
}

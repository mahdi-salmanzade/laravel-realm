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
    private static bool $warnedThisRequest = false;

    public function apply(Builder $builder, Model $model): void
    {
        if (app(RealmContext::class)->isTenancyDisabled()) {
            return;
        }

        $realmId = Realm::id();

        if ($realmId !== null) {
            $builder->where($model->getTable().'.realm_id', $realmId);
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

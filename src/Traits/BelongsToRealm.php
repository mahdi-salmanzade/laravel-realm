<?php

namespace Realm\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Realm\Exceptions\NoActiveRealmException;
use Realm\Facades\Realm;
use Realm\Models\Tenant;
use Realm\Scopes\RealmScope;

trait BelongsToRealm
{
    public static function bootBelongsToRealm(): void
    {
        static::addGlobalScope(new RealmScope);

        static::creating(function (Model $model) {
            if ($model->realm_id === null) {
                $currentId = Realm::id();

                if ($currentId !== null) {
                    $model->realm_id = $currentId;
                } elseif (config('realm.strict', true) && ! Realm::isTenancyDisabled()) {
                    throw new NoActiveRealmException(
                        'Cannot create '.class_basename($model).' without an active realm. '
                        .'Use Realm::run() to set context, or Realm::withoutTenancy() for system operations.'
                    );
                }
            }
        });
    }

    public function realm(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'realm_id');
    }

    public function scopeWithoutRealm(Builder $query): Builder
    {
        return $query->withoutGlobalScope(RealmScope::class);
    }

    public function scopeForRealm(Builder $query, Tenant|int $realm): Builder
    {
        $realmId = $realm instanceof Tenant ? $realm->id : $realm;

        return $query->withoutGlobalScope(RealmScope::class)
            ->where($this->getTable().'.realm_id', $realmId);
    }
}

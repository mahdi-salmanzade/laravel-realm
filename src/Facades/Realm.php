<?php

namespace Realm\Facades;

use Illuminate\Support\Facades\Facade;
use Realm\RealmManager;

/**
 * @method static \Realm\Models\Tenant|null current()
 * @method static int|null id()
 * @method static string|null key()
 * @method static bool check()
 * @method static bool isTenancyDisabled()
 * @method static mixed run(string|\Realm\Models\Tenant $tenant, \Closure $callback)
 * @method static mixed withoutTenancy(\Closure $callback)
 * @method static void forget()
 * @method static string|null getSecret(string|\Realm\Models\Tenant $tenant, string $key)
 * @method static void setSecret(string|\Realm\Models\Tenant $tenant, string $key, string $value)
 * @method static bool deleteSecret(string|\Realm\Models\Tenant $tenant, string $key)
 *
 * @see RealmManager
 */
class Realm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RealmManager::class;
    }
}

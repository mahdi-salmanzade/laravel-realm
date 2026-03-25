<?php

use Realm\Models\Tenant;
use Realm\Resolution\SubdomainResolver;

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy Strategy
    |--------------------------------------------------------------------------
    | v0.1 supports 'column' only. 'database' and 'schema' coming in v1.0.
    */
    'strategy' => 'column',

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    */
    'model' => Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Resolution
    |--------------------------------------------------------------------------
    */
    'resolvers' => [
        SubdomainResolver::class,
    ],

    'subdomain' => [
        'domain' => env('REALM_DOMAIN', 'localhost'),
    ],

    'header' => [
        'name' => 'X-Realm',
    ],

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    */
    'central_domains' => [
        env('REALM_DOMAIN', 'localhost'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict Mode (SECURITY — LEAVE THIS ON)
    |--------------------------------------------------------------------------
    | When true (default), querying BelongsToRealm models without active realm
    | returns empty results (fail-closed) and logs a warning. Creating records
    | without realm context throws NoActiveRealmException.
    */
    'strict' => env('REALM_STRICT', true),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    | When true, RealmScope warnings include a backtrace for easier debugging.
    | Leave off in production — backtraces are expensive.
    */
    'debug' => env('REALM_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Resolution Failure
    |--------------------------------------------------------------------------
    */
    'on_fail' => 'abort',
    'redirect_to' => '/',

];

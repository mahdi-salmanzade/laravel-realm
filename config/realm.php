<?php

use Realm\Models\Tenant;
use Realm\Resolution\SubdomainResolver;

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy Strategy
    |--------------------------------------------------------------------------
    | v0.1/v0.2 supports 'column' only. 'database' and 'schema' coming in v1.0.
    */
    'strategy' => 'column',

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    */
    'model' => Tenant::class, // Reserved: not yet used by core. Custom tenant model support planned for v1.0.

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    | The user model for the tenant_users pivot relationship.
    */
    'user_model' => 'App\\Models\\User',

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

    'path' => [
        'segment' => 1,
    ],

    'query' => [
        'parameter' => 'realm',
    ],

    'session' => [
        'key' => 'realm_key',
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
    | What happens when realm resolution fails in the ResolveRealm middleware.
    | Supported values: 'abort' (returns 404), 'redirect' (redirects to
    | redirect_to URL), 'exception' (throws RealmNotFoundException).
    */
    'on_fail' => 'abort',
    'redirect_to' => '/',

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    | When prefix is true, all cache keys are automatically prefixed with the
    | active tenant's key: realm:{tenant_key}:{original_key}.
    */
    'cache' => [
        'prefix' => true,
        'tag' => true, // Reserved: tag-based cache flush planned for a future release.
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    | When context is true, RealmAwareJob trait captures tenant context at
    | dispatch time and restores it at processing time via queue middleware.
    */
    'queue' => [
        'context' => true, // Reserved: queue context gating planned for a future release.
        'inactive_delay' => 300,
        'max_inactive_retries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    | When prefix_path is true, all storage paths are automatically prefixed:
    | {root}/{tenant_key}/{original_path}.
    */
    'storage' => [
        'prefix_path' => true,
        'root' => 'tenants',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail
    |--------------------------------------------------------------------------
    */
    'mail' => [
        'per_realm_config' => false, // Reserved: per-tenant mail configuration planned for a future release.
    ],

];

<p align="center">
  <img src="realm-banner-custom.svg" width="100%" alt="Laravel Realm">
</p>

<p align="center">
  <a href="https://packagist.org/packages/mahdisphp/laravel-realm"><img src="https://img.shields.io/packagist/v/mahdisphp/laravel-realm" alt="Version"></a>
  <a href="https://packagist.org/packages/mahdisphp/laravel-realm"><img src="https://img.shields.io/packagist/dt/mahdisphp/laravel-realm" alt="Downloads"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/mahdisphp/laravel-realm" alt="License"></a>
</p>

---

## What is Realm?

Realm adds multi-tenancy to your Laravel app with a single trait and a safety-first approach. It's designed for the 80% of SaaS apps that need row-level tenant isolation in a single database.

**v0.1 supports column strategy only.** Database-per-tenant and schema-per-tenant strategies are coming in v1.0.

### What makes Realm different?

- **Fail-closed by default.** If no tenant context is active, queries on tenant models return empty results — not all tenants' data. Data leaks should be impossible by default.
- **Clean naming.** The `Realm` facade and `Tenant` model are separate classes with zero method overlap. No ambiguity.
- **One trait, one macro.** Add `BelongsToRealm` to a model and `$table->realm()` to a migration. That's it.
- **Octane-ready.** Context is automatically reset between requests. No manual setup required.

### What Realm is NOT (yet)

- Battle-tested. This is a new package. Use in production at your own risk and report bugs.
- A replacement for [stancl/tenancy](https://tenancyforlaravel.com/). Stancl has years of production edge cases discovered and fixed. If you need database-per-tenant today, use stancl.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Installation

```bash
composer require mahdisphp/laravel-realm
php artisan realm:install
```

## Quick Start

### 1. Add the trait to your model

```php
use Realm\Traits\BelongsToRealm;

class Project extends Model
{
    use BelongsToRealm;
}
```

### 2. Add the column to your migration

```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->realm();  // Adds realm_id + index
    $table->string('name');
    $table->timestamps();
});
```

### 3. Wrap your routes

```php
// Tenant routes — realm context is resolved automatically
Route::realm(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::resource('projects', ProjectController::class);
});

// Central routes — guaranteed no tenant context
Route::central(function () {
    Route::get('/', [MarketingController::class, 'index']);
});
```

### 4. That's it

```php
// Inside a tenant route, queries are scoped automatically
Project::all();           // Only current tenant's projects
Project::create([...]);   // realm_id set automatically

// Outside tenant context (strict mode) — returns empty, not everything
Project::all();           // [] — fail-closed, logged warning
```

## Key Concepts

### The Facade vs The Model

```php
// FACADE — context management (Realm)
Realm::current();                    // Get active Tenant model
Realm::id();                         // Get active tenant ID
Realm::run('acme', fn() => ...);     // Execute in a tenant's context
Realm::withoutTenancy(fn() => ...);  // Disable all scoping

// MODEL — data operations (Tenant)
Tenant::create(['key' => 'acme', 'name' => 'Acme Corp']);
Tenant::where('active', true)->get();
```

### Strict Mode (Default: ON)

When no tenant context is active:
- **Model queries** return empty results (`WHERE 0 = 1`) and log a warning
- **Model creates** throw `NoActiveRealmException`
- Cache and storage fall back to global (unprefixed) space

### Escape Hatches

```php
// Bypass query scope for ONE query (cache/storage still scoped)
Project::withoutRealm()->get();

// Bypass ALL scoping (queries + cache + storage)
Realm::withoutTenancy(fn() => Project::all());

// Query a specific tenant
$acme = Tenant::where('key', 'acme')->first();
Project::forRealm($acme)->get();
```

### Shared vs Tenant Models

Not every model needs tenant scoping:

```php
// TENANT MODEL — scoped, has realm_id
class Project extends Model { use BelongsToRealm; }

// SHARED MODEL — no trait, no realm_id, available to all tenants
class Category extends Model { /* no BelongsToRealm */ }

// Relationships work across the boundary:
// $project->category works (Category is not scoped)
```

## Commands

```bash
php artisan realm:create acme "Acme Corp"   # Create a tenant
php artisan realm:list                       # List all tenants
php artisan realm:check                      # Diagnostic health check
php artisan realm:check --realm=acme         # Inspect specific tenant
php artisan realm:test                       # Verify isolation works (seeds, tests, cleans up)
```

## Testing

```php
use Realm\Testing\RealmTestHelpers;

class ProjectTest extends TestCase
{
    use RealmTestHelpers;

    public function test_projects_are_isolated(): void
    {
        $acme = $this->createRealm('acme');
        $globex = $this->createRealm('globex');

        $this->actingAsRealm($acme, fn() => Project::create(['name' => 'Acme Project']));
        $this->actingAsRealm($globex, fn() => Project::create(['name' => 'Globex Project']));

        $this->assertRealmCount($acme, Project::class, 1);
        $this->assertRealmMissing($acme, Project::class, ['name' => 'Globex Project']);
    }
}
```

## Blueprint Macro

```php
$table->realm();                   // Default: restrictOnDelete (safe)
$table->realm(cascade: true);      // Opt-in: cascadeOnDelete
```

`restrictOnDelete` is the default — deleting a tenant fails with a foreign key error, forcing you to handle data cleanup explicitly.

## Configuration

After installation, the config lives at `config/realm.php`. v0.1 is intentionally minimal:

- `strategy` — `'column'` (only option in v0.1)
- `resolvers` — array of resolver classes (SubdomainResolver, HeaderResolver)
- `strict` — `true` (fail-closed on missing context)
- `debug` — `false` (enable backtrace in scope warnings)
- `on_fail` — `'abort'` (what happens when resolution fails: abort/redirect/exception)

## Why not stancl/tenancy?

Use stancl if you need database-per-tenant today — it has years of battle-testing we don't. Realm is for the 80% of SaaS apps that only need column-based row isolation and want it with one trait, one macro, and fail-closed defaults out of the box.

## Roadmap

- **v0.1** (current) — Column strategy, subdomain + header resolution, strict mode, test helpers
- **v0.2** — Cache/queue/storage isolation, encrypted secrets, additional resolvers, tenant-aware AI (Laravel 13 agent conversations + embeddings scoped per tenant)
- **v1.0** — Database-per-tenant and schema-per-tenant strategies

## License

MIT. See [LICENSE](LICENSE).

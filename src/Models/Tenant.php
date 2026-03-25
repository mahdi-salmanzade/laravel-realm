<?php

namespace Realm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Realm\Events\RealmActivated;
use Realm\Events\RealmCreated;
use Realm\Events\RealmCreating;
use Realm\Events\RealmDeactivated;
use Realm\Events\RealmDeleted;
use Realm\Events\RealmDeleting;
use Realm\Exceptions\InvalidRealmKeyException;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $domain
 * @property array<string, mixed>|null $data
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static> where(mixed ...$args)
 * @method static Builder<static> whereIn(string $column, mixed $values)
 * @method static Builder<static> whereNotIn(string $column, mixed $values)
 * @method static Builder<static> orderBy(string $column, string $direction = 'asc')
 * @method static static create(array<string, mixed> $attributes = [])
 * @method static static firstOrFail()
 * @method static static|null find(int $id)
 * @method static int count()
 *
 * @mixin Builder<static>
 */
class Tenant extends Model
{
    protected $table = 'tenants';

    protected $fillable = ['key', 'name', 'domain', 'data', 'active'];

    /** @var array<string, string> */
    protected $casts = [
        'data' => 'array',
        'active' => 'boolean',
    ];

    /** @var array<string, mixed> */
    public static array $keyRules = [
        'pattern' => '/^[a-z0-9][a-z0-9\-]*$/',
        'max_length' => 63,
        'reserved' => ['www', 'api', 'admin', 'mail', 'ftp', 'localhost', 'realm', 'null'],
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tenant $tenant) {
            static::validateKey($tenant->key);
            RealmCreating::dispatch($tenant);
        });

        static::created(function (Tenant $tenant) {
            RealmCreated::dispatch($tenant);
        });

        static::deleting(function (Tenant $tenant) {
            RealmDeleting::dispatch($tenant);
        });

        static::deleted(function (Tenant $tenant) {
            RealmDeleted::dispatch($tenant);
        });

        static::updating(function (Tenant $tenant) {
            if ($tenant->isDirty('key')) {
                static::validateKey($tenant->key);
            }
        });

        static::updated(function (Tenant $tenant) {
            if ($tenant->wasChanged('active')) {
                if ($tenant->active) {
                    RealmActivated::dispatch($tenant);
                } else {
                    RealmDeactivated::dispatch($tenant);
                }
            }
        });
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /** @return BelongsToMany<Model, $this> */
    public function users(): BelongsToMany
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('realm.user_model', 'App\\Models\\User');

        return $this->belongsToMany($userModel, 'tenant_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<TenantSecret, $this> */
    public function secrets(): HasMany
    {
        return $this->hasMany(TenantSecret::class, 'tenant_id');
    }

    // -------------------------------------------------------
    // Secrets
    // -------------------------------------------------------

    public function setSecret(string $key, string $value): void
    {
        $this->secrets()->withoutGlobalScope('realm')->updateOrCreate(
            ['key' => $key],
            ['value' => Crypt::encryptString($value)]
        );
    }

    public function getSecret(string $key): ?string
    {
        $secret = $this->secrets()->withoutGlobalScope('realm')
            ->where('key', $key)
            ->first();

        return $secret ? Crypt::decryptString($secret->value) : null;
    }

    public function deleteSecret(string $key): bool
    {
        return $this->secrets()->withoutGlobalScope('realm')
            ->where('key', $key)
            ->delete() > 0;
    }

    // -------------------------------------------------------
    // Validation
    // -------------------------------------------------------

    public static function validateKey(string $key): void
    {
        if (! preg_match(static::$keyRules['pattern'], $key)) {
            throw new InvalidRealmKeyException(
                "Realm key '{$key}' is invalid. Must be lowercase alphanumeric with hyphens, "
                .'starting with a letter or number. No spaces, underscores, or special characters.'
            );
        }

        if (strlen($key) > static::$keyRules['max_length']) {
            throw new InvalidRealmKeyException('Realm key must be 63 characters or fewer.');
        }

        if (in_array($key, static::$keyRules['reserved'], true)) {
            throw new InvalidRealmKeyException("Realm key '{$key}' is reserved.");
        }
    }
}

<?php

namespace Realm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
 * @method static Builder<static> orderBy(string $column, string $direction = 'asc')
 * @method static static create(array<string, mixed> $attributes = [])
 * @method static static firstOrFail()
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
        });

        static::updating(function (Tenant $tenant) {
            if ($tenant->isDirty('key')) {
                static::validateKey($tenant->key);
            }
        });
    }

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

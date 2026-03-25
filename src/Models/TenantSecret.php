<?php

namespace Realm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Realm\Scopes\RealmScope;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $key
 * @property string $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static static updateOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(mixed ...$args)
 *
 * @mixin Builder<static>
 */
class TenantSecret extends Model
{
    protected $table = 'tenant_secrets';

    /** @var list<string> */
    protected $fillable = ['tenant_id', 'key', 'value'];

    /** @var list<string> */
    protected $hidden = ['value'];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('realm', new RealmScope('tenant_id'));
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

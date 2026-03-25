<?php

namespace Realm\Support;

use Illuminate\Database\Schema\Blueprint;

class RealmBlueprint
{
    public static function register(): void
    {
        Blueprint::macro('realm', function (bool $cascade = false) {
            /** @var Blueprint $this */
            $column = $this->foreignId('realm_id')->constrained('tenants');

            if ($cascade) {
                $column->cascadeOnDelete();
            } else {
                $column->restrictOnDelete();
            }

            $this->index('realm_id');
        });
    }
}

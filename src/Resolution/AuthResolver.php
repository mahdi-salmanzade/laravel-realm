<?php

namespace Realm\Resolution;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Realm\Models\Tenant;

class AuthResolver implements RealmResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $userId = $user->getAuthIdentifier();

        $pivots = DB::table('tenant_users')
            ->where('user_id', $userId)
            ->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get();

        if ($pivots->isEmpty()) {
            return null;
        }

        $tenantId = $pivots->first()->tenant_id;

        $tenant = Tenant::where('id', $tenantId)->where('active', true)->first();

        return $tenant?->key;
    }
}

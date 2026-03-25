<?php

namespace Realm\Resolution;

use Illuminate\Http\Request;
use Realm\Models\Tenant;

class DomainResolver implements RealmResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $host = $request->getHost();

        if ($host === '') {
            return null;
        }

        $tenant = Tenant::where('domain', $host)->where('active', true)->first();

        return $tenant?->key;
    }
}

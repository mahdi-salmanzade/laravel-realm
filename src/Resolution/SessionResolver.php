<?php

namespace Realm\Resolution;

use Illuminate\Http\Request;

class SessionResolver implements RealmResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $key = config('realm.session.key', 'realm_key');

        if (! $request->hasSession()) {
            return null;
        }

        $value = $request->session()->get($key);

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}

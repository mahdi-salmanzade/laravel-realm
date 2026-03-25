<?php

namespace Realm\Resolution;

use Illuminate\Http\Request;

class HeaderResolver implements RealmResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $headerName = config('realm.header.name', 'X-Realm');

        $value = $request->header($headerName);

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}

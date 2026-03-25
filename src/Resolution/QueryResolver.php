<?php

namespace Realm\Resolution;

use Illuminate\Http\Request;

class QueryResolver implements RealmResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $parameter = config('realm.query.parameter', 'realm');

        $value = $request->query($parameter);

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}

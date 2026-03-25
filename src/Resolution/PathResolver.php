<?php

namespace Realm\Resolution;

use Illuminate\Http\Request;

class PathResolver implements RealmResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $segment = (int) config('realm.path.segment', 1);

        $value = $request->segment($segment);

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}

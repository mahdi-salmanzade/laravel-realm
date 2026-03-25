<?php

namespace Realm\Resolution;

use Illuminate\Http\Request;

interface RealmResolverInterface
{
    public function resolve(Request $request): ?string;
}

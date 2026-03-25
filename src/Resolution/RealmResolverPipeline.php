<?php

namespace Realm\Resolution;

use Illuminate\Http\Request;

class RealmResolverPipeline
{
    /** @var array<RealmResolverInterface> */
    private array $resolvers = [];

    public function __construct()
    {
        $resolverClasses = config('realm.resolvers', []);

        foreach ($resolverClasses as $resolverClass) {
            $this->resolvers[] = app($resolverClass);
        }
    }

    public function resolve(Request $request): ?string
    {
        foreach ($this->resolvers as $resolver) {
            $key = $resolver->resolve($request);

            if ($key !== null) {
                return $key;
            }
        }

        return null;
    }
}

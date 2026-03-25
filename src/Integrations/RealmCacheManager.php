<?php

namespace Realm\Integrations;

use Illuminate\Cache\CacheManager;
use Realm\Context\RealmContext;

class RealmCacheManager extends CacheManager
{
    public function resolve($name)
    {
        $repository = parent::resolve($name);

        return new RealmCacheRepository(
            $repository,
            $this->app->make(RealmContext::class),
        );
    }
}

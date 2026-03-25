<?php

namespace Realm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Realm\Context\RealmContext;
use Realm\Events\RealmIdentified;
use Realm\Events\RealmNotFound;
use Realm\Exceptions\RealmNotFoundException;
use Realm\Models\Tenant;
use Realm\Resolution\RealmResolverPipeline;
use Realm\Strategies\TenancyStrategyInterface;
use Symfony\Component\HttpFoundation\Response;

class ResolveRealm
{
    public function __construct(
        private readonly RealmResolverPipeline $resolver,
        private readonly RealmContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $realmKey = $this->resolver->resolve($request);

        if (! $realmKey) {
            RealmNotFound::dispatch(null);

            return $this->handleFailure(null);
        }

        $tenant = Tenant::where('key', $realmKey)->where('active', true)->first();

        if (! $tenant) {
            RealmNotFound::dispatch($realmKey);

            return $this->handleFailure($realmKey);
        }

        $this->context->set($tenant);
        app(TenancyStrategyInterface::class)->switch($tenant);

        RealmIdentified::dispatch($tenant);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(TenancyStrategyInterface::class)->disconnect();
        $this->context->reset();
    }

    private function handleFailure(?string $realmKey): Response
    {
        return match (config('realm.on_fail')) {
            'redirect' => redirect(config('realm.redirect_to', '/')),
            'exception' => throw new RealmNotFoundException(
                $realmKey
                    ? "Realm '{$realmKey}' not found or inactive."
                    : 'No realm could be resolved from the request.'
            ),
            default => abort(404),
        };
    }
}

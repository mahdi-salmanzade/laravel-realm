<?php

namespace Realm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Realm\Context\RealmContext;
use Realm\Facades\Realm;
use Realm\Strategies\TenancyStrategyInterface;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Realm::check()) {
            Log::warning(
                'EnsureCentralRoute: Realm context was active on a central route. '
                .'This usually indicates a middleware ordering bug — ResolveRealm may be '
                .'applied before EnsureCentralRoute. Resetting context.',
                [
                    'realm_key' => Realm::key(),
                    'url' => $request->fullUrl(),
                ]
            );

            app(RealmContext::class)->reset();
            app(TenancyStrategyInterface::class)->disconnect();
        }

        return $next($request);
    }
}

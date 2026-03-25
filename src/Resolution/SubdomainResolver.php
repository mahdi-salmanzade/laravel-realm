<?php

namespace Realm\Resolution;

use Illuminate\Http\Request;

class SubdomainResolver implements RealmResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $host = $request->getHost();
        $domain = config('realm.subdomain.domain', 'localhost');

        // Check if the host ends with the configured domain
        if (! str_ends_with($host, '.'.$domain) && $host !== $domain) {
            return null;
        }

        // Extract the subdomain part
        if ($host === $domain) {
            return null;
        }

        $subdomain = rtrim(str_replace('.'.$domain, '', $host), '.');

        // Reject empty or multi-level subdomains
        if ($subdomain === '' || str_contains($subdomain, '.')) {
            return null;
        }

        // Check if this subdomain is in central domains
        $centralDomains = config('realm.central_domains', []);
        if (in_array($host, $centralDomains, true)) {
            return null;
        }

        return $subdomain;
    }
}

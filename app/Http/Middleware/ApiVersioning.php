<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersioning
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add API version headers
        $response->headers->set('X-API-Version', '1.0.0');
        $response->headers->set('X-API-Supported-Versions', '1.0');
        $response->headers->set('X-API-Deprecated-Versions', '');

        // Check for version in Accept header
        $acceptHeader = $request->header('Accept');
        if ($acceptHeader && str_contains($acceptHeader, 'application/vnd.prosight.v2+json')) {
            $response->headers->set('X-API-Notice', 'Version 2.0 not yet available. Using v1.0');
        }

        return $response;
    }
}
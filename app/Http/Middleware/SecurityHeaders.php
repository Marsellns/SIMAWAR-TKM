<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $siteMapPage = $request->routeIs('infrastruktur.sewa-lahan.index', 'infrastruktur.combat.index');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation='.($siteMapPage ? '(self)' : '()'));
        $response->headers->remove('X-Powered-By');

        $csp = "default-src 'self'; " .
            "base-uri 'self'; " .
            "form-action 'self'; " .
            "frame-ancestors 'self'; " .
            "object-src 'none'; " .
            "img-src 'self' data: blob: https:; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com https://code.highcharts.com https://cdn.datatables.net; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdn.datatables.net; " .
            "font-src 'self' data: https://fonts.gstatic.com; " .
            "connect-src 'self';";
        if ($siteMapPage) {
            $csp .= ' frame-src https://maps.google.com https://www.google.com;';
        }

        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}

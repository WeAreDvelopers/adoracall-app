<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeadersMiddleware
{
    /**
     * Add security headers to all responses
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Validate response exists before adding headers
        if ($response === null) {
            return response('', 200);
        }

        // HSTS (HTTP Strict-Transport-Security)
        // Forces HTTPS for 1 year (31536000 seconds) including subdomains
        $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // CSP (Content-Security-Policy)
        // Restricts resources that can be loaded
        $response->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; media-src 'self' https://*.cloudfront.net; connect-src 'self' https:; frame-ancestors 'none';");

        // X-Frame-Options (Clickjacking prevention)
        // Prevents site from being embedded in frames
        $response->header('X-Frame-Options', 'DENY');

        // X-Content-Type-Options (MIME sniffing prevention)
        // Forces browser to respect Content-Type header
        $response->header('X-Content-Type-Options', 'nosniff');

        // X-XSS-Protection (Legacy XSS protection)
        // Some older browsers respect this header
        $response->header('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy
        // Controls how much referrer info is shared
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy (Feature-Policy)
        // Controls browser features/APIs
        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}

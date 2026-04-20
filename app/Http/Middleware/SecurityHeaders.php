<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Only add HSTS if HTTPS is enabled
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy - adjusted for development and production
        $isDevelopment = config('app.env') === 'local' || config('app.debug');
        
        if ($isDevelopment) {
            // Relaxed CSP for development - allow Vite dev server
            $csp = "default-src 'self' 'unsafe-inline' 'unsafe-eval' data: http: ws:; " .
                   "style-src 'self' 'unsafe-inline' https:; " .
                   "img-src 'self' data: https: http:; " .
                   "font-src 'self' data: https:; " .
                   "connect-src 'self' http: ws: https:; " .
                   "frame-ancestors 'none';";
        } else {
            // Stricter CSP for production
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline'; " .
                   "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com; " .
                   "img-src 'self' data: https:; " .
                   "font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com; " .
                   "connect-src 'self' https:; " .
                   "frame-ancestors 'none';";
        }
        
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
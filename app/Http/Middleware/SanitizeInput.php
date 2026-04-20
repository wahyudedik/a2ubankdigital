<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize input data
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Remove potentially dangerous characters
                $value = strip_tags($value);
                
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                
                // Trim whitespace
                $value = trim($value);
                
                // Convert special characters to HTML entities for display
                // Note: Don't do this for password fields or other sensitive data
                if (!in_array($request->route()?->getName(), ['login', 'register']) && 
                    !str_contains(strtolower($request->getPathInfo()), 'password')) {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
        });
        
        // Replace the request input
        $request->replace($input);

        return $next($request);
    }
}
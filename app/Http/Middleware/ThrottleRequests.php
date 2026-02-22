<?php

namespace App\Http\Middleware;

use Closure;

class ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|int  $limit
     * @param  string  $window
     * @return \Illuminate\Http\Response
     */
    public function handle($request, Closure $next, $limit = 60, $window = 1)
    {
        // Simple implementation - just pass through
        // In production, you would implement proper rate limiting here
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Classes\ResponseBuilder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'admin') {
            return ResponseBuilder::build(null, 'Forbidden', Response::HTTP_FORBIDDEN);
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use existing header or generate a new one
        $requestId = $request->header('X-Request-ID', (string)\Str::uuid());

        // Set request ID into the request
        $request->headers->set('X-Request-ID', $requestId);

        // Share globally (optional, for logging)
        app()->instance('request-id', $requestId);

        Log::info('Incoming Request', [
            'requestId' => $requestId,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        // Continue processing
        $response = $next($request);

        // Add to response headers
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Driver;

final class CheckDriverAppAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Invalid authorization header'], 401);
        }

        // Remove bearer from token
        $token = substr($token, 7);

        $driver = Driver::where('token', $token)->first();

        if (!$driver) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Set the authenticated driver in the request for use in the controllers
        $request->merge(['authenticated_driver' => $driver]);

        // Explicitly indicate this is an API request to prevent web redirects
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}

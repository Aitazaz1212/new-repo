<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Retrieve the Authorization header
        $authorizationHeader = $request->header('Authorization');

        // Check if the header contains a Bearer token
        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return response()->json([
                'message' => 'Unauthorized: Missing or invalid Bearer token',
                'success' => false
            ], 401);
        }

        // Extract the token from the Bearer header
        $apiKeyFromHeader = substr($authorizationHeader, 7); // Remove 'Bearer ' prefix

        // Check if the API key exists in the database
        $apiKeyExists = ApiKey::where('key', $apiKeyFromHeader)->exists();

        if (!$apiKeyExists) {
            // Return a 403 response if the API key is invalid
            return response()->json([
                'message' => 'Forbidden: Invalid API Key',
                'success' => false
            ], 403);
        }

        // Forward the request if the API key is valid
        return $next($request);
    }
}

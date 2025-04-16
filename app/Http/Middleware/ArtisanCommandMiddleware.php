<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ArtisanCommandMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-Artisan-Key');
        
        if ($apiKey !== config('artisan.api_key')) {
            return response()->json([
                'error' => 'Unauthorized access'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
} 
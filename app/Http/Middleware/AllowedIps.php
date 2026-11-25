<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AllowedIps
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = array_filter(explode(',', config('app.allowed_ips')));
        $apiKey  = config('app.internal_api_key');

        if (in_array($request->ip(), $allowed) || ($apiKey && $request->header('x-app-rk') === $apiKey)) {
            return $next($request);
        }

        Log::channel('allowed_ips')->warning('Blocked request', ['ip' => $request->ip(), 'path' => $request->path()]);

        return response()->json(['message' => 'Forbidden.'], 403);
    }
}
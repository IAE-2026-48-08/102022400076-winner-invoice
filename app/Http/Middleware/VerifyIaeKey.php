<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIaeKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-IAE-KEY');
        $expectedKey = env('IAE_KEY', 'default_iae_key');

        if (!$key || $key !== $expectedKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid or missing X-IAE-KEY header',
                'errors' => null
            ], 401);
        }

        return $next($request);
    }
}

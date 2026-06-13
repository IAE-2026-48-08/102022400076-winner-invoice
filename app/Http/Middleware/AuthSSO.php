<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthSSO
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('sso.token') || ! $request->session()->has('sso.user')) {
            return redirect()
                ->route('login')
                ->with('error', 'Silakan login menggunakan akun SSO IAE.');
        }

        return $next($request);
    }
}

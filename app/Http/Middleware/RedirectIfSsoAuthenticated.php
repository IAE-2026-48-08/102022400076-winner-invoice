<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfSsoAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('sso.token')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}

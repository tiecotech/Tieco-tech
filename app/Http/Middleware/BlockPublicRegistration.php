<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Staff accounts are created by an administrator only. Public sign-up does not exist. */
class BlockPublicRegistration
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->is('register') || $request->is('register/*'), 404);

        return $next($request);
    }
}

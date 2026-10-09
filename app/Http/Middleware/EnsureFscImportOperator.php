<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFscImportOperator
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = $request->user()?->username;
        $allowed = config('fsc_web_import.operator_usernames', []);

        if (! is_string($username) || ! in_array($username, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}

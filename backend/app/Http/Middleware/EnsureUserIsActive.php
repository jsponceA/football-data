<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // aunque el token siga vigente
        if (! $request->user()?->is_active) {
            throw new ApiException(403, 'Tu usuario está desactivado.');
        }

        return $next($request);
    }
}

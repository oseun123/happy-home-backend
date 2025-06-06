<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;

class SuperadminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user() instanceof \App\Models\Superadmin) {

            return ResponseHelper::withError('Access denied', [], 403);
        }

        return $next($request);
    }
}

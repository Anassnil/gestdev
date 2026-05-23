<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAiAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check() || ! auth()->user()->can('manage-ai')) {
            abort(403, 'Unauthorized.');
        }
        return $next($request);
    }
}

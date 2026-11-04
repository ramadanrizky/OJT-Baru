<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAtasan
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('user_role') !== 'atasan') {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Akses atasan diperlukan.'], 403);
            }

            return response('Akses Ditolak', 403);
        }

        return $next($request);
    }
}

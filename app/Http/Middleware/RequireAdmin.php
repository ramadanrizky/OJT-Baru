<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('user_role') !== 'admin') {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Akses admin diperlukan.'], 403);
            }

            return response('Akses Ditolak', 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\LegacyUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            return $this->reject($request, 'Sesi telah berakhir, silakan login kembali.', 401);
        }

        $user = LegacyUser::query()->find($userId);

        if (! $user) {
            $request->session()->flush();

            return $this->reject($request, 'Sesi telah berakhir, silakan login kembali.', 401);
        }

        $request->attributes->set('legacy_user', $user);

        return $next($request);
    }

    private function reject(Request $request, string $message, int $status): Response
    {
        if ($request->is('api/*')) {
            return response()->json(['error' => $message], $status);
        }

        return redirect('/');
    }
}

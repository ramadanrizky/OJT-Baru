<?php

namespace App\Http\Controllers;

use App\Models\LegacyUser;
use App\Services\LegacyId;
use App\Services\LegacyPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly LegacyPassword $passwords)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            return response()->json(['error' => 'Email dan kata sandi harus diisi.'], 400);
        }

        $user = LegacyUser::query()->where('email', $email)->first();

        if (! $user || ! $this->passwords->verify($user->getAuthPassword(), $password)) {
            return response()->json(['error' => 'Email atau kata sandi salah'], 401);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'user_id' => (string) $user->getKey(),
            'user_name' => $user->fullname,
            'user_email' => $user->email,
            'user_role' => $user->role,
        ]);

        return response()->json([
            'message' => 'Login berhasil!',
            'redirect_url' => $this->dashboardUrl((string) $user->role),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $fullname = trim((string) $request->input('fullname', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($fullname === '' || $email === '' || $password === '') {
            return response()->json(['error' => 'Semua kolom harus diisi.'], 400);
        }

        if (LegacyUser::query()->where('email', $email)->exists()) {
            return response()->json(['error' => 'Email sudah terdaftar.'], 409);
        }

        $data = [
            'fullname' => $fullname,
            'email' => $email,
            'password_hash' => $this->passwords->make($password),
            'role' => 'user',
            'superior_id' => null,
        ];

        $user = new LegacyUser([
            'id' => LegacyId::make(),
            'data' => $data,
        ]);
        $user->syncIndexColumns()->save();

        return response()->json([
            'message' => 'Registrasi berhasil! Anda akan diarahkan ke halaman login.',
            'redirect_url' => '/',
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function dashboardUrl(string $role): string
    {
        return match ($role) {
            'admin' => '/admin/dashboard.html',
            'atasan' => '/atasan/dashboard.html',
            default => '/USER/dashboard.html',
        };
    }
}

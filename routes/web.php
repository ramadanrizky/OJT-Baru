<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LegacyProxyController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autentikasi (sudah dimigrasi ke Laravel)
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'login']);
Route::get('/register.html', [PageController::class, 'registerPage']);
Route::get('/pengumuman.html', [PageController::class, 'pengumuman']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| Halaman
|--------------------------------------------------------------------------
*/
Route::get('/dokum/{filename}', [PageController::class, 'dokum'])
    ->where('filename', '.*')
    ->middleware('legacy.auth');

Route::middleware(['legacy.auth', 'legacy.admin'])->group(function () {
    Route::get('/admin/dashboard.html', [PageController::class, 'admin'])
        ->defaults('view', 'admin.dashboard');
    Route::get('/admin/kelola-pengguna.html', [PageController::class, 'admin'])
        ->defaults('view', 'admin.kelola-pengguna');
    Route::get('/admin/jadwal-rapat.html', [PageController::class, 'admin'])
        ->defaults('view', 'admin.jadwal-rapat');
    Route::get('/admin/kalender-kegiatan.html', [PageController::class, 'admin'])
        ->defaults('view', 'admin.kalender-kegiatan');
    Route::get('/admin/kelola-pengumuman.html', [PageController::class, 'admin'])
        ->defaults('view', 'admin.kelola-pengumuman');
    Route::get('/admin/format-surat.html', [PageController::class, 'admin'])
        ->defaults('view', 'admin.format-surat');
});

Route::middleware(['legacy.auth', 'legacy.atasan'])->group(function () {
    Route::get('/atasan/dashboard.html', [PageController::class, 'atasan'])
        ->defaults('view', 'atasan.dashboard');
    Route::get('/atasan/tim-saya.html', [PageController::class, 'atasan'])
        ->defaults('view', 'atasan.tim-saya');
    Route::get('/atasan/jadwal-rapat.html', [PageController::class, 'atasan'])
        ->defaults('view', 'atasan.jadwal-rapat');
    Route::get('/atasan/kalender-kegiatan.html', [PageController::class, 'atasan'])
        ->defaults('view', 'atasan.kalender-kegiatan');
    Route::get('/atasan/kelola-pengumuman.html', [PageController::class, 'atasan'])
        ->defaults('view', 'atasan.kelola-pengumuman');
    Route::get('/atasan/persetujuan.html', [PageController::class, 'atasan'])
        ->defaults('view', 'atasan.persetujuan');
});

Route::middleware('legacy.auth')->group(function () {
    Route::get('/USER/dashboard.html', [PageController::class, 'user'])
        ->defaults('view', 'USER.dashboard');
    Route::get('/USER/alat-bantuan.html', [PageController::class, 'user'])
        ->defaults('view', 'USER.alat-bantuan');
    Route::get('/USER/pengingat.html', [PageController::class, 'user'])
        ->defaults('view', 'USER.pengingat');
    Route::get('/USER/riwayat-disposisi.html', [PageController::class, 'user'])
        ->defaults('view', 'USER.riwayat-disposisi');
    Route::get('/USER/jadwal-rapat.html', [PageController::class, 'user'])
        ->defaults('view', 'USER.jadwal-rapat');
    Route::get('/USER/kalender-kegiatan.html', [PageController::class, 'user'])
        ->defaults('view', 'USER.kalender-kegiatan');
});

/*
|--------------------------------------------------------------------------
| Proxy sementara ke Flask
|--------------------------------------------------------------------------
| Semua endpoint yang belum dimigrasi diteruskan ke Flask. Route eksplisit
| untuk modul yang sudah dimigrasi harus didaftarkan SEBELUM catch-all ini.
*/
Route::any('/api/{path?}', [LegacyProxyController::class, 'forward'])
    ->where('path', '.*');

Route::fallback(function () {
    abort(404);
});

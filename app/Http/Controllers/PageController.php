<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    public function login()
    {
        return view('LOGIN');
    }

    public function registerPage()
    {
        return view('register');
    }

    public function pengumuman()
    {
        return view('pengumuman');
    }

    public function admin(Request $request)
    {
        return $this->page($request, 'admin.dashboard');
    }

    public function atasan(Request $request)
    {
        return $this->page($request, 'atasan.dashboard');
    }

    public function user(Request $request)
    {
        return $this->page($request, 'USER.dashboard');
    }

    public function dokum(string $filename): Response
    {
        $allowed = ['html', 'htm', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'css', 'js'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (! in_array($extension, $allowed, true)) {
            return response('Jenis file tidak diizinkan', 403);
        }

        $root = realpath(base_path('../dokum'));
        $path = realpath(base_path('../dokum/'.str_replace('\\', '/', $filename)));

        if ($root === false || $path === false || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        return response()->file($path);
    }

    private function page(Request $request, string $view)
    {
        $view = $request->route('view') ?? $view;

        return view($view, [
            'user_name' => (string) $request->session()->get('user_name', ''),
            'user_email' => (string) $request->session()->get('user_email', ''),
            'user_id' => (string) $request->session()->get('user_id', ''),
        ]);
    }
}

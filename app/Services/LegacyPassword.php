<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Verifikasi hash password/PIN yang dibuat Werkzeug (Flask).
 *
 * Hash lama memakai format:
 *   scrypt:32768:8:1$salt$hex
 *   pbkdf2:sha256:600000$salt$hex
 *
 * PHP tidak mengekspos scrypt low-level yang dipakai Werkzeug, jadi scrypt
 * diverifikasi/dibuat lewat helper Python (legacy_bridge/legacy_password.py).
 * Password baru tetap memakai format Werkzeug agar aplikasi Flask lama masih
 * bisa memverifikasinya (kompatibel dua arah/rollback).
 */
class LegacyPassword
{
    public function verify(string $hash, string $plain): bool
    {
        if ($hash === '' || $plain === '') {
            return false;
        }

        if (str_starts_with($hash, 'scrypt:')) {
            return (bool) $this->callPython('verify', $plain, $hash)['ok'];
        }

        if (str_starts_with($hash, 'pbkdf2:')) {
            return $this->verifyPbkdf2($hash, $plain);
        }

        if (str_starts_with($hash, 'plain$$')) {
            return hash_equals(substr($hash, 7), $plain);
        }

        // Hash baru dari Laravel (bcrypt/argon) sebagai cadangan.
        return password_verify($plain, $hash);
    }

    public function make(string $plain): string
    {
        $result = $this->callPython('make', $plain);

        if (! isset($result['hash']) || ! is_string($result['hash'])) {
            throw new RuntimeException('Gagal membuat hash password lama.');
        }

        return $result['hash'];
    }

    private function verifyPbkdf2(string $hash, string $plain): bool
    {
        if (! preg_match('/^pbkdf2:([a-z0-9]+)(?::(\d+))?\$([^$]+)\$([0-9a-fA-F]+)$/', $hash, $m)) {
            return false;
        }

        $algorithm = $m[1];
        $iterations = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 1000;
        $salt = $m[3];
        $expected = hex2bin($m[4]);

        if ($expected === false || ! in_array($algorithm, hash_algos(), true)) {
            return false;
        }

        $actual = hash_pbkdf2($algorithm, $plain, $salt, $iterations, strlen($expected), true);

        return hash_equals($expected, $actual);
    }

    /**
     * @return array{ok: bool, hash?: string, error?: string}
     */
    private function callPython(string $action, string $plain, string $hash = ''): array
    {
        $script = base_path('../legacy_bridge/legacy_password.py');

        if (! is_file($script)) {
            throw new RuntimeException('Helper Python tidak ditemukan: '.$script);
        }

        $process = new Process([
            (string) config('migration.python_bin', 'python'),
            $script,
        ]);

        $process->setEnv($this->pythonEnvironment());
        $process->setInput(json_encode([
            'action' => $action,
            'plain' => $plain,
            'hash' => $hash,
        ], JSON_UNESCAPED_UNICODE));
        $process->setTimeout(30);
        $process->run();

        $output = trim($process->getOutput());
        $decoded = json_decode($output, true);

        if (! is_array($decoded)) {
            throw new RuntimeException(
                'Helper Python gagal: '.($process->getErrorOutput() ?: $output)
            );
        }

        return $decoded;
    }

    /**
     * Symfony Process membuang variabel OS yang tidak ada di $_SERVER/$_ENV
     * pada SAPI non-CLI. Tanpa USERPROFILE/APPDATA, Python tidak menemukan
     * site-packages milik user sehingga modul werkzeug gagal dimuat.
     */
    private function pythonEnvironment(): array
    {
        $env = [];

        foreach ([
            'USERPROFILE', 'APPDATA', 'LOCALAPPDATA', 'HOME',
            'HOMEDRIVE', 'HOMEPATH', 'SystemRoot', 'windir',
            'PATH', 'PATHEXT', 'TEMP', 'TMP', 'COMSPEC',
        ] as $key) {
            $value = getenv($key);

            if ($value !== false) {
                $env[$key] = $value;
            }
        }

        $env['PYTHONUTF8'] = '1';

        return $env;
    }
}

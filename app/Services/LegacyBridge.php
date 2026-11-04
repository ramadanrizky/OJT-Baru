<?php

namespace App\Services;

use App\Models\LegacyUser;

/**
 * Menandatangani identitas user yang diteruskan Laravel ke Flask.
 * Flask hanya menerima request dari localhost dengan HMAC yang valid.
 */
class LegacyBridge
{
    public function headers(?LegacyUser $user): array
    {
        if (! $user) {
            return [];
        }

        $identity = [
            'id' => (string) $user->getKey(),
            'name' => (string) ($user->fullname ?? ''),
            'email' => (string) ($user->email ?? ''),
            'role' => (string) ($user->role ?? ''),
            'ts' => time(),
        ];

        $encoded = rtrim(strtr(base64_encode(json_encode($identity, JSON_UNESCAPED_UNICODE)), '+/', '-_'), '=');

        return [
            'X-Portal-Identity' => $encoded,
            'X-Portal-Signature' => hash_hmac('sha256', $encoded, (string) config('migration.bridge_secret')),
        ];
    }

    public static function verify(string $encoded, string $signature, string $secret): ?array
    {
        if ($secret === '') {
            return null;
        }

        $expected = hash_hmac('sha256', $encoded, $secret);
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $json = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        if (! is_array($data)) {
            return null;
        }

        $ttl = (int) config('migration.bridge_ttl', 300);
        if (abs(time() - (int) ($data['ts'] ?? 0)) > $ttl) {
            return null;
        }

        return $data;
    }
}

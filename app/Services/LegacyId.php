<?php

namespace App\Services;

class LegacyId
{
    private const ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * ID dokumen lama dibuat db_mysql.py: 20 karakter ascii_letters + digits.
     */
    public static function make(int $length = 20): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $id = '';

        for ($i = 0; $i < $length; $i++) {
            $id .= self::ALPHABET[random_int(0, $max)];
        }

        return $id;
    }
}

<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class SafeEncryptedString implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * If the stored value is a Laravel-encrypted string, decrypt it. Otherwise,
     * return it as-is (legacy plaintext fallback) so existing data keeps working.
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value) || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    /**
     * Prepare the given value for storage.
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if (is_null($value) || $value === '') {
            return $value;
        }

        // Avoid double-encrypting an already-encrypted value
        try {
            Crypt::decryptString($value);
            return $value;
        } catch (DecryptException) {
            return Crypt::encryptString($value);
        }
    }
}

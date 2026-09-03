<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;

if (!function_exists('aes_encrypt')) {
    /**
     * Encrypt string using AES-256-CBC with HMAC-SHA256 authentication.
     */
    function aes_encrypt(string $value, ?string $key = null): string
    {
        if ($key === null) {
            return Crypt::encryptString($value);
        }

        $binaryKey = str_starts_with($key, 'base64:')
            ? (string) base64_decode(substr($key, 7))
            : (strlen($key) === 32 ? $key : hash('sha256', $key, true));

        return (new Encrypter($binaryKey, 'AES-256-CBC'))->encryptString($value);
    }
}

if (!function_exists('aes_decrypt')) {
    /**
     * Decrypt payload using AES-256-CBC with HMAC verification.
     * Returns null if payload is invalid or tampered.
     */
    function aes_decrypt(string $payload, ?string $key = null): ?string
    {
        try {
            if ($key === null) {
                return Crypt::decryptString($payload);
            }

            $binaryKey = str_starts_with($key, 'base64:')
                ? (string) base64_decode(substr($key, 7))
                : (strlen($key) === 32 ? $key : hash('sha256', $key, true));

            return (new Encrypter($binaryKey, 'AES-256-CBC'))->decryptString($payload);
        } catch (DecryptException) {
            return null;
        }
    }
}

if (!function_exists('encrypt_recursive')) {
    /**
     * Recursively encrypt all ID fields in an array or collection (e.g. for dropdown options).
     */
    function encrypt_recursive(mixed $data, ?string $parentKey = null): mixed
    {
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                if (is_array($value) || $value instanceof Arrayable) {
                    $result[$key] = encrypt_recursive($value, (string) $key);
                } else {
                    $isTargetKey = (is_string($key) && _is_encryptable_id_key($key))
                        || ($parentKey !== null && _is_encryptable_id_key($parentKey));

                    if ($isTargetKey && (is_int($value) || (is_string($value) && is_numeric($value)))) {
                        $result[$key] = encrypt($value);
                    } else {
                        $result[$key] = $value;
                    }
                }
            }

            return $result;
        }

        return $data;
    }
}

if (!function_exists('encrypt_ids_recursive')) {
    function encrypt_ids_recursive(mixed $data): mixed
    {
        return encrypt_recursive($data);
    }
}

if (!function_exists('_is_encryptable_id_key')) {
    function _is_encryptable_id_key(string $key): bool
    {
        return $key === 'id'
            || $key === 'ids'
            || (bool) preg_match('/(_id|Id|_ids|Ids)$/', $key);
    }
}


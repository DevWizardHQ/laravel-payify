<?php

namespace DevWizard\Payify\Support;

class SignatureVerifier
{
    public static function hmacSha256(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public static function verifyHmacSha256(string $payload, string $secret, string $signature): bool
    {
        return self::equals(self::hmacSha256($payload, $secret), $signature);
    }

    public static function equals(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        $padded = str_pad($data, strlen($data) + (4 - strlen($data) % 4) % 4, '=');

        return base64_decode(strtr($padded, '-_', '+/'));
    }

    public static function verifyRsa(string $payload, string $signature, string $publicKeyPem): bool
    {
        $key = openssl_pkey_get_public($publicKeyPem);
        if (! $key) {
            return false;
        }

        return openssl_verify($payload, $signature, $key, OPENSSL_ALGO_SHA256) === 1;
    }
}

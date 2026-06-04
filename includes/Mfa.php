<?php

declare(strict_types=1);

/**
 * TOTP RFC 6238 — compatível com Google Authenticator / Authy.
 */
final class Mfa
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, 31)];
        }
        return $secret;
    }

    public static function qrCodeUrl(string $secret, string $email): string
    {
        $cfg = require ONECHECK_ROOT . '/config/auth.php';
        $issuer = rawurlencode($cfg['mfa_issuer']);
        $label = rawurlencode($cfg['mfa_issuer'] . ':' . $email);
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data="
            . rawurlencode("otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}");
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeForSlice($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    private static function codeForSlice(string $secret, int $timeSlice): string
    {
        $key = self::base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $value = (
            ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff)
        );
        return str_pad((string) ($value % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        foreach (str_split($secret) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }
        return $output;
    }

    public static function isMandatoryForProfile(string $perfil): bool
    {
        $cfg = require ONECHECK_ROOT . '/config/auth.php';
        return in_array($perfil, $cfg['perfis_mfa_obrigatorio'], true);
    }
}

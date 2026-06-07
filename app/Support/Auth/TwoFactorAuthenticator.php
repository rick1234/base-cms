<?php

namespace App\Support\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

class TwoFactorAuthenticator
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, strlen(self::BASE32_ALPHABET) - 1)];
        }

        return $secret;
    }

    public function provisioningUri(User $user, string $secret, ?string $issuer = null): string
    {
        $issuer = $issuer ?: config('app.name', 'Base CMS');
        $label = rawurlencode($issuer.':'.($user->email ?: $user->displayName()));

        return 'otpauth://totp/'.$label.'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function qrCodeSvg(User $user, string $secret, ?string $issuer = null): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($this->provisioningUri($user, $secret, $issuer));
    }

    public function verify(?string $secret, ?string $code, int $window = 1): bool
    {
        $secret = $this->normalizeSecret($secret);
        $code = preg_replace('/\s+/', '', (string) $code);

        if ($secret === '' || ! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timeSlice = intdiv(time(), 30);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->code($secret, $timeSlice + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    public function code(string $secret, ?int $timeSlice = null): string
    {
        $timeSlice ??= intdiv(time(), 30);
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0).pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function normalizeSecret(?string $secret): string
    {
        return Str::of((string) $secret)
            ->upper()
            ->replaceMatches('/[^A-Z2-7]/', '')
            ->toString();
    }

    private function base32Decode(string $secret): string
    {
        $secret = $this->normalizeSecret($secret);
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        foreach (str_split($secret) as $char) {
            $value = strpos(self::BASE32_ALPHABET, $char);

            if ($value === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}

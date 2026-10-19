<?php

namespace App\Service;

class tokenComputer {
    private string $secret;
    public function __construct()
    {
        $this->secret = config('app.key');
    }
    public function b64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function b64url_decode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function createToken(array $payload, int $ttlSec = 3600): string
    {
        $payload['exp'] = time() + $ttlSec;
        $payload['jti'] = bin2hex(random_bytes(8));

        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $body = $this->b64url_encode($json);
        $sig  = $this->b64url_encode(hash_hmac('sha256', $body, $this->secret, true));
        return "$body.$sig";
    }

    public function verifyToken(?string $token): ?array
    {
        if (!$token || substr_count($token, '.') !== 1) return null;
        [$body, $sig] = explode('.', $token);

        $expected = $this->b64url_encode(hash_hmac('sha256', $body, $this->secret, true));
        if (!hash_equals($expected, $sig)) return null; // tanda tangan palsu

        $data = json_decode($this->b64url_decode($body), true);
        if (!is_array($data) || ($data['exp'] ?? 0) < time()) return null; // expired

        return $data;
    }
}
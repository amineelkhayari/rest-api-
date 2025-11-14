<?php
namespace App\Middleware;

use Core\Request;
use Core\Response;

class JwtAuth
{
    private string $publicKey;
    private string $issuer;
    private string $audience;

    public function __construct(string $publicKey, string $issuer, string $audience)
    {
        $this->publicKey = $publicKey;
        $this->issuer = $issuer;
        $this->audience = $audience;
    }

    public function __invoke(Request $req, Response $res, $next)
    {
        $auth = $req->getHeader('Authorization');
        if (!$auth || !preg_match('/^Bearer (.+)$/', $auth, $m)) {
            return $res->json(['error' => 'Missing or invalid Authorization header'], 401);
        }
        $jwt = $m[1];
        $payload = $this->decodeJwt($jwt);
        if (!$payload) {
            return $res->json(['error' => 'Invalid token'], 401);
        }
        if (($payload['iss'] ?? '') !== $this->issuer || ($payload['aud'] ?? '') !== $this->audience) {
            return $res->json(['error' => 'Invalid token claims'], 401);
        }
        if (($payload['exp'] ?? 0) < time()) {
            return $res->json(['error' => 'Token expired'], 401);
        }
        // Optionally attach user info to request
        $req->user = $payload;
        return $next($req, $res);
    }

    private function decodeJwt(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$header64, $payload64, $signature64] = $parts;

        $header = json_decode($this->base64UrlDecode($header64), true);
        $payload = json_decode($this->base64UrlDecode($payload64), true);

        if (!$header || !$payload) {
            return null;
        }

        $data = $header64 . '.' . $payload64;
        $signature = $this->base64UrlDecode($signature64);

        // ✅ Verify the signature before trusting the payload
        if (!$this->verifySignature($data, $signature, $header['alg'] ?? '', $this->publicKey)) {
            return null;
        }

        return $payload;
    }

    private function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private function verifySignature($data, $signature, $alg, $publicKey): bool
    {
        if ($alg === 'RS256') {
            return openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
        }
        // Add other algorithms if needed
        return false;
    }
}

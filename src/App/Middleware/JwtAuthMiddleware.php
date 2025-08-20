<?php

/**
 * JwtAuthMiddleware.php
 * Pure-PHP JWT validator for IdentityServer (ES256), no Composer.
 *
 * Usage (vanilla PHP):
 * -------------------------------------------------
 * require 'JwtAuthMiddleware.php';
 * $auth = new JwtAuthMiddleware(
 *     issuer: 'https://localhost:5001',   // <-- your IdentityServer base URL
 *     audience: 'manager',                // optional, set if you check 'aud'
 *     cacheFile: __DIR__.'/_jwks_cache.json',
 *     cacheTtl: 3600,                     // 1 hour cache
 *     clockSkew: 60                       // seconds of leeway for exp/nbf
 * );
 *
 * // Example "middleware" style:
 * $claims = $auth->requireBearer(); // exits with 401 on failure
 * // ... your protected code here, $claims has the JWT claims array ...
 *
 * echo "Hello " . ($claims['fullname'] ?? 'user');
 */

class JwtAuthMiddleware
{
    private string $issuer;
    private ?string $audience;
    private string $cacheFile;
    private int $cacheTtl;
    private int $clockSkew;

    public function __construct(
        string $issuer,
        ?string $audience = null,
        string $cacheFile = '/tmp/jwks_cache.json',
        int $cacheTtl = 3600,
        int $clockSkew = 60
    ) {
        $this->issuer = rtrim($issuer, '/');
        $this->audience = $audience;
        $this->cacheFile = $cacheFile;
        $this->cacheTtl = $cacheTtl;
        $this->clockSkew = $clockSkew;
    }

    /** Call this in your route before protected logic. Returns claims array or exits 401. */
    public function requireBearer(): array
    {
        $jwt = $this->getBearerToken();
        if (!$jwt) {
            $this->unauthorized('Missing Bearer token');
            return []; // <- satisfy Intelephense
        }

        try {
            $claims = $this->validate($jwt);
            return $claims;
        } catch (Exception $e) {
            $this->unauthorized($e->getMessage());
            return []; // <- satisfy Intelephense
        }

        // fallback (should never reach here)
        return [];
    }


    /** Validates a JWT and returns claims as associative array. Throws on error. */
    public function validate(string $jwt): array
    {
        [$hB64, $pB64, $sB64] = $this->splitJwt($jwt);

        $headerJson  = $this->b64url_decode($hB64);
        $payloadJson = $this->b64url_decode($pB64);
        if ($headerJson === false || $payloadJson === false) {
            throw new Exception('Invalid base64url encoding');
        }

        $header  = json_decode($headerJson, true);
        $claims  = json_decode($payloadJson, true);
        if (!is_array($header) || !is_array($claims)) {
            throw new Exception('Invalid JWT JSON');
        }

        $alg = $header['alg'] ?? null;
        $kid = $header['kid'] ?? null;
        if ($alg !== 'ES256') {
            throw new Exception('Unsupported alg (expected ES256)');
        }
        if (!$kid) {
            throw new Exception('Missing kid');
        }

        // Fetch JWKS (cached) and pick the key by kid
        $jwks = $this->getJwks();
        $jwk = $this->selectJwkByKid($jwks, $kid);
        if (!$jwk) {
            throw new Exception("No matching JWK for kid=$kid");
        }
        if (($jwk['kty'] ?? '') !== 'EC' || ($jwk['crv'] ?? '') !== 'P-256') {
            throw new Exception('Unsupported key type/curve (expected EC P-256)');
        }

        // Build PEM from JWK (x,y) and verify signature
        $publicKeyPem = $this->ecJwkToPem($jwk);
        $data = $hB64 . '.' . $pB64;
        $rawSig = $this->b64url_decode($sB64);
        if ($rawSig === false) {
            throw new Exception('Invalid signature base64url');
        }
        $derSig = $this->ecdsaRawToDer($rawSig);
        $ok = openssl_verify($data, $derSig, $publicKeyPem, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            throw new Exception('Signature verification failed');
        }

        // Validate registered claims
        $now = time();
        if (isset($claims['iss']) && $claims['iss'] !== $this->issuer) {
            throw new Exception('Invalid iss');
        }
        if ($this->audience !== null) {
            $aud = $claims['aud'] ?? null;
            $audValid = is_array($aud) ? in_array($this->audience, $aud, true) : ($aud === $this->audience);
            if (!$audValid) {
                throw new Exception('Invalid aud');
            }
        }
        if (isset($claims['exp']) && ($now - $this->clockSkew) >= (int)$claims['exp']) {
            throw new Exception('Token expired');
        }
        if (isset($claims['nbf']) && ($now + $this->clockSkew) < (int)$claims['nbf']) {
            throw new Exception('Token not yet valid');
        }
        if (isset($claims['iat']) && ($now + $this->clockSkew) < (int)$claims['iat'] - 86400 * 365) {
            // absurd iat in the far future (basic sanity check; optional)
            throw new Exception('Invalid iat');
        }

        return $claims;
    }

    /* ------------------------- Helpers below ------------------------- */

    private function getBearerToken(): ?string
    {
        $header = null;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $k => $v) {
                if (strtolower($k) === 'authorization') {
                    $header = $v;
                    break;
                }
            }
        }
        if (!$header) return null;

        if (preg_match('/^\s*Bearer\s+([A-Za-z0-9\-\._~\+\/]+=*)\s*$/i', $header, $m)) {
            return $m[1];
        }
        return null;
    }

    private function splitJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format');
        }
        return $parts;
    }

    private function b64url_decode(string $data)
    {
        $data = strtr($data, '-_', '+/');
        return base64_decode($data);
    }

    /** Fetch JWKS (cached) via the OIDC discovery document. */
    private function getJwks(): array
    {
        // Load cache if fresh
        if (is_file($this->cacheFile)) {
            $age = time() - filemtime($this->cacheFile);
            if ($age <= $this->cacheTtl) {
                $cached = json_decode(file_get_contents($this->cacheFile), true);
                if (is_array($cached)) return $cached;
            }
        }

        $oidc = $this->httpGetJson($this->issuer . '/.well-known/openid-configuration');
        $jwksUri = $oidc['jwks_uri'] ?? ($this->issuer . '/.well-known/openid-configuration/jwks'); // fallback
        $jwks = $this->httpGetJson($jwksUri);

        // Save cache
        @file_put_contents($this->cacheFile, json_encode($jwks, JSON_UNESCAPED_SLASHES));

        return $jwks;
    }

    private function httpGetJson(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false, // NOTE: set true and add CA in production
            CURLOPT_SSL_VERIFYHOST => 0,     // NOTE: set 2 in production
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Exception("HTTP GET failed: $err");
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code < 200 || $code >= 300) {
            throw new Exception("HTTP $code from $url");
        }
        $json = json_decode($resp, true);
        if (!is_array($json)) {
            throw new Exception("Invalid JSON from $url");
        }
        return $json;
    }

    private function selectJwkByKid(array $jwks, string $kid): ?array
    {
        foreach (($jwks['keys'] ?? []) as $k) {
            if (($k['kid'] ?? null) === $kid) return $k;
        }
        return null;
    }

    /** Convert EC (P-256) JWK (x,y) → PEM public key. */
    private function ecJwkToPem(array $jwk): string
    {
        $x = $this->b64url_decode($jwk['x']);
        $y = $this->b64url_decode($jwk['y']);
        if ($x === false || $y === false) {
            throw new Exception('Invalid JWK x/y');
        }

        // Uncompressed EC point (0x04 || X || Y)
        $ecPoint = "\x04" . $x . $y;

        // ASN.1 SubjectPublicKeyInfo for EC P-256:
        // SEQUENCE(
        //   SEQ( OID ecPublicKey, OID prime256v1 ),
        //   BIT STRING( 0x00 + ecPoint )
        // )
        $der =
            "\x30\x59" .
            "\x30\x13" .
            "\x06\x07\x2A\x86\x48\xCE\x3D\x02\x01" .       // OID: ecPublicKey
            "\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07" .   // OID: prime256v1
            "\x03\x42\x00" .                                // BIT STRING, 66 bytes incl 0x00 padding
            $ecPoint;

        $pem = "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split(base64_encode($der), 64, "\n") .
            "-----END PUBLIC KEY-----\n";

        return $pem;
    }

    /** Convert raw (r||s) 64-byte ECDSA signature → ASN.1 DER for OpenSSL. */
    private function ecdsaRawToDer(string $raw): string
    {
        $len = strlen($raw);
        if ($len % 2 !== 0) throw new Exception('Invalid ECDSA raw length');
        $half = intdiv($len, 2);
        $r = ltrim(substr($raw, 0, $half), "\x00");
        $s = ltrim(substr($raw, $half), "\x00");

        $encodeInt = function (string $v): string {
            if ($v === '') $v = "\x00";
            if (ord($v[0]) > 0x7f) $v = "\x00" . $v; // ensure positive INTEGER
            return "\x02" . chr(strlen($v)) . $v;
        };

        $rEnc = $encodeInt($r);
        $sEnc = $encodeInt($s);
        $seq  = $rEnc . $sEnc;

        return "\x30" . chr(strlen($seq)) . $seq;
    }

    private function unauthorized(string $reason): void
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'unauthorized', 'reason' => $reason]);
        exit;
    }
}

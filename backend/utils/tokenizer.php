<?php

class Tokenizer {
    /**
     * Retrieves secret key from environment or falls back to development key
     */
    private static function secret(): string {
        $secret = getenv('IVOTE_JWT_SECRET');
        if ($secret === false || $secret === '') {
            return 'ivote-dev-secret-change-me';
        }
        return $secret;
    }

    /**
     * Generates a signed HMAC-SHA256 JWT token valid for 30 days
     */
    public static function generateToken(string $email, int $ttlSeconds = 2592000): string {
        $payload = [
            'createdAt' => time(),
            'expires'   => time() + $ttlSeconds,
            'email'     => $email,
        ];
        return self::encodeJWT($payload);
    }

    /**
     * Decodes and validates signature + format. Returns decoded payload array or null if invalid.
     */
    public static function decodeToken(string $jwt): ?array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", self::secret(), true)
        );

        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        return is_array($payload) ? $payload : null;
    }

    /**
     * Verifies signature and expiration
     */
    public static function isValid(string $jwt): bool {
        $payload = self::decodeToken($jwt);
        return $payload !== null
            && isset($payload['expires'], $payload['email'])
            && (int)$payload['expires'] > time();
    }

    private static function encodeJWT(array $payload): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $headerEncoded = self::base64UrlEncode($header);

        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", self::secret(), true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
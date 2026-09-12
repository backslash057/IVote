<?php

class Tokenizer {
    private static function secret(): string {
        $secret = getenv('IVOTE_JWT_SECRET');
        if ($secret === false || $secret === '') {
            // Fallback de développement uniquement. Définir IVOTE_JWT_SECRET en production.
            $secret = 'ivote-dev-secret-change-me';
        }
        return $secret;
    }

    // Génère un JWT signé HMAC-SHA256
    public static function generateToken(string $email): string {
        $payload = [
            'createdAt' => time(),
            'expires' => time() + 60 * 60 * 24,
            'email' => $email,
        ];
        return self::encodeJWT($payload);
    }

    // Décode ET vérifie la signature du JWT. Retourne null si invalide.
    public static function decodeToken(string $jwt): ?array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "$headerEncoded.$payloadEncoded", self::secret(), true)
        );

        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        return is_array($payload) ? $payload : null;
    }

    // Vérifie la signature et l'expiration d'un token
    public static function isValid(string $jwt): bool {
        $payload = self::decodeToken($jwt);
        return $payload !== null
            && isset($payload['expires'], $payload['email'])
            && (int) $payload['expires'] > time();
    }

    // Encode une structure JWT
    private static function encodeJWT(array $payload): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $headerEncoded = self::base64UrlEncode($header);

        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", self::secret(), true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    // Encodage Base64 URL
    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // Décodage Base64 URL
    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
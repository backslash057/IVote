<?php

class Tokenizer {
    private static $secretKey = "i7gDGHTKGcXe36UzRiPbuexJvzXC2HFhGU7enz25bIEAK3kd7eT";

    // Generate JWT Token
    public static function generateToken($email) {
        $payload = [
            'createdAt' => time(),
            'expires' => time() + 60*60*24,
            'email' => $email
        ];

        return self::encodeJWT($payload);
    }

    // Decode JWT Token
    public static function decodeToken($jwt) {
        list($header, $payload, $signature) = explode('.', $jwt);

        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);
        return $decodedPayload;
    }

    // Validate JWT Token
    public static function validateToken($jwt) {
        try {
            $payload = self::decodeToken($jwt);
            if (isset($payload['exp']) && $payload['exp'] > time()) {
                return $payload['sub'];
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    // Encode JWT Structure
    private static function encodeJWT($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = self::base64UrlEncode($header);

        $payload = json_encode($payload);
        $payload = self::base64UrlEncode($payload);

        $signature = hash_hmac('sha256', "$header.$payload", self::$secretKey, true);
        $signature = self::base64UrlEncode($signature);

        return "$header.$payload.$signature";
    }

    // Base64 URL Encoding
    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    // Base64 URL Decoding
    private static function base64UrlDecode($data) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}

?>

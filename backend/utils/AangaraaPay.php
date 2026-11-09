<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/utils/http.php';

class AangaraaPayException extends RuntimeException
{
    public array $gatewayPayload = [];

    public static function withPayload(string $message, array $payload): self
    {
        $e = new self($message);
        $e->gatewayPayload = $payload;
        return $e;
    }
}

/**
 * Client minimal pour l'agrégateur AangaraaPay (PIALOA-TECH).
 *
 * Le transport cURL est utilisé pour récupérer séparément les erreurs réseau,
 * le code HTTP et le corps JSON de la passerelle.
 *
 * Configuration (variables d'environnement / backend/.env) :
 *   AANGARAA_ENABLED  = "1" pour activer les paiements réels (sinon simulation CSV)
 *   AANGARAA_APP_KEY  = clé fournie par AangaraaPay
 *   AANGARAA_API_URL  = base API (défaut : https://api-production.aangaraa-pay.com/api/v1)
 *   AANGARAA_NOTIFY_URL = URL webhook appelée par AangaraaPay
 */
class AangaraaPay
{
    public const STATUS_SUCCESS = 'SUCCESSFUL';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_CANCELLED = 'CANCELLED';

    private const OPERATORS = [
        'mtn_momo' => 'MTN MoMo',
        'orange_money' => 'Orange Money',
    ];

    public static function enabled(): bool
    {
        $flag = getenv('AANGARAA_ENABLED');
        return $flag !== false && $flag !== '' && $flag !== '0';
    }

    public static function appKey(): string
    {
        return trim((string)getenv('AANGARAA_APP_KEY'));
    }

    public static function apiUrl(): string
    {
        $url = trim((string)getenv('AANGARAA_API_URL'));
        if ($url === '') {
            return 'https://api-production.aangaraa-pay.com/api/v1';
        }
        return rtrim($url, '/');
    }

    public static function notifyUrl(): string
    {
        $url = trim((string)getenv('AANGARAA_NOTIFY_URL'));
        if ($url === '') {
            return 'https://ivote.arited.org/api/webhooks/aangaraa';
        }
        return $url;
    }

    public static function configured(): bool
    {
        return self::enabled() && self::appKey() !== '';
    }

    /**
     * Appel JSON vers la passerelle.
     *
     * @throws AangaraaPayException si la passerelle est injoignable
     */
    private static function request(string $method, string $path, array $payload = []): array
    {
        if (($payload['app_key'] ?? null) === '') {
            unset($payload['app_key']);
        }

        $url = self::apiUrl() . $path;
        if (!function_exists('curl_init')) {
            throw new AangaraaPayException('Le module PHP cURL est requis pour contacter AangaraaPay.');
        }

        $handle = curl_init($url);
        if ($handle === false) {
            throw new AangaraaPayException('Impossible d\'initialiser le client HTTP AangaraaPay.');
        }

        $options = [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: IVote/1.0',
                'Connection: close',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if ($payload !== []) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
        }
        curl_setopt_array($handle, $options);

        $body = curl_exec($handle);
        $httpStatus = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);

        if ($body === false) {
            error_log(sprintf(
                '[IVote] AangaraaPay request failed (%s %s):%s',
                $method,
                $url,
                $curlError !== '' ? ' ' . $curlError : ''
            ));
            throw new AangaraaPayException('Impossible de joindre l\'API AangaraaPay.');
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            $data = ['raw' => $body];
        }
        $data['__http_status'] = $httpStatus;
        return $data;
    }

    /**
     * Normalise un numéro de téléphone camerounais vers le format international
     * "2376XXXXXXXX". Retourne null si invalide ou non mobile.
     *
     * Accepte : "6XXXXXXXX" (9 digits), "2376XXXXXXXX", "+2376XXXXXXXX",
     * "002376XXXXXXXX", espaces/chiffres séparés par des 0 ou +.
     */
    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === null) {
            return null;
        }
        if (str_ends_with($digits, '237') && strlen($digits) > 9) {
            // cas "006XXXXXXXX237" improbable, on ignore ce chemin
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '237')) {
            $digits = substr($digits, 3);
        }
        if (strlen($digits) !== 9 || $digits[0] !== '6') {
            return null;
        }
        return '237' . $digits;
    }

    /**
     * Détecte l'opérateur à partir du numéro national 9 chiffres.
     * MTN : préfixes 650-654, 670-683 | Orange : 655-659, 690-699.
     */
    public static function detectOperator(string $phone): ?string
    {
        $normalized = self::normalizePhone($phone);
        if ($normalized === null) {
            return null;
        }
        $national = substr($normalized, 3);
        $prefix = (int)substr($national, 0, 3);
        if (($prefix >= 650 && $prefix <= 654) || ($prefix >= 670 && $prefix <= 683)) {
            return 'MTN';
        }
        if (($prefix >= 655 && $prefix <= 659) || ($prefix >= 690 && $prefix <= 699)) {
            return 'ORANGE';
        }
        return null;
    }

    public static function operatorLabel(string $operator, string $fallback = ''): string
    {
        return self::OPERATORS[$operator] ?? $fallback;
    }

    public static function gatewayOperator(string $paymentMethod): ?string
    {
        return match ($paymentMethod) {
            'mtn_momo' => 'MTN_Cameroon',
            'orange_money' => 'Orange_Cameroon',
            default => null,
        };
    }

    /**
     * Vérifie qu'un numéro est compatible avec le moyen de paiement choisi.
     */
    public static function phoneMatchesMethod(string $phone, string $paymentMethod): bool
    {
        $operator = self::detectOperator($phone);
        if ($operator === null) {
            return false;
        }
        return ($paymentMethod === 'mtn_momo' && $operator === 'MTN')
            || ($paymentMethod === 'orange_money' && $operator === 'ORANGE');
    }

    /**
     * Démarre un paiement direct (no_redirect).
     *
     * @param array $params phone_number, amount, transaction_id,
     *                      description?, notify_url?, operator?
     * @return array réponse brute de la passerelle
     */
    public static function initiate(array $params): array
    {
        $payload = [
            'phone_number' => self::normalizePhone((string)($params['phone_number'] ?? '')),
            'amount' => (string)(int)($params['amount'] ?? 0),
            'description' => trim((string)($params['description'] ?? 'Vote IVote')),
            'app_key' => self::appKey(),
            'transaction_id' => (string)($params['transaction_id'] ?? ''),
            'notify_url' => (string)($params['notify_url'] ?? self::notifyUrl()),
            'devise_id' => 'XAF',
        ];
        if (!empty($params['operator'])) {
            $payload['operator'] = $params['operator'];
        }
        return self::request('POST', '/no_redirect/payment', $payload);
    }

    /**
     * Interroge l'état d'un paiement depuis son payToken.
     */
    public static function checkStatus(string $payToken): array
    {
        return self::request('POST', '/aangaraa_check_status', [
            'payToken' => $payToken,
            'app_key' => self::appKey(),
        ]);
    }

    /**
     * Déclenche un retrait / disbursement vers un wallet Mobile Money.
     *
     * @param array $params phone_number, amount, payment_method, username?,
     *                       transaction_id?
     */
    public static function withdraw(array $params): array
    {
        $payload = [
            'app_key' => self::appKey(),
            'phone_number' => self::normalizePhone((string)($params['phone_number'] ?? '')),
            'amount' => (int)($params['amount'] ?? 0),
            'payment_method' => (string)($params['payment_method'] ?? ''),
            'username' => (string)($params['username'] ?? 'IVote Organizer'),
        ];
        if (!empty($params['transaction_id'])) {
            $payload['transaction_id'] = $params['transaction_id'];
        }
        return self::request('POST', '/aangaraa-pay/withdrawal', $payload);
    }

    /**
     * Extrait un statut normalisé (SUCCESSFUL/PENDING/FAILED/EXPIRED/CANCELLED)
     * d'une réponse brute de la passerelle, quelle que soit sa forme.
     */
    public static function resolveStatus(array $data): string
    {
        $candidates = [
            $data['status'] ?? '',
            $data['code'] ?? '',
            $data['data']['status'] ?? '',
            $data['data']['code'] ?? '',
            $data['payment']['status'] ?? '',
            $data['message'] ?? '',
            $data['error'] ?? '',
        ];
        foreach ($candidates as $value) {
            if (is_string($value)) {
                $map = [
                    'SUCCESSFUL' => self::STATUS_SUCCESS,
                    'SUCCESS' => self::STATUS_SUCCESS,
                    'PENDING' => self::STATUS_PENDING,
                    'PENDING_CONFIRMATION' => self::STATUS_PENDING,
                    'FAILED' => self::STATUS_FAILED,
                    'FAILURE' => self::STATUS_FAILED,
                    'EXPIRED' => self::STATUS_EXPIRED,
                    'CANCELLED' => self::STATUS_CANCELLED,
                    'CANCELED' => self::STATUS_CANCELLED,
                ];
                $key = strtoupper(trim($value));
                if (isset($map[$key])) {
                    return $map[$key];
                }
            }
        }
        return '';
    }

    public static function isSuccess(array $data): bool
    {
        return self::resolveStatus($data) === self::STATUS_SUCCESS;
    }
}
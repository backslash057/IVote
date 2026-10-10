<?php

require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Vrai quand la requête est arrivée en HTTPS côté PHP.
 *
 * `IVOTE_FORWARDED_PROTO` est injecté par notre nginx (voir deploy/nginx.conf)
 * depuis `$remote_addr`/`$scheme` : contrairement à `X-Forwarded-Proto`, un
 * client ne peut pas le falsifier. Repli sur HTTPS / X-Forwarded-Proto pour
 * les autres environnements.
 */
function http_request_is_https(): bool {
    $forwarded = (string)($_SERVER['IVOTE_FORWARDED_PROTO'] ?? getenv('IVOTE_FORWARDED_PROTO') ?: '');
    if ($forwarded !== '') {
        return strtolower($forwarded) === 'https';
    }

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

/**
 * Origines autorisées à consommer l'API (séparées par des virgules).
 * Par défaut : uniquement les origines de développement locale.
 */
function http_allowed_origins(): array {
    $configured = getenv('IVOTE_ALLOWED_ORIGINS');
    if ($configured === false || trim($configured) === '') {
        $configured = 'http://localhost:3000,http://127.0.0.1:3000';
    }

    $origins = [];
    foreach (explode(',', $configured) as $origin) {
        $origin = rtrim(trim($origin), '/');
        if ($origin !== '') {
            $origins[] = $origin;
        }
    }
    return $origins;
}

/**
 * Renvoie l'origine à répercuter en CORS, ou null si elle n'est pas autorisée.
 * Les requêtes same-origin n'ont pas besoin de CORS et renvoient null.
 */
function http_cors_origin(): ?string {
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin === '') {
        return null;
    }
    return in_array($origin, http_allowed_origins(), true) ? $origin : null;
}

function http_send_cors_headers(): void {
    $origin = http_cors_origin();
    if ($origin === null) {
        return;
    }

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
}

/**
 * Traite un preflight CORS. Retourne true si la requête est un OPTIONS interrompu.
 */
function http_handle_cors_preflight(): bool {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    if ($method !== 'OPTIONS' || strpos($uri, '/api/') !== 0) {
        return false;
    }

    http_send_cors_headers();
    http_response_code(204);
    return true;
}

/**
 * Vrai quand l'en-tête Origin désigne un autre hôte que celui de la requête.
 * Sert de garde CSRF pour les sessions portées par le cookie `auth_token`.
 * Sans en-tête Origin, ce n'est pas une requête navigante cross-site.
 */
function http_origin_is_cross_site(): bool {
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin === '') {
        return false;
    }

    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return true;
    }

    $originHost = strtolower((string)(parse_url($origin, PHP_URL_HOST) ?? ''));
    $originPort = parse_url($origin, PHP_URL_PORT);
    if ($originHost === '') {
        return true;
    }

    $originAuthority = $originHost . ($originPort !== null && $originPort !== false ? ':' . $originPort : '');
    return $originAuthority !== $host;
}

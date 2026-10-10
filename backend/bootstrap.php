<?php

/**
 * Chargement de la configuration avant tout usage de la base ou du JWT.
 *
 * - lit `backend/.env` si présent (les variables réelles du système priment)
 * - garantit que $_SERVER['DOCUMENT_ROOT'] est défini (utile sous PHP-FPM)
 * - échec ferme : si un secret requis manque, on refuse de démarrer
 *   (réponse HTML pour le navigateur, JSON pour /api ; les fichiers sous
 *   /public et /uploads ne sont pas bloqués afin de garder les assets lisibles)
 */

if (defined('IVOTE_BOOTSTRAPPED')) {
    return;
}
define('IVOTE_BOOTSTRAPPED', true);

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
}

$envFile = __DIR__ . '/.env';
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ($key === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) !== 1) {
            continue;
        }
        if (getenv($key) !== false) {
            continue;
        }

        $value = trim($value);
        $length = strlen($value);
        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$requestPath = is_string($requestPath) && $requestPath !== '' ? $requestPath : '/';

// Les fichiers statiques (CSS, JS, images) n'utilisent ni base ni JWT : on ne
// bloque pas leur service quand la configuration est incomplète, sinon la page
// d'erreur elle-même arrive sans style.
$isStaticAsset = $requestPath === '/public'
    || str_starts_with($requestPath, '/public/')
    || $requestPath === '/uploads'
    || str_starts_with($requestPath, '/uploads/');

$missing = [];
if (!$isStaticAsset) {
    foreach (['DB_USER', 'DB_PASS', 'IVOTE_JWT_SECRET'] as $required) {
        $value = getenv($required);
        if ($value === false || $value === '') {
            $missing[] = $required;
        }
    }
}

if ($missing !== []) {
    $missingList = implode(', ', $missing);
    $message = 'Configuration serveur incomplète : ' . $missingList
        . ' manquant(s). Copiez .env.example vers .env puis renseignez les valeurs.';

    error_log('[IVote] ' . $message);

    $wantsHtml = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'text/html');
    $isApi = str_starts_with($requestPath, '/api/');
    $body = ['success' => false, 'message' => $message];
    $status = 500;

    if (!headers_sent()) {
        http_response_code($status);
        if ($isApi || !$wantsHtml) {
            header('Content-Type: application/json; charset=utf-8');
        } else {
            header('Content-Type: text/html; charset=utf-8');
        }
    }

    if ($isApi || !$wantsHtml) {
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
        exit(1);
    }

    $items = '';
    foreach ($missing as $name) {
        $items .= '<li><code>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</code></li>';
    }

    echo '<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>IVote — configuration serveur manquante</title>
<style>
  :root { color-scheme: dark; }
  * { box-sizing: border-box; }
  body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
         background: #0b0f0d; color: #e6efe9; font: 16px/1.6 ui-sans-serif, system-ui, "Segoe UI", sans-serif;
         padding: 32px 20px; }
  .card { max-width: 640px; width: 100%; background: #131a17; border: 1px solid #232d28;
          border-radius: 14px; padding: 28px 26px; box-shadow: 0 18px 48px rgba(0,0,0,.45); }
  h1 { margin: 0 0 6px; font-size: 21px; color: #34d399; }
  p { margin: 12px 0; color: #a9b8b0; }
  ul { margin: 12px 0; padding-left: 20px; }
  li { margin: 4px 0; }
  code { font: 13px/1.5 ui-monospace, "SF Mono", Menlo, Consolas, monospace;
         background: #0b0f0d; border: 1px solid #232d28; border-radius: 6px; padding: 2px 7px;
         color: #fcd34d; word-break: break-all; }
  pre { margin: 8px 0 4px; padding: 12px 14px; background: #0b0f0d; border: 1px solid #232d28;
        border-radius: 8px; overflow-x: auto; color: #34d399;
        font: 13px/1.6 ui-monospace, "SF Mono", Menlo, Consolas, monospace; }
  .note { font-size: 14px; color: #7f8f87; border-top: 1px solid #232d28; padding-top: 14px; }
</style>
</head>
<body>
<div class="card">
  <h1>Configuration serveur incomplète</h1>
  <p>Le backend refuse de démarrer tant que la configuration est absente.</p>
  <p>Variables manquantes :</p>
  <ul>' . $items . '</ul>
  <p>Depuis <code>backend/</code>, exécutez :</p>
  <pre>cp .env.example .env
nano .env   <span style="color:#7f8f87"># renseigner les valeurs manquantes</span></pre>
  <p class="note">Puis générez la signature JWT : <code>openssl rand -hex 48</code>.
     Les variables déjà présentes dans l\u{2019}environnement du système priment sur le fichier.
     <code>.env</code> est ignoré par git et ne doit jamais être committé.</p>
  <p class="note">Erreur détaillée enregistrée dans le journal PHP (<code>error_log</code>).</p>
</div>
</body>
</html>';
    exit(1);
}

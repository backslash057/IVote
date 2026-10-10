<?php

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/utils/tokenizer.php';
require_once dirname(__DIR__) . '/utils/http.php';
require_once dirname(__DIR__) . '/utils/RateLimiter.php';

abstract class BaseController {
    protected PDO $db;

    public function __construct() {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['dbname']
        );

        $this->db = new PDO($dsn, $config['username'], $config['password']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Parses JSON request body or standard $_POST
     */
    protected function getRequestData(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        return $_POST;
    }

    /**
     * Resolves the token from Bearer header or auth_token Cookie.
     * Returns null or ['token' => ..., 'source' => 'bearer'|'cookie'].
     */
    protected function resolveToken(): ?array {
        $headers = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? '',
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''
        ];

        foreach ($headers as $header) {
            if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
                return ['token' => $matches[1], 'source' => 'bearer'];
            }
        }

        $cookie = $_COOKIE['auth_token'] ?? null;
        if (is_string($cookie) && $cookie !== '') {
            return ['token' => $cookie, 'source' => 'cookie'];
        }

        return null;
    }

    /**
     * Returns authenticated user info or null
     */
    public function getAuthUser(): ?array {
        $resolved = $this->resolveToken();
        if (!$resolved || !Tokenizer::isValid($resolved['token'])) {
            return null;
        }

        // A cookie session is only honoured for requests coming from our own
        // origin: blocks cross-site request forgery on every mutating endpoint.
        if ($resolved['source'] === 'cookie' && http_origin_is_cross_site()) {
            error_log('[IVote] Requête rejetée : cookie auth_token utilisé depuis une origine tierce ('
                . ($_SERVER['HTTP_ORIGIN'] ?? 'n/a') . ').');
            return null;
        }

        $payload = Tokenizer::decodeToken($resolved['token']);
        if (!$payload || !isset($payload['email'])) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT user_id, name, email, role, status, session_version FROM Users WHERE email = ?");
        $stmt->execute([$payload['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['status'] !== 'active' || (int)($payload['sessionVersion'] ?? 0) !== (int)$user['session_version']) {
            return null;
        }

        return $user;
    }

    /**
     * IP cliente pour la limitation de débit.
     *
     * Priorité :
     *  1. `IVOTE_CLIENT_IP` : injecté par notre nginx (fastcgi_param), non falsifiable.
     *  2. `X-Real-IP` / `X-Forwarded-For` : uniquement si `IVOTE_TRUST_PROXY` est
     *     défini (mode reverse proxy `php -S`). Sûr car `php -S` n'écoute que sur
     *     127.0.0.1 : seul nginx peut fournir ces en-têtes.
     *  3. `REMOTE_ADDR` : repli direct.
     */
    protected function clientIp(): string {
        $candidates = [];

        if (getenv('IVOTE_TRUST_PROXY') !== false && getenv('IVOTE_TRUST_PROXY') !== '' && getenv('IVOTE_TRUST_PROXY') !== '0') {
            $candidates[] = (string)($_SERVER['HTTP_X_REAL_IP'] ?? '');
            $candidates[] = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        }

        $candidates[] = (string)($_SERVER['IVOTE_CLIENT_IP'] ?? '');
        $candidates[] = (string)($_SERVER['REMOTE_ADDR'] ?? '');

        foreach ($candidates as $candidate) {
            $candidate = strtolower(trim(explode(',', $candidate)[0]));
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return 'unknown';
    }

    /**
     * Aborts with 429 when the caller exceeded the allowed attempts.
     */
    protected function enforceRateLimit(string $bucket, string $key, int $maxAttempts, int $windowSeconds, string $message): void {
        if (RateLimiter::hit($bucket, $key, $maxAttempts, $windowSeconds)) {
            return;
        }

        http_response_code(429);
        header('Retry-After: ' . $windowSeconds);
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit(1);
    }

    /**
     * Requires user authentication or exits with 401
     */
    protected function requireAuth(): ?array {
        $user = $this->getAuthUser();
        if (!$user) {
            http_response_code(401);
            return null;
        }
        return $user;
    }

    protected function requireRole(string $role): ?array {
        $user = $this->requireAuth();
        if (!$user) {
            return null;
        }

        if (($user['role'] ?? 'organizer') !== $role) {
            http_response_code(403);
            return null;
        }

        return $user;
    }

    protected function requireSuperAdmin(): ?array {
        return $this->requireRole('super_admin');
    }

    protected function requireOrganizer(): ?array {
        return $this->requireRole('organizer');
    }

    protected function getPlatformFeeRate(): float {
        $stmt = $this->db->prepare("SELECT setting_value FROM PlatformSetting WHERE setting_key = 'platform_fee_rate'");
        $stmt->execute();
        $rate = $stmt->fetchColumn();
        return is_numeric($rate) ? max(0, min(100, (float)$rate)) : 10.0;
    }

    protected function writeAudit(
        ?int $actorId,
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        ?string $reason = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO AuditLog (actor_id, action, resource_type, resource_id, reason, old_values, new_values, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $actorId,
            $action,
            $resourceType,
            $resourceId,
            $reason,
            $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    protected function createNotification(
        int $recipientId,
        string $type,
        string $title,
        string $message,
        ?int $senderId = null,
        ?string $resourceType = null,
        ?int $resourceId = null
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO Notification (recipient_id, sender_id, type, title, message, resource_type, resource_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$recipientId, $senderId, $type, $title, $message, $resourceType, $resourceId]);
    }

    /**
     * Verifies that the authenticated user is the owner of a campaign
     */
    protected function verifyCampaignOwner(int $campaignId, int $userId): bool {
        $stmt = $this->db->prepare("SELECT campaign_id FROM Campaign WHERE campaign_id = ? AND organiser_id = ?");
        $stmt->execute([$campaignId, $userId]);
        return (bool)$stmt->fetchColumn();
    }

    protected function isCampaignEditable(int $campaignId): bool {
        $stmt = $this->db->prepare("SELECT status, is_draft FROM Campaign WHERE campaign_id = ?");
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        return $campaign && ($campaign['status'] === 'draft' || $campaign['status'] === 'pending_review' || (int)$campaign['is_draft'] === 1);
    }

    /**
     * Upload helper with validation
     */
    protected function handleFileUpload(string $fieldName, string $prefix = 'file_'): array {
        if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'url' => null];
        }

        $file = $_FILES[$fieldName];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erreur lors du téléchargement du fichier (code: ' . $file['error'] . ').'];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions, true)) {
            return ['success' => false, 'message' => 'Format de fichier invalide. Extensions acceptées: jpg, jpeg, png, webp.'];
        }

        $allowedMimeTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['success' => false, 'message' => 'Le fichier importé n\'est pas une image valide.'];
        }

        $detectedMime = (string)($imageInfo['mime'] ?? '');
        if ($detectedMime !== $allowedMimeTypes[$ext]) {
            return ['success' => false, 'message' => 'Le contenu de l\'image ne correspond pas à son extension.'];
        }

        $maxSize = 5 * 1024 * 1024; // 5 MB
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Le fichier dépasse la taille maximale autorisée (5 Mo).'];
        }

        $dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['success' => false, 'message' => 'Impossible de créer le répertoire de destination.'];
        }

        $filename = uniqid($prefix, true) . '.' . $ext;
        $destination = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Échec du déplacement du fichier importé.'];
        }

        @chmod($destination, 0644);

        return ['success' => true, 'url' => '/uploads/' . $filename];
    }
}
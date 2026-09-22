<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/utils/tokenizer.php';

abstract class BaseController {
    protected PDO $db;

    public function __construct() {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

        $this->db = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
            $config['username'],
            $config['password']
        );
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
     * Resolves the token from Bearer header or auth_token Cookie
     */
    protected function resolveToken(): ?string {
        $headers = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? '',
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''
        ];

        foreach ($headers as $header) {
            if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
                return $matches[1];
            }
        }

        return $_COOKIE['auth_token'] ?? null;
    }

    /**
     * Returns authenticated user info or null
     */
    public function getAuthUser(): ?array {
        $token = $this->resolveToken();
        if (!$token || !Tokenizer::isValid($token)) {
            return null;
        }

        $payload = Tokenizer::decodeToken($token);
        if (!$payload || !isset($payload['email'])) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT user_id, name, email FROM Users WHERE email = ?");
        $stmt->execute([$payload['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
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

    /**
     * Verifies that the authenticated user is the owner of a campaign
     */
    protected function verifyCampaignOwner(int $campaignId, int $userId): bool {
        $stmt = $this->db->prepare("SELECT campaign_id FROM Campaign WHERE campaign_id = ? AND organiser_id = ?");
        $stmt->execute([$campaignId, $userId]);
        return (bool)$stmt->fetchColumn();
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

        return ['success' => true, 'url' => '/uploads/' . $filename];
    }
}
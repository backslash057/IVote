<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';

class AuthController extends BaseController {

    public function login(): array {
        $data = $this->getRequestData();
        $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = (string)($data['password'] ?? '');

        if (!$email || empty($password)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Email et mot de passe requis.'];
        }

        $this->enforceRateLimit(
            'auth_login',
            $this->clientIp() . '|' . strtolower($email),
            10,
            300,
            'Trop de tentatives de connexion. Réessayez dans quelques minutes.'
        );

        $stmt = $this->db->prepare("SELECT user_id, name, email, password, role, status, session_version FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['status'] !== 'active') {
            http_response_code(403);
            return ['success' => false, 'message' => 'Ce compte est suspendu ou désactivé.'];
        }

        if ($user && password_verify($password, $user['password'])) {
            $token = Tokenizer::generateToken($email, 2592000, (int)$user['session_version']);
            $this->setAuthCookie($token);

            return [
                'success' => true,
                'message' => 'Connexion réussie.',
                'token' => $token,
                'redirect' => $user['role'] === 'super_admin' ? '/admin' : '/dashboard',
                'user' => [
                    'user_id' => (int)$user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ]
            ];
        }

        http_response_code(401);
        return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
    }

    public function signup(): array {
        $data = $this->getRequestData();
        $name = trim($data['name'] ?? '');
        $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = (string)($data['password'] ?? '');

        if (empty($name) || !$email || empty($password)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Tous les champs sont requis.'];
        }

        if (strlen($password) < 8) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères.'];
        }

        $this->enforceRateLimit(
            'auth_signup',
            $this->clientIp(),
            20,
            3600,
            'Trop de comptes créés depuis cette adresse. Réessayez plus tard.'
        );

        $stmt = $this->db->prepare("SELECT user_id FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Un utilisateur avec cet email existe déjà.'];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO Users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword]);

        $newUserId = (int)$this->db->lastInsertId();
        $token = Tokenizer::generateToken($email);
        $this->setAuthCookie($token);

        http_response_code(201);
        return [
            'success' => true,
            'message' => 'Compte créé avec succès.',
            'token' => $token,
            'redirect' => '/dashboard',
            'user' => [
                'user_id' => $newUserId,
                'name' => $name,
                'email' => $email,
                'role' => 'organizer',
            ]
        ];
    }

    public function logout(): array {
        setcookie("auth_token", "", [
            "expires"  => time() - 3600,
            "path"     => "/",
            "httponly" => true,
            "samesite" => "Strict",
            "secure"   => http_request_is_https()
        ]);

        return ['success' => true, 'message' => 'Déconnexion réussie.'];
    }

    public function me(): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        return [
            'success' => true,
            'user' => [
                'user_id' => (int)$user['user_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'organizer',
                'status' => $user['status'] ?? 'active',
            ]
        ];
    }

    /**
     * PUT/POST /api/auth/profile
     * Updates profile details (name, email, and optionally password)
     */
    public function updateProfile(): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $this->enforceRateLimit(
            'auth_profile',
            $this->clientIp(),
            20,
            300,
            'Trop de modifications de profil. Réessayez dans quelques minutes.'
        );

        $userId = (int)$user['user_id'];
        $data = $this->getRequestData();

        $name = trim($data['name'] ?? '');
        $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $currentPassword = (string)($data['current_password'] ?? '');
        $newPassword = (string)($data['new_password'] ?? '');

        if (empty($name) || !$email) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le nom et un email valide sont obligatoires.'];
        }

        // Check if new email is already used by another user
        $stmtCheck = $this->db->prepare("SELECT user_id FROM Users WHERE email = ? AND user_id != ?");
        $stmtCheck->execute([$email, $userId]);
        if ($stmtCheck->fetch()) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Cet e-mail est déjà utilisé par un autre compte.'];
        }

        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                http_response_code(400);
                return ['success' => false, 'message' => 'Mot de passe actuel requis pour changer de mot de passe.'];
            }

            $stmtPass = $this->db->prepare("SELECT password FROM Users WHERE user_id = ?");
            $stmtPass->execute([$userId]);
            $dbPass = (string)$stmtPass->fetchColumn();

            if (!password_verify($currentPassword, $dbPass)) {
                http_response_code(401);
                return ['success' => false, 'message' => 'Mot de passe actuel incorrect.'];
            }

            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtUpdate = $this->db->prepare(
                "UPDATE Users SET name = ?, email = ?, password = ?, session_version = session_version + 1 WHERE user_id = ?"
            );
            $stmtUpdate->execute([$name, $email, $newHash, $userId]);

            // Invalidate every other outstanding token for this account.
            $stmtVersion = $this->db->prepare("SELECT session_version FROM Users WHERE user_id = ?");
            $stmtVersion->execute([$userId]);
            $sessionVersion = (int)$stmtVersion->fetchColumn();
        } else {
            $stmtUpdate = $this->db->prepare("UPDATE Users SET name = ?, email = ? WHERE user_id = ?");
            $stmtUpdate->execute([$name, $email, $userId]);
            $sessionVersion = (int)($user['session_version'] ?? 0);
        }

        $token = Tokenizer::generateToken($email, 2592000, $sessionVersion);
        $this->setAuthCookie($token);

        return [
            'success' => true,
            'message' => 'Profil mis à jour avec succès.',
            'token' => $token,
            'user' => [
                'user_id' => $userId,
                'name' => $name,
                'email' => $email,
            ]
        ];
    }

    /**
     * GET /api/auth/stats
     * Returns organizer aggregate summary
     */
    public function stats(): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $userId = (int)$user['user_id'];

        $sql = "
            SELECT 
                COUNT(DISTINCT c.campaign_id) AS total_campaigns,
                COUNT(DISTINCT CASE WHEN c.is_draft = 0 AND (c.date_cloture IS NULL OR c.date_cloture >= NOW()) THEN c.campaign_id END) AS active_campaigns,
                COALESCE(SUM(v.vote_count), 0) AS total_votes,
                COALESCE(SUM(v.amount_fcfa), 0) AS total_revenue
            FROM Campaign c
            LEFT JOIN Vote v ON v.campaign_id = c.campaign_id
            WHERE c.organiser_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalRevenue = (int)($stats['total_revenue'] ?? 0);
        $stmtNet = $this->db->prepare("SELECT COALESCE(SUM(v.organizer_net_amount_fcfa), 0) FROM Vote v JOIN Campaign c ON c.campaign_id = v.campaign_id WHERE c.organiser_id = ? AND v.payment_status = 'confirmed'");
        $stmtNet->execute([$userId]);
        $netRevenue = (int)$stmtNet->fetchColumn();

        return [
            'success' => true,
            'data' => [
                'total_campaigns' => (int)($stats['total_campaigns'] ?? 0),
                'active_campaigns' => (int)($stats['active_campaigns'] ?? 0),
                'total_votes' => (int)($stats['total_votes'] ?? 0),
                'total_revenue' => $totalRevenue,
                'available_balance' => $netRevenue
            ]
        ];
    }

    private function setAuthCookie(string $token): void {
        setcookie("auth_token", $token, [
            "httponly" => true,
            "samesite" => "Strict",
            "secure"   => http_request_is_https(),
            "expires"  => time() + (60 * 60 * 24 * 30),
            "path"     => "/"
        ]);
    }
}
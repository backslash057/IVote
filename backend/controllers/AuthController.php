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

        $stmt = $this->db->prepare("SELECT user_id, name, email, password FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $token = Tokenizer::generateToken($email);
            $this->setAuthCookie($token);

            return [
                'success' => true,
                'message' => 'Connexion réussie.',
                'token' => $token,
                'user' => [
                    'user_id' => (int)$user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
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
            'user' => [
                'user_id' => $newUserId,
                'name' => $name,
                'email' => $email,
            ]
        ];
    }

    public function logout(): array {
        setcookie("auth_token", "", [
            "expires"  => time() - 3600,
            "path"     => "/",
            "httponly" => true,
            "samesite" => "Strict"
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
            ]
        ];
    }

    private function setAuthCookie(string $token): void {
        setcookie("auth_token", $token, [
            "httponly" => true,
            "samesite" => "Strict",
            "expires"  => time() + (60 * 60 * 24 * 30),
            "path"     => "/"
        ]);
    }
}
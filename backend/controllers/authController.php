<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/utils/tokenizer.php';

class AuthController {
    private $db;

    public function __construct() {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

        $this->db = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
            $config['username'], $config['password']
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function login() {
        $request = json_decode(file_get_contents('php://input'), true) ?? [];

        $email = filter_var(trim($request['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $request['password'] ?? '';

        if (!$email || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $stmt = $this->db->prepare("SELECT password FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $this->setAuthCookie($email);
            return ['success' => true, 'message' => 'Successfully connected'];
        }

        return ['success' => false, 'message' => 'Email or password is incorrect'];
    }

    public function signup() {
        $request = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = trim($request['name'] ?? '');
        $email = filter_var(trim($request['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $request['password'] ?? '';

        if (empty($name) || !$email || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        $stmt = $this->db->prepare("SELECT user_id FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'A user with that email already exists'];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO Users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword]);

        $this->setAuthCookie($email);
        return ['success' => true, 'message' => 'Sign up successful'];
    }

    public function logout() {
        setcookie("auth_token", "", [
            "expires"  => time() - 3600,
            "path"     => "/",
            "httponly" => true,
            "samesite" => "Strict"
        ]);

        return ['success' => true, 'message' => 'Successfully disconnected'];
    }

    /**
     * Résout le token JWT depuis le header Authorization: Bearer ou le cookie
     */
    private function resolveToken(): ?string {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])
            && preg_match('/Bearer\s+(\S+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        }
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])
            && preg_match('/Bearer\s+(\S+)/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        }
        return $_COOKIE['auth_token'] ?? null;
    }

    public function checkAuthentification() {
        $token = $this->resolveToken();
        if (!$token || !Tokenizer::isValid($token)) {
            return null;
        }

        $payload = Tokenizer::decodeToken($token);
        $stmt = $this->db->prepare("SELECT user_id, name, email FROM Users WHERE email = ?");
        $stmt->execute([$payload['email']]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Login pour l'API mobile : renvoie le token directement dans la réponse
     */
    public function apiLogin(): array {
        $request = json_decode(file_get_contents('php://input'), true) ?? [];

        $email = filter_var(trim($request['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $request['password'] ?? '';

        if (!$email || empty($password)) {
            return ['success' => false, 'message' => 'Email et mot de passe requis.', 'code' => 400];
        }

        $stmt = $this->db->prepare("SELECT * FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return [
                'success' => true,
                'token' => Tokenizer::generateToken($email),
                'user' => [
                    'user_id' => (int)$user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                ],
            ];
        }

        return ['success' => false, 'message' => 'Email ou mot de passe incorrect.', 'code' => 401];
    }

    /**
     * Inscription pour l'API mobile : renvoie le token directement dans la réponse
     */
    public function apiSignup(): array {
        $request = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = trim($request['name'] ?? '');
        $email = filter_var(trim($request['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $request['password'] ?? '';

        if (empty($name) || !$email || empty($password)) {
            return ['success' => false, 'message' => 'Tous les champs sont requis.', 'code' => 400];
        }

        $stmt = $this->db->prepare("SELECT user_id FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Un utilisateur avec cet email existe déjà.', 'code' => 409];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO Users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword]);

        return [
            'success' => true,
            'token' => Tokenizer::generateToken($email),
            'user' => [
                'user_id' => (int)$this->db->lastInsertId(),
                'name' => $name,
                'email' => $email,
            ],
        ];
    }

    private function setAuthCookie($email) {
        $token = Tokenizer::generateToken($email);
        setcookie("auth_token", $token, [
            "httponly" => true,
            "samesite" => "Strict",
            "expires"  => time() + (60 * 60 * 24 * 30)
        ]);
    }
}
?>
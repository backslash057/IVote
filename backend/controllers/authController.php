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

    public function checkAuthentification() {
        $token = $_COOKIE['auth_token'] ?? null;
        if (!$token) return null;

        $payload = Tokenizer::decodeToken($token);
        if ($payload && isset($payload['expires'], $payload['email']) && $payload['expires'] > time()) {
            $stmt = $this->db->prepare("SELECT user_id, name, email FROM Users WHERE email = ?");
            $stmt->execute([$payload['email']]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return null;
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
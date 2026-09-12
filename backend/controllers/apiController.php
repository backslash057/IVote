<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/authController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/campaignController.php';

class ApiController {
    private AuthController $auth;
    private CampaignController $campaigns;

    public function __construct() {
        $this->auth = new AuthController();
        $this->campaigns = new CampaignController();
    }

    private function respond(array $data, ?int $code = null): array {
        if ($code !== null) {
            http_response_code($code);
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        return $data;
    }

    public function status(): array {
        return $this->respond(['success' => true, 'message' => 'IVote API OK']);
    }

    public function campaigns(): array {
        return $this->respond(['success' => true, 'data' => $this->campaigns->getCampaigns()]);
    }

    public function popular(): array {
        return $this->respond(['success' => true, 'data' => $this->campaigns->getActivePopularCampaigns(3)]);
    }

    public function show(string|int $id): array {
        $details = $this->campaigns->getCampaignDetails((int)$id);
        if (!$details) {
            return $this->respond(['success' => false, 'message' => 'Campagne introuvable.'], 404);
        }
        return $this->respond(['success' => true, 'data' => $details]);
    }

    public function login(): array {
        $result = $this->auth->apiLogin();
        $code = (int)($result['code'] ?? 200);
        unset($result['code']);
        return $this->respond($result, $code);
    }

    public function signup(): array {
        $result = $this->auth->apiSignup();
        $code = (int)($result['code'] ?? 201);
        unset($result['code']);
        return $this->respond($result, $code);
    }

    public function me(): array {
        $user = $this->auth->checkAuthentification();
        if (!$user) {
            return $this->respond(['success' => false, 'message' => 'Non authentifié.'], 401);
        }
        return $this->respond(['success' => true, 'user' => [
            'user_id' => (int)$user['user_id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ]]);
    }

    public function myCampaigns(): array {
        $user = $this->auth->checkAuthentification();
        if (!$user) {
            return $this->respond(['success' => false, 'message' => 'Non authentifié.'], 401);
        }
        return $this->respond(['success' => true, 'data' => $this->campaigns->getCampaignsByOrganizer((int)$user['user_id'])]);
    }

    public function dashboard(string|int $id): array {
        $user = $this->auth->checkAuthentification();
        if (!$user) {
            return $this->respond(['success' => false, 'message' => 'Non authentifié.'], 401);
        }
        $data = $this->campaigns->getDashboardData((int)$id, (int)$user['user_id']);
        if (!$data) {
            return $this->respond(['success' => false, 'message' => 'Campagne introuvable ou accès non autorisé.'], 403);
        }
        return $this->respond(['success' => true, 'data' => $data]);
    }

    public function create(): array {
        return $this->respond($this->campaigns->createCampaign());
    }

    public function update(string|int $id): array {
        return $this->respond($this->campaigns->updateCampaign($id));
    }

    public function updateStatus(string|int $id): array {
        return $this->respond($this->campaigns->updateStatus($id));
    }

    public function relaunch(string|int $id): array {
        return $this->respond($this->campaigns->relaunch($id));
    }

    public function votes(string|int $id): array {
        return $this->respond($this->campaigns->recordVote($id));
    }

    public function candidates(string|int $id): array {
        return $this->respond($this->campaigns->addCandidate($id));
    }

    public function payouts(string|int $id): array {
        return $this->respond($this->campaigns->createPayout($id));
    }
}
<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';

class CategoryController extends BaseController {

    /**
     * GET /api/campaigns/{id}/categories
     */
    public function index(string|int $id): array {
        $campaignId = (int)$id;
        $stmt = $this->db->prepare("SELECT * FROM Category WHERE campaign_id = ? ORDER BY name ASC");
        $stmt->execute([$campaignId]);

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * POST /api/campaigns/{id}/categories
     */
    public function store(string|int $id): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $name = trim($data['name'] ?? '');

        if ($campaignId <= 0 || empty($name)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le nom de la catégorie est obligatoire.'];
        }

        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        $categoryId = $this->findOrCreateCategory($campaignId, $name);

        http_response_code(201);
        return [
            'success' => true,
            'category_id' => $categoryId,
            'message' => 'Catégorie créée avec succès.'
        ];
    }

    /**
     * Internal helper to find an existing category or create a new one
     */
    public function findOrCreateCategory(int $campaignId, string $name): int {
        $stmtFind = $this->db->prepare("SELECT category_id FROM Category WHERE campaign_id = ? AND name = ?");
        $stmtFind->execute([$campaignId, $name]);
        $existing = $stmtFind->fetchColumn();

        if ($existing !== false) {
            return (int)$existing;
        }

        $stmtNew = $this->db->prepare("INSERT INTO Category (name, campaign_id) VALUES (?, ?)");
        $stmtNew->execute([$name, $campaignId]);
        return (int)$this->db->lastInsertId();
    }
}
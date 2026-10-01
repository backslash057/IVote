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
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
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

        if (!$this->isCampaignEditable($campaignId)) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Les catégories ne peuvent être modifiées qu’avant la publication.'];
        }

        $categoryId = $this->findOrCreateCategory($campaignId, $name);

        http_response_code(201);
        return [
            'success' => true,
            'category_id' => $categoryId,
            'message' => 'Catégorie créée avec succès.'
        ];
    }

    public function delete(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];

        $categoryId = (int)$id;
        $stmt = $this->db->prepare("SELECT campaign_id FROM Category WHERE category_id = ? AND campaign_id IN (SELECT campaign_id FROM Campaign WHERE organiser_id = ?)");
        $stmt->execute([$categoryId, (int)$user['user_id']]);
        $campaignId = (int)$stmt->fetchColumn();
        if (!$campaignId || !$this->isCampaignEditable($campaignId)) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Cette catégorie ne peut être supprimée qu’avant la publication.'];
        }

        $count = $this->db->prepare("SELECT COUNT(*) FROM Candidate WHERE category_id = ?");
        $count->execute([$categoryId]);
        if ((int)$count->fetchColumn() > 0) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Supprimez ou déplacez d’abord les candidats de cette catégorie.'];
        }

        $stmt = $this->db->prepare("DELETE FROM Category WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        return ['success' => true, 'message' => 'Catégorie supprimée avec succès.'];
    }

    public function update(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];

        $categoryId = (int)$id;
        $name = trim((string)($this->getRequestData()['name'] ?? ''));
        if ($categoryId <= 0 || $name === '') {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le nom de la catégorie est obligatoire.'];
        }

        $stmt = $this->db->prepare("SELECT campaign_id FROM Category WHERE category_id = ? AND campaign_id IN (SELECT campaign_id FROM Campaign WHERE organiser_id = ?)");
        $stmt->execute([$categoryId, (int)$user['user_id']]);
        $campaignId = (int)$stmt->fetchColumn();
        if (!$campaignId || !$this->isCampaignEditable($campaignId)) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Cette catégorie ne peut être modifiée qu’avant la publication.'];
        }

        $stmt = $this->db->prepare("UPDATE Category SET name = ? WHERE category_id = ?");
        try {
            $stmt->execute([$name, $categoryId]);
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                http_response_code(409);
                return ['success' => false, 'message' => 'Une catégorie porte déjà ce nom dans cette campagne.'];
            }
            throw $error;
        }
        return ['success' => true, 'message' => 'Catégorie modifiée.'];
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
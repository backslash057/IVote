<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/CategoryController.php';

class CandidateController extends BaseController {

    /**
     * GET /api/campaigns/{id}/candidates
     */
    public function index(string|int $id): array {
        $campaignId = (int)$id;

        $stmt = $this->db->prepare("
            SELECT cand.*, cat.name AS category_name, COALESCE(SUM(v.vote_count), 0) AS total_votes
            FROM Candidate cand
            LEFT JOIN Category cat ON cand.category_id = cat.category_id
            LEFT JOIN Vote v ON cand.candidate_id = v.candidate_id
            WHERE cand.campaign_id = ?
            GROUP BY cand.candidate_id
            ORDER BY cand.candidate_number ASC
        ");
        $stmt->execute([$campaignId]);

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * POST /api/campaigns/{id}/candidates
     */
    public function store(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();

        $name = trim($data['name'] ?? '');
        $candidateNumber = (int)($data['candidate_number'] ?? 0);
        $age = !empty($data['age']) ? (int)$data['age'] : null;
        $theme = trim($data['theme'] ?? '');
        $description = trim($data['description'] ?? '');
        $bio = trim($data['bio'] ?? '');

        $categoryId = !empty($data['category_id']) && $data['category_id'] !== 'new' ? (int)$data['category_id'] : null;
        $newCatName = trim($data['new_category_name'] ?? '');
        $categoryName = trim($data['category_name'] ?? '');

        if ($campaignId <= 0 || empty($name) || $candidateNumber <= 0) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Informations du candidat incomplètes (nom et numéro de dossard requis).'];
        }

        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée sur cette campagne.'];
        }

        if (!$this->isCampaignEditable($campaignId)) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Les candidats ne peuvent être modifiés qu’avant la publication.'];
        }

        // Handle category creation if required
        $categoryController = new CategoryController();
        if (!empty($newCatName)) {
            $categoryId = $categoryController->findOrCreateCategory($campaignId, $newCatName);
        } elseif (!empty($categoryName) && !$categoryId) {
            $categoryId = $categoryController->findOrCreateCategory($campaignId, $categoryName);
        }

        // Handle candidate image upload
        $upload = $this->handleFileUpload('image', 'cand_');
        if (!$upload['success']) {
            http_response_code(400);
            return $upload;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO Candidate (name, candidate_number, age, theme, description, bio, image_url, category_id, campaign_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name,
                $candidateNumber,
                $age,
                $theme ?: null,
                $description ?: null,
                $bio ?: null,
                $upload['url'],
                $categoryId,
                $campaignId
            ]);

            http_response_code(201);
            return [
                'success' => true,
                'candidate_id' => (int)$this->db->lastInsertId(),
                'message' => 'Candidat ajouté avec succès.'
            ];
        } catch (PDOException $e) {
            http_response_code(400);
            if ($e->getCode() === '23000') {
                return ['success' => false, 'message' => 'Ce numéro de dossard est déjà utilisé dans cette campagne.'];
            }
            return ['success' => false, 'message' => 'Erreur interne lors de l\'enregistrement du candidat.'];
        }
    }

    public function update(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];

        $candidateId = (int)$id;
        $data = $this->getRequestData();
        $name = trim((string)($data['name'] ?? ''));
        $candidateNumber = (int)($data['candidate_number'] ?? 0);
        if ($candidateId <= 0 || $name === '' || $candidateNumber <= 0) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Nom et numéro de candidat requis.'];
        }

        $stmt = $this->db->prepare("SELECT campaign_id FROM Candidate WHERE candidate_id = ? AND campaign_id IN (SELECT campaign_id FROM Campaign WHERE organiser_id = ?)");
        $stmt->execute([$candidateId, (int)$user['user_id']]);
        $campaignId = (int)$stmt->fetchColumn();
        if (!$campaignId || !$this->isCampaignEditable($campaignId)) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Ce candidat ne peut être modifié qu’avant la publication.'];
        }

        $categoryId = isset($data['category_id']) && $data['category_id'] !== '' ? (int)$data['category_id'] : null;
        if ($categoryId !== null) {
            $categoryCheck = $this->db->prepare("SELECT COUNT(*) FROM Category WHERE category_id = ? AND campaign_id = ?");
            $categoryCheck->execute([$categoryId, $campaignId]);
            if ((int)$categoryCheck->fetchColumn() !== 1) {
                http_response_code(400);
                return ['success' => false, 'message' => 'La catégorie doit appartenir à cette campagne.'];
            }
        }

        $stmt = $this->db->prepare("UPDATE Candidate SET name = ?, candidate_number = ?, age = ?, theme = ?, description = ?, bio = ?, category_id = ? WHERE candidate_id = ?");
        try {
            $stmt->execute([$name, $candidateNumber, !empty($data['age']) ? (int)$data['age'] : null, trim((string)($data['theme'] ?? '')) ?: null, trim((string)($data['description'] ?? '')) ?: null, trim((string)($data['bio'] ?? '')) ?: null, $categoryId, $candidateId]);
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                http_response_code(409);
                return ['success' => false, 'message' => 'Ce numéro de dossard est déjà utilisé dans cette campagne.'];
            }
            throw $error;
        }
        return ['success' => true, 'message' => 'Candidat modifié avec succès.'];
    }

    public function delete(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];

        $candidateId = (int)$id;
        $stmt = $this->db->prepare("SELECT campaign_id FROM Candidate WHERE candidate_id = ? AND campaign_id IN (SELECT campaign_id FROM Campaign WHERE organiser_id = ?)");
        $stmt->execute([$candidateId, (int)$user['user_id']]);
        $campaignId = (int)$stmt->fetchColumn();
        if (!$campaignId || !$this->isCampaignEditable($campaignId)) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Ce candidat ne peut être supprimé qu’avant la publication.'];
        }

        $voteCheck = $this->db->prepare("SELECT COUNT(*) FROM Vote WHERE candidate_id = ?");
        $voteCheck->execute([$candidateId]);
        if ((int)$voteCheck->fetchColumn() > 0) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Un candidat ayant des votes ne peut pas être supprimé.'];
        }

        $stmt = $this->db->prepare("DELETE FROM Candidate WHERE candidate_id = ?");
        $stmt->execute([$candidateId]);
        return ['success' => true, 'message' => 'Candidat supprimé avec succès.'];
    }
}
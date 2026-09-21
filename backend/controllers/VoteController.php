<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';

class VoteController extends BaseController {

    /**
     * GET /api/campaigns/{id}/votes
     */
    public function index(string|int $id): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $campaignId = (int)$id;
        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        $stmt = $this->db->prepare("
            SELECT v.*, c.name AS candidate_name, c.candidate_number, c.image_url AS candidate_avatar
            FROM Vote v
            JOIN Candidate c ON v.candidate_id = c.candidate_id
            WHERE v.campaign_id = ?
            ORDER BY v.created_at DESC
        ");
        $stmt->execute([$campaignId]);

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * POST /api/campaigns/{id}/votes
     */
    public function recordVote(string|int $id): array {
        $campaignId = (int)$id;
        $data = $this->getRequestData();

        $candidateId = (int)($data['candidate_id'] ?? 0);
        $voteCount = (int)($data['vote_count'] ?? 1);
        $paymentMethod = $data['payment_method'] ?? '';

        if ($campaignId <= 0 || $candidateId <= 0 || $voteCount <= 0 || $voteCount > 500 || !in_array($paymentMethod, ['mtn_momo', 'orange_money'], true)) {
            http_response_code(422);
            return ['success' => false, 'message' => 'Données de vote invalides (1 à 500 votes, moyen de paiement valide requis).'];
        }

        $stmtCheck = $this->db->prepare("SELECT price_per_vote, is_draft, date_cloture FROM Campaign WHERE campaign_id = ?");
        $stmtCheck->execute([$campaignId]);
        $camp = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$camp || (int)$camp['is_draft'] === 1 || ($camp['date_cloture'] && strtotime($camp['date_cloture']) < time())) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Cette campagne n\'est pas active ou est clôturée.'];
        }

        $stmtCand = $this->db->prepare("SELECT candidate_id FROM Candidate WHERE candidate_id = ? AND campaign_id = ?");
        $stmtCand->execute([$candidateId, $campaignId]);
        if (!$stmtCand->fetch()) {
            http_response_code(422);
            return ['success' => false, 'message' => 'Candidat introuvable dans cette campagne.'];
        }

        $price = (int)$camp['price_per_vote'];
        $discount = match (true) {
            $voteCount >= 50 => 0.8,
            $voteCount >= 20 => 0.85,
            $voteCount >= 10 => 0.9,
            default => 1.0,
        };

        $amountFcfa = (int)round($voteCount * $price * $discount);
        $transactionRef = 'TX-' . strtoupper(uniqid());

        $stmt = $this->db->prepare("
            INSERT INTO Vote (transaction_ref, candidate_id, campaign_id, payment_method, vote_count, amount_fcfa)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$transactionRef, $candidateId, $campaignId, $paymentMethod, $voteCount, $amountFcfa]);

        http_response_code(201);
        return [
            'success' => true,
            'message' => 'Vote enregistré avec succès.',
            'transaction_ref' => $transactionRef,
            'votes_added' => $voteCount,
            'amount_fcfa' => $amountFcfa,
        ];
    }
}
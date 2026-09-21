<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';

class PayoutController extends BaseController {

    /**
     * GET /api/campaigns/{id}/payouts
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

        $stmt = $this->db->prepare("SELECT * FROM Payout WHERE campaign_id = ? ORDER BY created_at DESC");
        $stmt->execute([$campaignId]);

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * POST /api/campaigns/{id}/payouts
     */
    public function store(string|int $id): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();

        $amount = (int)($data['amount'] ?? 0);
        $paymentMethod = $data['payment_method'] ?? '';
        $accountHolder = trim($data['account_holder'] ?? '');
        $walletNumber = trim($data['wallet_number'] ?? '');

        if ($campaignId <= 0 || $amount <= 0 || empty($accountHolder) || empty($walletNumber) || !in_array($paymentMethod, ['mtn_momo', 'orange_money'], true)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Formulaire de retrait incomplet ou données invalides.'];
        }

        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        // Calculate available balance
        $stmtRev = $this->db->prepare("SELECT COALESCE(SUM(amount_fcfa), 0) FROM Vote WHERE campaign_id = ?");
        $stmtRev->execute([$campaignId]);
        $totalRevenue = (int)$stmtRev->fetchColumn();

        $stmtPayouts = $this->db->prepare("SELECT COALESCE(SUM(amount_fcfa), 0) FROM Payout WHERE campaign_id = ? AND status != 'rejected'");
        $stmtPayouts->execute([$campaignId]);
        $totalRequestedPayouts = (int)$stmtPayouts->fetchColumn();

        $availableBalance = (int)round($totalRevenue * 0.90) - $totalRequestedPayouts;

        if ($amount > $availableBalance) {
            http_response_code(400);
            return ['success' => false, 'message' => "Solde disponible insuffisant (Disponible: {$availableBalance} FCFA)."];
        }

        $stmt = $this->db->prepare("
            INSERT INTO Payout (campaign_id, amount_fcfa, payment_method, account_holder, wallet_number, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$campaignId, $amount, $paymentMethod, $accountHolder, $walletNumber]);

        http_response_code(201);
        return ['success' => true, 'message' => 'Demande de retrait initiée avec succès.'];
    }
}
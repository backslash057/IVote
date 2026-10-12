<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';

class AdminController extends BaseController {

    public function overview(): array {
        $admin = $this->requireSuperAdmin();
        if (!$admin) {
            return ['success' => false, 'message' => 'Accès administrateur requis.'];
        }

        $queries = [
            'organizers' => "SELECT COUNT(*) FROM Users WHERE role = 'organizer'",
            'suspended_users' => "SELECT COUNT(*) FROM Users WHERE status = 'suspended'",
            'published_campaigns' => "SELECT COUNT(*) FROM Campaign WHERE status = 'published'",
            'suspended_campaigns' => "SELECT COUNT(*) FROM Campaign WHERE status = 'suspended'",
            'pending_payouts' => "SELECT COUNT(*) FROM Payout WHERE status IN ('pending', 'under_review')",
            'gross_revenue' => "SELECT COALESCE(SUM(amount_fcfa), 0) FROM Vote WHERE payment_status = 'confirmed'",
        ];

        $overview = [];
        foreach ($queries as $key => $sql) {
            $overview[$key] = (int)$this->db->query($sql)->fetchColumn();
        }

        return ['success' => true, 'data' => $overview];
    }

    public function users(): array {
        if (!$this->requireSuperAdmin()) {
            return ['success' => false, 'message' => 'Accès administrateur requis.'];
        }

        $stmt = $this->db->query(
            "SELECT user_id, name, email, role, status, created_at, suspended_at, suspension_reason
             FROM Users ORDER BY user_id DESC"
        );
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function updateUserStatus(string|int $id): array {
        $admin = $this->requireSuperAdmin();
        if (!$admin) {
            return ['success' => false, 'message' => 'Accès administrateur requis.'];
        }

        $userId = (int)$id;
        $data = $this->getRequestData();
        $targetStatus = $data['status'] ?? '';
        $reason = trim((string)($data['reason'] ?? ''));

        if (!in_array($targetStatus, ['active', 'suspended', 'disabled'], true)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Statut de compte invalide.'];
        }
        if ($userId === (int)$admin['user_id']) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le super-admin ne peut pas désactiver son propre compte.'];
        }
        if ($targetStatus !== 'active' && $reason === '') {
            http_response_code(400);
            return ['success' => false, 'message' => 'Un motif est obligatoire pour suspendre ou désactiver un compte.'];
        }

        $stmt = $this->db->prepare("SELECT user_id, name, email, role, status, suspension_reason FROM Users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target || $target['role'] !== 'organizer') {
            http_response_code(404);
            return ['success' => false, 'message' => 'Organisateur introuvable.'];
        }
        if ($target['status'] === $targetStatus) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Ce compte possède déjà ce statut.'];
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE Users SET status = ?, suspended_at = ?, suspended_by = ?, suspension_reason = ?, session_version = session_version + 1
                 WHERE user_id = ? AND role = 'organizer'"
            );
            $stmt->execute([
                $targetStatus,
                $targetStatus === 'active' ? null : date('Y-m-d H:i:s'),
                $targetStatus === 'active' ? null : (int)$admin['user_id'],
                $targetStatus === 'active' ? null : $reason,
                $userId,
            ]);

            if ($targetStatus !== 'active') {
                $liveCampaigns = $this->db->prepare("SELECT campaign_id, title, status FROM Campaign WHERE organiser_id = ? AND status = 'published'");
                $liveCampaigns->execute([$userId]);
                foreach ($liveCampaigns->fetchAll(PDO::FETCH_ASSOC) as $campaign) {
                    $campaignReason = "Organisateur {$targetStatus} : {$reason}";
                    $suspend = $this->db->prepare(
                        "UPDATE Campaign SET status = 'suspended', is_draft = 0, status_before_suspension = 'published', suspended_at = NOW(), suspended_by = ?, suspension_reason = ? WHERE campaign_id = ? AND status = 'published'"
                    );
                    $suspend->execute([(int)$admin['user_id'], $campaignReason, (int)$campaign['campaign_id']]);
                    $this->writeAudit((int)$admin['user_id'], 'campaign_auto_suspended', 'campaign', (int)$campaign['campaign_id'], $campaignReason, ['status' => 'published'], ['status' => 'suspended']);
                    $this->createNotification((int)$userId, 'campaign_suspended', 'Campagne suspendue', "Votre campagne « {$campaign['title']} » a été suspendue suite à la suspension de votre compte. Motif : {$reason}", (int)$admin['user_id'], 'campaign', (int)$campaign['campaign_id']);
                }
            }

            $this->writeAudit((int)$admin['user_id'], 'user_status_changed', 'user', $userId, $reason, $target, ['status' => $targetStatus]);
            $accountMessage = $targetStatus === 'active'
                ? 'Votre compte organisateur a été réactivé. Les campagnes suspendues restent hors ligne jusqu’à leur restauration par IVote.'
                : "Votre compte a été {$targetStatus}. Motif : {$reason}";
            $this->createNotification(
                $userId,
                $targetStatus === 'active' ? 'account_restored' : 'account_suspended',
                $targetStatus === 'active' ? 'Compte réactivé' : 'Compte suspendu',
                $accountMessage,
                (int)$admin['user_id'],
                'user',
                $userId
            );
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }

        return ['success' => true, 'message' => 'Statut de l’organisateur mis à jour.'];
    }

    public function campaigns(): array {
        if (!$this->requireSuperAdmin()) {
            return ['success' => false, 'message' => 'Accès administrateur requis.'];
        }

        $stmt = $this->db->query(
            "SELECT c.campaign_id, c.title, c.status, c.is_draft, c.suspension_reason, c.created_at,
                    u.user_id AS organizer_id, u.name AS organizer_name, u.email AS organizer_email
             FROM Campaign c JOIN Users u ON u.user_id = c.organiser_id
             ORDER BY c.campaign_id DESC"
        );
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function moderateCampaign(string|int $id): array {
        $admin = $this->requireSuperAdmin();
        if (!$admin) {
            return ['success' => false, 'message' => 'Accès administrateur requis.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $action = $data['action'] ?? '';
        $reason = trim((string)($data['reason'] ?? ''));

        $statusByAction = [
            'suspend' => 'suspended',
            'restore' => 'published',
            'close' => 'closed',
        ];
        if (!isset($statusByAction[$action])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Action de modération invalide.'];
        }
        if ($action !== 'restore' && $reason === '') {
            http_response_code(400);
            return ['success' => false, 'message' => 'Un motif est obligatoire pour cette action.'];
        }

        $stmt = $this->db->prepare(
            "SELECT campaign_id, title, organiser_id, status, status_before_suspension, suspension_reason FROM Campaign WHERE campaign_id = ?"
        );
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$campaign) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Campagne introuvable.'];
        }

        if ($action === 'suspend' && $campaign['status'] !== 'published') {
            http_response_code(409);
            return ['success' => false, 'message' => 'Seule une campagne publiée peut être suspendue.'];
        }
        if ($action === 'restore' && $campaign['status'] !== 'suspended') {
            http_response_code(409);
            return ['success' => false, 'message' => 'Cette campagne n’est pas suspendue.'];
        }
        if ($action === 'close' && $campaign['status'] !== 'published') {
            http_response_code(409);
            return ['success' => false, 'message' => 'Seule une campagne publiée peut être clôturée.'];
        }

        $newStatus = $action === 'restore'
            ? ($campaign['status_before_suspension'] ?: 'published')
            : $statusByAction[$action];
        $isDraft = $newStatus === 'draft' ? 1 : 0;
        $stmt = $this->db->prepare(
            "UPDATE Campaign
             SET status = ?, is_draft = ?,
                 date_cloture = CASE WHEN ? = 1 THEN NOW() ELSE date_cloture END,
                 status_before_suspension = CASE WHEN ? = 'suspended' THEN ? ELSE NULL END,
                 suspended_at = CASE WHEN ? = 'suspended' THEN NOW() ELSE NULL END,
                 suspended_by = CASE WHEN ? = 'suspended' THEN ? ELSE NULL END,
                 suspension_reason = CASE WHEN ? = 'suspended' THEN ? ELSE NULL END
             WHERE campaign_id = ?"
        );
        $stmt->execute([
            $newStatus,
            $isDraft,
            $action === 'close' ? 1 : 0,
            $newStatus,
            $campaign['status'],
            $newStatus,
            $newStatus,
            (int)$admin['user_id'],
            $newStatus,
            $newStatus === 'suspended' ? $reason : null,
            $campaignId,
        ]);

        $this->writeAudit((int)$admin['user_id'], 'campaign_moderated', 'campaign', $campaignId, $reason, $campaign, ['status' => $newStatus]);
        $this->createNotification(
            (int)$campaign['organiser_id'],
            'campaign_' . $action,
            $action === 'suspend' ? 'Campagne suspendue' : ($action === 'close' ? 'Campagne clôturée' : 'Campagne restaurée'),
            $action === 'restore' ? 'Votre campagne a été restaurée.' : "Votre campagne a été traitée par l’administration. Motif : {$reason}",
            (int)$admin['user_id'],
            'campaign',
            $campaignId
        );

        return ['success' => true, 'message' => 'Campagne modérée avec succès.'];
    }

    public function payouts(): array {
        if (!$this->requireSuperAdmin()) {
            return ['success' => false, 'message' => 'Accès administrateur requis.'];
        }

        $stmt = $this->db->query(
            "SELECT p.*, c.title, u.name AS organizer_name, u.email AS organizer_email
             FROM Payout p JOIN Campaign c ON c.campaign_id = p.campaign_id
             JOIN Users u ON u.user_id = c.organiser_id
             ORDER BY p.created_at DESC"
        );
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function reviewPayout(string|int $id): array {
        $admin = $this->requireSuperAdmin();
        if (!$admin) {
            return ['success' => false, 'message' => 'Accès administrateur requis.'];
        }

        $payoutId = (int)$id;
        $data = $this->getRequestData();
        $action = $data['action'] ?? '';
        $reason = trim((string)($data['reason'] ?? ''));
        $nextStatus = ['approve' => 'approved', 'reject' => 'rejected'][$action] ?? null;
        if ($nextStatus === null) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Choisissez valider ou refuser la demande.'];
        }
        if ($action === 'reject' && $reason === '') {
            http_response_code(400);
            return ['success' => false, 'message' => 'Un motif est obligatoire pour rejeter un retrait.'];
        }
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                "SELECT p.*, c.title, c.organiser_id FROM Payout p JOIN Campaign c ON c.campaign_id = p.campaign_id WHERE p.payout_id = ? FOR UPDATE"
            );
            $stmt->execute([$payoutId]);
            $payout = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$payout) {
                $this->db->rollBack();
                http_response_code(404);
                return ['success' => false, 'message' => 'Retrait introuvable.'];
            }
            if (!in_array($payout['status'], ['pending', 'under_review'], true)) {
                $this->db->rollBack();
                http_response_code(409);
                return ['success' => false, 'message' => 'Cette demande a déjà été traitée et ne peut plus être modifiée.'];
            }

            $stmt = $this->db->prepare(
                "UPDATE Payout SET status = ?, rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE payout_id = ? AND status IN ('pending', 'under_review')"
            );
            $stmt->execute([$nextStatus, $action === 'reject' ? $reason : null, (int)$admin['user_id'], $payoutId]);
            if ($stmt->rowCount() !== 1) {
                $this->db->rollBack();
                http_response_code(409);
                return ['success' => false, 'message' => 'Cette demande a déjà été traitée.'];
            }

            $this->writeAudit((int)$admin['user_id'], 'payout_' . $action, 'payout', $payoutId, $reason, $payout, ['status' => $nextStatus]);
            $this->createNotification(
                (int)$payout['organiser_id'],
                'payout_' . $action,
                $action === 'reject' ? 'Retrait refusé' : 'Retrait validé',
                $action === 'reject' ? "Votre retrait a été refusé. Motif : {$reason}" : 'Votre demande de retrait a été approuvée. Le transfert Mobile Money n’est pas encore exécuté par IVote.',
                (int)$admin['user_id'],
                'payout',
                $payoutId
            );
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }

        // Retrait Mobile Money réel via AangaraaPay (best-effort, hors transaction).
        if ($action === 'approve' && AangaraaPay::configured()) {
            $gatewayTxnId = null;
            try {
                $withdrawal = AangaraaPay::withdraw([
                    'phone_number' => (string)$payout['wallet_number'],
                    'amount' => (int)$payout['amount_fcfa'],
                    'payment_method' => (string)$payout['payment_method'] === 'orange_money' ? 'ORANGE-MOMO' : 'MTN-MOMO',
                    'username' => (string)$payout['account_holder'],
                    'transaction_id' => (string)$payout['payout_id'],
                ]);
                $gatewayTxnId = trim((string)($withdrawal['transaction_id'] ?? $withdrawal['txnid'] ?? $withdrawal['reference'] ?? $withdrawal['id'] ?? ''));
                $this->db->prepare("UPDATE Payout SET payment_reference = ?, processed_at = NOW() WHERE payout_id = ?")
                    ->execute([$gatewayTxnId !== '' ? $gatewayTxnId : json_encode($withdrawal, JSON_UNESCAPED_UNICODE), $payoutId]);
            } catch (Throwable $error) {
                error_log('[IVote] Retrait AangaraaPay échoué (payout ' . $payoutId . ') : ' . $error->getMessage());
            }
        }

        return ['success' => true, 'message' => $action === 'reject' ? 'Demande refusée.' : 'Demande validée.'];
    }

    public function sendNotification(): array {
        $admin = $this->requireSuperAdmin();
        if (!$admin) {
            return ['success' => false, 'message' => 'Accès administrateur requis.'];
        }

        $data = $this->getRequestData();
        $broadcast = filter_var($data['broadcast'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $recipientId = (int)($data['recipient_id'] ?? 0);
        $title = trim((string)($data['title'] ?? ''));
        $message = trim((string)($data['message'] ?? ''));
        if ((!$broadcast && $recipientId <= 0) || $title === '' || $message === '') {
            http_response_code(400);
            return ['success' => false, 'message' => 'Choisissez un organisateur ou le broadcast, puis renseignez le titre et le message.'];
        }

        if ($broadcast) {
            $stmt = $this->db->query("SELECT user_id FROM Users WHERE role = 'organizer' AND status = 'active'");
            $recipientIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } else {
            $stmt = $this->db->prepare("SELECT user_id FROM Users WHERE user_id = ? AND role = 'organizer' AND status = 'active'");
            $stmt->execute([$recipientId]);
            $validRecipient = $stmt->fetchColumn();
            if (!$validRecipient) {
                http_response_code(404);
                return ['success' => false, 'message' => 'Organisateur actif introuvable.'];
            }
            $recipientIds = [$recipientId];
        }
        if ($recipientIds === []) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Aucun organisateur actif ne peut recevoir cette annonce.'];
        }

        $this->db->beginTransaction();
        try {
            foreach ($recipientIds as $targetId) {
                $this->createNotification($targetId, 'admin_announcement', $title, $message, (int)$admin['user_id']);
            }
            $this->writeAudit(
                (int)$admin['user_id'],
                $broadcast ? 'notification_broadcast' : 'notification_sent',
                $broadcast ? 'organizers' : 'user',
                $broadcast ? null : $recipientId,
                null,
                null,
                ['title' => $title, 'recipient_count' => count($recipientIds), 'broadcast' => $broadcast]
            );
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
        return ['success' => true, 'message' => 'Annonce envoyée.', 'recipient_count' => count($recipientIds)];
    }
}

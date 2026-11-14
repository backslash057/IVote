<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';

class CampaignController extends BaseController {
    private const CAMPAIGN_CODE_ALPHABET = 'BCDFGHJKLMNPQRSTVWXYZ';

    public function status(): array {
        return ['success' => true, 'message' => 'IVote API Online'];
    }

    public function findCampaignIdByCode(string $code): ?int {
        if (!preg_match('/^[BCDFGHJKLMNPQRSTVWXYZ]{7}$/D', $code)) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT campaign_id FROM Campaign WHERE campaign_code = ?');
        $stmt->execute([$code]);
        $campaignId = $stmt->fetchColumn();

        return $campaignId === false ? null : (int)$campaignId;
    }

    public function publicMetadata(int $campaignId): ?array {
        $stmt = $this->db->prepare(
            'SELECT title, description, image_url, status
             FROM Campaign
             WHERE campaign_id = ?'
        );
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$campaign || in_array($campaign['status'], ['suspended', 'archived', 'draft', 'pending_review'], true)) {
            http_response_code(404);
            return null;
        }

        return $campaign;
    }

    private function generateCampaignCode(): string {
        do {
            $code = '';
            for ($i = 0; $i < 7; $i++) {
                $code .= self::CAMPAIGN_CODE_ALPHABET[random_int(0, strlen(self::CAMPAIGN_CODE_ALPHABET) - 1)];
            }

            $stmt = $this->db->prepare('SELECT 1 FROM Campaign WHERE campaign_code = ? LIMIT 1');
            $stmt->execute([$code]);
        } while ($stmt->fetchColumn() !== false);

        return $code;
    }

    /**
     * GET /api/campaigns
     */
    public function index(): array {
        $sql = "SELECT c.*,
                       u.name AS organizer_name,
                       COUNT(DISTINCT cand.candidate_id) AS candidate_count,
                       COALESCE(SUM(v.vote_count), 0) AS totalVotes,
                       GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.category_id SEPARATOR ', ') AS categories,
                       CASE
                           WHEN c.status = 'draft' THEN 'draft'
                           WHEN c.status = 'pending_review' THEN 'pending_review'
                           WHEN c.status = 'suspended' THEN 'suspended'
                           WHEN c.status = 'archived' THEN 'archived'
                           WHEN c.status = 'closed' THEN 'ended'
                           WHEN c.date_cloture IS NOT NULL AND c.date_cloture < NOW() THEN 'ended'
                           ELSE 'active'
                       END AS status
                FROM Campaign c
                LEFT JOIN Users u ON u.user_id = c.organiser_id
                LEFT JOIN Category cat ON cat.campaign_id = c.campaign_id
                LEFT JOIN Candidate cand ON cand.category_id = cat.category_id
                LEFT JOIN Vote v ON v.candidate_id = cand.candidate_id
                WHERE c.status = 'published'
                GROUP BY c.campaign_id
                ORDER BY c.campaign_id DESC";

        $stmt = $this->db->query($sql);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Récupère les campagnes populaires actives directement (Rendu serveur PHP)
     */
    public function getActivePopularCampaigns(int $limit = 4): array {
        $sql = "SELECT c.*,
                       u.name AS organizer_name,
                       COUNT(DISTINCT cand.candidate_id) AS candidate_count,
                       COALESCE(SUM(v.vote_count), 0) AS totalVotes,
                       GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.category_id SEPARATOR ', ') AS categories,
                       'active' AS status
                FROM Campaign c
                LEFT JOIN Users u ON u.user_id = c.organiser_id
                LEFT JOIN Category cat ON cat.campaign_id = c.campaign_id
                LEFT JOIN Candidate cand ON cand.category_id = cat.category_id
                LEFT JOIN Vote v ON v.candidate_id = cand.candidate_id
                WHERE c.status = 'published' AND (c.date_cloture IS NULL OR c.date_cloture >= NOW())
                GROUP BY c.campaign_id
                ORDER BY totalVotes DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * GET /api/my-campaigns
     */
    public function myCampaigns(): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $sql = "SELECT c.*,
                       u.name AS organizer_name,
                       COUNT(DISTINCT cand.candidate_id) AS candidate_count,
                       COALESCE(SUM(v.vote_count), 0) AS totalVotes,
                       GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.category_id SEPARATOR ', ') AS categories,
                       CASE
                           WHEN c.status = 'draft' THEN 'draft'
                           WHEN c.status = 'suspended' THEN 'suspended'
                           WHEN c.status = 'archived' THEN 'archived'
                           WHEN c.status = 'closed' THEN 'ended'
                           WHEN c.date_cloture IS NOT NULL AND c.date_cloture < NOW() THEN 'ended'
                           ELSE 'active'
                       END AS status
                FROM Campaign c
                INNER JOIN Users u ON u.user_id = c.organiser_id
                LEFT JOIN Category cat ON cat.campaign_id = c.campaign_id
                LEFT JOIN Candidate cand ON cand.category_id = cat.category_id
                LEFT JOIN Vote v ON v.candidate_id = cand.candidate_id
                WHERE c.organiser_id = :organizer_id
                GROUP BY c.campaign_id
                ORDER BY c.campaign_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':organizer_id', (int)$user['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * GET /api/campaigns/{id}
     */
    public function show(string|int $id): array {
        $campaignId = (int)$id;

        $campaignStmt = $this->db->prepare(
            "SELECT c.*, u.name AS organizer_name,
                    CASE
                        WHEN c.status = 'draft' THEN 'draft'
                        WHEN c.status = 'pending_review' THEN 'pending_review'
                        WHEN c.status = 'suspended' THEN 'suspended'
                        WHEN c.status = 'archived' THEN 'archived'
                        WHEN c.status = 'closed' THEN 'closed'
                        WHEN c.date_cloture IS NOT NULL AND c.date_cloture < NOW() THEN 'ended'
                        ELSE 'active'
                    END AS computed_status
             FROM Campaign c
             LEFT JOIN Users u ON u.user_id = c.organiser_id
             WHERE c.campaign_id = :id"
        );
        $campaignStmt->execute([':id' => $campaignId]);
        $campaign = $campaignStmt->fetch(PDO::FETCH_ASSOC);

        if (!$campaign) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Campagne introuvable.'];
        }

        if (in_array($campaign['status'], ['suspended', 'archived', 'draft', 'pending_review'], true)) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Cette campagne n’est pas disponible publiquement.'];
        }

        $candidateStmt = $this->db->prepare(
            "SELECT cat.category_id, cat.name AS category_name,
                    cand.candidate_id, cand.candidate_number, cand.name, cand.age, cand.theme,
                    cand.description, cand.bio, cand.image_url,
                    COALESCE(SUM(v.vote_count), 0) AS votes
             FROM Category cat
             LEFT JOIN Candidate cand ON cand.category_id = cat.category_id
             LEFT JOIN Vote v ON v.candidate_id = cand.candidate_id
             WHERE cat.campaign_id = :id
             GROUP BY cat.category_id, cat.name, cand.candidate_id, cand.candidate_number,
                      cand.name, cand.age, cand.theme, cand.description, cand.bio, cand.image_url
             ORDER BY cat.category_id, cand.candidate_id"
        );
        $candidateStmt->execute([':id' => $campaignId]);

        $categories = [];
        $totalVotes = 0;

        foreach ($candidateStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $categoryId = (int)$row['category_id'];
            $votes = (int)$row['votes'];

            if (!isset($categories[$categoryId])) {
                $categories[$categoryId] = [
                    'id' => $categoryId,
                    'name' => $row['category_name'],
                    'candidates' => []
                ];
            }

            if ($row['candidate_id'] !== null) {
                $categories[$categoryId]['candidates'][] = [
                    'id' => (int)$row['candidate_id'],
                    'number' => (int)$row['candidate_number'],
                    'name' => $row['name'],
                    'age' => $row['age'] !== null ? (int)$row['age'] : null,
                    'theme' => $row['theme'],
                    'description' => $row['description'],
                    'bio' => $row['bio'],
                    'image_url' => $row['image_url'],
                    'votes' => $votes
                ];
                $totalVotes += $votes;
            }
        }

        $campaign['categories'] = array_values($categories);
        $campaign['totalVotes'] = $totalVotes;
        $campaign['candidateCount'] = array_sum(array_map(
            static fn(array $category): int => count($category['candidates']),
            $campaign['categories']
        ));

        foreach ($campaign['categories'] as &$category) {
            foreach ($category['candidates'] as &$candidate) {
                $candidate['percentage'] = $totalVotes > 0
                    ? round(($candidate['votes'] / $totalVotes) * 100, 1)
                    : 0;
            }
        }
        unset($category, $candidate);

        return ['success' => true, 'data' => $campaign];
    }

    /**
     * GET /api/campaigns/{id}/dashboard
     */
    public function dashboard(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $campaignId = (int)$id;

        $stmt = $this->db->prepare("
            SELECT c.*, 
                   u.name AS organizer_name, 
                   u.email AS organizer_email,
                   CASE 
                       WHEN c.status = 'draft' THEN 'draft'
                       WHEN c.status = 'pending_review' THEN 'pending_review'
                       WHEN c.status = 'suspended' THEN 'suspended'
                       WHEN c.status = 'archived' THEN 'archived'
                       WHEN c.status = 'closed' THEN 'closed'
                       WHEN c.date_cloture IS NOT NULL AND c.date_cloture < NOW() THEN 'closed'
                       ELSE 'live'
                   END AS computed_status
            FROM Campaign c 
            JOIN Users u ON c.organiser_id = u.user_id 
            WHERE c.campaign_id = ? AND c.organiser_id = ?
        ");
        $stmt->execute([$campaignId, (int)$user['user_id']]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$campaign) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Campagne introuvable ou accès non autorisé.'];
        }

        $stmtCat = $this->db->prepare("SELECT * FROM Category WHERE campaign_id = ? ORDER BY name ASC");
        $stmtCat->execute([$campaignId]);
        $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        $stmtCand = $this->db->prepare("
            SELECT cand.*, 
                   cat.name AS category_name,
                   COALESCE(SUM(v.vote_count), 0) AS total_votes,
                   COALESCE(SUM(v.amount_fcfa), 0) AS candidate_revenue
            FROM Candidate cand
            LEFT JOIN Category cat ON cand.category_id = cat.category_id
            LEFT JOIN Vote v ON cand.candidate_id = v.candidate_id
            WHERE cand.campaign_id = ?
            GROUP BY cand.candidate_id
            ORDER BY total_votes DESC, cand.candidate_number ASC
        ");
        $stmtCand->execute([$campaignId]);
        $candidates = $stmtCand->fetchAll(PDO::FETCH_ASSOC);

        $totalVotes = array_sum(array_column($candidates, 'total_votes'));
        $totalRevenue = array_sum(array_column($candidates, 'candidate_revenue'));

        foreach ($candidates as &$cand) {
            $cand['percentage'] = $totalVotes > 0 ? round(($cand['total_votes'] / $totalVotes) * 100, 1) : 0;
            $cand['revenue'] = (int)$cand['candidate_revenue'];
        }
        unset($cand);

        $stmtTx = $this->db->prepare("
            SELECT v.*, c.name AS candidate_name, c.image_url AS candidate_avatar 
            FROM Vote v
            JOIN Candidate c ON v.candidate_id = c.candidate_id
            WHERE v.campaign_id = ?
            ORDER BY v.created_at DESC
        ");
        $stmtTx->execute([$campaignId]);
        $transactions = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

        $stmtPo = $this->db->prepare("SELECT * FROM Payout WHERE campaign_id = ? ORDER BY created_at DESC");
        $stmtPo->execute([$campaignId]);
        $payoutRequests = $stmtPo->fetchAll(PDO::FETCH_ASSOC);

        // Déduction des retraits validés et en cours (aligné avec PayoutController)
        $stmtNet = $this->db->prepare("SELECT COALESCE(SUM(organizer_net_amount_fcfa), 0) FROM Vote WHERE campaign_id = ? AND payment_status = 'confirmed'");
        $stmtNet->execute([$campaignId]);
        $netRevenue = (int)$stmtNet->fetchColumn();
        $stmtPay = $this->db->prepare("SELECT COALESCE(SUM(amount_fcfa), 0) FROM Payout WHERE campaign_id = ? AND status NOT IN ('rejected', 'cancelled')");
        $stmtPay->execute([$campaignId]);
        $totalRequestedPayouts = (int)$stmtPay->fetchColumn();

        $availableBalance = max(0, $netRevenue - $totalRequestedPayouts);

        return [
            'success' => true,
            'data' => [
                'campaign' => $campaign,
                'categories' => $categories,
                'candidates' => $candidates,
                'transactions' => $transactions,
                'payoutRequests' => $payoutRequests,
                'stats' => [
                    'totalVotes' => (int)$totalVotes,
                    'totalRevenue' => (int)$totalRevenue,
                    'netRevenue' => $netRevenue,
                    'availableBalance' => $availableBalance
                ]
            ]
        ];
    }

    /**
     * POST /api/campaigns
     */
    public function create(): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $data = $this->getRequestData();
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $pricePerVote = isset($data['price_per_vote']) ? (int)$data['price_per_vote'] : 100;
        $dateCloture = !empty($data['date_cloture']) ? trim($data['date_cloture']) : null;

        if (!$title || !$description) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le titre et la description sont obligatoires.'];
        }

        if ($pricePerVote < 50) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le prix par vote minimum est de 50 FCFA.'];
        }

        if ($dateCloture !== null) {
            $timestampCloture = strtotime($dateCloture);
            $minTimestamp = time() + 300;

            if ($timestampCloture === false) {
                http_response_code(400);
                return ['success' => false, 'message' => 'Le format de la date de clôture est invalide.'];
            }

            if ($timestampCloture < $minTimestamp) {
                http_response_code(400);
                return [
                    'success' => false, 
                    'message' => 'La date de clôture ne peut pas être dans le passé et doit être située au moins 5 minutes après l\'instant présent.'
                ];
            }

            $dateCloture = date('Y-m-d H:i:s', $timestampCloture);
        }

        $upload = $this->handleFileUpload('image', 'camp_');
        if (!$upload['success']) {
            http_response_code(400);
            return $upload;
        }

        $campaignId = 0;
        $campaignCode = '';
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $campaignCode = $this->generateCampaignCode();
            $stmt = $this->db->prepare("
                INSERT INTO Campaign (campaign_code, title, description, image_url, price_per_vote, date_cloture, organiser_id, is_draft)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");

            try {
                $stmt->execute([$campaignCode, $title, $description, $upload['url'], $pricePerVote, $dateCloture, (int)$user['user_id']]);
                $campaignId = (int)$this->db->lastInsertId();
                break;
            } catch (PDOException $e) {
                if ((int)($e->errorInfo[1] ?? 0) !== 1062 || $attempt === 9) {
                    throw $e;
                }
            }
        }

        http_response_code(201);
        return [
            'success' => true,
            'campaign_id' => $campaignId,
            'campaign_code' => $campaignCode,
            'message' => 'Campagne créée avec succès.'
        ];
    }

    /**
     * DELETE /api/campaigns/{id}
     */
    public function delete(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $campaignId = (int)$id;
        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }
        if (!$this->isCampaignEditable($campaignId)) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Seule une campagne brouillon peut être supprimée.'];
        }

        $stmt = $this->db->prepare("SELECT (SELECT COUNT(*) FROM Vote WHERE campaign_id = ?) + (SELECT COUNT(*) FROM Payout WHERE campaign_id = ?)");
        $stmt->execute([$campaignId, $campaignId]);
        if ((int)$stmt->fetchColumn() > 0) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Une campagne ayant des votes ou des retraits ne peut pas être supprimée.'];
        }

        $stmt = $this->db->prepare("DELETE FROM Campaign WHERE campaign_id = ? AND organiser_id = ?");
        $stmt->execute([$campaignId, (int)$user['user_id']]);
        return ['success' => true, 'message' => 'Campagne supprimée avec succès.'];
    }

    /**
     * POST /api/campaigns/{id}/update
     */
    public function update(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $pricePerVote = (int)($data['price_per_vote'] ?? 100);
        $dateCloture = !empty($data['date_cloture']) ? trim($data['date_cloture']) : null;

        if ($campaignId <= 0 || empty($title) || empty($description)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le titre et la description sont obligatoires.'];
        }

        if ($pricePerVote < 50) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le prix par vote minimum est de 50 FCFA.'];
        }

        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        if (!$this->isCampaignEditable($campaignId)) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Une campagne publiée ne peut plus être modifiée.'];
        }

        if ($dateCloture !== null) {
            $timestampCloture = strtotime($dateCloture);
            $minTimestamp = time() + 300;

            if ($timestampCloture === false) {
                http_response_code(400);
                return ['success' => false, 'message' => 'Le format de la date de clôture est invalide.'];
            }

            if ($timestampCloture < $minTimestamp) {
                http_response_code(400);
                return [
                    'success' => false, 
                    'message' => 'La date de clôture doit être située au moins 5 minutes après l\'instant présent.'
                ];
            }

            $dateCloture = date('Y-m-d H:i:s', $timestampCloture);
        }

        $stmt = $this->db->prepare("
            UPDATE Campaign 
            SET title = ?, description = ?, price_per_vote = ?, date_cloture = ? 
            WHERE campaign_id = ? AND organiser_id = ?
        ");
        $stmt->execute([$title, $description, $pricePerVote, $dateCloture, $campaignId, (int)$user['user_id']]);

        return ['success' => true, 'message' => 'Campagne modifiée avec succès.'];
    }

    /**
     * POST /api/campaigns/{id}/status
     */
    public function updateStatus(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $targetAction = $data['action'] ?? '';

        if ($campaignId <= 0 || !in_array($targetAction, ['publish', 'close'], true)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Action invalide.'];
        }

        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        $stateStmt = $this->db->prepare("SELECT status FROM Campaign WHERE campaign_id = ?");
        $stateStmt->execute([$campaignId]);
        $currentStatus = $stateStmt->fetchColumn();
        if ($targetAction === 'publish' && $currentStatus !== 'draft') {
            http_response_code(409);
            return ['success' => false, 'message' => 'Seule une campagne brouillon peut être publiée. Une campagne suspendue doit être restaurée par IVote.'];
        }
        if ($targetAction === 'close' && $currentStatus !== 'published') {
            http_response_code(409);
            return ['success' => false, 'message' => 'Seule une campagne publiée peut être clôturée.'];
        }

        if ($targetAction === 'publish') {
            // Vérification de présence d'au moins un candidat
            $candCheck = $this->db->prepare("SELECT COUNT(*) FROM Candidate WHERE campaign_id = ?");
            $candCheck->execute([$campaignId]);
            $candidateCount = (int)$candCheck->fetchColumn();

            if ($candidateCount === 0) {
                http_response_code(400);
                return ['success' => false, 'message' => 'Impossible de publier une campagne sans aucun candidat.'];
            }

            // Vérification si la date de clôture actuelle n'est pas déjà passée
            $campCheck = $this->db->prepare("SELECT date_cloture FROM Campaign WHERE campaign_id = ?");
            $campCheck->execute([$campaignId]);
            $currentDateCloture = $campCheck->fetchColumn();

            if ($currentDateCloture !== null && strtotime($currentDateCloture) <= time()) {
                http_response_code(400);
                return [
                    'success' => false, 
                    'message' => 'La date de clôture actuelle est dépassée. Veuillez modifier ou relancer la campagne avec une date future avant de publier.'
                ];
            }

            $stmt = $this->db->prepare("UPDATE Campaign SET is_draft = 0, status = 'published' WHERE campaign_id = ? AND organiser_id = ?");
            $stmt->execute([$campaignId, (int)$user['user_id']]);
        } else {
            $stmt = $this->db->prepare("UPDATE Campaign SET date_cloture = NOW(), status = 'closed' WHERE campaign_id = ? AND organiser_id = ?");
            $stmt->execute([$campaignId, (int)$user['user_id']]);
        }

        return ['success' => true, 'message' => 'Statut mis à jour avec succès.'];
    }

    /**
     * POST /api/campaigns/{id}/relaunch
     */
    public function relaunch(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $newEndDate = !empty($data['date_cloture']) ? trim($data['date_cloture']) : null;

        if ($campaignId <= 0 || empty($newEndDate)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Une date de clôture est obligatoire pour relancer la campagne.'];
        }

        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        $stateStmt = $this->db->prepare("SELECT status, date_cloture FROM Campaign WHERE campaign_id = ?");
        $stateStmt->execute([$campaignId]);
        $campaignState = $stateStmt->fetch(PDO::FETCH_ASSOC);
        if (!$campaignState || $campaignState['status'] === 'suspended') {
            http_response_code(409);
            return ['success' => false, 'message' => 'Une campagne suspendue ne peut pas être relancée. Contactez IVote.'];
        }
        if ($campaignState['status'] === 'draft' || ($campaignState['status'] === 'published' && (!$campaignState['date_cloture'] || strtotime($campaignState['date_cloture']) > time()))) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Seule une campagne clôturée peut être relancée.'];
        }

        $timestampCloture = strtotime($newEndDate);
        $minTimestamp = time() + 300;

        if ($timestampCloture === false) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le format de la date de clôture est invalide.'];
        }

        if ($timestampCloture < $minTimestamp) {
            http_response_code(400);
            return [
                'success' => false, 
                'message' => 'La date de clôture doit être située au moins 5 minutes après l\'instant présent.'
            ];
        }

        $newEndDate = date('Y-m-d H:i:s', $timestampCloture);

        $stmt = $this->db->prepare("
            UPDATE Campaign 
            SET is_draft = 0, status = 'published', date_cloture = ?
            WHERE campaign_id = ? AND organiser_id = ?
        ");
        $stmt->execute([$newEndDate, $campaignId, (int)$user['user_id']]);

        return ['success' => true, 'message' => 'Campagne relancée avec succès.'];
    }

    /**
     * POST /api/campaigns/{id}/update-photo
     */
    public function updatePhoto(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $campaignId = (int)$id;
        if ($campaignId <= 0) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Identifiant de campagne invalide.'];
        }

        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        if (!$this->isCampaignEditable($campaignId)) {
            http_response_code(409);
            return ['success' => false, 'message' => 'La photo ne peut être modifiée qu’avant la publication.'];
        }

        $upload = $this->handleFileUpload('campaign_image', 'camp_');
        if (!$upload['success']) {
            http_response_code(400);
            return $upload;
        }

        if (!$upload['url']) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Aucune image fournie.'];
        }

        $stmt = $this->db->prepare("UPDATE Campaign SET image_url = ? WHERE campaign_id = ? AND organiser_id = ?");
        $stmt->execute([$upload['url'], $campaignId, (int)$user['user_id']]);

        return ['success' => true, 'image_url' => $upload['url'], 'message' => 'Photo mise à jour avec succès.'];
    }
}
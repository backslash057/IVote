<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';

class CampaignController extends BaseController {

    public function status(): array {
        return ['success' => true, 'message' => 'IVote API Online'];
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
                           WHEN c.is_draft = 1 THEN 'draft'
                           WHEN c.date_cloture IS NOT NULL AND c.date_cloture < NOW() THEN 'ended'
                           ELSE 'active'
                       END AS status
                FROM Campaign c
                LEFT JOIN Users u ON u.user_id = c.organiser_id
                LEFT JOIN Category cat ON cat.campaign_id = c.campaign_id
                LEFT JOIN Candidate cand ON cand.category_id = cat.category_id
                LEFT JOIN Vote v ON v.candidate_id = cand.candidate_id
                WHERE c.is_draft = 0
                GROUP BY c.campaign_id
                ORDER BY c.campaign_id DESC";

        $stmt = $this->db->query($sql);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * GET /api/campaigns/popular
     */
    public function popular(): array {
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
                WHERE c.is_draft = 0 AND (c.date_cloture IS NULL OR c.date_cloture >= NOW())
                GROUP BY c.campaign_id
                ORDER BY totalVotes DESC
                LIMIT 3";

        $stmt = $this->db->query($sql);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * GET /api/my-campaigns
     */
    public function myCampaigns(): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $sql = "SELECT c.*,
                       u.name AS organizer_name,
                       COUNT(DISTINCT cand.candidate_id) AS candidate_count,
                       COALESCE(SUM(v.vote_count), 0) AS totalVotes,
                       GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.category_id SEPARATOR ', ') AS categories,
                       CASE
                           WHEN c.is_draft = 1 THEN 'draft'
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
                        WHEN c.is_draft = 1 THEN 'draft'
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
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $campaignId = (int)$id;

        $stmt = $this->db->prepare("
            SELECT c.*, 
                   u.name AS organizer_name, 
                   u.email AS organizer_email,
                   CASE 
                       WHEN c.is_draft = 1 THEN 'draft'
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

        return [
            'success' => true,
            'data' => [
                'campaign' => $campaign,
                'categories' => $categories,
                'candidates' => $candidates,
                'transactions' => $transactions,
                'payoutRequests' => $payoutRequests,
                'stats' => [
                    'totalVotes' => $totalVotes,
                    'totalRevenue' => $totalRevenue,
                    'platformFee' => (int)round($totalRevenue * 0.10),
                    'availableBalance' => (int)round($totalRevenue * 0.90)
                ]
            ]
        ];
    }

    /**
     * POST /api/campaigns
     */
    public function create(): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $data = $this->getRequestData();
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $pricePerVote = isset($data['price_per_vote']) ? (int)$data['price_per_vote'] : 100;
        $dateCloture = !empty($data['date_cloture']) ? $data['date_cloture'] : null;

        if (!$title || !$description) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Le titre et la description sont obligatoires.'];
        }

        $upload = $this->handleFileUpload('image', 'camp_');
        if (!$upload['success']) {
            http_response_code(400);
            return $upload;
        }

        $stmt = $this->db->prepare("
            INSERT INTO Campaign (title, description, image_url, price_per_vote, date_cloture, organiser_id, is_draft) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$title, $description, $upload['url'], $pricePerVote, $dateCloture, (int)$user['user_id']]);

        http_response_code(201);
        return [
            'success' => true,
            'campaign_id' => (int)$this->db->lastInsertId(),
            'message' => 'Campagne créée avec succès.'
        ];
    }

    /**
     * POST /api/campaigns/{id}/update
     */
    public function update(string|int $id): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $pricePerVote = (int)($data['price_per_vote'] ?? 100);
        $dateCloture = !empty($data['date_cloture']) ? $data['date_cloture'] : null;

        if ($campaignId <= 0 || empty($title)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Données invalides.'];
        }

        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
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
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
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

        if ($targetAction === 'publish') {
            $stmt = $this->db->prepare("UPDATE Campaign SET is_draft = 0 WHERE campaign_id = ? AND organiser_id = ?");
            $stmt->execute([$campaignId, (int)$user['user_id']]);
        } else {
            $stmt = $this->db->prepare("UPDATE Campaign SET date_cloture = NOW() WHERE campaign_id = ? AND organiser_id = ?");
            $stmt->execute([$campaignId, (int)$user['user_id']]);
        }

        return ['success' => true, 'message' => 'Statut mis à jour avec succès.'];
    }

    /**
     * POST /api/campaigns/{id}/relaunch
     */
    public function relaunch(string|int $id): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $newEndDate = $data['date_cloture'] ?? null;

        if ($campaignId <= 0 || empty($newEndDate)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Date de clôture invalide.'];
        }

        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        $stmt = $this->db->prepare("
            UPDATE Campaign 
            SET is_draft = 0, date_cloture = ? 
            WHERE campaign_id = ? AND organiser_id = ?
        ");
        $stmt->execute([$newEndDate, $campaignId, (int)$user['user_id']]);

        return ['success' => true, 'message' => 'Campagne relancée avec succès.'];
    }

    /**
     * POST /api/campaigns/{id}/update-photo
     */
    public function updatePhoto(string|int $id): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
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
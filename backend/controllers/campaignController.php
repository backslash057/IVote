<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/controllers/authController.php";

class CampaignController {
    private PDO $db;

    public function __construct() {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

        $this->db = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
            $config['username'],
            $config['password']
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Récupère l'ID de l'utilisateur connecté via AuthController
     */
    private function getAuthUserId(): ?int {
        $authController = new AuthController();
        $user = $authController->checkAuthentification();
        return $user ? (int)$user['user_id'] : null;
    }

    /**
     * Parse automatiquement les données entrantes (JSON ou $_POST)
     */
    private function getRequestData(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        return $_POST;
    }

    /**
     * Récupère toutes les campagnes publiques actives (non-brouillons)
     */
    public function getCampaigns(): array {
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les campagnes d'un organisateur spécifique
     */
    public function getCampaignsByOrganizer(int $organizerId): array {
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
        $stmt->bindValue(':organizer_id', $organizerId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Campagnes populaires actives
     */
    public function getPopularCampaigns(int $limit = 3): array {
        $sql = "SELECT c.*, COALESCE(SUM(v.vote_count), 0) AS totalVotes
                FROM Campaign c
                LEFT JOIN Category cat ON c.campaign_id = cat.campaign_id
                LEFT JOIN Candidate cand ON cat.category_id = cand.category_id
                LEFT JOIN Vote v ON cand.candidate_id = v.candidate_id
                WHERE c.is_draft = 0
                GROUP BY c.campaign_id
                ORDER BY totalVotes DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Campagnes populaires actives et non clôturées
     */
    public function getActivePopularCampaigns(int $limit = 3): array {
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
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une campagne par son ID
     */
    public function show(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM Campaign WHERE campaign_id = :id");
        $stmt->execute([':id' => $id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $campaign ?: null;
    }

    /**
     * Détails complets pour la page publique d'une campagne
     */
    public function getCampaignDetails(int $id): ?array {
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
        $campaignStmt->execute([':id' => $id]);
        $campaign = $campaignStmt->fetch(PDO::FETCH_ASSOC);

        if (!$campaign) {
            return null;
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
        $candidateStmt->execute([':id' => $id]);

        $campaign['categories'] = [];
        $campaign['totalVotes'] = 0;

        foreach ($candidateStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $categoryId = (int)$row['category_id'];
            $votes = (int)$row['votes'];

            if (!isset($campaign['categories'][$categoryId])) {
                $campaign['categories'][$categoryId] = [
                    'id' => $categoryId,
                    'name' => $row['category_name'],
                    'candidates' => []
                ];
            }

            if ($row['candidate_id'] !== null) {
                $campaign['categories'][$categoryId]['candidates'][] = [
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
                $campaign['totalVotes'] += $votes;
            }
        }

        $campaign['categories'] = array_values($campaign['categories']);
        $campaign['candidateCount'] = array_sum(array_map(
            static fn(array $category): int => count($category['candidates']),
            $campaign['categories']
        ));

        foreach ($campaign['categories'] as &$category) {
            foreach ($category['candidates'] as &$candidate) {
                $candidate['percentage'] = $campaign['totalVotes'] > 0
                    ? round(($candidate['votes'] / $campaign['totalVotes']) * 100, 1)
                    : 0;
            }
        }
        unset($category, $candidate);

        return $campaign;
    }

    /**
     * Charge toutes les données pour le Tableau de Bord Organisateur
     */
    public function getDashboardData(int $campaignId, int $userId): ?array {
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
        $stmt->execute([$campaignId, $userId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$campaign) {
            return null;
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
        ];
    }

    /**
     * Traitement asynchrone POST /campaigns/new
     */
    public function createCampaign(): array {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            http_response_code(401);
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

        try {
            $imageUrl = null;
            if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $filename = uniqid('camp_', true) . '.' . $ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
                        $imageUrl = '/uploads/' . $filename;
                    }
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO Campaign (title, description, image_url, price_per_vote, date_cloture, organiser_id, is_draft) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$title, $description, $imageUrl, $pricePerVote, $dateCloture, $userId]);

            http_response_code(201);
            return [
                'success' => true,
                'campaign_id' => (int)$this->db->lastInsertId(),
                'message' => 'Campagne créée avec succès.'
            ];
        } catch (Throwable $e) {
            error_log('[createCampaign] ' . $e->getMessage());
            http_response_code(500);
            return ['success' => false, 'message' => 'Une erreur interne est survenue lors de la création.'];
        }
    }

    /**
     * Traitement asynchrone POST /campaigns/{id}/update
     */
    public function updateCampaign(string|int $id): array {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            http_response_code(401);
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

        $stmt = $this->db->prepare("
            UPDATE Campaign 
            SET title = ?, description = ?, price_per_vote = ?, date_cloture = ? 
            WHERE campaign_id = ? AND organiser_id = ?
        ");
        $stmt->execute([$title, $description, $pricePerVote, $dateCloture, $campaignId, $userId]);

        return ['success' => true, 'message' => 'Campagne modifiée avec succès.'];
    }

    /**
     * Traitement asynchrone POST /campaigns/{id}/status (publier / clôturer)
     */
    public function updateStatus(string|int $id): array {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            http_response_code(401);
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $targetAction = $data['action'] ?? ''; // 'publish' ou 'close'

        if ($campaignId <= 0 || !in_array($targetAction, ['publish', 'close'], true)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Action invalide.'];
        }

        if ($targetAction === 'publish') {
            $stmt = $this->db->prepare("UPDATE Campaign SET is_draft = 0 WHERE campaign_id = ? AND organiser_id = ?");
            $stmt->execute([$campaignId, $userId]);
        } else {
            $stmt = $this->db->prepare("UPDATE Campaign SET date_cloture = NOW() WHERE campaign_id = ? AND organiser_id = ?");
            $stmt->execute([$campaignId, $userId]);
        }

        return ['success' => true, 'message' => 'Statut mis à jour avec succès.'];
    }

    /**
     * Traitement asynchrone POST /campaigns/{id}/relaunch
     */
    public function relaunch(string|int $id): array {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            http_response_code(401);
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $newEndDate = $data['date_cloture'] ?? null;

        if ($campaignId <= 0 || empty($newEndDate)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Date de clôture invalide.'];
        }

        $stmt = $this->db->prepare("
            UPDATE Campaign 
            SET is_draft = 0, date_cloture = ? 
            WHERE campaign_id = ? AND organiser_id = ?
        ");
        $stmt->execute([$newEndDate, $campaignId, $userId]);

        return ['success' => true, 'message' => 'Campagne relancée avec succès.'];
    }

    /**
     * Traitement asynchrone POST /campaigns/{id}/update-photo
     */
    public function updatePhoto(string|int $id): array {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            http_response_code(401);
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $campaignId = (int)$id;
        if ($campaignId <= 0 || empty($_FILES['campaign_image']) || $_FILES['campaign_image']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Image manquante ou invalide.'];
        }

        $file = $_FILES['campaign_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Format d\'image non supporté.'];
        }

        $dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = uniqid('camp_', true) . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            $imageUrl = '/uploads/' . $filename;
            $stmt = $this->db->prepare("UPDATE Campaign SET image_url = ? WHERE campaign_id = ? AND organiser_id = ?");
            $stmt->execute([$imageUrl, $campaignId, $userId]);

            return ['success' => true, 'image_url' => $imageUrl, 'message' => 'Photo mise à jour avec succès.'];
        }

        http_response_code(500);
        return ['success' => false, 'message' => 'Erreur lors du transfert du fichier.'];
    }

    /**
     * Traitement asynchrone POST /campaigns/{id}/candidates
     */
    public function addCandidate(string|int $id): array {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            http_response_code(401);
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $name = trim($data['name'] ?? '');
        $candidateNumber = (int)($data['candidate_number'] ?? 0);
        $age = !empty($data['age']) ? (int)$data['age'] : null;
        $theme = trim($data['theme'] ?? '');
        $categoryId = !empty($data['category_id']) && $data['category_id'] !== 'new' ? (int)$data['category_id'] : null;
        $newCatName = trim($data['new_category_name'] ?? '');
        $categoryName = trim($data['category_name'] ?? '');

        if ($campaignId <= 0 || empty($name) || $candidateNumber <= 0) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Informations du candidat incomplètes.'];
        }

        $stmtOwner = $this->db->prepare("SELECT campaign_id FROM Campaign WHERE campaign_id = ? AND organiser_id = ?");
        $stmtOwner->execute([$campaignId, $userId]);
        if (!$stmtOwner->fetch()) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        try {
            if (!empty($newCatName)) {
                $categoryId = $this->createCategory($campaignId, $newCatName);
            } elseif (!empty($categoryName)) {
                $categoryId = $this->createCategory($campaignId, $categoryName, true);
            }

            $imageUrl = null;
            if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $filename = uniqid('cand_', true) . '.' . $ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
                        $imageUrl = '/uploads/' . $filename;
                    }
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO Candidate (name, candidate_number, age, theme, image_url, category_id, campaign_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $candidateNumber, $age, $theme, $imageUrl, $categoryId, $campaignId]);

            return ['success' => true, 'message' => 'Candidat ajouté avec succès.'];
        } catch (PDOException $e) {
            http_response_code(400);
            if ($e->getCode() === '23000') {
                return ['success' => false, 'message' => 'Ce numéro de dossard est déjà utilisé dans cette campagne.'];
            }
            return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement.'];
        }
    }

    /**
     * Traitement asynchrone POST /campaigns/{id}/payouts
     */
    public function createPayout(string|int $id): array {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            http_response_code(401);
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
            return ['success' => false, 'message' => 'Formulaire de retrait incomplet.'];
        }

        $dashData = $this->getDashboardData($campaignId, $userId);
        if (!$dashData || $amount > $dashData['stats']['availableBalance']) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Solde disponible insuffisant.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO Payout (campaign_id, amount_fcfa, payment_method, account_holder, wallet_number, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$campaignId, $amount, $paymentMethod, $accountHolder, $walletNumber]);

        return ['success' => true, 'message' => 'Demande de retrait initiée avec succès.'];
    }

    /**
     * Traitement asynchrone POST /campaigns/{id}/votes
     */
    public function recordVote(string|int $id): array {
        $campaignId = (int)$id;
        $data = $this->getRequestData();
        $candidateId = (int)($data['candidate_id'] ?? 0);
        $voteCount = (int)($data['vote_count'] ?? 1);
        $paymentMethod = $data['payment_method'] ?? '';

        if ($campaignId <= 0 || $candidateId <= 0 || $voteCount <= 0 || $voteCount > 500 || !in_array($paymentMethod, ['mtn_momo', 'orange_money'], true)) {
            http_response_code(422);
            return ['success' => false, 'message' => 'Données de vote invalides (1 à 500 votes).'];
        }

        $stmtCheck = $this->db->prepare("
            SELECT price_per_vote, is_draft, date_cloture
            FROM Campaign
            WHERE campaign_id = ?
        ");
        $stmtCheck->execute([$campaignId]);
        $camp = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$camp || (int)$camp['is_draft'] === 1 || ($camp['date_cloture'] && strtotime($camp['date_cloture']) < time())) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Cette campagne n\'est pas active.'];
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

        return [
            'success' => true,
            'message' => 'Vote enregistré avec succès.',
            'transaction_ref' => $transactionRef,
            'votes_added' => $voteCount,
            'amount_fcfa' => $amountFcfa,
        ];
    }

    /**
     * Crée une catégorie (ou la réutilise si reuse = true et qu'elle existe déjà).
     */
    private function createCategory(int $campaignId, string $name, bool $reuse = false): int {
        if ($reuse) {
            $stmtFind = $this->db->prepare("SELECT category_id FROM Category WHERE campaign_id = ? AND name = ?");
            $stmtFind->execute([$campaignId, $name]);
            $existing = $stmtFind->fetchColumn();
            if ($existing !== false) {
                return (int)$existing;
            }
        }

        $stmtNew = $this->db->prepare("INSERT INTO Category (name, campaign_id) VALUES (?, ?)");
        $stmtNew->execute([$name, $campaignId]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Suppression d'une campagne
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Campaign WHERE campaign_id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
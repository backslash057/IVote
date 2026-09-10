<?php

class CampaignController {
    private $db;

    public function __construct() {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

        $this->db = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
            $config['username'], $config['password']
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    
    public function getCampaigns(): array {
        $sql = "SELECT c.*,
                       u.name AS organizer_name,
                       COUNT(DISTINCT cand.candidate_id) AS candidate_count,
                       COALESCE(SUM(v.vote_count), 0) AS totalVotes,
                       GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.category_id SEPARATOR ', ') AS categories,
                       CASE
                           WHEN c.date_cloture IS NULL OR c.date_cloture >= NOW() THEN 'active'
                           ELSE 'ended'
                       END AS status
                FROM Campaign c
                LEFT JOIN Users u ON u.user_id = c.organiser_id
                LEFT JOIN Category cat ON cat.campaign_id = c.campaign_id
                LEFT JOIN Candidate cand ON cand.category_id = cat.category_id
                LEFT JOIN Vote v ON v.candidate_id = cand.candidate_id
                GROUP BY c.campaign_id
                ORDER BY c.campaign_id DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function getPopularCampaigns(int $limit = 3) {
        // Requête récupérant les campagnes et calculant le nombre total de votes par campagne
        $sql = "SELECT c.*, COALESCE(SUM(v.vote_count), 0) AS totalVotes
                FROM Campaign c
                LEFT JOIN Category cat ON c.campaign_id = cat.campaign_id
                LEFT JOIN Candidate cand ON cat.category_id = cand.category_id
                LEFT JOIN Vote v ON cand.candidate_id = v.candidate_id
                GROUP BY c.campaign_id
                ORDER BY totalVotes DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActivePopularCampaigns(int $limit = 3) {
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
                WHERE c.date_cloture IS NULL OR c.date_cloture >= NOW()
                GROUP BY c.campaign_id
                ORDER BY totalVotes DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function show(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM Campaign WHERE campaign_id = :id");
        $stmt->execute([':id' => $id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $campaign ?: null;
    }

    public function getCampaignDetails(int $id): ?array {
        $campaignStmt = $this->db->prepare(
            "SELECT c.*, u.name AS organizer_name
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
                    cand.description, cand.bio,
                    COALESCE(SUM(v.vote_count), 0) AS votes
             FROM Category cat
             LEFT JOIN Candidate cand ON cand.category_id = cat.category_id
             LEFT JOIN Vote v ON v.candidate_id = cand.candidate_id
             WHERE cat.campaign_id = :id
             GROUP BY cat.category_id, cat.name, cand.candidate_id, cand.candidate_number,
                      cand.name, cand.age, cand.theme,
                      cand.description, cand.bio
             ORDER BY cat.category_id, cand.candidate_id"
        );
        $candidateStmt->execute([':id' => $id]);

        $campaign['categories'] = [];
        $campaign['totalVotes'] = 0;

        foreach ($candidateStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $categoryId = (int) $row['category_id'];
            $votes = (int) $row['votes'];

            if (!isset($campaign['categories'][$categoryId])) {
                $campaign['categories'][$categoryId] = [
                    'id' => $categoryId,
                    'name' => $row['category_name'],
                    'candidates' => []
                ];
            }

            if ($row['candidate_id'] !== null) {
                $campaign['categories'][$categoryId]['candidates'][] = [
                    'id' => (int) $row['candidate_id'],
                    'number' => (int) $row['candidate_number'],
                    'name' => $row['name'],
                    'age' => $row['age'] !== null ? (int) $row['age'] : null,
                    'theme' => $row['theme'],
                    'description' => $row['description'],
                    'bio' => $row['bio'],
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

    public function recordVote(int $id): array {
        $request = json_decode(file_get_contents('php://input'), true) ?? [];
        $candidateId = filter_var($request['candidate_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $voteCount = filter_var($request['vote_count'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $paymentMethod = $request['payment_method'] ?? null;

        if (!$candidateId || !$voteCount || !in_array($paymentMethod, ['MOMO', 'OM'], true)) {
            http_response_code(422);
            return ['success' => false, 'message' => 'Candidat, quantité et moyen de paiement invalides.'];
        }

        $stmt = $this->db->prepare(
            "SELECT cand.candidate_id
             FROM Candidate cand
             INNER JOIN Category cat ON cat.category_id = cand.category_id
             WHERE cand.candidate_id = :candidate_id
               AND cat.campaign_id = :campaign_id
                             AND EXISTS (
                                     SELECT 1 FROM Campaign campaign
                                     WHERE campaign.campaign_id = :campaign_id_check
                                         AND (campaign.date_cloture IS NULL OR campaign.date_cloture >= NOW())
                             )"
        );
        $stmt->execute([
            ':candidate_id' => $candidateId,
            ':campaign_id' => $id,
            ':campaign_id_check' => $id
        ]);

        if (!$stmt->fetchColumn()) {
            http_response_code(422);
            return ['success' => false, 'message' => 'Ce candidat ne peut pas recevoir de vote pour cette campagne.'];
        }

        $insert = $this->db->prepare(
            "INSERT INTO Vote (candidate_id, payment_method, vote_count)
             VALUES (:candidate_id, :payment_method, :vote_count)"
        );
        $insert->execute([
            ':candidate_id' => $candidateId,
            ':payment_method' => $paymentMethod,
            ':vote_count' => $voteCount
        ]);

        return [
            'success' => true,
            'message' => 'Votes enregistrés.',
            'votes_added' => $voteCount
        ];
    }

    
    public function store(array $data): bool {
        $sql = "INSERT INTO Campaign (title, description, image_url, date_cloture, organiser_id) 
                VALUES (:title, :description, :image_url, :date_cloture, :organiser_id)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':title'       => $data['title'],
            ':description' => $data['description'],
            ':image_url'   => $data['image_url'] ?? null,
            ':date_cloture' => $data['date_cloture'] ?? null,
            ':organiser_id' => $data['organiser_id']
        ]);
    }

    
    public function update(int $id, array $data): bool {
        $sql = "UPDATE Campaign 
                SET title = :title, 
                    description = :description, 
                    image_url = :image_url, 
                    date_cloture = :date_cloture 
                WHERE campaign_id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'          => $id,
            ':title'       => $data['title'],
            ':description' => $data['description'],
            ':image_url'   => $data['image_url'] ?? null,
            ':date_cloture' => $data['date_cloture'] ?? null
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Campaign WHERE campaign_id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
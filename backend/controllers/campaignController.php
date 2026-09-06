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

    
    public function index(): array {
        $stmt = $this->db->query("SELECT * FROM Campaign ORDER BY campaign_id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function getPopularCampaigns(int $limit = 3) {
        // Requête récupérant les campagnes et calculant le nombre total de votes par campagne
        $sql = "SELECT c.*, COALESCE(COUNT(v.vote_id), 0) AS totalVotes
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

    
    public function show(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM Campaign WHERE campaign_id = :id");
        $stmt->execute([':id' => $id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $campaign ?: null;
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
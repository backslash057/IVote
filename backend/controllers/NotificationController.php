<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';

class NotificationController extends BaseController {

    public function index(): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $stmt = $this->db->prepare(
            "SELECT notification_id, sender_id, type, title, message, resource_type, resource_id, read_at, created_at
             FROM Notification WHERE recipient_id = ? ORDER BY created_at DESC LIMIT 100"
        );
        $stmt->execute([(int)$user['user_id']]);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function unreadCount(): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Notification WHERE recipient_id = ? AND read_at IS NULL");
        $stmt->execute([(int)$user['user_id']]);
        return ['success' => true, 'count' => (int)$stmt->fetchColumn()];
    }

    public function markRead(string|int $id): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $stmt = $this->db->prepare("UPDATE Notification SET read_at = NOW() WHERE notification_id = ? AND recipient_id = ?");
        $stmt->execute([(int)$id, (int)$user['user_id']]);
        return ['success' => true, 'message' => 'Notification marquée comme lue.'];
    }

    public function markAllRead(): array {
        $user = $this->requireAuth();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $stmt = $this->db->prepare("UPDATE Notification SET read_at = NOW() WHERE recipient_id = ? AND read_at IS NULL");
        $stmt->execute([(int)$user['user_id']]);
        return ['success' => true, 'message' => 'Notifications marquées comme lues.'];
    }
}

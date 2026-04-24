<?php
class NotificationController extends AppController {

    private function requireLogin(): array {
        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return $_SESSION['usuario'];
    }

    private function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET ?controller=Notification&action=getAll */
    public function getAll(): void {
        $user  = $this->requireLogin();
        $notifs = NotificationModel::getForUser((int)$user['id'], 20);
        $this->json(['notifications' => $notifs]);
    }

    /** GET ?controller=Notification&action=count */
    public function count(): void {
        $user  = $this->requireLogin();
        $count = NotificationModel::getUnreadCount((int)$user['id']);
        $this->json(['count' => $count]);
    }

    /** POST ?controller=Notification&action=markRead  body: id=N */
    public function markRead(): void {
        $user = $this->requireLogin();
        $id   = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            NotificationModel::markRead($id, (int)$user['id']);
        }
        $this->json(['ok' => true]);
    }

    /** POST ?controller=Notification&action=markAllRead */
    public function markAllRead(): void {
        $user = $this->requireLogin();
        NotificationModel::markAllRead((int)$user['id']);
        $this->json(['ok' => true]);
    }
}

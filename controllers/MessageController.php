<?php
class MessageController extends AppController {

    private function requireLogin(): array {
        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return $_SESSION['usuario'];
    }

    private function json(array $data, int $code = 200): never {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError(string $msg, int $code = 400): never {
        $this->json(['error' => $msg], $code);
    }

    /** GET ?controller=Message&action=users */
    public function users(): void {
        $user    = $this->requireLogin();
        $isAdmin = ($user['rol'] ?? 'user') === 'admin';
        $users   = $isAdmin
            ? UsuarioModel::listAllForMessaging((int)$user['id'])
            : UsuarioModel::getInSameDepts((int)$user['id']);
        $this->json(['users' => $users]);
    }

    /** GET ?controller=Message&action=conversations */
    public function conversations(): void {
        $user  = $this->requireLogin();
        $convs = MessageModel::getConversationsForUser((int)$user['id']);
        $this->json(['conversations' => $convs]);
    }

    /** POST ?controller=Message&action=create  body JSON: {user_id: N} */
    public function create(): void {
        $user    = $this->requireLogin();
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $otherId = (int)($input['user_id'] ?? 0);
        if (!$otherId || $otherId === (int)$user['id']) {
            $this->jsonError('user_id inválido');
        }
        $conv = MessageModel::getOrCreateConversation((int)$user['id'], $otherId);
        $this->json(['conversation' => $conv]);
    }

    /** GET ?controller=Message&action=getMessages&id=N */
    public function getMessages(): void {
        $user   = $this->requireLogin();
        $convId = (int)($_GET['id'] ?? 0);
        if (!$convId) $this->jsonError('id requerido');
        if (!MessageModel::isParticipant($convId, (int)$user['id'])) {
            $this->jsonError('No autorizado', 403);
        }
        $msgs = MessageModel::getMessages($convId, (int)$user['id']);
        $this->json(['messages' => $msgs]);
    }

    /** POST ?controller=Message&action=send  body JSON: {conversation_id: N, content: "..."} */
    public function send(): void {
        $user    = $this->requireLogin();
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $convId  = (int)($input['conversation_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        if (!$convId) $this->jsonError('conversation_id requerido');
        if ($content === '') $this->jsonError('content requerido');
        if (!MessageModel::isParticipant($convId, (int)$user['id'])) {
            $this->jsonError('No autorizado', 403);
        }
        $id = MessageModel::sendMessage($convId, (int)$user['id'], $content);
        $this->json(['id' => $id], 201);
    }

    /** GET ?controller=Message&action=unreadCount */
    public function unreadCount(): void {
        $user  = $this->requireLogin();
        $count = MessageModel::getUnreadCount((int)$user['id']);
        $this->json(['count' => $count]);
    }
}

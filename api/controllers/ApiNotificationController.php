<?php
/**
 * Controlador API para notificaciones.
 */
class ApiNotificationController extends ApiController {

    /**
     * Lista las notificaciones más recientes del usuario autenticado
     */
    public function index(): void {
        $user = $this->requireApiAuth();
        // Usamos el modelo NotificationModel para obtener notificaciones
        $notifications = NotificationModel::getForUser((int)$user['id'], 20);
        $this->jsonResponse([
            'notifications' => $notifications
        ], 200);
    }

    /**
     * Devuelve el número de notificaciones no leídas
     */
    public function count(): void {
        $user = $this->requireApiAuth();
        $count = NotificationModel::getUnreadCount((int)$user['id']);
        $this->jsonResponse(['count' => $count], 200);
    }


    /**
     * Marca todas las notificaciones del usuario como leídas
     */
    public function readAll(): void {
        $user = $this->requireApiAuth();
        NotificationModel::markAllRead((int)$user['id']);
        $this->jsonResponse(['message' => 'Todas las notificaciones marcadas como leídas'], 200);
    }

    /**
     * Marca una notificación concreta como leída
     */
    public function markRead($id): void {
        $user = $this->requireApiAuth();
        NotificationModel::markRead((int)$id, (int)$user['id']);
        $this->jsonResponse(['message' => "Notificación $id marcada como leída"], 200);
    }
}

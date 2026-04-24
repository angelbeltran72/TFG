<?php
class NotificationModel {

  // Tipos válidos: ticket_assigned, ticket_comment, ticket_status, ticket_created, ticket_overdue
  public static function create(
    int     $userId,
    string  $type,
    string  $message,
    ?string $resourceType = null,
    ?int    $resourceId   = null,
    ?string $url          = null
  ): int {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO notifications (user_id, type, message, resource_type, resource_id)
       VALUES (?, ?, ?, ?, ?)"
    );
    $st->execute([$userId, $type, $message, $resourceType, $resourceId]);
    return (int)$db->lastInsertId();
  }

  // Cuenta notificaciones no leídas para el badge del icono
  public static function getUnreadCount(int $userId): int {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL"
    );
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
  }

  // Retorna las notificaciones más recientes del panel (leídas y no leídas)
  public static function getForUser(int $userId, int $limit = 20): array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id, type, message, resource_type, resource_id, read_at, created_at
       FROM notifications
       WHERE user_id = ?
       ORDER BY created_at DESC
       LIMIT " . (int)$limit
    );
    $st->execute([$userId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function markRead(int $notifId, int $userId): bool {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "UPDATE notifications SET read_at = NOW()
       WHERE id = ? AND user_id = ? AND read_at IS NULL"
    );
    return $st->execute([$notifId, $userId]);
  }

  public static function markAllRead(int $userId): bool {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "UPDATE notifications SET read_at = NOW()
       WHERE user_id = ? AND read_at IS NULL"
    );
    return $st->execute([$userId]);
  }
}

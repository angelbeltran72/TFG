<?php
class TicketCommentModel {

  public static function getByTicket(int $ticketId, bool $includeInternal = true): array {
    $db = SPDO::singleton();
    $internalWhere = $includeInternal ? '' : ' AND tc.is_internal = 0';
    $st = $db->prepare(
      "SELECT tc.id, tc.ticket_id, tc.user_id, tc.contenido, tc.is_internal,
              tc.event_type, tc.created_at, tc.updated_at, u.nombre AS autor_nombre
       FROM ticket_comments tc
       LEFT JOIN users u ON u.id = tc.user_id
       WHERE tc.ticket_id = ?$internalWhere
       ORDER BY tc.created_at ASC, tc.id ASC"
    );
    $st->execute([$ticketId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function add(int $ticketId, int $userId, string $contenido, bool $isInternal = false): int {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO ticket_comments (ticket_id, user_id, contenido, is_internal, event_type, created_at)
       VALUES (?, ?, ?, ?, NULL, NOW())"
    );
    $st->execute([$ticketId, $userId, $contenido, (int)$isInternal]);
    return (int)$db->lastInsertId();
  }

  public static function logEvent(int $ticketId, int $userId, string $eventType, string $contenido): void {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO ticket_comments (ticket_id, user_id, contenido, is_internal, event_type, created_at)
       VALUES (?, ?, ?, 0, ?, NOW())"
    );
    $st->execute([$ticketId, $userId, $contenido, $eventType]);
  }
}

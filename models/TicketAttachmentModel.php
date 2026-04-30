<?php
class TicketAttachmentModel {

  public static function getByTicket(int $ticketId): array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id, ticket_id, user_id, filename, storage_path, original_name, mime_type, size_bytes, created_at
         FROM ticket_attachments
        WHERE ticket_id = ?
        ORDER BY created_at ASC"
    );
    $st->execute([$ticketId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function save(int $ticketId, ?int $userId, string $filename, string $storagePath, string $originalName, string $mimeType, int $sizeBytes): int {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO ticket_attachments (ticket_id, user_id, filename, storage_path, original_name, mime_type, size_bytes)
       VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $st->execute([$ticketId, $userId, $filename, $storagePath, $originalName, $mimeType, $sizeBytes]);
    return (int)$db->lastInsertId();
  }
}

<?php
class UserDepartamentoModel {

  /** Get all departments a user belongs to */
  public static function getByUser(int $userId): array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT d.id, d.nombre, d.color, ud.assigned_at
       FROM user_departamentos ud
       JOIN departamentos d ON d.id = ud.departamento_id
       WHERE ud.user_id = ?
       ORDER BY d.nombre"
    );
    $st->execute([$userId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /** Get all users in a department */
  public static function getUsersByDepto(int $deptoId): array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT u.id, u.nombre, u.email, u.rol, u.avatar_path, ud.assigned_at
       FROM user_departamentos ud
       JOIN users u ON u.id = ud.user_id
       WHERE ud.departamento_id = ?
       ORDER BY u.nombre"
    );
    $st->execute([$deptoId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function assign(int $userId, int $deptoId, ?int $assignedBy): bool {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT IGNORE INTO user_departamentos (user_id, departamento_id, assigned_by) VALUES (?, ?, ?)"
    );
    return $st->execute([$userId, $deptoId, $assignedBy]);
  }

  public static function remove(int $userId, int $deptoId): bool {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "DELETE FROM user_departamentos WHERE user_id = ? AND departamento_id = ?"
    );
    return $st->execute([$userId, $deptoId]);
  }

  public static function isUserInDepto(int $userId, int $deptoId): bool {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT 1 FROM user_departamentos WHERE user_id = ? AND departamento_id = ? LIMIT 1"
    );
    $st->execute([$userId, $deptoId]);
    return (bool)$st->fetchColumn();
  }

  /** Remove all department assignments for a user */
  public static function removeAllForUser(int $userId): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("DELETE FROM user_departamentos WHERE user_id = ?");
    return $st->execute([$userId]);
  }

  /** All active departments a user belongs to (primary + junction table, deduplicated) */
  public static function getAllForUser(int $userId): array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT DISTINCT d.id, d.nombre, d.color
       FROM departamentos d
       WHERE d.is_active = 1 AND (
         d.id = (SELECT departamento_id FROM users WHERE id = ? LIMIT 1)
         OR d.id IN (SELECT departamento_id FROM user_departamentos WHERE user_id = ?)
       )
       ORDER BY d.nombre"
    );
    $st->execute([$userId, $userId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }
}

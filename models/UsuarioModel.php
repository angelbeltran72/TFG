<?php
class UsuarioModel {

  public static function findByEmail($email) {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id, nombre, email, password_hash, rol, is_active, avatar_path, must_change_password
       FROM users WHERE email = ?"
    );
    $st->execute([$email]);
    return $st->fetch(PDO::FETCH_ASSOC);
  }

  public static function findRolById(int $id): ?array {
    $db = SPDO::singleton();
    $st = $db->prepare("SELECT rol, is_active FROM users WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function existsByEmail($email): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    return (bool)$st->fetchColumn();
  }

  public static function create($nombre, $email, $password_hash, $rol = "user"): int {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO users (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)"
    );
    $st->execute([$nombre, $email, $password_hash, $rol]);
    return (int)$db->lastInsertId();
  }

  /** Crea un usuario con contraseña temporal (fuerza cambio en primer login) */
  public static function createByAdmin(string $nombre, string $email, string $password_hash, string $rol = "user"): int {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO users (nombre, email, password_hash, rol, must_change_password) VALUES (?, ?, ?, ?, 1)"
    );
    $st->execute([$nombre, $email, $password_hash, $rol]);
    return (int)$db->lastInsertId();
  }

  public static function saveRememberToken($id, $hash): void {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    $st->execute([$hash, $id]);
  }

  public static function findByRememberToken($hash) {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id, nombre, email, rol, is_active, avatar_path FROM users WHERE remember_token = ? LIMIT 1"
    );
    $st->execute([$hash]);
    return $st->fetch(PDO::FETCH_ASSOC);
  }

  public static function updatePassword(int $id, string $password_hash): void {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $st->execute([$password_hash, $id]);
  }

  public static function setMustChangePassword(int $id, bool $value): void {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET must_change_password = ? WHERE id = ?");
    $st->execute([(int)$value, $id]);
  }

  public static function updateAvatar(int $id, string $avatarPath): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
    return $st->execute([$avatarPath, $id]);
  }

  public static function removeAvatar(int $id): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET avatar_path = NULL WHERE id = ?");
    return $st->execute([$id]);
  }

  public static function updateActive(int $id, int $isActive): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    return $st->execute([(int)$isActive, $id]);
  }

  public static function updateRol(int $id, string $rol): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET rol = ? WHERE id = ?");
    return $st->execute([$rol, $id]);
  }

  public static function touchLastLogin(int $id): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
    return $st->execute([$id]);
  }

  public static function touchLastSeen(int $id): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET last_seen_at = NOW() WHERE id = ?");
    return $st->execute([$id]);
  }

  public static function findById(int $id): ?array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id, nombre, email, rol, avatar_path, last_login_at, last_seen_at,
              is_active, must_change_password, departamento_id, created_at, notification_prefs
       FROM users WHERE id = ?"
    );
    $st->execute([$id]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    return $u ?: null;
  }

  public static function getNotificationPrefs(int $id): array {
    $db = SPDO::singleton();
    try {
      $st = $db->prepare("SELECT notification_prefs FROM users WHERE id = ?");
      $st->execute([$id]);
      $raw = $st->fetchColumn();
      if (!$raw) return [];
      return json_decode($raw, true) ?? [];
    } catch (\Exception $e) {
      return [];
    }
  }

  public static function saveNotificationPrefs(int $id, array $prefs): bool {
    $db = SPDO::singleton();
    try {
      $st = $db->prepare("UPDATE users SET notification_prefs = ? WHERE id = ?");
      return $st->execute([json_encode($prefs), $id]);
    } catch (\Exception $e) {
      return false;
    }
  }

  public static function listAll(): array {
    $db = SPDO::singleton();
    $st = $db->query(
      "SELECT u.id, u.nombre, u.email, u.rol, u.avatar_path, u.last_seen_at, u.is_active, u.departamento_id,
              COUNT(t.id) AS ticket_count
       FROM users u
       LEFT JOIN tickets t ON t.creado_por = u.id
       GROUP BY u.id
       ORDER BY u.rol DESC, u.nombre ASC"
    );
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function findPublicById(int $id): ?array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id, nombre, email, rol, avatar_path, last_seen_at FROM users WHERE id = ?"
    );
    $st->execute([$id]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    return $u ?: null;
  }

  public static function searchAll(string $q): array {
    $db = SPDO::singleton();
    $q  = trim($q);
    if ($q === "") return self::listAll();

    $like = "%" . $q . "%";
    $st = $db->prepare(
      "SELECT id, nombre, email, rol, avatar_path, last_seen_at, is_active
       FROM users
       WHERE nombre LIKE ? OR email LIKE ?
       ORDER BY rol DESC, nombre ASC"
    );
    $st->execute([$like, $like]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function updateProfile(int $id, string $nombre, string $email): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET nombre = ?, email = ? WHERE id = ?");
    return $st->execute([$nombre, $email, $id]);
  }

  public static function delete(int $id): bool {
    $db = SPDO::singleton();
    // fk_tickets_creado_por has no ON DELETE — remove their tickets first; children cascade
    $db->prepare("DELETE FROM tickets WHERE creado_por = ?")->execute([$id]);
    $st = $db->prepare("DELETE FROM users WHERE id = ?");
    return $st->execute([$id]);
  }

  public static function countCreatedTickets(int $id): int {
    $db = SPDO::singleton();
    $st = $db->prepare("SELECT COUNT(*) FROM tickets WHERE creado_por = ?");
    $st->execute([$id]);
    return (int)$st->fetchColumn();
  }

  public static function updateDepartamento(int $id, ?int $deptoId): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET departamento_id = ? WHERE id = ?");
    return $st->execute([$deptoId, $id]);
  }

  public static function saveApiToken(int $id, string $hash): void {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET api_token_hash = ? WHERE id = ?");
    $st->execute([$hash, $id]);
  }

  public static function findByApiTokenHash(string $hash): ?array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id, nombre, email, rol, is_active FROM users WHERE api_token_hash = ? LIMIT 1"
    );
    $st->execute([$hash]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function revokeApiToken(int $id): void {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE users SET api_token_hash = NULL WHERE id = ?");
    $st->execute([$id]);
  }

  public static function listAllForMessaging(int $excludeId): array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id, nombre, rol, avatar_path FROM users
       WHERE is_active = 1 AND id != ? ORDER BY nombre ASC"
    );
    $st->execute([$excludeId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getInSameDepts(int $userId): array {
    $db = SPDO::singleton();
    $st = $db->prepare("
        SELECT DISTINCT u.id, u.nombre, u.rol, u.avatar_path
        FROM users u
        WHERE u.id != :uid
          AND u.is_active = 1
          AND (
            u.departamento_id IN (
                SELECT departamento_id FROM user_departamentos WHERE user_id = :uid2
                UNION
                SELECT departamento_id FROM users WHERE id = :uid3 AND departamento_id IS NOT NULL
            )
            OR u.id IN (
                SELECT ud2.user_id FROM user_departamentos ud2
                WHERE ud2.departamento_id IN (
                    SELECT departamento_id FROM user_departamentos WHERE user_id = :uid4
                    UNION
                    SELECT departamento_id FROM users WHERE id = :uid5 AND departamento_id IS NOT NULL
                )
            )
          )
        ORDER BY u.nombre
    ");
    $st->execute([
        ':uid'  => $userId,
        ':uid2' => $userId,
        ':uid3' => $userId,
        ':uid4' => $userId,
        ':uid5' => $userId,
    ]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }
}

<?php
class ActivityLogModel {

  /**
   * 
   * @param int|null $userId  
   * @param string   $type    
   * @param string   $detail  
   * @param string|null $ip
   */
  public static function log(?int $userId, string $type, string $detail, ?string $ip = null): void {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO activity_log (user_id, type, detail, ip) VALUES (?, ?, ?, ?)"
    );
    $st->execute([$userId, $type, mb_substr($detail, 0, 500), $ip]);
  }

  /**
   * Obtiene las entradas de registro paginadas con filtros opcionales.
   *
   * @param array $filters  ['user_id' => int, 'type' => string, 'days' => int]
   * @param int   $limit
   * @param int   $offset
   */
  public static function getRecent(array $filters = [], int $limit = 50, int $offset = 0): array {
    $db     = SPDO::singleton();
    $where  = ["1=1"];
    $params = [];

    if (!empty($filters['user_id'])) {
      $where[]  = "al.user_id = ?";
      $params[] = (int)$filters['user_id'];
    }
    if (!empty($filters['type'])) {
      $where[]  = "al.type = ?";
      $params[] = $filters['type'];
    }
    if (!empty($filters['days'])) {
      $where[]  = "al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
      $params[] = (int)$filters['days'];
    }

    $sql = "
      SELECT al.id, al.type, al.detail, al.ip, al.created_at,
             u.nombre AS user_nombre, u.rol AS user_rol, u.avatar_path
      FROM activity_log al
      LEFT JOIN users u ON u.id = al.user_id
      WHERE " . implode(" AND ", $where) . "
      ORDER BY al.created_at DESC
      LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function countRecent(array $filters = []): int {
    $db     = SPDO::singleton();
    $where  = ["1=1"];
    $params = [];

    if (!empty($filters['user_id'])) {
      $where[]  = "user_id = ?";
      $params[] = (int)$filters['user_id'];
    }
    if (!empty($filters['type'])) {
      $where[]  = "type = ?";
      $params[] = $filters['type'];
    }
    if (!empty($filters['days'])) {
      $where[]  = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
      $params[] = (int)$filters['days'];
    }

    $sql = "SELECT COUNT(*) FROM activity_log WHERE " . implode(" AND ", $where);
    $st  = $db->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
  }
}

<?php
class TicketModel {

  public static function getCategorias(): array {
    $db = SPDO::singleton();
    $st = $db->query(
      "SELECT id, nombre, color, departamento_id FROM categorias
       WHERE is_active = 1 ORDER BY nombre"
    );
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getUsuariosAsignables(): array {
    $db = SPDO::singleton();
    $st = $db->query("SELECT id, nombre, email, rol FROM users ORDER BY nombre");
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function create(array $data): int {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO tickets
         (titulo, descripcion, categoria_id, departamento_id, prioridad, estado, creado_por, asignado_a, cliente_email, cliente_user_id, due_date, created_at)
       VALUES (?, ?, ?, ?, ?, 'sin_abrir', ?, ?, ?, ?, ?, NOW())"
    );
    $st->execute([
      $data['titulo'],
      $data['descripcion'],
      $data['categoria_id'],
      $data['departamento_id'] ?? null,
      $data['prioridad'],
      $data['creado_por'],
      $data['asignado_a'] ?? null,
      $data['cliente_email'] ?? null,
      $data['cliente_user_id'] ?? null,
      $data['due_date'] ?? null,
    ]);
    return (int)$db->lastInsertId();
  }

  public static function findUserIdByEmail(string $email): ?int {
    $email = trim($email);
    if ($email === '') {
      return null;
    }
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id
       FROM users
       WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))
       LIMIT 1"
    );
    $st->execute([$email]);
    $id = $st->fetchColumn();
    return $id !== false ? (int)$id : null;
  }

  public static function linkClientTicketsByEmail(int $userId, string $email): int {
    $email = trim($email);
    if ($userId <= 0 || $email === '') {
      return 0;
    }
    $db = SPDO::singleton();
    $st = $db->prepare(
      "UPDATE tickets
       SET cliente_user_id = ?
       WHERE deleted_at IS NULL
         AND cliente_email IS NOT NULL
         AND TRIM(cliente_email) <> ''
         AND LOWER(TRIM(cliente_email)) = LOWER(TRIM(?))
         AND (cliente_user_id IS NULL OR cliente_user_id = 0)"
    );
    $st->execute([$userId, $email]);
    return $st->rowCount();
  }

  public static function listForUser(int $userId, bool $isAdmin, ?string $role = null): array {
    $db = SPDO::singleton();
    $role = self::resolveRole($userId, $role);
    [$where, $params] = self::buildVisibilityWhere('t', $userId, $isAdmin, self::getEffectiveDeptIds($userId, $isAdmin, null, $role), $role);

    $sql =
      "SELECT t.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
              u1.nombre AS creado_por_nombre, u2.nombre AS asignado_a_nombre
       FROM tickets t
       LEFT JOIN categorias c ON c.id = t.categoria_id
       LEFT JOIN users u1 ON u1.id = t.creado_por
       LEFT JOIN users u2 ON u2.id = t.asignado_a
       WHERE $where
       ORDER BY t.id DESC";

    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function findById(int $id, int $userId, bool $isAdmin, ?string $role = null): ?array {
    $db = SPDO::singleton();
    $role = self::resolveRole($userId, $role);
    [$where, $params] = self::buildVisibilityWhere('t', $userId, $isAdmin, self::getEffectiveDeptIds($userId, $isAdmin, null, $role), $role);

    $sql =
      "SELECT t.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
              u1.nombre AS creado_por_nombre, u2.nombre AS asignado_a_nombre,
              u3.nombre AS cliente_nombre
       FROM tickets t
       LEFT JOIN categorias c ON c.id = t.categoria_id
       LEFT JOIN users u1 ON u1.id = t.creado_por
       LEFT JOIN users u2 ON u2.id = t.asignado_a
       LEFT JOIN users u3 ON u3.id = t.cliente_user_id
       WHERE t.id = ? AND $where
       LIMIT 1";

    $st = $db->prepare($sql);
    $st->execute(array_merge([$id], $params));
    $t = $st->fetch(PDO::FETCH_ASSOC);
    return $t ?: null;
  }

  public static function countActiveForUser(int $userId): int {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT COUNT(*) FROM tickets
       WHERE creado_por = ? AND estado IN ('sin_abrir','abierta','en_proceso') AND deleted_at IS NULL"
    );
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
  }

  public static function getUserStats(int $userId): array {
    $db = SPDO::singleton();
    $sql = "
      SELECT
        SUM(CASE WHEN estado IN ('sin_abrir','abierta') AND asignado_a = ? THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN estado = 'en_proceso'             AND asignado_a = ? THEN 1 ELSE 0 END) AS en_proceso,
        SUM(CASE WHEN estado IN ('resuelta','cerrada')  AND asignado_a = ? THEN 1 ELSE 0 END) AS resueltos,
        SUM(CASE WHEN creado_por = ?                                       THEN 1 ELSE 0 END) AS creados
      FROM tickets
      WHERE (asignado_a = ? OR creado_por = ?) AND deleted_at IS NULL
    ";
    $st = $db->prepare($sql);
    $st->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
  }

  public static function getDashboardSummary(int $userId, bool $isAdmin, array $agentDeptIds = [], ?string $role = null): array {
    $db = SPDO::singleton();
    $role = self::resolveRole($userId, $role);
    [$where, $params] = self::buildVisibilityWhere('t', $userId, $isAdmin, self::getEffectiveDeptIds($userId, $isAdmin, $agentDeptIds, $role), $role);

    $sql = "
      SELECT
        COUNT(*) AS total_visible,
        SUM(CASE WHEN t.estado = 'sin_abrir' THEN 1 ELSE 0 END) AS sin_abrir,
        SUM(CASE WHEN t.estado = 'abierta' THEN 1 ELSE 0 END) AS abiertas,
        SUM(CASE WHEN t.estado = 'en_proceso' THEN 1 ELSE 0 END) AS en_proceso,
        SUM(CASE WHEN t.estado = 'resuelta' THEN 1 ELSE 0 END) AS resueltas,
        SUM(CASE WHEN t.estado = 'cerrada' THEN 1 ELSE 0 END) AS cerradas,
        SUM(CASE WHEN t.estado IN ('sin_abrir','abierta','en_proceso') THEN 1 ELSE 0 END) AS sin_resolver
      FROM tickets t
      WHERE $where
    ";
    $st = $db->prepare($sql);
    $st->execute($params);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
      'total_visible' => (int)($row['total_visible'] ?? 0),
      'sin_abrir'     => (int)($row['sin_abrir'] ?? 0),
      'abiertas'      => (int)($row['abiertas'] ?? 0),
      'en_proceso'    => (int)($row['en_proceso'] ?? 0),
      'resueltas'     => (int)($row['resueltas'] ?? 0),
      'cerradas'      => (int)($row['cerradas'] ?? 0),
      'sin_resolver'  => (int)($row['sin_resolver'] ?? 0),
    ];
  }

  public static function getDashboardTrend(int $userId, bool $isAdmin, array $agentDeptIds = [], int $days = 7, ?string $role = null): array {
    $db = SPDO::singleton();
    $days = ($days === 30) ? 30 : 7;
    $role = self::resolveRole($userId, $role);
    [$where, $params] = self::buildVisibilityWhere('t', $userId, $isAdmin, self::getEffectiveDeptIds($userId, $isAdmin, $agentDeptIds, $role), $role);

    $sql = "
      SELECT DATE(t.created_at) AS day_key, COUNT(*) AS total
      FROM tickets t
      WHERE $where
        AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
      GROUP BY DATE(t.created_at)
      ORDER BY day_key ASC
    ";
    $st = $db->prepare($sql);
    $st->execute(array_merge($params, [$days - 1]));

    $counts = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $counts[$row['day_key']] = (int)$row['total'];
    }

    $weekdayMap = [
      1 => 'LUN',
      2 => 'MAR',
      3 => 'MIE',
      4 => 'JUE',
      5 => 'VIE',
      6 => 'SAB',
      7 => 'DOM',
    ];

    $series = [];
    $startTs = strtotime('-' . ($days - 1) . ' days');
    for ($i = 0; $i < $days; $i++) {
      $ts = strtotime('+' . $i . ' days', $startTs);
      $key = date('Y-m-d', $ts);
      $series[] = [
        'date'      => $key,
        'label'     => $days === 7 ? $weekdayMap[(int)date('N', $ts)] : date('d/m', $ts),
        'shortLabel'=> $days === 7 ? $weekdayMap[(int)date('N', $ts)] : date('d', $ts),
        'count'     => $counts[$key] ?? 0,
      ];
    }

    return $series;
  }

  public static function getDashboardPriorityBreakdown(int $userId, bool $isAdmin, array $agentDeptIds = [], ?string $role = null): array {
    $db = SPDO::singleton();
    $role = self::resolveRole($userId, $role);
    [$where, $params] = self::buildVisibilityWhere('t', $userId, $isAdmin, self::getEffectiveDeptIds($userId, $isAdmin, $agentDeptIds, $role), $role);

    $sql = "
      SELECT t.prioridad, COUNT(*) AS total
      FROM tickets t
      WHERE $where
        AND t.estado IN ('sin_abrir','abierta','en_proceso')
      GROUP BY t.prioridad
    ";
    $st = $db->prepare($sql);
    $st->execute($params);

    $defaults = [
      'critica' => ['key' => 'critica', 'label' => 'Crítica', 'count' => 0, 'pct' => 0.0],
      'alta'    => ['key' => 'alta',    'label' => 'Alta',    'count' => 0, 'pct' => 0.0],
      'media'   => ['key' => 'media',   'label' => 'Media',   'count' => 0, 'pct' => 0.0],
      'baja'    => ['key' => 'baja',    'label' => 'Baja',    'count' => 0, 'pct' => 0.0],
    ];

    $activeTotal = 0;
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $key = $row['prioridad'];
      if (!isset($defaults[$key])) {
        continue;
      }
      $count = (int)$row['total'];
      $defaults[$key]['count'] = $count;
      $activeTotal += $count;
    }

    foreach ($defaults as $key => $item) {
      $defaults[$key]['pct'] = $activeTotal > 0 ? round(($item['count'] / $activeTotal) * 100, 1) : 0.0;
    }

    return array_values($defaults);
  }

  public static function getDashboardTopCategories(int $userId, bool $isAdmin, array $agentDeptIds = [], int $limit = 4, ?string $role = null): array {
    $db = SPDO::singleton();
    $role = self::resolveRole($userId, $role);
    [$where, $params] = self::buildVisibilityWhere('t', $userId, $isAdmin, self::getEffectiveDeptIds($userId, $isAdmin, $agentDeptIds, $role), $role);
    $totalVisible = self::countVisibleTickets($userId, $isAdmin, $agentDeptIds, $role);

    $sql = "
      SELECT
        COALESCE(c.id, 0) AS categoria_id,
        COALESCE(c.nombre, 'Sin categoría') AS categoria_nombre,
        COALESCE(c.color, '#94a3b8') AS categoria_color,
        COUNT(*) AS total
      FROM tickets t
      LEFT JOIN categorias c ON c.id = t.categoria_id
      WHERE $where
      GROUP BY COALESCE(c.id, 0), COALESCE(c.nombre, 'Sin categoría'), COALESCE(c.color, '#94a3b8')
      ORDER BY total DESC, categoria_nombre ASC
      LIMIT " . (int)$limit;

    $st = $db->prepare($sql);
    $st->execute($params);

    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $count = (int)$row['total'];
      $rows[] = [
        'id'    => (int)$row['categoria_id'],
        'nombre'=> $row['categoria_nombre'],
        'color' => $row['categoria_color'],
        'count' => $count,
        'pct'   => $totalVisible > 0 ? round(($count / $totalVisible) * 100, 1) : 0.0,
      ];
    }

    return $rows;
  }

  public static function getDashboardRecentActivity(int $userId, bool $isAdmin, array $agentDeptIds = [], int $limit = 8, ?string $role = null): array {
    $db = SPDO::singleton();
    $role = self::resolveRole($userId, $role);
    [$where, $params] = self::buildVisibilityWhere('t', $userId, $isAdmin, self::getEffectiveDeptIds($userId, $isAdmin, $agentDeptIds, $role), $role);
    $eventWhere = " AND (tc.event_type IS NULL OR tc.event_type IN ('state_change','priority_change','assignment'))";
    if (!$isAdmin) {
      $eventWhere .= " AND tc.is_internal = 0";
    }

    $sql = "
      SELECT
        tc.id,
        tc.ticket_id,
        tc.user_id,
        tc.contenido,
        tc.is_internal,
        tc.event_type,
        tc.created_at,
        t.titulo,
        u.nombre AS actor_nombre,
        u.avatar_path AS actor_avatar
      FROM ticket_comments tc
      JOIN tickets t ON t.id = tc.ticket_id
      LEFT JOIN users u ON u.id = tc.user_id
      WHERE $where
        $eventWhere
      ORDER BY tc.created_at DESC, tc.id DESC
      LIMIT " . (int)$limit;

    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function listForUserFiltered(int $userId, bool $isAdmin, array $filters, int $limit, int $offset, ?string $role = null): array {
    $db = SPDO::singleton();
    [$where, $params] = self::buildFilterWhere($userId, $isAdmin, $filters, $role);
    $order = ($filters['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $sql =
      "SELECT t.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
              u1.nombre AS creado_por_nombre, u2.nombre AS asignado_a_nombre
       FROM tickets t
       LEFT JOIN categorias c ON c.id = t.categoria_id
       LEFT JOIN users u1 ON u1.id = t.creado_por
       LEFT JOIN users u2 ON u2.id = t.asignado_a
       WHERE $where
       ORDER BY t.created_at $order
       LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function countForUserFiltered(int $userId, bool $isAdmin, array $filters, ?string $role = null): int {
    $db = SPDO::singleton();
    [$where, $params] = self::buildFilterWhere($userId, $isAdmin, $filters, $role);
    $st = $db->prepare("SELECT COUNT(*) FROM tickets t WHERE $where");
    $st->execute($params);
    return (int)$st->fetchColumn();
  }

  public static function update(int $id, array $fields): bool {
    if (empty($fields)) return false;
    $db      = SPDO::singleton();
    $allowed = ['estado', 'prioridad', 'asignado_a', 'titulo', 'descripcion', 'categoria_id', 'due_date', 'deleted_at', 'cliente_email', 'cliente_user_id'];
    $set     = [];
    $params  = [];
    foreach ($fields as $key => $value) {
      if (in_array($key, $allowed, true)) {
        $set[]    = "$key = ?";
        $params[] = $value;
      }
    }
    if (empty($set)) return false;
    $params[] = $id;
    $st = $db->prepare("UPDATE tickets SET " . implode(', ', $set) . " WHERE id = ? AND deleted_at IS NULL");
    return $st->execute($params);
  }

  public static function getUserNameById(int $id): string {
    if ($id <= 0) return '';
    $db = SPDO::singleton();
    $st = $db->prepare("SELECT nombre FROM users WHERE id = ?");
    $st->execute([$id]);
    return (string)($st->fetchColumn() ?: '');
  }

  public static function softDelete(int $id): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE tickets SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
    return $st->execute([$id]);
  }

  public static function listByStatus(int $userId, bool $isAdmin, ?string $role = null): array {
    $tickets = self::listForUser($userId, $isAdmin, $role);
    $grouped = [];
    foreach ($tickets as $ticket) {
      $grouped[$ticket['estado']][] = $ticket;
    }
    return $grouped;
  }

  public static function listRecentActivityForUser(int $userId, int $limit = 8): array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT tc.event_type, tc.contenido, tc.created_at, tc.is_internal,
              t.id AS ticket_id, t.titulo
       FROM ticket_comments tc
       JOIN tickets t ON t.id = tc.ticket_id
       WHERE tc.user_id = ? AND t.deleted_at IS NULL
       ORDER BY tc.created_at DESC
       LIMIT " . (int)$limit
    );
    $st->execute([$userId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function listRecentForUser(int $userId, bool $isAdmin, int $limit = 6, ?string $role = null): array {
    $db = SPDO::singleton();
    $role = self::resolveRole($userId, $role);
    [$where, $params] = self::buildVisibilityWhere('t', $userId, $isAdmin, self::getEffectiveDeptIds($userId, $isAdmin, null, $role), $role);

    $sql =
      "SELECT t.*, u2.nombre AS asignado_a_nombre
       FROM tickets t
       LEFT JOIN users u2 ON u2.id = t.asignado_a
       WHERE $where
       ORDER BY t.id DESC
       LIMIT " . (int)$limit;

    $st  = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function buildVisibilityWhere(string $alias, int $userId, bool $isAdmin, array $agentDeptIds = [], ?string $role = null): array {
    $prefix = trim($alias) !== '' ? trim($alias) . '.' : '';
    $where  = $prefix . "deleted_at IS NULL";
    $params = [];

    if (!$isAdmin) {
      $role = self::resolveRole($userId, $role);
      if ($role === 'cliente') {
        $where   .= " AND {$prefix}cliente_user_id = ?";
        $params[] = $userId;
        return [$where, $params];
      }

      $deptIds = self::normalizeDeptIds($agentDeptIds);
      if (!empty($deptIds)) {
        $placeholders = implode(',', array_fill(0, count($deptIds), '?'));
        $where   .= " AND ({$prefix}creado_por = ? OR {$prefix}asignado_a = ? OR {$prefix}departamento_id IN ($placeholders))";
        $params[] = $userId;
        $params[] = $userId;
        $params   = array_merge($params, $deptIds);
      } else {
        $where   .= " AND ({$prefix}creado_por = ? OR {$prefix}asignado_a = ?)";
        $params[] = $userId;
        $params[] = $userId;
      }
    }

    return [$where, $params];
  }

  private static function buildFilterWhere(int $userId, bool $isAdmin, array $filters, ?string $role = null): array {
    $role = self::resolveRole($userId, $role);
    if (!empty($filters['solo_mis_tickets'])) {
      $where  = "t.deleted_at IS NULL AND t.asignado_a = ?";
      $params = [$userId];
    } else {
      $deptIds = self::getEffectiveDeptIds($userId, $isAdmin, $filters['agent_dept_ids'] ?? [], $role);
      [$where, $params] = self::buildVisibilityWhere('t', $userId, $isAdmin, $deptIds, $role);
    }

    if ($isAdmin && !empty($filters['departamento_id'])) {
      $where   .= " AND t.departamento_id = ?";
      $params[] = (int)$filters['departamento_id'];
    }
    if (!empty($filters['estado'])) {
      $where   .= " AND t.estado = ?";
      $params[] = $filters['estado'];
    }
    if (!empty($filters['prioridad'])) {
      $where   .= " AND t.prioridad = ?";
      $params[] = $filters['prioridad'];
    }
    if (!empty($filters['categoria_id'])) {
      $where   .= " AND t.categoria_id = ?";
      $params[] = (int)$filters['categoria_id'];
    }
    if (!empty($filters['q'])) {
      $where   .= " AND (t.titulo LIKE ? OR t.descripcion LIKE ? OR t.cliente_email LIKE ?)";
      $params[] = '%' . $filters['q'] . '%';
      $params[] = '%' . $filters['q'] . '%';
      $params[] = '%' . $filters['q'] . '%';
    }

    return [$where, $params];
  }

  private static function countVisibleTickets(int $userId, bool $isAdmin, array $agentDeptIds = [], ?string $role = null): int {
    $db = SPDO::singleton();
    $role = self::resolveRole($userId, $role);
    [$where, $params] = self::buildVisibilityWhere('t', $userId, $isAdmin, self::getEffectiveDeptIds($userId, $isAdmin, $agentDeptIds, $role), $role);
    $st = $db->prepare("SELECT COUNT(*) FROM tickets t WHERE $where");
    $st->execute($params);
    return (int)$st->fetchColumn();
  }

  private static function getEffectiveDeptIds(int $userId, bool $isAdmin, ?array $agentDeptIds = null, ?string $role = null): array {
    if ($isAdmin) {
      return [];
    }
    $role = self::resolveRole($userId, $role);
    if ($role === 'cliente') {
      return [];
    }
    if ($agentDeptIds !== null && $agentDeptIds !== []) {
      return self::normalizeDeptIds($agentDeptIds);
    }
    return self::fetchDeptIdsForUser($userId);
  }

  private static function resolveRole(int $userId, ?string $role = null): string {
    if ($role !== null && $role !== '') {
      return $role;
    }
    if (!class_exists('UsuarioModel')) {
      return 'user';
    }
    $row = UsuarioModel::findRolById($userId);
    return $row['rol'] ?? 'user';
  }

  private static function fetchDeptIdsForUser(int $userId): array {
    return self::normalizeDeptIds(array_column(UserDepartamentoModel::getAllForUser($userId), 'id'));
  }

  private static function normalizeDeptIds(array $deptIds): array {
    $deptIds = array_values(array_unique(array_filter(array_map('intval', $deptIds), fn($id) => $id > 0)));
    sort($deptIds);
    return $deptIds;
  }
}

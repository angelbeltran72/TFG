<?php
class UserPermissionModel {

  // Permisos base por rol (true = concedido por defecto). Las filas de user_permissions los sobreescriben.
  private static array $defaults = [
    'admin' => [
      'crear_tickets'             => true,
      'ver_tickets_departamento'  => true,
      'comentar_tickets'          => true,
      'ver_todos_tickets'         => true,
      'cambiar_estado_ajenos'     => true,
      'reasignar_tickets'         => true,
      'cerrar_tickets_ajenos'     => true,
      'crear_en_nombre_de'        => true,
      'acceso_kanban'             => true,
      'acceso_configuracion'      => true,
    ],
    'user' => [
      'crear_tickets'             => true,
      'ver_tickets_departamento'  => false,
      'comentar_tickets'          => true,
      'ver_todos_tickets'         => false,
      'cambiar_estado_ajenos'     => false,
      'reasignar_tickets'         => false,
      'cerrar_tickets_ajenos'     => false,
      'crear_en_nombre_de'        => false,
      'acceso_kanban'             => true,
      'acceso_configuracion'      => false,
    ],
    'cliente' => [
      'crear_tickets'             => false,
      'ver_tickets_departamento'  => false,
      'comentar_tickets'          => false,
      'ver_todos_tickets'         => false,
      'cambiar_estado_ajenos'     => false,
      'reasignar_tickets'         => false,
      'cerrar_tickets_ajenos'     => false,
      'crear_en_nombre_de'        => false,
      'acceso_kanban'             => false,
      'acceso_configuracion'      => false,
    ],
  ];

  // Obtiene todos los overrides de un usuario como [permiso => concedido]
  public static function getForUser(int $userId): array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT permission, granted FROM user_permissions WHERE user_id = ?"
    );
    $st->execute([$userId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out  = [];
    foreach ($rows as $r) {
      $out[$r['permission']] = (bool)$r['granted'];
    }
    return $out;
  }

  // Resuelve el permiso final: el override tiene prioridad; si no hay override, se usa el rol.
  public static function check(int $userId, string $permission, string $role): bool {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT granted FROM user_permissions WHERE user_id = ? AND permission = ? LIMIT 1"
    );
    $st->execute([$userId, $permission]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if ($row !== false) {
      return (bool)$row['granted'];
    }

    return self::$defaults[$role][$permission] ?? false;
  }

  // Inserta o actualiza un override de permiso
  public static function set(int $userId, string $permission, bool $granted, ?int $grantedBy): bool {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO user_permissions (user_id, permission, granted, granted_by)
       VALUES (?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE granted = VALUES(granted), granted_by = VALUES(granted_by)"
    );
    return $st->execute([$userId, $permission, (int)$granted, $grantedBy]);
  }

  // Elimina todos los overrides del usuario (resetea a los permisos del rol)
  public static function resetForUser(int $userId): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
    return $st->execute([$userId]);
  }

  // Retorna los permisos predeterminados de un rol
  public static function getDefaults(string $role): array {
    return self::$defaults[$role] ?? self::$defaults['user'];
  }
}

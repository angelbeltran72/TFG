<?php
class SystemSettingModel {

  // Obtiene el valor de una clave de configuración (null si no existe)
  public static function get(string $key): ?string {
    $db = SPDO::singleton();
    $st = $db->prepare("SELECT value FROM system_settings WHERE setting_key = ? LIMIT 1");
    $st->execute([$key]);
    $val = $st->fetchColumn();
    return ($val !== false) ? (string)$val : null;
  }

  // Retorna todos los ajustes como [clave => ['value' => ..., 'type' => ...]]
  public static function getAll(): array {
    $db = SPDO::singleton();
    $st = $db->query("SELECT setting_key, value, type FROM system_settings ORDER BY setting_key");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out  = [];
    foreach ($rows as $r) {
      $out[$r['setting_key']] = [
        'value' => $r['value'],
        'type'  => $r['type'],
      ];
    }
    return $out;
  }

  // Actualiza un ajuste; retorna false si la clave no existe
  public static function set(string $key, string $value, int $updatedBy): bool {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "UPDATE system_settings SET value = ?, updated_by = ? WHERE setting_key = ?"
    );
    $st->execute([$value, $updatedBy, $key]);
    return $st->rowCount() > 0;
  }

  // Obtiene un ajuste como booleano
  public static function getBool(string $key, bool $default = false): bool {
    $val = self::get($key);
    if ($val === null) return $default;
    return $val === '1' || $val === 'true';
  }

  // Obtiene un ajuste como entero
  public static function getInt(string $key, int $default = 0): int {
    $val = self::get($key);
    return ($val !== null) ? (int)$val : $default;
  }
}

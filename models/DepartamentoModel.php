<?php
class DepartamentoModel {

  public static function getAll(): array {
    $db = SPDO::singleton();
    $st = $db->query(
      "SELECT id, nombre, descripcion, color, is_active, created_at
       FROM departamentos ORDER BY nombre"
    );
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getAllActive(): array {
    $db = SPDO::singleton();
    $st = $db->query(
      "SELECT id, nombre, descripcion, color FROM departamentos
       WHERE is_active = 1 ORDER BY nombre"
    );
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

/** Devuelve los departamentos con el número de miembros activos */
  public static function getAllWithMemberCount(): array {
    $db = SPDO::singleton();
    $st = $db->query(
      "SELECT d.id, d.nombre, d.descripcion, d.color, d.is_active,
              COUNT(ud.user_id) AS member_count
       FROM departamentos d
       LEFT JOIN user_departamentos ud ON ud.departamento_id = d.id
       GROUP BY d.id
       ORDER BY d.nombre"
    );
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function findById(int $id): ?array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id, nombre, descripcion, color, is_active FROM departamentos WHERE id = ?"
    );
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function create(string $nombre, ?string $descripcion, string $color = '#4648d4'): int {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO departamentos (nombre, descripcion, color) VALUES (?, ?, ?)"
    );
    $st->execute([$nombre, $descripcion, $color]);
    return (int)$db->lastInsertId();
  }

  public static function update(int $id, string $nombre, ?string $descripcion, string $color, int $isActive): bool {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "UPDATE departamentos SET nombre = ?, descripcion = ?, color = ?, is_active = ? WHERE id = ?"
    );
    return $st->execute([$nombre, $descripcion, $color, $isActive, $id]);
  }

  public static function setActive(int $id, bool $active): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE departamentos SET is_active = ? WHERE id = ?");
    return $st->execute([(int)$active, $id]);
  }

  public static function delete(int $id): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("DELETE FROM departamentos WHERE id = ?");
    return $st->execute([$id]);
  }
}

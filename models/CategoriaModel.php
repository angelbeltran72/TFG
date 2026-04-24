<?php
class CategoriaModel {

  public static function listAll(): array {
    $db = SPDO::singleton();
    $st = $db->query(
      "SELECT id, nombre, descripcion, color, is_active, departamento_id, created_at
       FROM categorias ORDER BY nombre"
    );
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function findById(int $id): ?array {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "SELECT id, nombre, descripcion, color, is_active, departamento_id FROM categorias WHERE id = ?"
    );
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function create(string $nombre, string $color, ?string $descripcion, ?int $departamentoId): int {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "INSERT INTO categorias (nombre, color, descripcion, departamento_id) VALUES (?, ?, ?, ?)"
    );
    $st->execute([$nombre, $color, $descripcion, $departamentoId]);
    return (int)$db->lastInsertId();
  }

  public static function update(int $id, string $nombre, string $color, ?string $descripcion, ?int $departamentoId): bool {
    $db = SPDO::singleton();
    $st = $db->prepare(
      "UPDATE categorias SET nombre = ?, color = ?, descripcion = ?, departamento_id = ? WHERE id = ?"
    );
    return $st->execute([$nombre, $color, $descripcion, $departamentoId, $id]);
  }

  public static function toggle(int $id): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE categorias SET is_active = 1 - is_active WHERE id = ?");
    return $st->execute([$id]);
  }

  public static function listAllWithCounts(): array {
    $db = SPDO::singleton();
    $st = $db->query(
      "SELECT c.id, c.nombre, c.descripcion, c.color, c.is_active, c.departamento_id,
              COUNT(t.id) AS ticket_count
       FROM categorias c
       LEFT JOIN tickets t ON t.categoria_id = c.id
       GROUP BY c.id
       ORDER BY c.nombre"
    );
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  // FK fk_tickets_categoria no tiene ON DELETE: primero se eliminan los tickets, sus hijos se eliminan en cascada.
  public static function delete(int $id): bool {
    $db = SPDO::singleton();
    $db->prepare("DELETE FROM tickets WHERE categoria_id = ?")->execute([$id]);
    $st = $db->prepare("DELETE FROM categorias WHERE id = ?");
    return $st->execute([$id]);
  }
}

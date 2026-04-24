<?php
class EtiquetaModel {

  public static function listAll(): array {
    $db = SPDO::singleton();
    $st = $db->query("SELECT id, nombre, color, created_at FROM etiquetas ORDER BY nombre");
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function create(string $nombre, string $color): int {
    $db = SPDO::singleton();
    $st = $db->prepare("INSERT INTO etiquetas (nombre, color) VALUES (?, ?)");
    $st->execute([$nombre, $color]);
    return (int)$db->lastInsertId();
  }

  public static function update(int $id, string $nombre, string $color): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE etiquetas SET nombre = ?, color = ? WHERE id = ?");
    return $st->execute([$nombre, $color, $id]);
  }

  public static function delete(int $id): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("DELETE FROM etiquetas WHERE id = ?");
    return $st->execute([$id]);
  }
}

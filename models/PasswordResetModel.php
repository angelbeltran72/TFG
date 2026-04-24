<?php
class PasswordResetModel {

  public static function create($user_id, $token_hash, $expires_at) {
    $db = SPDO::singleton();
    $st = $db->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)");
    $st->execute([$user_id, $token_hash, $expires_at]);
  }

  public static function findValidByTokenHash($token_hash) {
    $db = SPDO::singleton();
    $st = $db->prepare("
      SELECT * FROM password_resets
      WHERE token_hash=? AND used=0 AND expires_at > NOW()
      ORDER BY id DESC
      LIMIT 1
    ");
    $st->execute([$token_hash]);
    return $st->fetch(PDO::FETCH_ASSOC);
  }

  // Comprobar si ya existe un token activo para no reenviar
  public static function hasActiveToken(int $user_id): bool {
    $db = SPDO::singleton();
    $st = $db->prepare("
      SELECT 1 FROM password_resets
      WHERE user_id=? AND used=0 AND expires_at > NOW()
      LIMIT 1
    ");
    $st->execute([$user_id]);
    return (bool)$st->fetchColumn();
  }

  // Eliminar tokens anteriores del usuario antes de crear uno nuevo
  public static function deleteByUser(int $user_id): void {
    $db = SPDO::singleton();
    $st = $db->prepare("DELETE FROM password_resets WHERE user_id=?");
    $st->execute([$user_id]);
  }

  public static function markUsed($id) {
    $db = SPDO::singleton();
    $st = $db->prepare("UPDATE password_resets SET used=1 WHERE id=?");
    $st->execute([$id]);
  }
}

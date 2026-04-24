<?php
class Csrf {

  public static function generate(): string {
    if (empty($_SESSION["csrf_token"])) {
      $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
  }

  public static function validate(string $token): bool {
    if (empty($token) || empty($_SESSION["csrf_token"])) {
      return false;
    }
    return hash_equals($_SESSION["csrf_token"], $token);
  }

  public static function field(): string {
    return '<input type="hidden" name="csrf_token" value="'
      . htmlspecialchars(self::generate(), ENT_QUOTES, "UTF-8") . '">';
  }

  public static function regenerate(): void {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
  }
}

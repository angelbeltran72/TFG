<?php
class SPDO {
  private static $instance = null;

  public static function singleton() {
    if (self::$instance === null) {
      $c = Config::db();
      $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['dbname']};charset={$c['charset']}";
      self::$instance = new PDO($dsn, $c["user"], $c["pass"], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
      ]);
    }
    return self::$instance;
  }
}

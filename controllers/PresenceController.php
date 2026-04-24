<?php
// Controlador de presencia — presence.js hace ping periódico a verEstado para registrar actividad
class PresenceController extends AppController {

  private function requireLogin(): array {
    if (!isset($_SESSION["usuario"])) {
      http_response_code(401);
      echo "unauthorized";
      exit;
    }
    return $_SESSION["usuario"];
  }

  // Actualiza last_seen_at del usuario y devuelve timestamp actual en JSON
  public function verEstado() {
    $u = $this->requireLogin();
    UsuarioModel::touchLastSeen((int)$u["id"]);

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["ok" => true, "ts" => date("Y-m-d H:i:s")]);
    exit;
  }
}

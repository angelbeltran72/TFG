<?php
/**
 * Controlador API para presencia y último visto.
 */
class ApiPresenceController extends ApiController {
  /**
   * Marca al usuario como activo y devuelve un timestamp.
   */
  public function ping(): void {
    $user = $this->requireApiAuth();
    UsuarioModel::touchLastSeen((int)$user["id"]);
    $this->jsonResponse(["ok" => true, "ts" => date("Y-m-d H:i:s")], 200);
  }
}

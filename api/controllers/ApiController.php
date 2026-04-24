<?php
// Clase base para controladores API: utilidades de JSON, autenticación Bearer y cabeceras.
class ApiController {
  public function __construct() {
    // Todas las respuestas usan Content-Type JSON.
    header("Content-Type: application/json; charset=utf-8");
  }

  // Lee el body JSON de la petición; retorna array vacío si no es JSON válido.
  protected function getJsonInput(): array {
    $body = file_get_contents("php://input");
    $data = json_decode($body, true);
    return is_array($data) ? $data : [];
  }

  // Envía respuesta JSON con el código HTTP indicado.
  protected function jsonResponse($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Envía error JSON con código HTTP y detalles de validación opcionales.
  protected function jsonError(string $message, int $status = 400, ?array $errors = null): void {
    $payload = ["error" => $message];
    if ($errors !== null) {
      $payload["errors"] = $errors;
    }
    $this->jsonResponse($payload, $status);
  }

  // Obtiene la cabecera Authorization del servidor o de getallheaders().
  protected function getAuthorizationHeader(): string {
    if (!empty($_SERVER["HTTP_AUTHORIZATION"])) {
      return trim($_SERVER["HTTP_AUTHORIZATION"]);
    }

    if (!empty($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
      return trim($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]);
    }

    if (function_exists("getallheaders")) {
      $headers = getallheaders();
      if (!empty($headers["Authorization"])) {
        return trim($headers["Authorization"]);
      }
      if (!empty($headers["authorization"])) {
        return trim($headers["authorization"]);
      }
    }

    return "";
  }

  // Extrae el token Bearer de la cabecera Authorization.
  protected function getBearerToken(): ?string {
    $header = $this->getAuthorizationHeader();
    if ($header === "") {
      return null;
    }

    if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
      return trim($matches[1]);
    }

    return null;
  }

  // Valida el token API Bearer y retorna el usuario asociado.
  protected function requireApiAuth(): array {
    $token = $this->getBearerToken();
    if ($token === null) {
      $this->jsonError("Autenticación requerida", 401);
    }

    $hash = hash("sha256", $token);
    $user = UsuarioModel::findByApiTokenHash($hash);
    if (!$user || !(int)$user["is_active"]) {
      $this->jsonError("Token inválido o expirado", 401);
    }

    return $user;
  }
}

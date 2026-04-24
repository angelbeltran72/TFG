<?php
/**
 * Controlador para endpoints de autenticación API.
 */
class ApiAuthController extends ApiController {
  /**
   * Login API: genera y devuelve un token Bearer.
   */
  public function login(): void {
    $input = $this->getJsonInput();
    $email = trim($input["email"] ?? "");
    $password = $input["password"] ?? "";

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $this->jsonError("Email inválido", 422, ["email" => "Email inválido"]);
    }

    if ($password === "") {
      $this->jsonError("La contraseña es obligatoria", 422, ["password" => "La contraseña es obligatoria"]);
    }

    $user = UsuarioModel::findByEmail($email);
    if (!$user || !password_verify($password, $user["password_hash"])) {
      $this->jsonError("Email o contraseña incorrectos", 401);
    }

    if (!(int)$user["is_active"]) {
      $this->jsonError("Usuario desactivado", 403);
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash("sha256", $token);
    UsuarioModel::saveApiToken((int)$user["id"], $hash);

    $this->jsonResponse([
      "access_token" => $token,
      "token_type" => "Bearer",
      "user" => [
        "id" => (int)$user["id"],
        "nombre" => $user["nombre"],
        "email" => $user["email"],
        "rol" => $user["rol"],
      ],
    ], 200);
  }

  /**
   * Logout API: revoca el token activo.
   */
  public function logout(): void {
    $user = $this->requireApiAuth();
    UsuarioModel::revokeApiToken((int)$user["id"]);
    $this->jsonResponse(["message" => "Sesión cerrada correctamente"], 200);
  }

  /**
   * Devuelve los datos básicos del usuario autenticado.
   */
  public function me(): void {
    $user = $this->requireApiAuth();
    $this->jsonResponse([
      "id" => (int)$user["id"],
      "nombre" => $user["nombre"],
      "email" => $user["email"],
      "rol" => $user["rol"],
    ], 200);
  }
}

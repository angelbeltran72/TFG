<?php
/**
 * Controlador API para usuarios y datos de perfil.
 */
class ApiUserController extends ApiController {
  /**
   * Lista todos los usuarios. Solo admins pueden acceder aquí.
   */
  public function index(): void {
    $user = $this->requireApiAuth();
    if (($user["rol"] ?? "user") !== "admin") {
      $this->jsonError("Acceso restringido", 403);
    }

    $q = trim($_GET["q"] ?? "");
    $users = $q === "" ? UsuarioModel::listAll() : UsuarioModel::searchAll($q);
    $this->jsonResponse(["users" => $users], 200);
  }

  /**
   * Devuelve datos públicos de un usuario por su ID.
   */
  public function show(int $id): void {
    $this->requireApiAuth();

    if ($id <= 0) {
      $this->jsonError("ID inválido", 400);
    }

    $u = UsuarioModel::findPublicById($id);
    if (!$u) {
      $this->jsonError("Usuario no encontrado", 404);
    }

    $stats = TicketModel::getUserStats((int)$u["id"]);
    $this->jsonResponse(["user" => $u, "stats" => $stats], 200);
  }

  /**
   * Devuelve los datos del usuario autenticado.
   */
  public function profile(): void {
    $user = $this->requireApiAuth();
    $this->jsonResponse([
      "id" => (int)$user["id"],
      "nombre" => $user["nombre"],
      "email" => $user["email"],
      "rol" => $user["rol"],
    ], 200);
  }

  /**
   * Devuelve estadísticas del perfil y los tickets recientes.
   */
  public function profileStats(): void {
    $user = $this->requireApiAuth();
    $stats = TicketModel::getUserStats((int)$user["id"]);
    $recent = TicketModel::listRecentForUser((int)$user["id"], (($user["rol"] ?? "user") === "admin"), 6);
    $this->jsonResponse(["stats" => $stats, "recent" => $recent], 200);
  }

  public function update(int $id): void {
    $caller = $this->requireApiAuth();

    $isSelf  = ((int)$caller["id"] === $id);
    $isAdmin = (($caller["rol"] ?? "user") === "admin");

    if (!$isSelf && !$isAdmin) {
      $this->jsonError("Sin permisos para editar este usuario", 403);
    }

    $existing = UsuarioModel::findById($id);
    if (!$existing) {
      $this->jsonError("Usuario no encontrado", 404);
    }

    $body   = json_decode(file_get_contents("php://input"), true) ?? [];
    $nombre = isset($body["nombre"]) ? trim($body["nombre"]) : null;
    $email  = isset($body["email"])  ? trim($body["email"])  : null;
    $rol    = isset($body["rol"])    ? trim($body["rol"])    : null;

    if ($nombre !== null && mb_strlen($nombre) < 2) {
      $this->jsonError("El nombre debe tener al menos 2 caracteres", 422);
    }
    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $this->jsonError("Email inválido", 422);
    }
    if ($email !== null && $email !== $existing["email"] && UsuarioModel::existsByEmail($email)) {
      $this->jsonError("Email ya en uso", 422);
    }
    if ($rol !== null && !in_array($rol, ["admin", "user"], true)) {
      $this->jsonError("Rol inválido", 422);
    }

    $newNombre = $nombre ?? $existing["nombre"];
    $newEmail  = $email  ?? $existing["email"];
    UsuarioModel::updateProfile($id, $newNombre, $newEmail);

    if ($rol !== null && $isAdmin) {
      UsuarioModel::updateRol($id, $rol);
    }

    $this->jsonResponse(["message" => "Usuario actualizado"], 200);
  }

  public function toggleStatus(int $id): void {
    $caller = $this->requireApiAuth();
    if (($caller["rol"] ?? "user") !== "admin") {
      $this->jsonError("Acceso restringido", 403);
    }
    if ((int)$caller["id"] === $id) {
      $this->jsonError("No puedes desactivar tu propia cuenta", 422);
    }

    $existing = UsuarioModel::findById($id);
    if (!$existing) {
      $this->jsonError("Usuario no encontrado", 404);
    }

    $newActive = (int)$existing["is_active"] === 1 ? 0 : 1;
    UsuarioModel::updateActive($id, $newActive);
    $this->jsonResponse(["is_active" => $newActive], 200);
  }
}

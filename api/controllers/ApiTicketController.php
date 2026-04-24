<?php
/**
 * Controlador API para recursos de tickets.
 * Añade paginación, filtros y operaciones CRUD básicas.
 */
class ApiTicketController extends ApiController {
  /**
   * Lista tickets con paginación y filtros.
   */
  public function index(): void {
    $user = $this->requireApiAuth();
    $isAdmin = (($user["rol"] ?? "user") === "admin");

    $page = max(1, (int)($_GET["page"] ?? 1));
    $perPage = min(100, max(1, (int)($_GET["per_page"] ?? 20)));
    $offset = ($page - 1) * $perPage;

    $filters = [
      "estado" => trim($_GET["estado"] ?? ""),
      "prioridad" => trim($_GET["prioridad"] ?? ""),
      "categoria_id" => $_GET["categoria_id"] ?? null,
      "q" => trim($_GET["q"] ?? ""),
    ];

    $tickets = TicketModel::listForUserFiltered((int)$user["id"], $isAdmin, $filters, $perPage, $offset);
    $total = TicketModel::countForUserFiltered((int)$user["id"], $isAdmin, $filters);

    $this->jsonResponse([
      "page" => $page,
      "per_page" => $perPage,
      "total" => $total,
      "tickets" => $tickets,
    ], 200);
  }

  /**
   * Crea un nuevo ticket.
   */
  public function store(): void {
    $user = $this->requireApiAuth();
    if (($user["rol"] ?? "") === "cliente") {
      $this->jsonError("Los clientes no pueden crear incidencias", 403);
    }
    $isAdmin = (($user["rol"] ?? "user") === "admin");
    $input = $this->getJsonInput();

    $titulo       = trim($input["titulo"] ?? "");
    $categoriaId  = (int)($input["categoria_id"] ?? 0);
    $prioridad    = trim($input["prioridad"] ?? "media");
    $descripcion  = trim($input["descripcion"] ?? "");
    $departamentoId = isset($input["departamento_id"]) && $input["departamento_id"] !== "" && $input["departamento_id"] !== null
      ? (int)$input["departamento_id"] : null;
    $asignadoA = null;

    if ($isAdmin && array_key_exists("asignado_a", $input)) {
      $raw = $input["asignado_a"];
      $asignadoA = ($raw === null || $raw === "" ? null : (int)$raw);
    }

    $errors = [];
    if ($titulo === "" || mb_strlen($titulo) < 3) {
      $errors["titulo"] = "El título es obligatorio (mín. 3 caracteres).";
    }
    if ($categoriaId <= 0) {
      $errors["categoria_id"] = "Selecciona una categoría.";
    }
    if (!in_array($prioridad, ["baja", "media", "alta", "critica"], true)) {
      $errors["prioridad"] = "Prioridad no válida.";
    }
    if ($descripcion === "" || mb_strlen($descripcion) < 10) {
      $errors["descripcion"] = "La descripción es obligatoria (mín. 10 caracteres).";
    }

    if (!empty($errors)) {
      $this->jsonError("Errores de validación", 422, $errors);
    }

    $id = TicketModel::create([
      "titulo"          => $titulo,
      "descripcion"     => $descripcion,
      "categoria_id"    => $categoriaId,
      "departamento_id" => $departamentoId,
      "prioridad"       => $prioridad,
      "creado_por"      => (int)$user["id"],
      "asignado_a"      => $asignadoA,
    ]);

    $this->jsonResponse(["ticket_id" => $id, "message" => "Incidencia creada correctamente."], 201);
  }

  /**
   * Muestra un ticket específico.
   */
  public function show(int $id): void {
    $user = $this->requireApiAuth();
    if ($id <= 0) {
      $this->jsonError("ID inválido", 400);
    }

    $isAdmin = (($user["rol"] ?? "user") === "admin");
    $ticket = TicketModel::findById($id, (int)$user["id"], $isAdmin);
    if (!$ticket) {
      $this->jsonError("Incidencia no encontrada", 404);
    }

    $this->jsonResponse(["ticket" => $ticket], 200);
  }

  /**
   * Actualiza campos editables de un ticket.
   */
  public function update(int $id): void {
    $user = $this->requireApiAuth();
    if (($user["rol"] ?? "") === "cliente") {
      $this->jsonError("Los clientes no pueden modificar incidencias", 403);
    }
    if ($id <= 0) {
      $this->jsonError("ID inválido", 400);
    }

    $isAdmin = (($user["rol"] ?? "user") === "admin");
    $ticket = TicketModel::findById($id, (int)$user["id"], $isAdmin);
    if (!$ticket) {
      $this->jsonError("Incidencia no encontrada", 404);
    }

    $input = $this->getJsonInput();
    $fields = [];

    if (array_key_exists("estado", $input)) {
      $estado = trim($input["estado"] ?? "");
      if (!in_array($estado, ["abierta", "en_proceso", "resuelta", "cerrada"], true)) {
        $this->jsonError("Estado no válido", 422, ["estado" => "Estado no válido"]);
      }
      if (!$isAdmin && !in_array($estado, ["en_proceso", "resuelta"], true)) {
        $this->jsonError("No tienes permiso para cambiar al estado solicitado", 403);
      }
      $fields["estado"] = $estado;
    }

    if (array_key_exists("asignado_a", $input)) {
      if (!$isAdmin) {
        $this->jsonError("Sólo el administrador puede reasignar incidencias", 403);
      }
      $raw = $input["asignado_a"];
      $fields["asignado_a"] = ($raw === null || $raw === "" ? null : (int)$raw);
    }

    if (empty($fields)) {
      $this->jsonError("No se recibieron campos para actualizar", 422);
    }

    $updated = TicketModel::update($id, $fields);
    if (!$updated) {
      $this->jsonError("No se pudo actualizar la incidencia", 500);
    }

    $this->jsonResponse(["message" => "Incidencia actualizada correctamente"], 200);
  }

  public function comments(int $id): void {
    $user    = $this->requireApiAuth();
    $isAdmin = (($user["rol"] ?? "user") === "admin");

    if ($id <= 0) {
      $this->jsonError("ID inválido", 400);
    }

    $ticket = TicketModel::findById($id, (int)$user["id"], $isAdmin);
    if (!$ticket) {
      $this->jsonError("Incidencia no encontrada", 404);
    }

    $comments = TicketCommentModel::getByTicket($id);
    $this->jsonResponse(["comments" => $comments], 200);
  }

  public function addComment(int $id): void {
    $user    = $this->requireApiAuth();
    if (($user["rol"] ?? "") === "cliente") {
      $this->jsonError("Los clientes no pueden añadir comentarios", 403);
    }
    $isAdmin = (($user["rol"] ?? "user") === "admin");

    if ($id <= 0) {
      $this->jsonError("ID inválido", 400);
    }

    $ticket = TicketModel::findById($id, (int)$user["id"], $isAdmin);
    if (!$ticket) {
      $this->jsonError("Incidencia no encontrada", 404);
    }

    $input      = $this->getJsonInput();
    $contenido  = trim($input["contenido"] ?? "");
    $isInternal = (bool)($input["is_internal"] ?? false);

    if ($contenido === "" || mb_strlen($contenido) < 2) {
      $this->jsonError("El comentario no puede estar vacío", 422, ["contenido" => "Mínimo 2 caracteres."]);
    }

    if ($isInternal && !$isAdmin) {
      $this->jsonError("Solo los administradores pueden añadir notas internas", 403);
    }

    $commentId = TicketCommentModel::add($id, (int)$user["id"], $contenido, $isInternal);
    $this->jsonResponse(["comment_id" => $commentId, "message" => "Comentario añadido correctamente"], 201);
  }

  public function changeStatus(int $id): void {
    $user    = $this->requireApiAuth();
    if (($user["rol"] ?? "") === "cliente") {
      $this->jsonError("Los clientes no pueden cambiar el estado de una incidencia", 403);
    }
    $isAdmin = (($user["rol"] ?? "user") === "admin");

    if ($id <= 0) {
      $this->jsonError("ID inválido", 400);
    }

    $ticket = TicketModel::findById($id, (int)$user["id"], $isAdmin);
    if (!$ticket) {
      $this->jsonError("Incidencia no encontrada", 404);
    }

    $input  = $this->getJsonInput();
    $estado = trim($input["estado"] ?? "");

    $estadosValidos = ["sin_abrir", "abierta", "en_proceso", "resuelta", "cerrada"];
    if (!in_array($estado, $estadosValidos, true)) {
      $this->jsonError("Estado no válido", 422, ["estado" => "Valores permitidos: " . implode(", ", $estadosValidos)]);
    }

    if (!$isAdmin && !in_array($estado, ["abierta", "en_proceso", "resuelta"], true)) {
      $this->jsonError("No tienes permiso para cambiar al estado solicitado", 403);
    }

    $ok = TicketModel::update($id, ["estado" => $estado]);
    if (!$ok) {
      $this->jsonError("No se pudo cambiar el estado", 500);
    }

    $this->jsonResponse(["message" => "Estado actualizado correctamente", "estado" => $estado], 200);
  }

  public function changePriority(int $id): void {
    $user    = $this->requireApiAuth();
    $isAdmin = (($user["rol"] ?? "user") === "admin");

    if ($id <= 0) {
      $this->jsonError("ID inválido", 400);
    }

    $ticket = TicketModel::findById($id, (int)$user["id"], $isAdmin);
    if (!$ticket) {
      $this->jsonError("Incidencia no encontrada", 404);
    }

    if (!$isAdmin) {
      $this->jsonError("Solo los administradores pueden cambiar la prioridad", 403);
    }

    $input     = $this->getJsonInput();
    $prioridad = trim($input["prioridad"] ?? "");

    $validas = ["baja", "media", "alta", "critica"];
    if (!in_array($prioridad, $validas, true)) {
      $this->jsonError("Prioridad no válida", 422, ["prioridad" => "Valores permitidos: " . implode(", ", $validas)]);
    }

    $ok = TicketModel::update($id, ["prioridad" => $prioridad]);
    if (!$ok) {
      $this->jsonError("No se pudo cambiar la prioridad", 500);
    }

    $this->jsonResponse(["message" => "Prioridad actualizada correctamente", "prioridad" => $prioridad], 200);
  }

  public function destroy(int $id): void {
    $user    = $this->requireApiAuth();
    $isAdmin = (($user["rol"] ?? "user") === "admin");

    if (!$isAdmin) {
      $this->jsonError("Solo los administradores pueden eliminar incidencias", 403);
    }

    if ($id <= 0) {
      $this->jsonError("ID inválido", 400);
    }

    $ticket = TicketModel::findById($id, (int)$user["id"], true);
    if (!$ticket) {
      $this->jsonError("Incidencia no encontrada", 404);
    }

    $ok = TicketModel::softDelete($id);
    if (!$ok) {
      $this->jsonError("No se pudo eliminar la incidencia", 500);
    }

    $this->jsonResponse(["message" => "Incidencia eliminada correctamente"], 200);
  }
}

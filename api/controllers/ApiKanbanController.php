<?php
class ApiKanbanController extends ApiController {

  private const ESTADOS_VALIDOS = ['sin_abrir', 'abierta', 'en_proceso', 'resuelta', 'cerrada'];

  public function index(): void {
    $user    = $this->requireApiAuth();
    $isAdmin = ($user['rol'] === 'admin');

    $grouped = TicketModel::listByStatus((int)$user['id'], $isAdmin);

    $this->jsonResponse(['kanban' => $grouped], 200);
  }

  public function move(int $id): void {
    $user    = $this->requireApiAuth();
    $isAdmin = ($user['rol'] === 'admin');

    if ($id <= 0) {
      $this->jsonError('ID inválido', 400);
    }

    $ticket = TicketModel::findById($id, (int)$user['id'], $isAdmin);
    if (!$ticket) {
      $this->jsonError('Incidencia no encontrada', 404);
    }

    $input  = $this->getJsonInput();
    $estado = trim($input['estado'] ?? '');

    if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
      $this->jsonError('Estado no válido', 422, ['estado' => 'Valores permitidos: ' . implode(', ', self::ESTADOS_VALIDOS)]);
    }

    $ok = TicketModel::update($id, ['estado' => $estado]);
    if (!$ok) {
      $this->jsonError('No se pudo actualizar el estado', 500);
    }

    $this->jsonResponse(['message' => 'Estado actualizado correctamente', 'estado' => $estado], 200);
  }
}

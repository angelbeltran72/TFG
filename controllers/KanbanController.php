<?php
// Controlador del tablero Kanban — requiere sesión activa
class KanbanController extends AppController {

  public function index() {
    if (!isset($_SESSION["usuario"])) {
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }

    $user    = $_SESSION["usuario"];
    $role    = $user["rol"] ?? "user";
    $isAdmin = ($user["rol"] === "admin");
    $canKanban = UserPermissionModel::check((int)$user["id"], "acceso_kanban", $role);

    if (!$canKanban) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "No tienes permiso para acceder al Kanban."];
      header("Location: index.php?controller=Dashboard&action=index");
      exit;
    }

    $kanban  = TicketModel::listByStatus((int)$user["id"], $isAdmin, $role);

    $this->view->show("kanbanView", [
      "user"   => $user,
      "kanban" => $kanban,
    ]);
  }
}

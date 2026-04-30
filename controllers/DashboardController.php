<?php
// Controlador del panel principal — requiere sesión activa
class DashboardController extends AppController {

  public function index() {
    if (!isset($_SESSION["usuario"])) {
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }

    $user    = $_SESSION["usuario"];
    $userId  = (int)$user["id"];
    $isAdmin = ($user["rol"] === "admin");
    $role    = $user["rol"] ?? "user";
    if ($role === "cliente") {
      header("Location: index.php?controller=Ticket&action=misTickets");
      exit;
    }

    $range = (int)($_GET["range"] ?? 7);
    if (!in_array($range, [7, 30], true)) {
      $range = 7;
    }

    $agentDeptIds = ($isAdmin || $role === "cliente")
      ? []
      : array_map("intval", array_column(UserDepartamentoModel::getAllForUser($userId), "id"));

    $summary      = TicketModel::getDashboardSummary($userId, $isAdmin, $agentDeptIds, $role);
    $trendByRange = [
      "7" => TicketModel::getDashboardTrend($userId, $isAdmin, $agentDeptIds, 7, $role),
      "30" => TicketModel::getDashboardTrend($userId, $isAdmin, $agentDeptIds, 30, $role),
    ];
    $priorities   = TicketModel::getDashboardPriorityBreakdown($userId, $isAdmin, $agentDeptIds, $role);
    $recentEvents = TicketModel::getDashboardRecentActivity($userId, $isAdmin, $agentDeptIds, 8, $role);
    $categories   = TicketModel::getDashboardTopCategories($userId, $isAdmin, $agentDeptIds, 4, $role);

    $nav = [
      "canKanban" => UserPermissionModel::check($userId, "acceso_kanban", $role),
      "canConfig" => $isAdmin && UserPermissionModel::check($userId, "acceso_configuracion", $role),
      "canUsers"  => $isAdmin,
    ];

    $this->view->show("dashboardView", [
      "user"         => $user,
      "range"        => $range,
      "summary"      => $summary,
      "trendByRange" => $trendByRange,
      "priorities"   => $priorities,
      "recentEvents" => $recentEvents,
      "categories"   => $categories,
      "nav"          => $nav,
    ]);
  }
}

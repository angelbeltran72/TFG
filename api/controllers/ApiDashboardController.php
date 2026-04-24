<?php
class ApiDashboardController extends ApiController {

  public function stats(): void {
    $user    = $this->requireApiAuth();
    $userId  = (int)$user['id'];
    $isAdmin = ($user['rol'] === 'admin');

    $rawStats = TicketModel::getUserStats($userId);
    $stats = [
      'pendientes' => (int)($rawStats['pendientes'] ?? 0),
      'en_proceso' => (int)($rawStats['en_proceso'] ?? 0),
      'resueltos'  => (int)($rawStats['resueltos']  ?? 0),
      'creados'    => (int)($rawStats['creados']     ?? 0),
    ];
    $recent  = TicketModel::listRecentForUser($userId, $isAdmin, 6);
    $unread  = NotificationModel::getUnreadCount($userId);

    $this->jsonResponse([
      'stats'   => $stats,
      'recent'  => $recent,
      'unread'  => $unread,
    ], 200);
  }
}

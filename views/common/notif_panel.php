<?php
$_nuid  = (int)($_SESSION['usuario']['id'] ?? 0);
try {
    $_notis = $_nuid ? NotificationModel::getForUser($_nuid, 20) : [];
} catch (\Throwable $e) {
    $_notis = [];
}
$_nbadge = 0;
foreach ($_notis as $_n) { if ($_n['read_at'] === null) $_nbadge++; }
?>
<div class="notif-wrapper">
  <button class="app-icon-btn" id="notif-btn" aria-label="Notificaciones" aria-expanded="false">
    <span class="material-symbols-outlined">notifications</span>
    <span class="notif-badge" id="notif-count"
          style="display:<?= $_nbadge > 0 ? 'flex' : 'none' ?>"><?= $_nbadge ?></span>
  </button>

  <div class="notif-panel" id="notif-panel" role="dialog" aria-label="Panel de notificaciones">
    <div class="notif-panel-header">
      <span class="notif-panel-title">Notificaciones</span>
      <button class="notif-mark-all" id="notif-mark-all">Marcar todo como leído</button>
    </div>

    <div class="notif-list" id="notif-list">
      <?php if (empty($_notis)): ?>
        <div class="notif-empty">No tienes notificaciones</div>
      <?php else: ?>
        <?php foreach ($_notis as $_n):
          $_unread = ($_n['read_at'] === null);
          [$_icon, $_cls] = match($_n['type']) {
            'ticket_assigned' => ['assignment_ind', 'primary'],
            'ticket_comment'  => ['comment',        'warning'],
            'ticket_status'   => ['swap_horiz',     'secondary'],
            'ticket_created'  => ['add_circle',     'primary'],
            'ticket_overdue'  => ['schedule',       'error'],
            default           => ['notifications',  'primary'],
          };
          $_url = $_n['url'] ?? (
            ($_n['resource_type'] === 'ticket' && $_n['resource_id'])
              ? '<?= BASE ?>/index.php?controller=Ticket&action=ver&id=' . (int)$_n['resource_id']
              : null
          );
          $_diff = time() - strtotime($_n['created_at']);
          $_hora = $_diff < 60
            ? 'Ahora mismo'
            : ($_diff < 3600
                ? 'Hace ' . floor($_diff / 60) . ' min'
                : ($_diff < 86400
                    ? 'Hace ' . floor($_diff / 3600) . ' h'
                    : 'Hace ' . floor($_diff / 86400) . ' d'));
        ?>
        <div class="notif-item<?= $_unread ? ' no-leido' : '' ?>"
             data-id="<?= (int)$_n['id'] ?>"
             <?= $_url ? 'data-url="' . htmlspecialchars($_url) . '"' : '' ?>
             <?= $_url ? 'style="cursor:pointer"' : '' ?>>
          <div class="notif-icon <?= $_cls ?>">
            <span class="material-symbols-outlined"><?= $_icon ?></span>
          </div>
          <div class="notif-body">
            <p class="notif-text"><?= htmlspecialchars($_n['message']) ?></p>
            <p class="notif-hora"><?= $_hora ?></p>
          </div>
          <?php if ($_unread): ?><div class="notif-punto"></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="<?= BASE ?>/assets/js/notifications.js?v=<?= filemtime(__DIR__ . '/../../assets/js/notifications.js') ?>"></script>

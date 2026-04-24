<script>window.__BASE = '<?= BASE ?>';</script>
<?php if (isset($_SESSION["usuario"])): ?>
<script src="<?= BASE ?>/assets/js/presence.js"></script>
<?php endif; ?>
<script src="<?= BASE ?>/assets/js/notifications.js"></script>
<script src="<?= BASE ?>/assets/js/theme.js"></script>
<script src="<?= BASE ?>/assets/js/sidebar.js"></script>
<script>
(() => {
  const nav = window.__NAV_PERMISSIONS;
  if (!nav || typeof nav !== "object") return;

  const removeLinks = (href) => {
    document.querySelectorAll('a[href="' + href + '"]').forEach((el) => el.remove());
  };

  if (!nav.can_kanban) {
    removeLinks("index.php?controller=Kanban&action=index");
  }
  if (!nav.can_config) {
    removeLinks("index.php?controller=Config&action=index");
  }
  if (!nav.can_users) {
    removeLinks("index.php?controller=Perfil&action=listarUsuarios");
  }
})();
</script>
<!-- AlertHub © 2025-2026 | Licensed under CC BY-SA 4.0 https://creativecommons.org/licenses/by-sa/4.0/ -->
</body>
</html>

<?php
$user     ??= $_SESSION["usuario"] ?? ["nombre" => "Usuario", "rol" => "user"];
$usuarios ??= [];
$q        ??= "";
$__pageTitle = "Usuarios";
$_cargoMap = ['admin' => 'Administrador', 'cliente' => 'Cliente'];
$cargo = $_cargoMap[$user["rol"] ?? "user"] ?? 'Agente de soporte';

function uCargo(string $rol): string {
  return match($rol) {
    'admin'   => 'Administrador',
    'cliente' => 'Cliente',
    default   => 'Agente de soporte',
  };
}
function uIsOnline(?string $lastSeen): bool {
  if (!$lastSeen) return false;
  return (time() - strtotime($lastSeen)) < 300;
}
function uInitials(string $nombre): string {
  $parts = explode(" ", trim($nombre));
  $i = strtoupper(substr($parts[0], 0, 1));
  if (isset($parts[1])) $i .= strtoupper(substr($parts[1], 0, 1));
  return $i;
}
?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/usuarios.css">

<!-- Sidebar -->
<aside class="app-sidebar">
  <div class="app-brand">
    <div class="app-brand-icon">
      <span class="material-symbols-outlined">speed</span>
    </div>
    <div>
      <h1>AlertHub</h1>
      <p>Gestor de Incidencias</p>
    </div>
  </div>

  <nav class="app-nav" aria-label="Navegación principal">
    <a href="index.php?controller=Dashboard&action=index">
      <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
      <span>Dashboard</span>
    </a>
    <a href="index.php?controller=Ticket&action=listar">
      <span class="material-symbols-outlined" aria-hidden="true">confirmation_number</span>
      <span>Tickets</span>
    </a>
    <a href="index.php?controller=Ticket&action=misTickets">
      <span class="material-symbols-outlined" aria-hidden="true">assignment_ind</span>
      <span>Mis Tickets</span>
    </a>
    <a href="index.php?controller=Kanban&action=index">
      <span class="material-symbols-outlined" aria-hidden="true">view_kanban</span>
      <span>Kanban Board</span>
    </a>
    <a href="index.php?controller=Ticket&action=nuevo">
      <span class="material-symbols-outlined" aria-hidden="true">add_circle</span>
      <span>Nueva Incidencia</span>
    </a>
    <a class="active" aria-current="page" href="index.php?controller=Perfil&action=listarUsuarios">
      <span class="material-symbols-outlined">group</span>
      <span>Usuarios</span>
    </a>
    <a href="index.php?controller=Perfil&action=verPerfil">
      <span class="material-symbols-outlined">person</span>
      <span>Mi Perfil</span>
    </a>
    <a href="index.php?controller=Config&action=index">
      <span class="material-symbols-outlined">settings</span>
      <span>Configuración</span>
    </a>
  </nav>

  <div class="app-nav-footer">
    <a href="index.php?controller=Soporte&action=index">
      <span class="material-symbols-outlined">contact_support</span>
      <span>Soporte</span>
    </a>
    <form method="POST" action="index.php?controller=Auth&action=cerrarSesion">
      <?= Csrf::field() ?>
      <button type="submit" class="app-nav-btn">
        <span class="material-symbols-outlined">logout</span>
        <span>Cerrar Sesión</span>
      </button>
    </form>
  </div>
</aside>

<!-- Main -->
<main class="app-main" id="main-content">

  <!-- TopBar -->
  <header class="app-topbar">
    <div class="app-topbar-right" style="margin-left:auto">
      <?php require_once "views/common/notif_panel.php"; ?>
      <div class="app-topbar-divider"></div>
      <a href="index.php?controller=Perfil&action=verPerfil" class="app-user">
        <div class="app-user-info">
          <span class="app-user-name"><?= htmlspecialchars($user["nombre"]) ?></span>
          <span class="app-user-role"><?= htmlspecialchars($cargo) ?></span>
        </div>
        <div class="app-avatar">
          <?php if (!empty($user["avatar_path"])): ?>
            <img src="<?= htmlspecialchars($user["avatar_path"]) ?>" alt="Avatar de <?= htmlspecialchars($user["nombre"]) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
          <?php else: ?>
            <?= strtoupper(substr($user["nombre"], 0, 1)) ?>
          <?php endif; ?>
        </div>
      </a>
    </div>
  </header>

  <!-- Content Canvas -->
  <div class="app-canvas">

    <!-- Page header -->
    <div class="ul-page-header">
      <div>
        <h2 class="ul-page-title">Equipo</h2>
        <p class="ul-page-subtitle"><?= count($usuarios) ?> usuario<?= count($usuarios) !== 1 ? "s" : "" ?> registrados</p>
      </div>

      <form method="GET" action="index.php" class="ul-search-form">
        <input type="hidden" name="controller" value="Perfil">
        <input type="hidden" name="action" value="listarUsuarios">
        <div class="ul-search">
          <span class="material-symbols-outlined">search</span>
          <input type="text" name="q" placeholder="Buscar por nombre o email..." value="<?= htmlspecialchars($q) ?>">
          <?php if ($q !== ""): ?>
            <a href="index.php?controller=Perfil&action=listarUsuarios" class="ul-search-clear" title="Limpiar">
              <span class="material-symbols-outlined">close</span>
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Grid de usuarios -->
    <?php if (empty($usuarios)): ?>
      <div class="ul-empty">
        <span class="material-symbols-outlined">group_off</span>
        <p>No se encontraron usuarios<?= $q !== "" ? " para «" . htmlspecialchars($q) . "»" : "" ?></p>
      </div>
    <?php else: ?>
      <div class="ul-grid">
        <?php foreach ($usuarios as $u): ?>
          <?php
          $online   = uIsOnline($u["last_seen_at"] ?? null);
          $initials = uInitials($u["nombre"]);
          $uCargo   = uCargo($u["rol"]);
          $uRolClass = match($u["rol"]) { 'admin' => 'admin', 'cliente' => 'cliente', default => '' };
          ?>
          <a href="index.php?controller=Perfil&action=verPerfilUsuario&id=<?= (int)$u["id"] ?>" class="ul-card">

            <div class="ul-card-top">
              <!-- Avatar -->
              <div class="ul-avatar-wrap">
                <div class="ul-avatar <?= $uRolClass ?>">
                  <?php if (!empty($u["avatar_path"])): ?>
                    <img src="<?= htmlspecialchars($u["avatar_path"]) ?>" alt="">
                  <?php else: ?>
                    <?= htmlspecialchars($initials) ?>
                  <?php endif; ?>
                </div>
                <?php if ($online): ?>
                  <span class="ul-online-dot"></span>
                <?php endif; ?>
              </div>

              <!-- Badge de rol -->
              <span class="ul-role-badge <?= $uRolClass ?>">
                <?= htmlspecialchars($uCargo) ?>
              </span>
            </div>

            <!-- Info -->
            <div class="ul-card-body">
              <p class="ul-name"><?= htmlspecialchars($u["nombre"]) ?></p>
              <p class="ul-email"><?= htmlspecialchars($u["email"]) ?></p>
            </div>

            <!-- Footer -->
            <div class="ul-card-footer">
              <?php if ($online): ?>
                <span class="ul-status online">
                  <span class="ul-status-dot"></span>
                  En línea
                </span>
              <?php elseif (!empty($u["last_seen_at"])): ?>
                <span class="ul-status offline">
                  <span class="material-symbols-outlined">schedule</span>
                  <?= htmlspecialchars(date("d M, H:i", strtotime($u["last_seen_at"]))) ?>
                </span>
              <?php else: ?>
                <span class="ul-status offline">
                  <span class="material-symbols-outlined">schedule</span>
                  Sin actividad
                </span>
              <?php endif; ?>

              <span class="ul-card-arrow">
                <span class="material-symbols-outlined">arrow_forward</span>
              </span>
            </div>

          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</main>
<?php require_once "views/common/msg_panel.php"; ?>

<?php require_once "views/common/pie.php"; ?>

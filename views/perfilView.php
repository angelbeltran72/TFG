<?php
$user           ??= $_SESSION["usuario"] ?? ["nombre" => "Usuario", "rol" => "user"];
$isAdmin        ??= false;
$stats          ??= [];
$recentActivity ??= [];
$primaryDept    ??= null;
$userDepts      ??= [];
$notifPrefs     ??= [];
$createdAt      ??= null;
$_pfRole        = $user["rol"] ?? "user";
$isCliente      = $_pfRole === 'cliente';
$_cargoMap      = ['admin' => 'Administrador', 'cliente' => 'Cliente'];
$cargo          = $_cargoMap[$_pfRole] ?? 'Agente de soporte';

$defaultNotifPrefs = ['ticket_assigned' => true, 'ticket_comment' => true, 'ticket_status' => false, 'ticket_overdue' => true];
$notifPrefs = array_merge($defaultNotifPrefs, $notifPrefs);

function pfTimeAgo(string $datetime): string {
  $diff = time() - strtotime($datetime);
  if ($diff < 60)     return 'Hace un momento';
  if ($diff < 3600)   return 'Hace ' . floor($diff / 60) . ' min';
  if ($diff < 86400)  return 'Hace ' . floor($diff / 3600) . ' h';
  if ($diff < 172800) return 'Ayer';
  return date('d M Y', strtotime($datetime));
}
?>
<?php $__pageTitle = "Mi Perfil"; ?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/perfil.css">

<!-- Formulario oculto para subir avatar -->
<form id="pf-avatar-form" method="POST" action="index.php?controller=Perfil&action=actualizarAvatar" enctype="multipart/form-data" style="display:none">
  <?= Csrf::field() ?>
  <input type="file" id="pf-file-input" name="avatar" accept="image/jpeg,image/png,image/webp">
</form>
<!-- Formulario oculto para quitar avatar -->
<form id="pf-avatar-remove-form" method="POST" action="index.php?controller=Perfil&action=quitarAvatar" style="display:none">
  <?= Csrf::field() ?>
</form>

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
    <?php if ($isCliente): ?>
    <a href="index.php?controller=Ticket&action=misTickets">
      <span class="material-symbols-outlined" aria-hidden="true">assignment_ind</span>
      <span>Mis Tickets</span>
    </a>
    <a class="active" aria-current="page" href="index.php?controller=Perfil&action=verPerfil">
      <span class="material-symbols-outlined">person</span>
      <span>Mi Perfil</span>
    </a>
    <?php else: ?>
    <a href="index.php?controller=Dashboard&action=index">
      <span class="material-symbols-outlined">dashboard</span>
      <span>Dashboard</span>
    </a>
    <a href="index.php?controller=Ticket&action=listar">
      <span class="material-symbols-outlined">confirmation_number</span>
      <span>Tickets</span>
    </a>
    <a href="index.php?controller=Ticket&action=misTickets">
      <span class="material-symbols-outlined">assignment_ind</span>
      <span>Mis Tickets</span>
    </a>
    <a href="index.php?controller=Kanban&action=index">
      <span class="material-symbols-outlined">view_kanban</span>
      <span>Kanban Board</span>
    </a>
    <a href="index.php?controller=Ticket&action=nuevo">
      <span class="material-symbols-outlined">add_circle</span>
      <span>Nueva Incidencia</span>
    </a>
    <a href="index.php?controller=Perfil&action=listarUsuarios">
      <span class="material-symbols-outlined">group</span>
      <span>Usuarios</span>
    </a>
    <a class="active" aria-current="page" href="index.php?controller=Perfil&action=verPerfil">
      <span class="material-symbols-outlined" aria-hidden="true">person</span>
      <span>Mi Perfil</span>
    </a>
    <a href="index.php?controller=Config&action=index">
      <span class="material-symbols-outlined">settings</span>
      <span>Configuración</span>
    </a>
    <?php endif; ?>
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

    <!-- Hero -->
    <div class="pf-hero">
      <div class="pf-hero-banner"></div>
      <div class="pf-hero-body">

        <!-- Avatar con upload -->
        <div class="pf-avatar-wrap">
          <div class="pf-avatar">
            <?php if (!empty($user["avatar_path"])): ?>
              <img src="<?= htmlspecialchars($user["avatar_path"]) ?>" alt="Avatar">
            <?php else: ?>
              <?= strtoupper(substr($user["nombre"], 0, 1)) ?>
            <?php endif; ?>
          </div>
          <button type="button" class="pf-avatar-upload" title="Cambiar foto">
            <span class="material-symbols-outlined">photo_camera</span>
          </button>
          <?php if (!empty($user["avatar_path"])): ?>
            <button type="button" class="pf-avatar-remove" id="pf-avatar-remove-btn" title="Quitar foto">
              <span class="material-symbols-outlined">close</span>
            </button>
          <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="pf-hero-info">
          <p class="pf-hero-name"><?= htmlspecialchars($user["nombre"]) ?></p>
          <div class="pf-hero-meta">
            <span class="pf-role-badge"><?= htmlspecialchars($cargo) ?></span>
            <span class="pf-online-badge">
              <span class="pf-online-dot"></span>
              En línea
            </span>
          </div>
          <p class="pf-hero-email">
            <span class="material-symbols-outlined">mail</span>
            <?= htmlspecialchars($user["email"] ?? "") ?>
          </p>
        </div>

        <!-- Stats -->
        <div class="pf-hero-stats">
          <div class="pf-stat">
            <span class="pf-stat-value"><?= (int)($stats["creados"] ?? 0) ?></span>
            <span class="pf-stat-label">Tickets creados</span>
          </div>
          <div class="pf-stat">
            <span class="pf-stat-value"><?= (int)($stats["resueltos"] ?? 0) ?></span>
            <span class="pf-stat-label">Resueltos</span>
          </div>
          <div class="pf-stat">
            <span class="pf-stat-value"><?= (int)($stats["en_proceso"] ?? 0) ?></span>
            <span class="pf-stat-label">En proceso</span>
          </div>
        </div>

      </div>
    </div>

    <!-- Grid -->
    <div class="pf-grid">

      <!-- Columna principal -->
      <div class="pf-col-primary">

        <!-- Información personal -->
        <div class="pf-card">
          <p class="pf-section-title">
            <span class="material-symbols-outlined">manage_accounts</span>
            Información personal
          </p>

          <form method="POST" action="index.php?controller=Perfil&action=guardarPerfil">
            <?= Csrf::field() ?>
            <input type="hidden" name="email" value="<?= htmlspecialchars($user["email"] ?? "") ?>">
            <div class="pf-form-grid">

              <div class="pf-form-group">
                <label class="pf-label">Nombre completo</label>
                <input class="pf-input" type="text" name="nombre" value="<?= htmlspecialchars($user["nombre"]) ?>" placeholder="Tu nombre completo">
              </div>

              <div class="pf-form-group">
                <label class="pf-label">Correo electrónico</label>
                <input class="pf-input" type="email" value="<?= htmlspecialchars($user["email"] ?? "") ?>" disabled>
                <span class="pf-input-hint">El correo no se puede modificar</span>
              </div>

              <?php if (!$isAdmin): ?>
              <div class="pf-form-group">
                <label class="pf-label">Departamento</label>
                <?php if ($primaryDept): ?>
                  <div class="pf-input-locked">
                    <span class="material-symbols-outlined">corporate_fare</span>
                    <?= htmlspecialchars($primaryDept["nombre"]) ?>
                  </div>
                <?php else: ?>
                  <div class="pf-input-locked" style="color:var(--text-secondary)">
                    <span class="material-symbols-outlined">corporate_fare</span>
                    Sin departamento asignado
                  </div>
                <?php endif; ?>
                <span class="pf-input-hint">Los departamentos los gestiona el administrador</span>
              </div>
              <?php endif; ?>

              <div class="pf-form-group">
                <label class="pf-label">Cargo</label>
                <div class="pf-input-locked">
                  <span class="material-symbols-outlined">lock</span>
                  <?= htmlspecialchars($cargo) ?>
                </div>
                <?php if (!$isAdmin): ?>
                  <span class="pf-input-hint">Solo los administradores pueden modificar el cargo</span>
                <?php endif; ?>
              </div>

            </div>

            <div class="pf-form-actions">
              <button type="reset" class="pf-btn-ghost">Descartar</button>
              <button type="submit" class="pf-btn-primary">
                <span class="material-symbols-outlined">save</span>
                Guardar cambios
              </button>
            </div>
          </form>
        </div>

        <!-- Seguridad -->
        <div class="pf-card">
          <p class="pf-section-title">
            <span class="material-symbols-outlined">lock</span>
            Seguridad
          </p>
          <div class="pf-security-text">
            <p>Para cambiar tu contraseña te enviaremos un enlace de recuperación a tu correo electrónico registrado.</p>
            <div class="pf-security-email">
              <span class="material-symbols-outlined">mail</span>
              <?= htmlspecialchars($user["email"] ?? "") ?>
            </div>
          </div>
          <form method="POST" action="index.php?controller=Auth&action=enviarRecuperacionDesdePerfil" style="align-self:flex-start;">
            <?= Csrf::field() ?>
            <button type="submit" class="pf-btn-primary">
              <span class="material-symbols-outlined">send</span>
              Enviar enlace de recuperación
            </button>
          </form>
        </div>

        <!-- Preferencias de notificaciones -->
        <div class="pf-card">
          <p class="pf-section-title">
            <span class="material-symbols-outlined">notifications</span>
            Preferencias de notificaciones
          </p>

          <form method="POST" action="index.php?controller=Perfil&action=guardarPreferenciasNotificacion">
            <?= Csrf::field() ?>
            <div class="pf-notif-list">

              <div class="pf-notif-item">
                <div class="pf-notif-info">
                  <p class="pf-notif-label">Ticket asignado</p>
                  <p class="pf-notif-desc">Recibe un aviso cuando alguien te asigne una incidencia</p>
                </div>
                <label class="app-toggle">
                  <input type="checkbox" name="notif[ticket_assigned]" value="1" <?= $notifPrefs['ticket_assigned'] ? 'checked' : '' ?>>
                  <span class="app-toggle-track"></span>
                </label>
              </div>

              <div class="pf-notif-item">
                <div class="pf-notif-info">
                  <p class="pf-notif-label">Nuevo comentario</p>
                  <p class="pf-notif-desc">Notificaciones de comentarios en tickets donde participas</p>
                </div>
                <label class="app-toggle">
                  <input type="checkbox" name="notif[ticket_comment]" value="1" <?= $notifPrefs['ticket_comment'] ? 'checked' : '' ?>>
                  <span class="app-toggle-track"></span>
                </label>
              </div>

              <div class="pf-notif-item">
                <div class="pf-notif-info">
                  <p class="pf-notif-label">Cambio de estado</p>
                  <p class="pf-notif-desc">Cuando el estado de un ticket que te pertenece cambia</p>
                </div>
                <label class="app-toggle">
                  <input type="checkbox" name="notif[ticket_status]" value="1" <?= $notifPrefs['ticket_status'] ? 'checked' : '' ?>>
                  <span class="app-toggle-track"></span>
                </label>
              </div>

              <div class="pf-notif-item">
                <div class="pf-notif-info">
                  <p class="pf-notif-label">Recordatorio de fecha límite</p>
                  <p class="pf-notif-desc">Aviso 24h antes de que venza un ticket asignado a ti</p>
                </div>
                <label class="app-toggle">
                  <input type="checkbox" name="notif[ticket_overdue]" value="1" <?= $notifPrefs['ticket_overdue'] ? 'checked' : '' ?>>
                  <span class="app-toggle-track"></span>
                </label>
              </div>

            </div>

            <div class="pf-form-actions" style="border-top:none; padding-top:0; margin-top:16px;">
              <button type="submit" class="pf-btn-primary">
                <span class="material-symbols-outlined">save</span>
                Guardar preferencias
              </button>
            </div>
          </form>
        </div>

      </div>

      <!-- Columna secundaria -->
      <div class="pf-col-secondary" style="display:flex;flex-direction:column;gap:16px;">

        <!-- Detalles de cuenta -->
        <div class="pf-card-aside">
          <p class="pf-section-title">
            <span class="material-symbols-outlined">badge</span>
            Detalles de cuenta
          </p>

          <div class="pf-info-list">
            <div class="pf-info-row">
              <span class="pf-info-label">
                <span class="material-symbols-outlined">shield_person</span>
                Rol
              </span>
              <span class="pf-info-value" style="color:var(--primary); font-weight:700;">
                <?= htmlspecialchars($cargo) ?>
              </span>
            </div>
            <div class="pf-info-row">
              <span class="pf-info-label">
                <span class="material-symbols-outlined">radio_button_checked</span>
                Estado
              </span>
              <span class="pf-online-badge">
                <span class="pf-online-dot"></span>
                Activo
              </span>
            </div>
          </div>

          <div class="pf-info-divider" style="margin:14px 0;"></div>

          <div class="pf-info-list">
            <div class="pf-info-row">
              <span class="pf-info-label">
                <span class="material-symbols-outlined">calendar_today</span>
                Miembro desde
              </span>
              <span class="pf-info-value">
                <?= $createdAt ? htmlspecialchars(date("d M Y", strtotime($createdAt))) : '—' ?>
              </span>
            </div>
            <div class="pf-info-row">
              <span class="pf-info-label">
                <span class="material-symbols-outlined">login</span>
                Último acceso
              </span>
              <span class="pf-info-value">
                <?php
                if (!empty($user["last_seen_at"])) {
                  echo htmlspecialchars(date("d M Y, H:i", strtotime($user["last_seen_at"])));
                } else {
                  echo "–";
                }
                ?>
              </span>
            </div>
            <div class="pf-info-row">
              <span class="pf-info-label">
                <span class="material-symbols-outlined">language</span>
                Idioma
              </span>
              <span class="pf-info-value">Español</span>
            </div>
            <div class="pf-info-row">
              <span class="pf-info-label">
                <span class="material-symbols-outlined">schedule</span>
                Zona horaria
              </span>
              <span class="pf-info-value">UTC+1</span>
            </div>
          </div>
        </div>

        <!-- Actividad reciente — se expande para rellenar el alto restante -->
        <div class="pf-card-aside" style="flex:1;">
          <p class="pf-section-title">
            <span class="material-symbols-outlined">history</span>
            Actividad reciente
          </p>

          <?php
            $actIconMap = [
              'state_change'    => ['icon' => 'sync',       'cls' => 'warning'],
              'priority_change' => ['icon' => 'flag',       'cls' => 'warning'],
              'assignment'      => ['icon' => 'person_add', 'cls' => 'primary'],
              ''                => ['icon' => 'chat',       'cls' => 'neutral'],
            ];
            $stateLabels = ['sin_abrir'=>'Sin abrir','abierta'=>'Abierto','en_proceso'=>'En proceso','resuelta'=>'Resuelto','cerrada'=>'Cerrado'];
          ?>
          <div class="pf-activity-list">
            <?php if (empty($recentActivity)): ?>
              <p style="color:var(--text-secondary);font-size:13px;padding:8px 0">No hay actividad reciente registrada.</p>
            <?php else: foreach ($recentActivity as $act):
              $et   = $act['event_type'] ?? '';
              $meta = $actIconMap[$et] ?? $actIconMap[''];

              if ($et === 'state_change') {
                $parts = explode('|', $act['contenido'] ?? '');
                $to    = $stateLabels[$parts[1] ?? ''] ?? ($parts[1] ?? '');
                $text  = "Cambió estado a <strong>$to</strong> en";
              } elseif ($et === 'priority_change') {
                $parts = explode('|', $act['contenido'] ?? '');
                $to    = ucfirst($parts[1] ?? '');
                $text  = "Cambió prioridad a <strong>$to</strong> en";
              } elseif ($et === 'assignment') {
                $who  = htmlspecialchars($act['contenido'] ?? '');
                $text = "Asignó ticket a <strong>$who</strong> en";
              } else {
                $text = "Comentó en";
              }
            ?>
              <div class="pf-activity-item">
                <div class="pf-activity-icon <?= $meta['cls'] ?>">
                  <span class="material-symbols-outlined"><?= $meta['icon'] ?></span>
                </div>
                <div class="pf-activity-body">
                  <p class="pf-activity-text">
                    <?= $text ?>
                    <a href="index.php?controller=Ticket&action=detalle&id=<?= (int)$act['ticket_id'] ?>" class="pf-activity-ticket">
                      #<?= (int)$act['ticket_id'] ?>
                    </a>
                    <span style="color:var(--text-secondary);font-size:12px"> · <?= htmlspecialchars(mb_strimwidth($act['titulo'] ?? '', 0, 40, '…')) ?></span>
                  </p>
                  <p class="pf-activity-time"><?= pfTimeAgo($act['created_at']) ?></p>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

      </div>
    </div>
  </div>

</main>
<?php require_once "views/common/msg_panel.php"; ?>

<script>
  /* Avatar upload */
  const avatarBtn   = document.querySelector('.pf-avatar-upload');
  const fileInput   = document.getElementById('pf-file-input');
  const avatarForm  = document.getElementById('pf-avatar-form');

  avatarBtn.addEventListener('click', () => fileInput.click());
  fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) avatarForm.submit();
  });


  /* Remove avatar */
  const removeBtn  = document.getElementById('pf-avatar-remove-btn');
  const removeForm = document.getElementById('pf-avatar-remove-form');
  if (removeBtn && removeForm) {
    removeBtn.addEventListener('click', () => {
      if (confirm('¿Quitar la foto de perfil?')) removeForm.submit();
    });
  }
</script>

<?php require_once "views/common/pie.php"; ?>

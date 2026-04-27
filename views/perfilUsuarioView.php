<?php
$target      ??= [];
$isAdmin     ??= true;
$stats       ??= [];
$ultimos     ??= [];
$targetDepts ??= [];
$allDepts    ??= [];
$createdAt   ??= null;

$_cargoMap      = ['admin' => 'Administrador', 'cliente' => 'Cliente'];
$targetCargo    = $_cargoMap[$target["rol"] ?? "user"] ?? 'Agente de soporte';
$targetInitial  = strtoupper(substr($target["nombre"] ?? "U", 0, 1));
$targetIsActive = (bool)($target["is_active"] ?? true);
$targetDeptIds  = array_map('intval', array_column($targetDepts, 'id'));

$isOnline = false;
if (!empty($target["last_seen_at"])) {
  $isOnline = (time() - strtotime($target["last_seen_at"])) < 300;
}

function estadoToClass(string $e): string {
  return match($e) {
    'abierta'    => 'open',
    'en_proceso' => 'in-progress',
    'resuelta'   => 'resolved',
    'cerrada'    => 'closed',
    default      => 'open',
  };
}
function estadoToLabel(string $e): string {
  return match($e) {
    'abierta'    => 'Abierta',
    'en_proceso' => 'En proceso',
    'resuelta'   => 'Resuelta',
    'cerrada'    => 'Cerrada',
    default      => ucfirst($e),
  };
}
$__pageTitle = isset($target["nombre"]) ? "Perfil de " . $target["nombre"] : "Perfil de usuario";
?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/perfil.css">

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
    <a href="index.php?controller=Perfil&action=listarUsuarios" class="pf-back-btn">
      <span class="material-symbols-outlined">arrow_back</span>
      <span>Volver a Usuarios</span>
    </a>
    <div class="app-topbar-right">
      <?php require_once "views/common/notif_panel.php"; ?>
      <div class="app-topbar-divider"></div>
      <a href="index.php?controller=Perfil&action=verPerfil" class="app-user">
        <div class="app-user-info">
          <span class="app-user-name"><?= htmlspecialchars($_SESSION["usuario"]["nombre"] ?? "") ?></span>
          <span class="app-user-role">
            <?= ['admin' => 'Administrador', 'cliente' => 'Cliente'][$_SESSION["usuario"]["rol"] ?? "user"] ?? 'Agente de soporte' ?>
          </span>
        </div>
        <div class="app-avatar">
          <?php if (!empty($_SESSION["usuario"]["avatar_path"])): ?>
            <img src="<?= htmlspecialchars($_SESSION["usuario"]["avatar_path"]) ?>" alt="Avatar de <?= htmlspecialchars($_SESSION["usuario"]["nombre"] ?? "") ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
          <?php else: ?>
            <?= strtoupper(substr($_SESSION["usuario"]["nombre"] ?? "U", 0, 1)) ?>
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

        <!-- Avatar (solo lectura) -->
        <div class="pf-avatar-wrap">
          <div class="pf-avatar">
            <?php if (!empty($target["avatar_path"])): ?>
              <img src="<?= htmlspecialchars($target["avatar_path"]) ?>" alt="Avatar">
            <?php else: ?>
              <?= $targetInitial ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Info + botón mensaje -->
        <div class="pf-hero-info">
          <p class="pf-hero-name"><?= htmlspecialchars($target["nombre"] ?? "Usuario") ?></p>
          <div class="pf-hero-meta">
            <span class="pf-role-badge"><?= htmlspecialchars($targetCargo) ?></span>
            <?php if (!$targetIsActive): ?>
              <span class="pf-inactive-badge">Cuenta desactivada</span>
            <?php elseif ($isOnline): ?>
              <span class="pf-online-badge">
                <span class="pf-online-dot"></span>
                En línea
              </span>
            <?php else: ?>
              <span style="font-size:10px;font-weight:600;color:#94a3b8;">
                <?php if (!empty($target["last_seen_at"])): ?>
                  Última vez <?= htmlspecialchars(date("d M, H:i", strtotime($target["last_seen_at"]))) ?>
                <?php else: ?>
                  Sin actividad registrada
                <?php endif; ?>
              </span>
            <?php endif; ?>
          </div>
          <p class="pf-hero-email">
            <span class="material-symbols-outlined">mail</span>
            <?= htmlspecialchars($target["email"] ?? "") ?>
          </p>
          <!-- Botón de mensaje — abre el panel de chat -->
          <button type="button" class="pf-btn-message" id="btn-abrir-chat">
            <span class="material-symbols-outlined">chat</span>
            Enviar mensaje
          </button>
        </div>

        <!-- Stats -->
        <div class="pf-hero-stats">
          <div class="pf-stat">
            <span class="pf-stat-value"><?= (int)($stats["creados"] ?? 0) ?></span>
            <span class="pf-stat-label">Creados</span>
          </div>
          <div class="pf-stat">
            <span class="pf-stat-value"><?= (int)($stats["resueltos"] ?? 0) ?></span>
            <span class="pf-stat-label">Resueltos</span>
          </div>
          <div class="pf-stat">
            <span class="pf-stat-value"><?= (int)($stats["pendientes"] ?? 0) ?></span>
            <span class="pf-stat-label">Pendientes</span>
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

          <div class="pf-form-grid">

            <div class="pf-form-group">
              <label class="pf-label">Nombre completo</label>
              <div class="pf-input-locked">
                <span class="material-symbols-outlined">person</span>
                <?= htmlspecialchars($target["nombre"] ?? "—") ?>
              </div>
            </div>

            <div class="pf-form-group">
              <label class="pf-label">Correo electrónico</label>
              <div class="pf-input-locked">
                <span class="material-symbols-outlined">mail</span>
                <?= htmlspecialchars($target["email"] ?? "—") ?>
              </div>
            </div>

            <div class="pf-form-group">
              <label class="pf-label">Departamentos</label>
              <?php if (!empty($targetDepts)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:6px;padding:6px 0;">
                  <?php foreach ($targetDepts as $dept): ?>
                    <span class="pf-dept-tag">
                      <span class="pf-dept-dot" style="background:<?= htmlspecialchars($dept['color'] ?? '#4648d4') ?>"></span>
                      <?= htmlspecialchars($dept['nombre']) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
                <span class="pf-input-hint">Los departamentos los gestiona el administrador</span>
              <?php else: ?>
                <div class="pf-input-locked" style="color:var(--on-surface-variant)">
                  <span class="material-symbols-outlined">corporate_fare</span>
                  Sin departamento asignado
                </div>
                <span class="pf-input-hint">Los departamentos los gestiona el administrador</span>
              <?php endif; ?>
            </div>

            <div class="pf-form-group">
              <label class="pf-label">Cargo</label>
              <div class="pf-input-locked">
                <span class="material-symbols-outlined">lock</span>
                <?= htmlspecialchars($targetCargo) ?>
              </div>
            </div>

          </div>
        </div>

        <!-- Últimas incidencias -->
        <div class="pf-card">
          <p class="pf-section-title">
            <span class="material-symbols-outlined">confirmation_number</span>
            Últimas incidencias
            <?php if (!empty($ultimos)): ?>
              <span style="margin-left:auto;font-size:10px;font-weight:700;background:var(--surface-container);color:var(--on-surface-variant);padding:1px 7px;border-radius:9999px;">
                <?= count($ultimos) ?>
              </span>
            <?php endif; ?>
          </p>

          <?php if (empty($ultimos)): ?>
            <div class="pf-tickets-empty">
              <span class="material-symbols-outlined" style="font-size:32px;display:block;margin-bottom:8px;color:#cbd5e1;">inbox</span>
              Sin incidencias registradas
            </div>
          <?php else: ?>
            <div class="pf-tickets-list">
              <?php foreach ($ultimos as $t): ?>
                <a href="index.php?controller=Ticket&action=detalle&id=<?= (int)$t["id"] ?>" class="pf-ticket-item">
                  <span class="pf-ticket-id">#<?= (int)$t["id"] ?></span>
                  <span class="pf-ticket-title"><?= htmlspecialchars($t["titulo"] ?? "") ?></span>
                  <span class="pf-ticket-status badge-<?= estadoToClass($t["estado"] ?? "abierta") ?>">
                    <?= estadoToLabel($t["estado"] ?? "abierta") ?>
                  </span>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- Columna secundaria: detalles -->
      <div class="pf-col-secondary">
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
                <?= htmlspecialchars($targetCargo) ?>
              </span>
            </div>
            <div class="pf-info-row">
              <span class="pf-info-label">
                <span class="material-symbols-outlined">radio_button_checked</span>
                Estado
              </span>
              <?php if (!$targetIsActive): ?>
                <span class="pf-inactive-badge">Desactivada</span>
              <?php elseif ($isOnline): ?>
                <span class="pf-online-badge">
                  <span class="pf-online-dot"></span>
                  En línea
                </span>
              <?php else: ?>
                <span class="pf-info-value" style="color:#94a3b8;">Desconectado</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="pf-info-divider" style="margin:14px 0;"></div>

          <div class="pf-info-list">
            <div class="pf-info-row">
              <span class="pf-info-label">
                <span class="material-symbols-outlined">mail</span>
                Correo
              </span>
              <span class="pf-info-value" style="font-size:11px;"><?= htmlspecialchars($target["email"] ?? "–") ?></span>
            </div>
            <?php if ($createdAt): ?>
            <div class="pf-info-row">
              <span class="pf-info-label">
                <span class="material-symbols-outlined">calendar_today</span>
                Miembro desde
              </span>
              <span class="pf-info-value">
                <?= htmlspecialchars(date("d M Y", strtotime($createdAt))) ?>
              </span>
            </div>
            <?php endif; ?>
            <div class="pf-info-row">
              <span class="pf-info-label">
                <span class="material-symbols-outlined">update</span>
                Última actividad
              </span>
              <span class="pf-info-value">
                <?php
                if (!empty($target["last_seen_at"])) {
                  echo htmlspecialchars(date("d M Y, H:i", strtotime($target["last_seen_at"])));
                } else {
                  echo "–";
                }
                ?>
              </span>
            </div>
          </div>

          <?php if ($isAdmin): ?>
            <div class="pf-info-divider" style="margin:14px 0;"></div>
            <p class="pf-section-title" style="margin-bottom:12px;">
              <span class="material-symbols-outlined">bolt</span>
              Acciones de admin
            </p>

            <!-- Formulario oculto para toggle de cuenta -->
            <form method="POST" action="index.php?controller=Perfil&action=toggleActivarUsuario" id="form-toggle-activo">
              <?= Csrf::field() ?>
              <input type="hidden" name="user_id" value="<?= (int)($target['id'] ?? 0) ?>">
            </form>

            <div style="display:flex;flex-direction:column;gap:8px;">
              <button type="button" class="pf-btn-ghost" style="justify-content:center; font-size:12px;"
                      onclick="document.getElementById('modal-edit-user').classList.add('open')">
                <span class="material-symbols-outlined" style="font-size:15px;">edit</span>
                Editar perfil
              </button>
              <?php if ($targetIsActive): ?>
                <button type="button" class="pf-btn-danger" style="justify-content:center; font-size:12px;"
                        onclick="if(confirm('¿Desactivar esta cuenta? El usuario no podrá iniciar sesión.')) document.getElementById('form-toggle-activo').submit()">
                  <span class="material-symbols-outlined" style="font-size:15px;">person_off</span>
                  Desactivar cuenta
                </button>
              <?php else: ?>
                <button type="button" class="pf-btn-success" style="justify-content:center; font-size:12px;"
                        onclick="document.getElementById('form-toggle-activo').submit()">
                  <span class="material-symbols-outlined" style="font-size:15px;">person_add</span>
                  Activar cuenta
                </button>
              <?php endif; ?>
            </div>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>
</main>

<?php require_once "views/common/msg_panel.php"; ?>

<!-- Modal: Editar perfil de usuario -->
<div class="pf-modal-overlay" id="modal-edit-user">
  <div class="pf-modal">
    <div class="pf-modal-header">
      <p class="pf-modal-title">Editar perfil de usuario</p>
      <button type="button" class="pf-modal-close" id="modal-edit-close">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <form method="POST" action="index.php?controller=Perfil&action=editarPerfilUsuario">
      <?= Csrf::field() ?>
      <input type="hidden" name="user_id" value="<?= (int)($target['id'] ?? 0) ?>">
      <div class="pf-modal-body">

        <div class="pf-form-group">
          <label class="pf-label">Nombre completo</label>
          <input class="pf-input" type="text" name="nombre"
                 value="<?= htmlspecialchars($target['nombre'] ?? '') ?>"
                 placeholder="Nombre completo" required minlength="2">
        </div>

        <div class="pf-form-group">
          <label class="pf-label">Correo electrónico</label>
          <div class="pf-input-locked">
            <span class="material-symbols-outlined">lock</span>
            <?= htmlspecialchars($target['email'] ?? '') ?>
          </div>
          <span class="pf-input-hint">El correo no se puede modificar</span>
        </div>

        <div class="pf-form-group">
          <label class="pf-label">Rol</label>
          <select class="pf-select" name="rol">
            <option value="user"    <?= ($target['rol'] ?? 'user') === 'user'    ? 'selected' : '' ?>>Agente de soporte</option>
            <option value="admin"  <?= ($target['rol'] ?? 'user') === 'admin'  ? 'selected' : '' ?>>Administrador</option>
            <option value="cliente"<?= ($target['rol'] ?? 'user') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
          </select>
        </div>

        <div class="pf-form-group">
          <label class="pf-label">Departamentos</label>
          <?php if (!empty($allDepts)): ?>
            <div class="pf-modal-dept-list">
              <?php foreach ($allDepts as $dept): ?>
                <label class="pf-modal-dept-item">
                  <input type="checkbox" name="departamentos[]" value="<?= (int)$dept['id'] ?>"
                         <?= in_array((int)$dept['id'], $targetDeptIds) ? 'checked' : '' ?>>
                  <span class="pf-dept-dot" style="background:<?= htmlspecialchars($dept['color'] ?? '#4648d4') ?>"></span>
                  <?= htmlspecialchars($dept['nombre']) ?>
                </label>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <span style="font-size:12px;color:var(--on-surface-variant);">No hay departamentos activos</span>
          <?php endif; ?>
        </div>

      </div>
      <div class="pf-modal-footer">
        <button type="button" class="pf-btn-ghost" id="modal-edit-cancel">Cancelar</button>
        <button type="submit" class="pf-btn-primary">
          <span class="material-symbols-outlined">save</span>
          Guardar cambios
        </button>
      </div>
    </form>
  </div>
</div>

<script>
/* Chat: abrir panel y abrir conversación con este usuario */
document.getElementById('btn-abrir-chat').addEventListener('click', function () {
  const targetId   = <?= (int)($target['id'] ?? 0) ?>;
  const targetName = <?= json_encode($target['nombre'] ?? '') ?>;
  if (typeof window.msgOpenWith === 'function') {
    window.msgOpenWith(targetId, targetName);
  } else {
    document.getElementById('pf-msg-panel').classList.add('open');
    const tab = document.getElementById('pf-msg-tab-btn');
    tab.style.opacity      = '0';
    tab.style.pointerEvents = 'none';
  }
});

/* Modal: editar perfil */
const modalOverlay = document.getElementById('modal-edit-user');

document.getElementById('modal-edit-close').addEventListener('click',  () => modalOverlay.classList.remove('open'));
document.getElementById('modal-edit-cancel').addEventListener('click', () => modalOverlay.classList.remove('open'));

modalOverlay.addEventListener('click', function (e) {
  if (e.target === this) this.classList.remove('open');
});
</script>

<?php require_once "views/common/pie.php"; ?>

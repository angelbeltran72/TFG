<?php
$user  ??= $_SESSION["usuario"] ?? ["nombre" => "Admin", "rol" => "admin"];
$cargo = "Administrador";

$usuarios           ??= [];
$categorias         ??= [];
$departamentos      ??= [];
$etiquetas          ??= [];
$permisosPorUsuario ??= [];
$activityLog        ??= [];
$logFiltros         ??= ['user_id' => '', 'type' => '', 'days' => 7];
$settings           ??= [];

// Settings shortcuts
$sv = fn(string $k, string $d = '') => $settings[$k]['value'] ?? $d;
$sb = fn(string $k) => ($settings[$k]['value'] ?? '0') === '1';

// Avatar color by user id + rol
function cfgAvatarColor(int $id, string $rol): string {
  if ($rol === 'admin') return 'admin';
  $colors = ['green', 'pink', 'orange', 'teal', 'purple'];
  return $colors[$id % count($colors)];
}

function cfgFormatTs(?string $dt): string {
  if (!$dt) return 'Nunca';
  $months = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  $ts = strtotime($dt);
  return (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ', ' . date('H:i', $ts);
}

function cfgRelTime(?string $dt): string {
  if (!$dt) return 'Nunca';
  $diff = time() - strtotime($dt);
  if ($diff < 120)   return 'Ahora mismo';
  if ($diff < 3600)  return 'Hace ' . (int)($diff / 60) . ' min';
  if ($diff < 86400) return 'Hace ' . (int)($diff / 3600) . ' h';
  return cfgFormatTs($dt);
}

$permDefs = [
  'Tickets' => [
    'crear_tickets'            => ['Crear tickets', 'Puede abrir nuevas incidencias en el sistema'],
    'ver_tickets_departamento' => ['Ver tickets del departamento', 'Puede ver todos los tickets del departamento, no solo los suyos'],
    'comentar_tickets'         => ['Comentar en tickets', 'Puede añadir comentarios en tickets (propios o asignados)'],
    'ver_todos_tickets'        => ['Ver todos los tickets', 'Puede ver tickets creados por otros usuarios'],
    'cambiar_estado_ajenos'    => ['Cambiar estado de tickets ajenos', 'Puede modificar el estado de tickets que no son suyos'],
    'reasignar_tickets'        => ['Reasignar tickets', 'Puede cambiar el agente asignado a cualquier ticket'],
    'cerrar_tickets_ajenos'    => ['Cerrar tickets ajenos', 'Puede cerrar y resolver tickets de otros usuarios'],
    'crear_en_nombre_de'       => ['Crear tickets en nombre de otro', 'Puede abrir tickets asignando otro usuario como creador'],
  ],
  'Sistema' => [
    'acceso_kanban'        => ['Acceso al Kanban Board', 'Puede ver y usar el tablero Kanban'],
    'acceso_configuracion' => ['Acceso a Configuración', 'Puede acceder al panel de administración'],
  ],
];

$defaultsAdmin = UserPermissionModel::getDefaults('admin');
$defaultsUser  = UserPermissionModel::getDefaults('user');

$firstUserId = !empty($usuarios) ? (int)$usuarios[0]['id'] : 0;
$__pageTitle = "Configuración";
?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/config.css">

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
    <a href="index.php?controller=Perfil&action=listarUsuarios">
      <span class="material-symbols-outlined" aria-hidden="true">group</span>
      <span>Usuarios</span>
    </a>
    <a href="index.php?controller=Perfil&action=verPerfil">
      <span class="material-symbols-outlined" aria-hidden="true">person</span>
      <span>Mi Perfil</span>
    </a>
    <a class="active" aria-current="page" href="index.php?controller=Config&action=index">
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
    <div class="cfg-layout">

      <!-- Top nav -->
      <nav class="cfg-topnav">
        <div class="cfg-topnav-header">
          <h2>Configuración</h2>
          <p>Panel de administración</p>
        </div>

        <button class="cfg-nav-item active" data-section="usuarios" onclick="cfgShow('usuarios')">
          <span class="material-symbols-outlined">manage_accounts</span>
          Gestión de usuarios
        </button>
        <button class="cfg-nav-item" data-section="permisos" onclick="cfgShow('permisos')">
          <span class="material-symbols-outlined">admin_panel_settings</span>
          Permisos especiales
        </button>

        <div class="cfg-topnav-sep"></div>

        <button class="cfg-nav-item" data-section="categorias" onclick="cfgShow('categorias')">
          <span class="material-symbols-outlined">label</span>
          Categorías de tickets
        </button>
        <button class="cfg-nav-item" data-section="departamentos" onclick="cfgShow('departamentos')">
          <span class="material-symbols-outlined">domain</span>
          Departamentos
        </button>
        <button class="cfg-nav-item" data-section="sistema" onclick="cfgShow('sistema')">
          <span class="material-symbols-outlined">tune</span>
          Configuración del sistema
        </button>

        <div class="cfg-topnav-sep"></div>

        <button class="cfg-nav-item" data-section="registro" onclick="cfgShow('registro')">
          <span class="material-symbols-outlined">history</span>
          Registro de actividad
        </button>
      </nav>

      <!-- Content -->
      <div class="cfg-content">


        <!-- ════════════════════════════════════════════
             Sección: Gestión de usuarios
        ════════════════════════════════════════════ -->
        <div class="cfg-section active" id="cfg-usuarios">

          <div class="cfg-section-header">
            <div>
              <h3 class="cfg-section-title">Gestión de usuarios</h3>
              <p class="cfg-section-subtitle">Administra cuentas, roles y estados de acceso</p>
            </div>
            <button class="cfg-btn-primary" onclick="cfgModalOpen('modal-crear-usuario')">
              <span class="material-symbols-outlined">person_add</span>
              Crear usuario
            </button>
          </div>

          <div class="cfg-search-bar">
            <span class="material-symbols-outlined">search</span>
            <input type="text" placeholder="Buscar por nombre o email..." oninput="cfgFilterUsuarios(this.value)">
          </div>

          <div class="cfg-table-wrap">
            <table class="cfg-table">
              <thead>
                <tr>
                  <th>Usuario</th>
                  <th>Rol</th>
                  <th>Estado</th>
                  <th>Último acceso</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
              <?php
                $rolBadgeMap = [
                  'admin'   => ['class' => 'admin',   'label' => 'Administrador'],
                  'user'    => ['class' => 'agente',  'label' => 'Agente de soporte'],
                  'cliente' => ['class' => 'cliente', 'label' => 'Cliente'],
                ];
                $rolNextMap = [
                  'admin'   => 'Agente',
                  'user'    => 'Cliente',
                  'cliente' => 'Administrador',
                ];
              ?>
              <?php foreach ($usuarios as $u):
                $uid      = (int)$u['id'];
                $isOwn    = $uid === (int)$user['id'];
                $isAdmin  = $u['rol'] === 'admin';
                $active   = (int)$u['is_active'];
                $aColor   = cfgAvatarColor($uid, $u['rol']);
                $initial  = strtoupper(mb_substr($u['nombre'], 0, 1));
                $rolBadge = $rolBadgeMap[$u['rol']] ?? $rolBadgeMap['user'];
                $rolNext  = $rolNextMap[$u['rol']]  ?? 'Agente';
              ?>
                <tr<?= !$active ? ' style="opacity:0.6"' : '' ?>>
                  <td>
                    <div class="cfg-user-cell">
                      <a href="index.php?controller=Perfil&action=verPerfilUsuario&id=<?= (int)$u['id'] ?>" title="Ver perfil de <?= htmlspecialchars($u['nombre']) ?>" style="flex-shrink:0;border-radius:10px;display:block;line-height:0">
                        <div class="cfg-avatar <?= $aColor ?>" style="cursor:pointer">
                          <?php if (!empty($u['avatar_path'])): ?>
                            <img src="<?= htmlspecialchars($u['avatar_path']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
                          <?php else: ?>
                            <?= $initial ?>
                          <?php endif; ?>
                        </div>
                      </a>
                      <div>
                        <div class="cfg-user-name"><?= htmlspecialchars($u['nombre']) ?></div>
                        <div class="cfg-user-email"><?= htmlspecialchars($u['email']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="cfg-badge <?= $rolBadge['class'] ?>">
                      <?= $rolBadge['label'] ?>
                    </span>
                  </td>
                  <td>
                    <span class="cfg-badge <?= $active ? 'activo' : 'inactivo' ?>">
                      <?= $active ? 'Activo' : 'Inactivo' ?>
                    </span>
                  </td>
                  <td style="font-size:12px;color:var(--on-surface-variant)">
                    <?= cfgRelTime($u['last_seen_at'] ?? null) ?>
                  </td>
                  <td>
                    <div class="cfg-table-actions">
                      <?php if ($isOwn): ?>
                        <button class="cfg-icon-btn" title="Es tu propia cuenta" disabled style="opacity:0.35">
                          <span class="material-symbols-outlined">lock</span>
                        </button>
                        <button class="cfg-icon-btn" title="Editar" onclick="cfgOpenEditUsuario(<?= $uid ?>, '<?= htmlspecialchars(addslashes($u['nombre'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= $u['rol'] ?>', <?= $active ?>)">
                          <span class="material-symbols-outlined">edit</span>
                        </button>
                      <?php else: ?>
                        <!-- Editar -->
                        <button class="cfg-icon-btn" title="Editar datos" onclick="cfgOpenEditUsuario(<?= $uid ?>, '<?= htmlspecialchars(addslashes($u['nombre'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= $u['rol'] ?>', <?= $active ?>)">
                          <span class="material-symbols-outlined">edit</span>
                        </button>
                        <!-- Toggle activo -->
                        <form method="POST" action="index.php?controller=Config&action=toggleUsuario" style="display:inline">
                          <?= Csrf::field() ?>
                          <input type="hidden" name="id" value="<?= $uid ?>">
                          <button type="submit" class="cfg-icon-btn" title="<?= $active ? 'Desactivar cuenta' : 'Reactivar cuenta' ?>">
                            <span class="material-symbols-outlined"><?= $active ? 'toggle_on' : 'toggle_off' ?></span>
                          </button>
                        </form>
                        <!-- Cambiar rol -->
                        <form method="POST" action="index.php?controller=Config&action=cambiarRolUsuario" style="display:inline">
                          <?= Csrf::field() ?>
                          <input type="hidden" name="id" value="<?= $uid ?>">
                          <button type="submit" class="cfg-icon-btn" title="Cambiar a <?= $rolNext ?>">
                            <span class="material-symbols-outlined">swap_horiz</span>
                          </button>
                        </form>
                        <!-- Forzar cambio de contraseña -->
                        <form method="POST" action="index.php?controller=Config&action=forzarCambioPassword" style="display:inline">
                          <?= Csrf::field() ?>
                          <input type="hidden" name="id" value="<?= $uid ?>">
                          <button type="submit" class="cfg-icon-btn" title="Forzar cambio de contraseña">
                            <span class="material-symbols-outlined">lock_reset</span>
                          </button>
                        </form>
                        <!-- Quitar avatar -->
                        <?php if (!empty($u['avatar_path'])): ?>
                        <form method="POST" action="index.php?controller=Config&action=quitarAvatarUsuario" style="display:inline">
                          <?= Csrf::field() ?>
                          <input type="hidden" name="id" value="<?= $uid ?>">
                          <button type="submit" class="cfg-icon-btn" title="Quitar avatar">
                            <span class="material-symbols-outlined">no_photography</span>
                          </button>
                        </form>
                        <?php endif; ?>
                        <!-- Eliminar cuenta -->
                        <?php
                          $uTickets  = (int)($u['ticket_count'] ?? 0);
                          $delMsg    = $uTickets > 0
                            ? "¿Eliminar la cuenta de {$u['nombre']}? ATENCIÓN: se eliminarán también {$uTickets} ticket(s) creados por este usuario. Esta acción no se puede deshacer."
                            : "¿Eliminar la cuenta de {$u['nombre']}? Esta acción no se puede deshacer.";
                        ?>
                        <form method="POST" action="index.php?controller=Config&action=eliminarUsuario" style="display:inline"
                          onsubmit="return confirm('<?= htmlspecialchars(addslashes($delMsg)) ?>')">
                          <?= Csrf::field() ?>
                          <input type="hidden" name="id" value="<?= $uid ?>">
                          <input type="hidden" name="ticket_count" value="<?= $uTickets ?>">
                          <button type="submit" class="cfg-icon-btn danger" title="Eliminar cuenta">
                            <span class="material-symbols-outlined">delete</span>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($usuarios)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--on-surface-variant);padding:24px">No hay usuarios registrados.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div><!-- /cfg-usuarios -->


        <!-- ════════════════════════════════════════════
             Sección: Permisos especiales
        ════════════════════════════════════════════ -->
        <div class="cfg-section" id="cfg-permisos">

          <div class="cfg-section-header">
            <div>
              <h3 class="cfg-section-title">Permisos especiales</h3>
              <p class="cfg-section-subtitle">Añade o revoca permisos individuales sin cambiar el rol del usuario</p>
            </div>
          </div>

          <div class="cfg-perm-layout">

            <!-- Lista de usuarios -->
            <div class="cfg-perm-users">
              <div class="cfg-perm-users-header">Selecciona un usuario</div>
              <div class="cfg-perm-search">
                <span class="material-symbols-outlined">search</span>
                <input type="text" placeholder="Buscar usuario..." oninput="cfgFilterPermisos(this.value)">
              </div>

              <?php $firstPerm = true; foreach ($usuarios as $u):
                $uid    = (int)$u['id'];
                $aColor = cfgAvatarColor($uid, $u['rol']);
                $rolLabel = $u['rol'] === 'admin' ? 'Administrador' : 'Agente de soporte';
              ?>
              <button class="cfg-perm-user-btn <?= $firstPerm ? 'active' : '' ?>" data-user="<?= $uid ?>" onclick="cfgPermShow(<?= $uid ?>, this)">
                <div class="cfg-avatar cfg-avatar--sm <?= $aColor ?>">
                  <?php if (!empty($u['avatar_path'])): ?>
                    <img src="<?= htmlspecialchars($u['avatar_path']) ?>" alt="">
                  <?php else: ?>
                    <?= strtoupper(mb_substr($u['nombre'], 0, 1)) ?>
                  <?php endif; ?>
                </div>
                <div>
                  <div class="cfg-perm-user-name"><?= htmlspecialchars($u['nombre']) ?></div>
                  <div class="cfg-perm-user-role"><?= $rolLabel ?></div>
                </div>
              </button>
              <?php $firstPerm = false; endforeach; ?>
            </div><!-- /cfg-perm-users -->

            <!-- Paneles de permisos -->
            <div class="cfg-perm-panels">

              <?php $firstPanel = true; foreach ($usuarios as $u):
                $uid      = (int)$u['id'];
                $isAdmin  = $u['rol'] === 'admin';
                $overrides = $permisosPorUsuario[$uid] ?? [];
                $defaults  = $isAdmin ? $defaultsAdmin : $defaultsUser;
              ?>
              <div class="cfg-perm-panel <?= $firstPanel ? 'active' : '' ?>" id="perm-<?= $uid ?>">

                <?php if ($isAdmin): ?>
                  <?php foreach ($permDefs as $groupLabel => $perms): ?>
                  <div class="cfg-perm-card">
                    <div class="cfg-perm-card-header">
                      <span class="material-symbols-outlined"><?= $groupLabel === 'Tickets' ? 'confirmation_number' : 'apps' ?></span>
                      <?= $groupLabel ?>
                    </div>
                    <?php foreach ($perms as $perm => [$label, $desc]): ?>
                    <div class="cfg-perm-row">
                      <div class="cfg-perm-row-info">
                        <div class="cfg-perm-row-name"><?= $label ?></div>
                        <div class="cfg-perm-row-desc"><?= $desc ?></div>
                      </div>
                      <div class="cfg-perm-row-right">
                        <span class="cfg-perm-default granted">✓ Por rol</span>
                        <label class="app-toggle">
                          <input type="checkbox" checked disabled>
                          <span class="app-toggle-track"></span>
                        </label>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php endforeach; ?>
                  <div style="display:flex;justify-content:flex-end">
                    <p style="font-size:12px;color:var(--on-surface-variant);font-style:italic">
                      Los administradores tienen todos los permisos por rol. No es posible restringirlos individualmente.
                    </p>
                  </div>

                <?php else: ?>
                  <form method="POST" action="index.php?controller=Config&action=guardarPermisos">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="user_id" value="<?= $uid ?>">

                    <?php foreach ($permDefs as $groupLabel => $perms): ?>
                    <div class="cfg-perm-card">
                      <div class="cfg-perm-card-header">
                        <span class="material-symbols-outlined"><?= $groupLabel === 'Tickets' ? 'confirmation_number' : 'apps' ?></span>
                        <?= $groupLabel ?>
                      </div>
                      <?php foreach ($perms as $perm => [$label, $desc]):
                        $roleDefault = $defaults[$perm] ?? false;
                        $hasOverride = array_key_exists($perm, $overrides);
                        $effective   = $hasOverride ? $overrides[$perm] : $roleDefault;
                        $defaultLabel = $roleDefault ? '✓ Por rol' : '✗ Por rol';
                        $defaultClass = $roleDefault ? 'granted' : 'denied';
                        if ($hasOverride) {
                          $defaultLabel = $effective ? '✓ Personalizado' : '✗ Personalizado';
                          $defaultClass = $effective ? 'granted' : 'denied';
                        }
                      ?>
                      <div class="cfg-perm-row">
                        <div class="cfg-perm-row-info">
                          <div class="cfg-perm-row-name"><?= $label ?></div>
                          <div class="cfg-perm-row-desc"><?= $desc ?></div>
                        </div>
                        <div class="cfg-perm-row-right">
                          <span class="cfg-perm-default <?= $defaultClass ?>"><?= $defaultLabel ?></span>
                          <label class="app-toggle">
                            <input type="checkbox" name="perms[<?= $perm ?>]"<?= $effective ? ' checked' : '' ?>>
                            <span class="app-toggle-track"></span>
                          </label>
                        </div>
                      </div>
                      <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px">
                      <button type="button" class="cfg-btn-ghost" onclick="cfgResetPermisos(<?= $uid ?>)">Resetear a valores por defecto</button>
                      <button type="submit" class="cfg-btn-primary">
                        <span class="material-symbols-outlined">save</span>
                        Guardar cambios
                      </button>
                    </div>
                  </form>

                  <!-- Hidden reset form -->
                  <form id="form-reset-perms-<?= $uid ?>" method="POST" action="index.php?controller=Config&action=resetearPermisos" style="display:none">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="user_id" value="<?= $uid ?>">
                  </form>
                <?php endif; ?>

              </div><!-- /perm-<?= $uid ?> -->
              <?php $firstPanel = false; endforeach; ?>

              <?php if (empty($usuarios)): ?>
                <div class="cfg-perm-panel active" style="display:flex;align-items:center;justify-content:center;color:var(--on-surface-variant);padding:40px">
                  No hay usuarios registrados.
                </div>
              <?php endif; ?>

            </div><!-- /cfg-perm-panels -->
          </div><!-- /cfg-perm-layout -->
        </div><!-- /cfg-permisos -->


        <!-- ════════════════════════════════════════════
             Sección: Categorías de tickets
        ════════════════════════════════════════════ -->
        <div class="cfg-section" id="cfg-categorias">

          <div class="cfg-section-header">
            <div>
              <h3 class="cfg-section-title">Categorías de tickets</h3>
              <p class="cfg-section-subtitle">Crea, edita y elimina las categorías disponibles al abrir un ticket</p>
            </div>
            <button class="cfg-btn-primary" onclick="document.getElementById('cat-add-form').classList.toggle('open')">
              <span class="material-symbols-outlined">add</span>
              Nueva categoría
            </button>
          </div>

          <!-- Formulario de añadir -->
          <form method="POST" action="index.php?controller=Config&action=crearCategoria">
            <?= Csrf::field() ?>
            <div class="cfg-cat-add-form" id="cat-add-form" style="flex-wrap:wrap;gap:8px;align-items:center">
              <input type="text" name="nombre" class="cfg-cat-add-input" placeholder="Nombre de la categoría..." style="flex:1;min-width:160px" required>
              <input type="text" name="descripcion" class="cfg-cat-add-input" placeholder="Descripción (opcional)..." style="flex:2;min-width:180px">
              <input type="color" name="color" value="#6366f1" title="Color"
                     style="width:36px;height:36px;border:none;border-radius:8px;cursor:pointer;padding:2px;background:none">
              <select name="departamento_id" class="cfg-setting-select" style="width:auto;min-width:140px">
                <option value="">Sin departamento</option>
                <?php foreach ($departamentos as $d): ?>
                  <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="cfg-btn-primary" style="padding:7px 14px">
                <span class="material-symbols-outlined">check</span>
                Añadir
              </button>
              <button type="button" class="cfg-btn-ghost" onclick="document.getElementById('cat-add-form').classList.remove('open')" style="padding:7px 14px">
                Cancelar
              </button>
            </div>
          </form>

          <div class="cfg-cat-list">
            <?php if (empty($categorias)): ?>
              <div style="text-align:center;padding:32px;color:var(--on-surface-variant)">No hay categorías creadas.</div>
            <?php endif; ?>
            <?php foreach ($categorias as $cat): ?>
            <div class="cfg-cat-row">
              <div class="cfg-cat-dot" style="background:<?= htmlspecialchars($cat['color']) ?>"></div>
              <div style="flex:1;min-width:0">
                <span class="cfg-cat-name"><?= htmlspecialchars($cat['nombre']) ?></span>
                <?php if ($cat['descripcion']): ?>
                  <span style="display:block;font-size:11px;color:var(--on-surface-variant);margin-top:2px"><?= htmlspecialchars($cat['descripcion']) ?></span>
                <?php endif; ?>
              </div>
              <span class="cfg-cat-count"><?= (int)$cat['ticket_count'] ?> tickets</span>
              <?php if (!$cat['is_active']): ?>
                <span class="cfg-badge inactivo" style="font-size:10px;padding:2px 8px">Inactiva</span>
              <?php endif; ?>
              <div class="cfg-table-actions">
                <button class="cfg-icon-btn" title="Editar"
                  onclick="cfgOpenEditCat(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['nombre'])) ?>', '<?= htmlspecialchars($cat['color']) ?>', '<?= htmlspecialchars(addslashes($cat['descripcion'] ?? '')) ?>', '<?= $cat['departamento_id'] ?? '' ?>')">
                  <span class="material-symbols-outlined">edit</span>
                </button>
                <form method="POST" action="index.php?controller=Config&action=toggleCategoria" style="display:inline">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                  <button type="submit" class="cfg-icon-btn" title="<?= $cat['is_active'] ? 'Desactivar' : 'Activar' ?>">
                    <span class="material-symbols-outlined"><?= $cat['is_active'] ? 'toggle_on' : 'toggle_off' ?></span>
                  </button>
                </form>
                <?php
                  $ticketCount = (int)$cat['ticket_count'];
                  $confirmMsg  = $ticketCount > 0
                    ? "¿Eliminar la categoría «{$cat['nombre']}»? ATENCIÓN: se eliminarán también {$ticketCount} ticket(s) asignados."
                    : "¿Eliminar la categoría «{$cat['nombre']}»?";
                ?>
                <form method="POST" action="index.php?controller=Config&action=eliminarCategoria" style="display:inline"
                  onsubmit="return confirm('<?= htmlspecialchars(addslashes($confirmMsg)) ?>')">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                  <input type="hidden" name="ticket_count" value="<?= $ticketCount ?>">
                  <button type="submit" class="cfg-icon-btn danger" title="Eliminar">
                    <span class="material-symbols-outlined">delete</span>
                  </button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div><!-- /cfg-categorias -->


        <!-- ════════════════════════════════════════════
             Sección: Departamentos
        ════════════════════════════════════════════ -->
        <div class="cfg-section" id="cfg-departamentos">

          <div class="cfg-section-header">
            <div>
              <h3 class="cfg-section-title">Departamentos</h3>
              <p class="cfg-section-subtitle">Organiza a los agentes por departamentos y asigna categorías específicas a cada uno</p>
            </div>
            <button class="cfg-btn-primary" onclick="document.getElementById('dep-add-form').classList.toggle('open')">
              <span class="material-symbols-outlined">add</span>
              Nuevo departamento
            </button>
          </div>

          <!-- Formulario de añadir -->
          <form method="POST" action="index.php?controller=Config&action=crearDepartamento">
            <?= Csrf::field() ?>
            <div class="cfg-cat-add-form" id="dep-add-form" style="flex-wrap:wrap;gap:8px;align-items:center">
              <input type="text" name="nombre" class="cfg-cat-add-input" placeholder="Nombre del departamento..." style="flex:1;min-width:160px" required>
              <input type="text" name="descripcion" class="cfg-cat-add-input" placeholder="Descripción (opcional)..." style="flex:2;min-width:200px">
              <input type="color" name="color" value="#4648d4" title="Color del departamento"
                     style="width:36px;height:36px;border:none;border-radius:8px;cursor:pointer;padding:2px;background:none">
              <button type="submit" class="cfg-btn-primary" style="padding:7px 14px">
                <span class="material-symbols-outlined">check</span>
                Añadir
              </button>
              <button type="button" class="cfg-btn-ghost" onclick="document.getElementById('dep-add-form').classList.remove('open')" style="padding:7px 14px">
                Cancelar
              </button>
            </div>
          </form>

          <div class="cfg-cat-list">
            <?php if (empty($departamentos)): ?>
              <div style="text-align:center;padding:32px;color:var(--on-surface-variant)">No hay departamentos creados.</div>
            <?php endif; ?>
            <?php foreach ($departamentos as $dep): ?>
            <div class="cfg-cat-row">
              <div class="cfg-cat-dot" style="background:<?= htmlspecialchars($dep['color']) ?>"></div>
              <div style="flex:1;min-width:0">
                <span class="cfg-cat-name"><?= htmlspecialchars($dep['nombre']) ?></span>
                <?php if ($dep['descripcion']): ?>
                  <span style="display:block;font-size:11px;color:var(--on-surface-variant);margin-top:2px"><?= htmlspecialchars($dep['descripcion']) ?></span>
                <?php endif; ?>
              </div>
              <span class="cfg-cat-count"><?= (int)$dep['member_count'] ?> <?= $dep['member_count'] == 1 ? 'miembro' : 'miembros' ?></span>
              <span class="cfg-badge <?= $dep['is_active'] ? 'activo' : 'inactivo' ?>" style="font-size:10px;padding:2px 8px">
                <?= $dep['is_active'] ? 'Activo' : 'Inactivo' ?>
              </span>
              <div class="cfg-table-actions">
                <button class="cfg-icon-btn" title="Gestionar miembros"
                  onclick="cfgOpenDepUsers(<?= $dep['id'] ?>, '<?= htmlspecialchars(addslashes($dep['nombre'])) ?>')">
                  <span class="material-symbols-outlined">group</span>
                </button>
                <button class="cfg-icon-btn" title="Editar"
                  onclick="cfgOpenEditDep(<?= $dep['id'] ?>, '<?= htmlspecialchars(addslashes($dep['nombre'])) ?>', '<?= htmlspecialchars(addslashes($dep['descripcion'] ?? '')) ?>', '<?= htmlspecialchars($dep['color']) ?>', <?= (int)$dep['is_active'] ?>)">
                  <span class="material-symbols-outlined">edit</span>
                </button>
                <form method="POST" action="index.php?controller=Config&action=toggleDepartamento" style="display:inline">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $dep['id'] ?>">
                  <input type="hidden" name="is_active" value="<?= (int)$dep['is_active'] ?>">
                  <button type="submit" class="cfg-icon-btn" title="<?= $dep['is_active'] ? 'Desactivar' : 'Activar' ?>">
                    <span class="material-symbols-outlined"><?= $dep['is_active'] ? 'toggle_on' : 'toggle_off' ?></span>
                  </button>
                </form>
                <form method="POST" action="index.php?controller=Config&action=eliminarDepartamento" style="display:inline"
                  onsubmit="return confirm('¿Eliminar el departamento «<?= htmlspecialchars(addslashes($dep['nombre'])) ?>»?')">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $dep['id'] ?>">
                  <button type="submit" class="cfg-icon-btn danger" title="Eliminar">
                    <span class="material-symbols-outlined">delete</span>
                  </button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div><!-- /cfg-departamentos -->


        <!-- ════════════════════════════════════════════
             Sección: Configuración del sistema
        ════════════════════════════════════════════ -->
        <div class="cfg-section" id="cfg-sistema">

          <div class="cfg-section-header">
            <div>
              <h3 class="cfg-section-title">Configuración del sistema</h3>
              <p class="cfg-section-subtitle">Parámetros globales que afectan al comportamiento de la aplicación</p>
            </div>
          </div>

          <form method="POST" action="index.php?controller=Config&action=guardarSettings">
            <?= Csrf::field() ?>

            <!-- Grupo: Registro y acceso -->
            <div class="cfg-settings-group">
              <div class="cfg-settings-group-header">
                <span class="material-symbols-outlined">login</span>
                Registro y acceso
              </div>
              <div class="cfg-setting-row">
                <div class="cfg-setting-info">
                  <div class="cfg-setting-label">Registro de nuevas cuentas</div>
                  <div class="cfg-setting-desc">Permite que usuarios nuevos se registren desde la pantalla de login. Si está desactivado, solo los administradores pueden crear cuentas.</div>
                </div>
                <label class="app-toggle">
                  <input type="checkbox" name="registro_abierto"<?= $sb('registro_abierto') ? ' checked' : '' ?>>
                  <span class="app-toggle-track"></span>
                </label>
              </div>
              <div class="cfg-setting-row">
                <div class="cfg-setting-info">
                  <div class="cfg-setting-label">Máximo de tickets abiertos por usuario</div>
                  <div class="cfg-setting-desc">Número máximo de tickets en estado "abierto" o "en proceso" que un agente puede tener simultáneamente. 0 = sin límite.</div>
                </div>
                <input type="number" name="max_tickets_por_usuario" class="cfg-setting-input" value="<?= htmlspecialchars($sv('max_tickets_por_usuario', '0')) ?>" min="0">
              </div>
            </div>

            <!-- Grupo: Localización -->
            <div class="cfg-settings-group">
              <div class="cfg-settings-group-header">
                <span class="material-symbols-outlined">language</span>
                Localización
              </div>
              <?php
                $tzOptions = [
                  'Europe/Madrid'       => 'Europe/Madrid (UTC+1)',
                  'Europe/London'       => 'Europe/London (UTC+0)',
                  'America/New_York'    => 'America/New_York (UTC−5)',
                  'America/Los_Angeles' => 'America/Los_Angeles (UTC−8)',
                  'Asia/Tokyo'          => 'Asia/Tokyo (UTC+9)',
                  'UTC'                 => 'UTC',
                ];
                $currentTz = $sv('zona_horaria', 'Europe/Madrid');
                $fmtOptions = [
                  'DD/MM/YYYY' => 'DD/MM/YYYY — Ej: ' . date('d/m/Y'),
                  'MM/DD/YYYY' => 'MM/DD/YYYY — Ej: ' . date('m/d/Y'),
                  'YYYY-MM-DD' => 'YYYY-MM-DD — Ej: ' . date('Y-m-d'),
                ];
                $currentFmt = $sv('formato_fecha', 'DD/MM/YYYY');
              ?>
              <div class="cfg-setting-row">
                <div class="cfg-setting-info">
                  <div class="cfg-setting-label">Zona horaria por defecto</div>
                  <div class="cfg-setting-desc">Zona horaria usada para mostrar fechas en tickets, logs y actividad.</div>
                </div>
                <select name="zona_horaria" class="cfg-setting-select">
                  <?php foreach ($tzOptions as $val => $label): ?>
                    <option value="<?= $val ?>"<?= $currentTz === $val ? ' selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="cfg-setting-row">
                <div class="cfg-setting-info">
                  <div class="cfg-setting-label">Formato de fecha</div>
                  <div class="cfg-setting-desc">Define cómo se muestran las fechas en toda la aplicación.</div>
                </div>
                <select name="formato_fecha" class="cfg-setting-select">
                  <?php foreach ($fmtOptions as $val => $label): ?>
                    <option value="<?= $val ?>"<?= $currentFmt === $val ? ' selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- Grupo: Asignación de tickets -->
            <div class="cfg-settings-group">
              <div class="cfg-settings-group-header">
                <span class="material-symbols-outlined">assignment_ind</span>
                Asignación de tickets
              </div>
              <?php $modoAsig = $sv('modo_asignacion', 'roundrobin'); ?>
              <div class="cfg-setting-row">
                <div class="cfg-setting-info">
                  <div class="cfg-setting-label">Modo de asignación automática</div>
                  <div class="cfg-setting-desc">Define cómo se reparten los nuevos tickets entre los agentes disponibles al crearse.</div>
                </div>
                <div class="cfg-radio-group">
                  <label class="cfg-radio-option">
                    <input type="radio" name="modo_asignacion" value="roundrobin"<?= $modoAsig === 'roundrobin' ? ' checked' : '' ?>>
                    Round-Robin — turno rotativo entre agentes
                  </label>
                  <label class="cfg-radio-option">
                    <input type="radio" name="modo_asignacion" value="cola"<?= $modoAsig === 'cola' ? ' checked' : '' ?>>
                    Cola general — sin asignar hasta intervención manual
                  </label>
                </div>
              </div>
              <div class="cfg-setting-row">
                <div class="cfg-setting-info">
                  <div class="cfg-setting-label">Incluir administradores en la rotación</div>
                  <div class="cfg-setting-desc">Si está activo, los administradores también reciben tickets en el ciclo Round-Robin.</div>
                </div>
                <label class="app-toggle">
                  <input type="checkbox" name="incluir_admins_rotacion"<?= $sb('incluir_admins_rotacion') ? ' checked' : '' ?>>
                  <span class="app-toggle-track"></span>
                </label>
              </div>
            </div>

            <!-- Grupo: Modo de mantenimiento -->
            <?php $mantenimiento = $sb('modo_mantenimiento'); ?>
            <div class="cfg-maintenance-card<?= $mantenimiento ? ' active' : '' ?>" id="maintenance-card">
              <div class="cfg-maintenance-header">
                <div class="cfg-maintenance-info">
                  <div class="cfg-maintenance-icon">
                    <span class="material-symbols-outlined">construction</span>
                  </div>
                  <div>
                    <div class="cfg-maintenance-title">Modo de mantenimiento</div>
                    <div class="cfg-maintenance-desc">
                      Al activarlo, los usuarios con rol <strong>Agente</strong> verán una pantalla de "Trabajando en el servidor" al intentar acceder.<br>
                      Los <strong>administradores</strong> pueden seguir accediendo con normalidad.
                    </div>
                  </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-shrink:0">
                  <span class="cfg-maintenance-badge" id="maintenance-badge">
                    <span class="dot"></span>
                    <?= $mantenimiento ? 'Activado' : 'Desactivado' ?>
                  </span>
                  <label class="app-toggle" style="width:48px;height:26px">
                    <input type="checkbox" name="modo_mantenimiento" id="maintenance-toggle"
                           onchange="cfgMaintenanceToggle(this.checked)"<?= $mantenimiento ? ' checked' : '' ?>>
                    <span class="app-toggle-track"></span>
                  </label>
                </div>
              </div>
              <div class="cfg-maintenance-footer">
                <span class="material-symbols-outlined">info</span>
                Los cambios se aplican de forma inmediata. Los agentes con sesión activa serán redirigidos en su próxima acción.
              </div>
            </div>

            <div class="cfg-settings-save-bar">
              <button type="submit" class="cfg-btn-primary">
                <span class="material-symbols-outlined">save</span>
                Guardar configuración
              </button>
            </div>

          </form>
        </div>


        <!-- ════════════════════════════════════════════
             Sección: Registro de actividad
        ════════════════════════════════════════════ -->
        <div class="cfg-section" id="cfg-registro">

          <div class="cfg-section-header">
            <div>
              <h3 class="cfg-section-title">Registro de actividad</h3>
              <p class="cfg-section-subtitle">Historial de acciones relevantes realizadas en el sistema</p>
            </div>
          </div>

          <div class="cfg-log-filters">
            <div class="cfg-log-search">
              <span class="material-symbols-outlined">search</span>
              <input type="text" placeholder="Buscar en el registro..." oninput="cfgFilterLog(this.value)">
            </div>
            <form method="GET" action="index.php" id="log-filter-form" style="display:contents">
              <input type="hidden" name="controller" value="Config">
              <input type="hidden" name="action" value="index">
              <select name="log_user" class="cfg-log-select" onchange="cfgSubmitLogFilter()">
                <option value="">Todos los usuarios</option>
                <?php foreach ($usuarios as $u): ?>
                  <option value="<?= $u['id'] ?>"<?= (string)$logFiltros['user_id'] === (string)$u['id'] ? ' selected' : '' ?>>
                    <?= htmlspecialchars($u['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <select name="log_type" class="cfg-log-select" onchange="cfgSubmitLogFilter()">
                <option value="">Todos los tipos</option>
                <?php foreach (['permisos' => 'Permisos', 'usuario' => 'Usuario', 'ticket' => 'Ticket', 'sistema' => 'Sistema', 'auth' => 'Auth'] as $val => $label): ?>
                  <option value="<?= $val ?>"<?= $logFiltros['type'] === $val ? ' selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
              <select name="log_days" class="cfg-log-select" onchange="cfgSubmitLogFilter()">
                <option value="1"<?= $logFiltros['days'] == 1 ? ' selected' : '' ?>>Últimas 24 horas</option>
                <option value="7"<?= $logFiltros['days'] == 7 ? ' selected' : '' ?>>Últimos 7 días</option>
                <option value="30"<?= $logFiltros['days'] == 30 ? ' selected' : '' ?>>Últimos 30 días</option>
                <option value="0"<?= $logFiltros['days'] == 0 ? ' selected' : '' ?>>Todo el historial</option>
              </select>
            </form>
          </div>

          <div class="cfg-table-wrap">
            <table class="cfg-table" id="log-table">
              <thead>
                <tr>
                  <th>Fecha y hora</th>
                  <th>Usuario</th>
                  <th>Tipo</th>
                  <th>Detalle</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($activityLog)): ?>
                <tr><td colspan="4" style="text-align:center;color:var(--on-surface-variant);padding:24px">No hay entradas en el registro para los filtros seleccionados.</td></tr>
              <?php endif; ?>
              <?php foreach ($activityLog as $entry):
                $entryName  = $entry['user_nombre'] ?? 'Sistema';
                $entryRol   = $entry['user_rol']    ?? 'system';
                $hasAvatar  = !empty($entry['avatar_path']);
                $aColor     = $entryRol === 'admin' ? 'admin' : 'green';
                $initial    = strtoupper(mb_substr($entryName, 0, 1));
                $typeMap    = ['permisos' => 'perm', 'usuario' => 'user', 'ticket' => 'ticket', 'sistema' => 'system', 'auth' => 'auth'];
                $typeCss    = $typeMap[$entry['type']] ?? 'system';
                $typeLabels = ['permisos' => 'Permisos', 'usuario' => 'Usuario', 'ticket' => 'Ticket', 'sistema' => 'Sistema', 'auth' => 'Auth'];
                $typeLabel  = $typeLabels[$entry['type']] ?? ucfirst($entry['type']);
              ?>
                <tr>
                  <td style="font-size:12px;color:var(--on-surface-variant);white-space:nowrap">
                    <?= cfgFormatTs($entry['created_at']) ?>
                  </td>
                  <td>
                    <div class="cfg-user-cell">
                      <div class="cfg-avatar <?= $aColor ?>" style="width:26px;height:26px;font-size:10px;border-radius:7px">
                        <?php if ($hasAvatar): ?>
                          <img src="<?= htmlspecialchars($entry['avatar_path']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
                        <?php else: ?>
                          <?= $initial ?>
                        <?php endif; ?>
                      </div>
                      <span style="font-size:12px;font-weight:600"><?= htmlspecialchars($entryName) ?></span>
                    </div>
                  </td>
                  <td><span class="cfg-log-type <?= $typeCss ?>"><?= $typeLabel ?></span></td>
                  <td style="font-size:12px;color:var(--on-surface)"><?= htmlspecialchars($entry['detail']) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div><!-- /cfg-registro -->


      </div><!-- /cfg-content -->
    </div><!-- /cfg-layout -->
  </div><!-- /app-canvas -->
</main>


<!-- ════════════════════════════════════════════════
     Modal: Crear usuario
════════════════════════════════════════════════ -->
<div class="cfg-modal-overlay" id="modal-crear-usuario" onclick="cfgModalOutsideClose(event, 'modal-crear-usuario')">
  <div class="cfg-modal">
    <div class="cfg-modal-header">
      <div>
        <div class="cfg-modal-title">Crear nuevo usuario</div>
        <div class="cfg-modal-subtitle">La cuenta se creará activa de inmediato</div>
      </div>
      <button class="cfg-modal-close" onclick="cfgModalClose('modal-crear-usuario')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <div class="cfg-modal-notice">
      <span class="material-symbols-outlined">info</span>
      La contraseña inicial será 1234. Al iniciar sesión por primera vez, el usuario deberá establecer una contraseña personal antes de acceder.
    </div>

    <form method="POST" action="index.php?controller=Config&action=crearUsuario">
      <?= Csrf::field() ?>

      <div class="cfg-form-field">
        <label class="cfg-form-label">Nombre completo</label>
        <input type="text" name="nombre" class="cfg-form-input" placeholder="Ej: María González" required>
      </div>

      <div class="cfg-form-field">
        <label class="cfg-form-label">Correo electrónico</label>
        <input type="email" name="email" class="cfg-form-input" placeholder="maria.gonzalez@empresa.com" required>
      </div>

      <div class="cfg-form-field" style="margin-bottom:0">
        <label class="cfg-form-label">Rol</label>
        <select name="rol" class="cfg-form-select">
          <option value="user">Agente de soporte</option>
          <option value="admin">Administrador</option>
          <option value="cliente">Cliente</option>
        </select>
      </div>

      <div class="cfg-modal-footer">
        <button type="button" class="cfg-btn-ghost" onclick="cfgModalClose('modal-crear-usuario')">Cancelar</button>
        <button type="submit" class="cfg-btn-primary">
          <span class="material-symbols-outlined">person_add</span>
          Crear usuario
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ════════════════════════════════════════════════
     Modal: Editar usuario
════════════════════════════════════════════════ -->
<div class="cfg-modal-overlay" id="modal-editar-usuario" onclick="cfgModalOutsideClose(event, 'modal-editar-usuario')">
  <div class="cfg-modal">
    <div class="cfg-modal-header">
      <div>
        <div class="cfg-modal-title">Editar usuario</div>
        <div class="cfg-modal-subtitle">Modifica nombre, email, rol o estado de la cuenta</div>
      </div>
      <button class="cfg-modal-close" onclick="cfgModalClose('modal-editar-usuario')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <form method="POST" action="index.php?controller=Config&action=editarUsuario">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="edit-user-id">

      <div class="cfg-form-field">
        <label class="cfg-form-label">Nombre completo</label>
        <input type="text" name="nombre" id="edit-user-nombre" class="cfg-form-input" required>
      </div>

      <div class="cfg-form-field">
        <label class="cfg-form-label">Correo electrónico</label>
        <input type="email" name="email" id="edit-user-email" class="cfg-form-input" required>
      </div>

      <div class="cfg-form-field">
        <label class="cfg-form-label">Rol</label>
        <select name="rol" id="edit-user-rol" class="cfg-form-select">
          <option value="user">Agente de soporte</option>
          <option value="admin">Administrador</option>
          <option value="cliente">Cliente</option>
        </select>
      </div>

      <div class="cfg-form-field" style="margin-bottom:0">
        <label class="cfg-form-label">Estado</label>
        <div style="display:flex;align-items:center;gap:10px">
          <label class="app-toggle">
            <input type="checkbox" name="is_active" id="edit-user-active">
            <span class="app-toggle-track"></span>
          </label>
          <span style="font-size:13px;color:var(--on-surface-variant)">Cuenta activa</span>
        </div>
      </div>

      <div class="cfg-modal-footer">
        <button type="button" class="cfg-btn-ghost" onclick="cfgModalClose('modal-editar-usuario')">Cancelar</button>
        <button type="submit" class="cfg-btn-primary">
          <span class="material-symbols-outlined">save</span>
          Guardar cambios
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ════════════════════════════════════════════════
     Modal: Editar categoría
════════════════════════════════════════════════ -->
<div class="cfg-modal-overlay" id="modal-editar-cat" onclick="cfgModalOutsideClose(event, 'modal-editar-cat')">
  <div class="cfg-modal">
    <div class="cfg-modal-header">
      <div>
        <div class="cfg-modal-title">Editar categoría</div>
        <div class="cfg-modal-subtitle">Los cambios afectan a todos los tickets con esta categoría</div>
      </div>
      <button class="cfg-modal-close" onclick="cfgModalClose('modal-editar-cat')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <form method="POST" action="index.php?controller=Config&action=editarCategoria">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="edit-cat-id">

      <div class="cfg-form-field">
        <label class="cfg-form-label">Nombre</label>
        <input type="text" name="nombre" id="edit-cat-nombre" class="cfg-form-input" required>
      </div>

      <div class="cfg-form-field">
        <label class="cfg-form-label">Descripción</label>
        <input type="text" name="descripcion" id="edit-cat-descripcion" class="cfg-form-input" placeholder="Opcional">
      </div>

      <div class="cfg-form-field">
        <label class="cfg-form-label">Color identificativo</label>
        <div style="display:flex;align-items:center;gap:12px">
          <input type="color" name="color" id="edit-cat-color"
                 style="width:40px;height:40px;border:none;border-radius:8px;cursor:pointer;padding:2px;background:none">
          <span style="font-size:12px;color:var(--on-surface-variant)">Color que identifica la categoría</span>
        </div>
      </div>

      <div class="cfg-form-field" style="margin-bottom:0">
        <label class="cfg-form-label">Departamento</label>
        <select name="departamento_id" id="edit-cat-dept" class="cfg-form-select">
          <option value="">Sin departamento</option>
          <?php foreach ($departamentos as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="cfg-modal-footer">
        <button type="button" class="cfg-btn-ghost" onclick="cfgModalClose('modal-editar-cat')">Cancelar</button>
        <button type="submit" class="cfg-btn-primary">
          <span class="material-symbols-outlined">save</span>
          Guardar cambios
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ════════════════════════════════════════════════
     Modal: Editar departamento
════════════════════════════════════════════════ -->
<div class="cfg-modal-overlay" id="modal-editar-dep" onclick="cfgModalOutsideClose(event, 'modal-editar-dep')">
  <div class="cfg-modal">
    <div class="cfg-modal-header">
      <div>
        <div class="cfg-modal-title">Editar departamento</div>
        <div class="cfg-modal-subtitle">Los cambios se aplican a todos los usuarios del departamento</div>
      </div>
      <button class="cfg-modal-close" onclick="cfgModalClose('modal-editar-dep')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <form method="POST" action="index.php?controller=Config&action=editarDepartamento">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="edit-dep-id">

      <div class="cfg-form-field">
        <label class="cfg-form-label">Nombre</label>
        <input type="text" name="nombre" id="edit-dep-nombre" class="cfg-form-input" required>
      </div>

      <div class="cfg-form-field">
        <label class="cfg-form-label">Descripción</label>
        <input type="text" name="descripcion" id="edit-dep-descripcion" class="cfg-form-input" placeholder="Descripción del departamento...">
      </div>

      <div class="cfg-form-field">
        <label class="cfg-form-label">Color identificativo</label>
        <div style="display:flex;align-items:center;gap:12px">
          <input type="color" name="color" id="edit-dep-color"
                 style="width:40px;height:40px;border:none;border-radius:8px;cursor:pointer;padding:2px;background:none">
          <span style="font-size:12px;color:var(--on-surface-variant)">Color que identifica el departamento en listas y tickets</span>
        </div>
      </div>

      <div class="cfg-form-field" style="margin-bottom:0">
        <label class="cfg-form-label">Estado</label>
        <div style="display:flex;align-items:center;gap:10px">
          <label class="app-toggle">
            <input type="checkbox" name="is_active" id="edit-dep-active">
            <span class="app-toggle-track"></span>
          </label>
          <span style="font-size:13px;color:var(--on-surface-variant)">Departamento activo — los usuarios pueden ser asignados</span>
        </div>
      </div>

      <div class="cfg-modal-footer">
        <button type="button" class="cfg-btn-ghost" onclick="cfgModalClose('modal-editar-dep')">Cancelar</button>
        <button type="submit" class="cfg-btn-primary">
          <span class="material-symbols-outlined">save</span>
          Guardar cambios
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ════════════════════════════════════════════════
     Modal: Gestionar miembros de departamento
════════════════════════════════════════════════ -->
<div class="cfg-modal-overlay" id="modal-dep-users" onclick="cfgModalOutsideClose(event, 'modal-dep-users')">
  <div class="cfg-modal" style="max-width:680px;width:100%">
    <div class="cfg-modal-header">
      <div>
        <div class="cfg-modal-title">Miembros del departamento</div>
        <div class="cfg-modal-subtitle" id="dep-users-subtitle">—</div>
      </div>
      <button class="cfg-modal-close" onclick="cfgModalClose('modal-dep-users')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <div id="dep-users-loading" style="padding:32px;text-align:center;color:var(--on-surface-variant)">
      <span class="material-symbols-outlined" style="font-size:32px;display:block;margin-bottom:8px">hourglass_empty</span>
      Cargando…
    </div>

    <div id="dep-users-body" style="display:none">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:0 0 4px">

        <!-- Miembros actuales -->
        <div>
          <p style="font-size:12px;font-weight:600;color:var(--on-surface-variant);text-transform:uppercase;letter-spacing:.04em;margin:0 0 10px">
            Miembros actuales
          </p>
          <div id="dep-members-list" style="display:flex;flex-direction:column;gap:6px;max-height:320px;overflow-y:auto"></div>
          <p id="dep-members-empty" style="display:none;font-size:13px;color:var(--on-surface-variant);padding:12px 0">
            Sin miembros asignados.
          </p>
        </div>

        <!-- Usuarios disponibles -->
        <div>
          <p style="font-size:12px;font-weight:600;color:var(--on-surface-variant);text-transform:uppercase;letter-spacing:.04em;margin:0 0 10px">
            Añadir usuarios
          </p>
          <input type="text" id="dep-users-search" class="cfg-form-input" placeholder="Buscar por nombre o email…"
                 style="margin-bottom:8px;padding:6px 10px;font-size:13px" oninput="depUsersFilter()">
          <div id="dep-available-list" style="display:flex;flex-direction:column;gap:6px;max-height:280px;overflow-y:auto"></div>
          <p id="dep-available-empty" style="display:none;font-size:13px;color:var(--on-surface-variant);padding:12px 0">
            Todos los usuarios ya son miembros.
          </p>
        </div>

      </div>
    </div>

    <div class="cfg-modal-footer" style="border-top:1px solid var(--surface-container);margin-top:12px;padding-top:12px">
      <button type="button" class="cfg-btn-ghost" onclick="cfgModalClose('modal-dep-users')">Cerrar</button>
    </div>
  </div>
</div>


<?php require_once "views/common/msg_panel.php"; ?>

<script>
/* Navegación de secciones */
function cfgShow(id) {
  document.querySelectorAll('.cfg-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.cfg-nav-item').forEach(b => b.classList.remove('active'));
  document.getElementById('cfg-' + id).classList.add('active');
  document.querySelector(`.cfg-nav-item[data-section="${id}"]`).classList.add('active');
  if (id === 'registro') cfgShowSection(id);
}

/* Navegación de permisos por usuario */
function cfgPermShow(userId, btn) {
  document.querySelectorAll('.cfg-perm-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.cfg-perm-user-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('perm-' + userId).classList.add('active');
  btn.classList.add('active');
}

function cfgResetPermisos(userId) {
  if (!confirm('¿Restablecer todos los permisos a los valores por defecto del rol?')) return;
  document.getElementById('form-reset-perms-' + userId).submit();
}

/* Modo de mantenimiento */
function cfgMaintenanceToggle(on) {
  const card  = document.getElementById('maintenance-card');
  const badge = document.getElementById('maintenance-badge');
  card.classList.toggle('active', on);
  badge.innerHTML = on
    ? '<span class="dot"></span> Activado'
    : '<span class="dot"></span> Desactivado';
}

/* Filtro tabla de usuarios */
function cfgFilterUsuarios(q) {
  const val = q.toLowerCase().trim();
  document.querySelectorAll('#cfg-usuarios .cfg-table tbody tr').forEach(row => {
    const name  = row.querySelector('.cfg-user-name')?.textContent.toLowerCase()  ?? '';
    const email = row.querySelector('.cfg-user-email')?.textContent.toLowerCase() ?? '';
    row.style.display = (!val || name.includes(val) || email.includes(val)) ? '' : 'none';
  });
}

/* Filtro lista de permisos */
function cfgFilterPermisos(q) {
  const val = q.toLowerCase().trim();
  document.querySelectorAll('.cfg-perm-user-btn').forEach(btn => {
    const name = btn.querySelector('.cfg-perm-user-name')?.textContent.toLowerCase() ?? '';
    btn.style.display = (!val || name.includes(val)) ? '' : 'none';
  });
}

/* Filtro registro (client-side) */
function cfgFilterLog(q) {
  const val = q.toLowerCase().trim();
  document.querySelectorAll('#log-table tbody tr').forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = (!val || text.includes(val)) ? '' : 'none';
  });
}

/* Submit filtros de registro manteniendo sección activa */
function cfgSubmitLogFilter() {
  const form = document.getElementById('log-filter-form');
  const params = new URLSearchParams(new FormData(form));
  window.location.href = 'index.php?' + params.toString() + '#registro';
}

/* Modal */
function cfgModalOpen(id) {
  document.getElementById(id).classList.add('open');
}
function cfgModalClose(id) {
  document.getElementById(id).classList.remove('open');
}
function cfgModalOutsideClose(e, id) {
  if (e.target === document.getElementById(id)) cfgModalClose(id);
}

/* Poblar modal editar usuario */
function cfgOpenEditUsuario(id, nombre, email, rol, isActive) {
  document.getElementById('edit-user-id').value     = id;
  document.getElementById('edit-user-nombre').value = nombre;
  document.getElementById('edit-user-email').value  = email;
  document.getElementById('edit-user-rol').value    = rol;
  document.getElementById('edit-user-active').checked = !!isActive;
  cfgModalOpen('modal-editar-usuario');
}

/* Poblar modal editar categoría */
function cfgOpenEditCat(id, nombre, color, descripcion, deptId) {
  document.getElementById('edit-cat-id').value          = id;
  document.getElementById('edit-cat-nombre').value      = nombre;
  document.getElementById('edit-cat-color').value       = color;
  document.getElementById('edit-cat-descripcion').value = descripcion || '';
  document.getElementById('edit-cat-dept').value        = deptId || '';
  cfgModalOpen('modal-editar-cat');
}

/* Poblar modal editar departamento */
function cfgOpenEditDep(id, nombre, descripcion, color, isActive) {
  document.getElementById('edit-dep-id').value          = id;
  document.getElementById('edit-dep-nombre').value      = nombre;
  document.getElementById('edit-dep-descripcion').value = descripcion || '';
  document.getElementById('edit-dep-color').value       = color;
  document.getElementById('edit-dep-active').checked    = !!isActive;
  cfgModalOpen('modal-editar-dep');
}

/* Hash-based section on load */
(function() {
  const hash = location.hash.replace('#', '');
  if (hash && document.getElementById('cfg-' + hash)) cfgShow(hash);
})();

/* ═══════════════════════════════════════════════
   Modal: gestión de miembros de departamento
═══════════════════════════════════════════════ */
const DEP_CSRF = '<?= htmlspecialchars(Csrf::generate()) ?>';
let depCurrentId   = 0;
let depAllMembers  = [];
let depAvailable   = [];

const rolLabels = { admin: 'Administrador', user: 'Agente', cliente: 'Cliente' };
const rolColors = { admin: 'admin', user: 'agente', cliente: 'cliente' };

function depAvatar(u) {
  if (u.avatar_path) {
    return `<img src="${escHtmlDep(u.avatar_path)}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">`;
  }
  return escHtmlDep((u.nombre || '?')[0].toUpperCase());
}

function escHtmlDep(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function depUserCard(u, action) {
  const rolLabel = rolLabels[u.rol] || u.rol;
  const rolColor = rolColors[u.rol] || 'agente';
  const icon     = action === 'add' ? 'person_add' : 'person_remove';
  const title    = action === 'add' ? 'Añadir al departamento' : 'Quitar del departamento';
  const fn       = action === 'add' ? `depAddUser(${u.id})` : `depRemoveUser(${u.id})`;
  return `
    <div class="cfg-user-cell" id="dep-card-${u.id}" style="background:var(--surface-container);border-radius:10px;padding:8px 10px;gap:10px">
      <div class="cfg-avatar" style="width:32px;height:32px;font-size:13px;flex-shrink:0">${depAvatar(u)}</div>
      <div style="flex:1;min-width:0">
        <div class="cfg-user-name" style="font-size:13px">${escHtmlDep(u.nombre)}</div>
        <div class="cfg-user-email" style="font-size:11px">${escHtmlDep(u.email)}</div>
      </div>
      <span class="cfg-badge ${rolColor}" style="font-size:10px;padding:2px 7px">${rolLabel}</span>
      <button class="cfg-icon-btn" title="${title}" onclick="${fn}" style="flex-shrink:0">
        <span class="material-symbols-outlined" style="font-size:18px">${icon}</span>
      </button>
    </div>`;
}

function depRenderLists() {
  const ml   = document.getElementById('dep-members-list');
  const al   = document.getElementById('dep-available-list');
  const me   = document.getElementById('dep-members-empty');
  const ae   = document.getElementById('dep-available-empty');
  const q    = (document.getElementById('dep-users-search').value || '').toLowerCase();

  const filtered = depAvailable.filter(u =>
    u.nombre.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  );

  ml.innerHTML = depAllMembers.map(u => depUserCard(u, 'remove')).join('');
  al.innerHTML = filtered.map(u => depUserCard(u, 'add')).join('');

  me.style.display = depAllMembers.length === 0 ? '' : 'none';
  ae.style.display = filtered.length === 0    ? '' : 'none';
}

function depUsersFilter() { depRenderLists(); }

async function cfgOpenDepUsers(depId, depNombre) {
  depCurrentId = depId;
  document.getElementById('dep-users-subtitle').textContent = depNombre;
  document.getElementById('dep-users-loading').style.display = '';
  document.getElementById('dep-users-body').style.display    = 'none';
  document.getElementById('dep-users-search').value          = '';
  cfgModalOpen('modal-dep-users');

  try {
    const r = await fetch(`index.php?controller=Config&action=ajaxMiembrosDepartamento&dep_id=${depId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await r.json();
    if (!data.ok) throw new Error();
    depAllMembers = data.miembros;
    depAvailable  = data.disponibles;
    depRenderLists();
    document.getElementById('dep-users-loading').style.display = 'none';
    document.getElementById('dep-users-body').style.display    = '';
  } catch {
    document.getElementById('dep-users-loading').innerHTML =
      '<span style="color:var(--error)">Error al cargar los miembros. Recarga la página.</span>';
  }
}

async function depAddUser(userId) {
  const card = document.getElementById(`dep-card-${userId}`);
  if (card) card.style.opacity = '0.4';
  try {
    const body = new URLSearchParams({ dep_id: depCurrentId, user_id: userId, csrf_token: DEP_CSRF });
    const r    = await fetch('index.php?controller=Config&action=ajaxAsignarUsuarioDepartamento', {
      method: 'POST', body
    });
    const data = await r.json();
    if (!data.ok) throw new Error(data.msg || 'Error');

    const u = depAvailable.find(x => x.id == userId);
    if (u) {
      depAvailable  = depAvailable.filter(x => x.id != userId);
      depAllMembers = [...depAllMembers, u].sort((a, b) => a.nombre.localeCompare(b.nombre));
    }
    depRenderLists();
  } catch (e) {
    if (card) card.style.opacity = '';
    alert(e.message || 'Error al añadir el usuario.');
  }
}

async function depRemoveUser(userId) {
  const card = document.getElementById(`dep-card-${userId}`);
  if (card) card.style.opacity = '0.4';
  try {
    const body = new URLSearchParams({ dep_id: depCurrentId, user_id: userId, csrf_token: DEP_CSRF });
    const r    = await fetch('index.php?controller=Config&action=ajaxQuitarUsuarioDepartamento', {
      method: 'POST', body
    });
    const data = await r.json();
    if (!data.ok) throw new Error(data.msg || 'Error');

    const u = depAllMembers.find(x => x.id == userId);
    if (u) {
      depAllMembers = depAllMembers.filter(x => x.id != userId);
      depAvailable  = [...depAvailable, u].sort((a, b) => a.nombre.localeCompare(b.nombre));
    }
    depRenderLists();
  } catch (e) {
    if (card) card.style.opacity = '';
    alert(e.message || 'Error al quitar el usuario.');
  }
}
</script>

<?php require_once "views/common/pie.php"; ?>

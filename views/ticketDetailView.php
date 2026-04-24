<?php
$user       ??= $_SESSION["usuario"] ?? ["nombre" => "Usuario", "rol" => "user"];
$role       ??= $user["rol"] ?? "user";
$isCliente  = $role === 'cliente';
$cargoMap   = ['admin' => 'Administrador', 'cliente' => 'Cliente'];
$cargo      = $cargoMap[$role] ?? 'Agente de soporte';
$__pageTitle = isset($ticket["titulo"]) ? "Ticket #" . (int)($ticket["id"] ?? 0) . " — " . $ticket["titulo"] : "Detalle de ticket";
?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/ticketDetail.css">

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
    <a class="active" aria-current="page" href="index.php?controller=Ticket&action=misTickets">
      <span class="material-symbols-outlined">assignment_ind</span>
      <span>Mis Tickets</span>
    </a>
    <a href="index.php?controller=Perfil&action=verPerfil">
      <span class="material-symbols-outlined">person</span>
      <span>Mi Perfil</span>
    </a>
    <?php else: ?>
    <a href="index.php?controller=Dashboard&action=index">
      <span class="material-symbols-outlined">dashboard</span>
      <span>Dashboard</span>
    </a>
    <a class="active" aria-current="page" href="index.php?controller=Ticket&action=listar">
      <span class="material-symbols-outlined" aria-hidden="true">confirmation_number</span>
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
    <a href="index.php?controller=Perfil&action=verPerfil">
      <span class="material-symbols-outlined">person</span>
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
    <a href="<?= $isCliente ? 'index.php?controller=Ticket&action=misTickets' : 'index.php?controller=Ticket&action=listar' ?>" class="td-back-btn">
      <span class="material-symbols-outlined">arrow_back</span>
      <span>Volver a <?= $isCliente ? 'Mis Tickets' : 'Tickets' ?></span>
    </a>
    <div class="app-topbar-right">
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

    <?php
      $ticket      ??= [];
      $comentarios ??= [];
      $isAdmin     ??= false;

      $estadoLabel = ['sin_abrir'=>'Sin abrir','abierta'=>'Abierta','en_proceso'=>'En proceso','resuelta'=>'Resuelta','cerrada'=>'Cerrada'];
      $estadoBadge = ['sin_abrir'=>'badge-sin-abrir','abierta'=>'badge-abierta','en_proceso'=>'badge-en-proceso','resuelta'=>'badge-resuelta','cerrada'=>'badge-cerrada'];
      $prioLabel   = ['baja'=>'Baja','media'=>'Media','alta'=>'Alta','critica'=>'Crítica'];
      $prioCls     = ['baja'=>'low','media'=>'medium','alta'=>'high','critica'=>'critical'];

      $estado = $ticket['estado']    ?? 'sin_abrir';
      $prio   = $ticket['prioridad'] ?? 'media';
      $tId    = (int)($ticket['id']  ?? 0);
    ?>

    <!-- Page Header -->
    <div class="td-page-header">
      <div class="td-title-row">
        <span class="td-ticket-id">#<?= $tId ?></span>
        <h2 class="td-page-title"><?= htmlspecialchars($ticket['titulo'] ?? '') ?></h2>
      </div>
    </div>

    <!-- Grid -->
    <div class="td-grid">

      <!-- Primary column -->
      <div class="td-col-primary">

        <!-- Descripción -->
        <div class="td-card">
          <p class="td-section-title">
            <span class="material-symbols-outlined">description</span>
            Descripción
          </p>
          <div class="td-description">
            <?= nl2br(htmlspecialchars($ticket['descripcion'] ?? '')) ?>
          </div>
        </div>

        <!-- Actividad -->
        <div class="td-card">
          <p class="td-section-title">
            <span class="material-symbols-outlined">forum</span>
            Actividad
            <span class="td-section-count"><?= count($comentarios) + 1 ?></span>
          </p>

          <div class="td-timeline">

            <!-- Evento: ticket creado -->
            <div class="td-timeline-item<?= empty($comentarios) ? ' ultimo' : '' ?>">
              <div class="td-tl-node event">
                <span class="material-symbols-outlined">add_circle</span>
              </div>
              <div class="td-tl-body">
                <p class="td-event-text">
                  <strong><?= htmlspecialchars($ticket['creado_por_nombre'] ?? 'Sistema') ?></strong> creó el ticket
                </p>
                <span class="td-tl-time"><?= htmlspecialchars(substr($ticket['created_at'] ?? '', 0, 16)) ?></span>
              </div>
            </div>

            <?php
            $totalItems = count($comentarios);
            foreach ($comentarios as $idx => $c):
              $isLast    = ($idx === $totalItems - 1);
              $eventType = $c['event_type'] ?? '';
              $autor     = $c['autor_nombre'] ?? 'Sistema';
              $isOwn     = ((int)($c['user_id'] ?? 0) === (int)$user['id']);
              $time      = htmlspecialchars(substr($c['created_at'] ?? '', 0, 16));
            ?>

            <?php if ($eventType !== ''): // Evento de sistema ?>

              <?php
                if ($eventType === 'state_change') {
                  $parts    = explode('|', $c['contenido']);
                  $newState = $parts[1] ?? ($parts[0] ?? '');
                  $newLabel = $estadoLabel[$newState] ?? $newState;
                  $icon     = 'published_with_changes';
                  $nodeClass = 'status';
                  $text = '<strong>' . htmlspecialchars($autor) . '</strong> cambió el estado a <strong>' . htmlspecialchars($newLabel) . '</strong>';
                } elseif ($eventType === 'priority_change') {
                  $parts   = explode('|', $c['contenido']);
                  $newPrio = $parts[1] ?? ($parts[0] ?? '');
                  $newPrioLabel = $prioLabel[$newPrio] ?? $newPrio;
                  $icon     = 'flag';
                  $nodeClass = 'event';
                  $text = '<strong>' . htmlspecialchars($autor) . '</strong> cambió la prioridad a <strong>' . htmlspecialchars($newPrioLabel) . '</strong>';
                } elseif ($eventType === 'assignment') {
                  $assignee = trim($c['contenido']);
                  $icon     = $assignee !== '' ? 'person_check' : 'person_remove';
                  $nodeClass = 'event';
                  $text = $assignee !== ''
                    ? '<strong>' . htmlspecialchars($autor) . '</strong> asignó el ticket a <strong>' . htmlspecialchars($assignee) . '</strong>'
                    : '<strong>' . htmlspecialchars($autor) . '</strong> desasignó el ticket';
                } else {
                  $icon     = 'info';
                  $nodeClass = 'event';
                  $text = htmlspecialchars($c['contenido']);
                }
              ?>
              <div class="td-timeline-item<?= $isLast ? ' ultimo' : '' ?>">
                <div class="td-tl-node <?= $nodeClass ?>">
                  <span class="material-symbols-outlined"><?= $icon ?></span>
                </div>
                <div class="td-tl-body">
                  <p class="td-event-text"><?= $text ?></p>
                  <span class="td-tl-time"><?= $time ?></span>
                </div>
              </div>

            <?php else: // Comentario de usuario ?>

              <?php
                $partsC = explode(' ', trim($autor));
                $inicC  = strtoupper(substr($partsC[0],0,1).(isset($partsC[1])?substr($partsC[1],0,1):''));
              ?>
              <div class="td-timeline-item<?= $isLast ? ' ultimo' : '' ?>">
                <div class="td-tl-avatar<?= $isOwn ? ' self' : '' ?>"><?= htmlspecialchars($inicC) ?></div>
                <div class="td-tl-body">
                  <div class="td-comment-header">
                    <span class="td-comment-author"><?= htmlspecialchars($autor) ?></span>
                    <?php if (!empty($c['is_internal'])): ?>
                      <span class="td-comment-internal" title="Nota interna">
                        <span class="material-symbols-outlined" style="font-size:14px">lock</span>
                        Interna
                      </span>
                    <?php endif; ?>
                    <span class="td-tl-time"><?= $time ?></span>
                  </div>
                  <div class="td-comment-bubble<?= !empty($c['is_internal']) ? ' internal' : '' ?>">
                    <?= nl2br(htmlspecialchars($c['contenido'])) ?>
                  </div>
                </div>
              </div>

            <?php endif; ?>
            <?php endforeach; ?>

          </div>

          <!-- Añadir comentario -->
          <?php if ($estado !== 'cerrada' && !$isCliente): ?>
          <form method="POST" action="index.php?controller=Ticket&action=comentar" class="td-comment-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="ticket_id" value="<?= $tId ?>">
            <div class="td-tl-avatar self"><?= strtoupper(substr($user["nombre"], 0, 2)) ?></div>
            <div class="td-comment-input-wrap">
              <textarea name="contenido" class="td-comment-input" placeholder="Escribe un comentario..." rows="2" required></textarea>
              <div class="td-comment-actions">
                <button type="submit" class="td-comment-submit">
                  <span class="material-symbols-outlined">send</span>
                  Comentar
                </button>
              </div>
            </div>
          </form>
          <?php endif; ?>

        </div>

      </div>

      <!-- Secondary column: metadatos -->
      <div class="td-col-secondary">
        <div class="td-card-meta">

          <p class="td-section-title">
            <span class="material-symbols-outlined">tune</span>
            Detalles
          </p>

          <div class="td-meta-list">
            <div class="td-meta-row">
              <span class="td-meta-label">
                <span class="material-symbols-outlined">radio_button_checked</span>
                Estado
              </span>
              <span class="td-status-badge <?= $estadoBadge[$estado] ?? 'badge-sin-abrir' ?>">
                <?= $estadoLabel[$estado] ?? htmlspecialchars($estado) ?>
              </span>
            </div>
            <div class="td-meta-row">
              <span class="td-meta-label">
                <span class="material-symbols-outlined">flag</span>
                Prioridad
              </span>
              <span class="td-priority-badge <?= $prioCls[$prio] ?? 'medium' ?>">
                <span class="td-priority-dot"></span><?= $prioLabel[$prio] ?? htmlspecialchars($prio) ?>
              </span>
            </div>
            <div class="td-meta-row">
              <span class="td-meta-label">
                <span class="material-symbols-outlined">category</span>
                Categoría
              </span>
              <span class="td-meta-value"><?= htmlspecialchars($ticket['categoria_nombre'] ?? '—') ?></span>
            </div>
            <?php if (!empty($ticket['asignado_a_nombre'])): ?>
            <div class="td-meta-row">
              <span class="td-meta-label">
                <span class="material-symbols-outlined">person_check</span>
                Asignado a
              </span>
              <div class="td-meta-user">
                <?php
                  $nomAs   = $ticket['asignado_a_nombre'];
                  $partsAs = explode(' ', trim($nomAs));
                  $inicAs  = strtoupper(substr($partsAs[0],0,1).(isset($partsAs[1])?substr($partsAs[1],0,1):''));
                ?>
                <div class="td-user-avatar"><?= htmlspecialchars($inicAs) ?></div>
                <span><?= htmlspecialchars($nomAs) ?></span>
              </div>
            </div>
            <?php endif; ?>
            <div class="td-meta-row">
              <span class="td-meta-label">
                <span class="material-symbols-outlined">person</span>
                Creado por
              </span>
              <div class="td-meta-user">
                <?php
                  $nomCr   = $ticket['creado_por_nombre'] ?? 'Sistema';
                  $partsCr = explode(' ', trim($nomCr));
                  $inicCr  = strtoupper(substr($partsCr[0],0,1).(isset($partsCr[1])?substr($partsCr[1],0,1):''));
                ?>
                <div class="td-user-avatar"><?= htmlspecialchars($inicCr) ?></div>
                <span><?= htmlspecialchars($nomCr) ?></span>
              </div>
            </div>
            <?php if (!$isCliente && !empty($ticket['cliente_email'])): ?>
            <div class="td-meta-row">
              <span class="td-meta-label">
                <span class="material-symbols-outlined">contact_mail</span>
                Cliente
              </span>
              <span class="td-meta-value">
                <?php if (!empty($ticket['cliente_nombre'])): ?>
                  <?= htmlspecialchars($ticket['cliente_nombre']) ?><br>
                  <small style="color:var(--text-muted,#94a3b8)"><?= htmlspecialchars($ticket['cliente_email']) ?></small>
                <?php else: ?>
                  <?= htmlspecialchars($ticket['cliente_email']) ?>
                <?php endif; ?>
              </span>
            </div>
            <?php endif; ?>
          </div>

          <div class="td-meta-divider"></div>

          <div class="td-meta-list">
            <div class="td-meta-row">
              <span class="td-meta-label">
                <span class="material-symbols-outlined">calendar_today</span>
                Creado el
              </span>
              <span class="td-meta-value"><?= htmlspecialchars(substr($ticket['created_at'] ?? '', 0, 10)) ?></span>
            </div>
            <div class="td-meta-row">
              <span class="td-meta-label">
                <span class="material-symbols-outlined">update</span>
                Actualizado
              </span>
              <span class="td-meta-value"><?= htmlspecialchars(substr($ticket['updated_at'] ?? $ticket['created_at'] ?? '', 0, 10)) ?></span>
            </div>
          </div>

          <div class="td-meta-divider"></div>

          <p class="td-section-title">
            <span class="material-symbols-outlined">bolt</span>
            Acciones
          </p>

          <div class="td-actions">
            <?php if (!$isCliente && $estado !== 'resuelta' && $estado !== 'cerrada'): ?>
            <form method="POST" action="index.php?controller=Ticket&action=cambiarEstado">
              <?= Csrf::field() ?>
              <input type="hidden" name="ticket_id" value="<?= $tId ?>">
              <input type="hidden" name="estado" value="resuelta">
              <button type="submit" class="td-btn-resolve">
                <span class="material-symbols-outlined">check_circle</span>
                Marcar como Resuelto
              </button>
            </form>
            <?php endif; ?>
            <?php if ($isAdmin && $estado !== 'cerrada'): ?>
            <form method="POST" action="index.php?controller=Ticket&action=cambiarEstado">
              <?= Csrf::field() ?>
              <input type="hidden" name="ticket_id" value="<?= $tId ?>">
              <input type="hidden" name="estado" value="cerrada">
              <button type="submit" class="td-btn-close">
                <span class="material-symbols-outlined">lock</span>
                Cerrar Ticket
              </button>
            </form>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
            <a href="index.php?controller=Ticket&action=editar&id=<?= $tId ?>" class="td-btn-edit">
              <span class="material-symbols-outlined">edit</span>
              Editar Ticket
            </a>
            <?php endif; ?>
          </div>

        </div>
      </div>

    </div>
  </div>
</main>
<?php require_once "views/common/msg_panel.php"; ?>

<?php require_once "views/common/pie.php"; ?>

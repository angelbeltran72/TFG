<?php
$user  ??= $_SESSION["usuario"] ?? ["nombre" => "Usuario", "rol" => "user"];
$cargo = ($user["rol"] ?? "user") === "admin" ? "Administrador" : "Agente de soporte";
$__pageTitle = "Kanban Board";
?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/kanban.css">

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
    <a class="active" aria-current="page" href="index.php?controller=Kanban&action=index">
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

  <!-- Canvas -->
  <div class="app-canvas">

    <!-- Barra de filtros -->
    <div class="kb-topbar">
      <div class="kb-filter-pills">
        <span class="kb-filter-label">Prioridad</span>
        <button class="kb-filter-pill active" data-priority="all">Todas</button>
        <button class="kb-filter-pill p-critical" data-priority="critical">Crítica</button>
        <button class="kb-filter-pill p-high"     data-priority="high">Alta</button>
        <button class="kb-filter-pill p-medium"   data-priority="medium">Media</button>
        <button class="kb-filter-pill p-low"      data-priority="low">Baja</button>
      </div>
    </div>

    <!-- Board -->
    <?php
      $kanban ??= [];
      $kbCols = [
        'sin_abrir'  => ['label'=>'Sin Abrir',  'color'=>'#94a3b8'],
        'abierta'    => ['label'=>'Abierto',     'color'=>'#4648d4'],
        'en_proceso' => ['label'=>'En Proceso',  'color'=>'#d97706'],
        'resuelta'   => ['label'=>'Resuelto',    'color'=>'#16a34a'],
        'cerrada'    => ['label'=>'Cerrado',     'color'=>'#64748b'],
      ];
      $kbPrioCls   = ['baja'=>'low','media'=>'medium','alta'=>'high','critica'=>'critical'];
      $kbPrioLabel = ['baja'=>'Baja','media'=>'Media','alta'=>'Alta','critica'=>'Crítica'];
    ?>
    <div class="kb-board" id="kb-board">

      <?php foreach ($kbCols as $estado => $col):
        $tarjetas = $kanban[$estado] ?? [];
      ?>
      <!-- <?= $col['label'] ?> -->
      <div class="kb-col" data-col="<?= $estado ?>">
        <div class="kb-col-header">
          <span class="kb-col-dot" style="background:<?= $col['color'] ?>"></span>
          <span class="kb-col-title"><?= $col['label'] ?></span>
          <span class="kb-col-count"><?= count($tarjetas) ?></span>
        </div>
        <div class="kb-col-body">
          <?php foreach ($tarjetas as $t):
            $tId   = (int)$t['id'];
            $prio  = $t['prioridad'];
            $nomAs = $t['asignado_a_nombre'] ?? '';
            $parts = $nomAs ? explode(' ', trim($nomAs)) : [];
            $inic  = $nomAs ? strtoupper(substr($parts[0],0,1).(isset($parts[1])?substr($parts[1],0,1):'')) : '';
          ?>
          <div class="kb-card" draggable="true"
               data-id="<?= $tId ?>"
               data-priority="<?= $kbPrioCls[$prio] ?? 'medium' ?>"
               data-href="index.php?controller=Ticket&action=detalle&id=<?= $tId ?>">
            <div class="kb-card-top">
              <div class="kb-priority <?= $kbPrioCls[$prio] ?? 'medium' ?>">
                <span class="kb-dot"></span><?= $kbPrioLabel[$prio] ?? htmlspecialchars($prio) ?>
              </div>
              <span class="kb-card-time"><?= htmlspecialchars(substr($t['created_at'] ?? '', 0, 10)) ?></span>
            </div>
            <p class="kb-card-title"><?= htmlspecialchars($t['titulo']) ?></p>
            <div class="kb-card-bottom">
              <span class="kb-card-id">#<?= $tId ?></span>
              <span class="kb-cat"><?= htmlspecialchars($t['categoria_nombre'] ?? '—') ?></span>
              <?php if ($nomAs): ?>
                <div class="kb-avatar" title="<?= htmlspecialchars($nomAs) ?>"><?= htmlspecialchars($inic) ?></div>
              <?php else: ?>
                <div class="kb-avatar empty"><span class="material-symbols-outlined">person</span></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

    </div><!-- /kb-board -->
  </div><!-- /app-canvas -->
</main>

<?php require_once "views/common/msg_panel.php"; ?>

<meta name="csrf-token" content="<?= htmlspecialchars(Csrf::generate(), ENT_QUOTES, 'UTF-8') ?>">
<script>
/* Kanban — Arrastrar y soltar + Filtro */

/* Filtro de prioridad */
document.querySelectorAll('.kb-filter-pill').forEach(pill => {
  pill.addEventListener('click', () => {
    document.querySelectorAll('.kb-filter-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    const priority = pill.dataset.priority;
    document.querySelectorAll('.kb-card').forEach(card => {
      const match = priority === 'all' || card.dataset.priority === priority;
      card.classList.toggle('oculto', !match);
    });
    updateCounters();
  });
});

/* Clic en tarjeta → detalle */
document.querySelectorAll('.kb-card').forEach(card => {
  card.addEventListener('click', () => {
    if (card.dataset.href) window.location.href = card.dataset.href;
  });
});

/* Drag & Drop */
let dragging   = null;
let placeholder = null;

function makePlaceholder(h) {
  const el = document.createElement('div');
  el.className = 'kb-card-placeholder';
  el.style.height = h + 'px';
  return el;
}

function getAfterElement(body, y) {
  const cards = [...body.querySelectorAll('.kb-card:not(.arrastrando)')];
  return cards.reduce((closest, card) => {
    const box    = card.getBoundingClientRect();
    const offset = y - box.top - box.height / 2;
    return (offset < 0 && offset > closest.offset)
      ? { offset, element: card }
      : closest;
  }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function updateCounters() {
  document.querySelectorAll('.kb-col').forEach(col => {
    const count = col.querySelectorAll('.kb-card:not(.oculto)').length;
    col.querySelector('.kb-col-count').textContent = count;
  });
}

document.querySelectorAll('.kb-card').forEach(card => {
  card.addEventListener('dragstart', e => {
    dragging    = card;
    placeholder = makePlaceholder(card.offsetHeight);
    e.dataTransfer.effectAllowed = 'move';
    setTimeout(() => card.classList.add('arrastrando'), 0);
  });

  card.addEventListener('dragend', () => {
    card.classList.remove('arrastrando');
    placeholder?.remove();
    placeholder = null;
    dragging    = null;
    document.querySelectorAll('.kb-col-body').forEach(b => b.classList.remove('arrastrando-sobre'));
    updateCounters();
  });
});

document.querySelectorAll('.kb-col-body').forEach(body => {
  body.addEventListener('dragover', e => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    body.classList.add('arrastrando-sobre');
    const after = getAfterElement(body, e.clientY);
    if (placeholder) {
      after ? body.insertBefore(placeholder, after) : body.appendChild(placeholder);
    }
  });

  body.addEventListener('dragleave', e => {
    if (!body.contains(e.relatedTarget)) {
      body.classList.remove('arrastrando-sobre');
    }
  });

  body.addEventListener('drop', e => {
    e.preventDefault();
    body.classList.remove('arrastrando-sobre');
    if (!dragging || !placeholder) return;

    const nuevoEstado  = body.closest('.kb-col').dataset.col;
    const ticketId     = dragging.dataset.id;
    const estadoAntes  = dragging.closest('.kb-col')?.dataset.col ?? nuevoEstado;
    const sibling      = placeholder.nextSibling;
    const parentAntes  = dragging.parentElement;
    const siblingAntes = dragging.nextSibling;

    placeholder.replaceWith(dragging);
    updateCounters();

    if (estadoAntes === nuevoEstado) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const data = new FormData();
    data.append('csrf_token', csrfToken);
    data.append('ticket_id',  ticketId);
    data.append('estado',     nuevoEstado);

    fetch('index.php?controller=Ticket&action=ajaxCambiarEstado', {
      method: 'POST',
      body: data,
    })
    .then(r => r.json())
    .then(json => {
      if (!json.ok) {
        parentAntes.insertBefore(dragging, siblingAntes);
        updateCounters();
        alert(json.msg || 'Error al actualizar el estado.');
      }
    })
    .catch(() => {
      parentAntes.insertBefore(dragging, siblingAntes);
      updateCounters();
    });
  });
});
</script>

<?php require_once "views/common/pie.php"; ?>

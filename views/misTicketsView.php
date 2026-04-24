<?php
$user      ??= $_SESSION["usuario"] ?? ["nombre" => "Usuario", "rol" => "user"];
$role      ??= $user["rol"] ?? "user";
$isCliente = $role === 'cliente';
$cargoMap  = ['admin' => 'Administrador', 'cliente' => 'Cliente'];
$cargo     = $cargoMap[$role] ?? 'Agente de soporte';
$usuarios  ??= [];
$__pageTitle = "Mis Tickets";
?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/tickets.css">

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
      <span class="material-symbols-outlined" aria-hidden="true">assignment_ind</span>
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
    <a href="index.php?controller=Ticket&action=listar">
      <span class="material-symbols-outlined">confirmation_number</span>
      <span>Tickets</span>
    </a>
    <a class="active" aria-current="page" href="index.php?controller=Ticket&action=misTickets">
      <span class="material-symbols-outlined" aria-hidden="true">assignment_ind</span>
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

    <!-- Barra de filtros -->
    <?php
      $filters    ??= [];
      $fEstado    = $filters['estado']       ?? '';
      $fPrioridad = $filters['prioridad']    ?? '';
      $fQ         = $filters['q']            ?? '';
      $fCat       = $filters['categoria_id'] ?? '';
      $fSort      = $filters['sort']         ?? 'desc';
      $categorias ??= [];
      function mtUrl(array $extra = []): string {
        global $fEstado, $fPrioridad, $fQ, $fCat, $fSort;
        $base = ['controller'=>'Ticket','action'=>'misTickets',
                 'estado'=>$fEstado,'prioridad'=>$fPrioridad,
                 'q'=>$fQ,'categoria_id'=>$fCat,'sort'=>$fSort];
        $p = array_filter(array_merge($base, $extra), fn($v)=>$v!=='');
        return 'index.php?' . http_build_query($p);
      }
    ?>
    <div id="tk-results" style="margin-bottom:16px">
    <form method="GET" action="index.php" id="tk-filter-form">
      <input type="hidden" name="controller" value="Ticket">
      <input type="hidden" name="action"     value="misTickets">
      <?php if ($fEstado):    ?><input type="hidden" name="estado"       value="<?= htmlspecialchars($fEstado) ?>"><?php endif; ?>
      <?php if ($fPrioridad): ?><input type="hidden" name="prioridad"    value="<?= htmlspecialchars($fPrioridad) ?>"><?php endif; ?>
      <?php if ($fCat):       ?><input type="hidden" name="categoria_id" value="<?= htmlspecialchars($fCat) ?>"><?php endif; ?>
    <div class="tk-filter-bar">
      <div class="tk-filter-search">
        <span class="material-symbols-outlined">search</span>
        <input type="text" name="q" value="<?= htmlspecialchars($fQ) ?>" placeholder="Buscar tickets"
               id="tk-search-input" autocomplete="off">
      </div>
      <div class="tk-filter-group">
        <a class="tk-filter-pill<?= $fEstado===''?        ' active':'' ?>" href="<?= mtUrl() ?>">Todos</a>
        <a class="tk-filter-pill<?= $fEstado==='sin_abrir'?' active':'' ?>" href="<?= mtUrl(['estado'=>'sin_abrir']) ?>">Sin abrir</a>
        <a class="tk-filter-pill<?= $fEstado==='abierta'?  ' active':'' ?>" href="<?= mtUrl(['estado'=>'abierta']) ?>">Abiertos</a>
        <a class="tk-filter-pill<?= $fEstado==='en_proceso'?' active':'' ?>" href="<?= mtUrl(['estado'=>'en_proceso']) ?>">En proceso</a>
        <a class="tk-filter-pill<?= $fEstado==='resuelta'?  ' active':'' ?>" href="<?= mtUrl(['estado'=>'resuelta']) ?>">Resueltos</a>
        <a class="tk-filter-pill<?= $fEstado==='cerrada'?   ' active':'' ?>" href="<?= mtUrl(['estado'=>'cerrada']) ?>">Cerrados</a>
      </div>
      <div class="tk-filter-sep"></div>
      <div class="tk-filter-group">
        <a class="tk-filter-pill<?= $fPrioridad==='critica'?' active p-high':   ' p-high'   ?>" href="<?= mtUrl(['prioridad'=>'critica','estado'=>$fEstado]) ?>">Crítica</a>
        <a class="tk-filter-pill<?= $fPrioridad==='alta'?   ' active p-high':   ' p-high'   ?>" href="<?= mtUrl(['prioridad'=>'alta',   'estado'=>$fEstado]) ?>">Alta</a>
        <a class="tk-filter-pill<?= $fPrioridad==='media'?  ' active p-medium':  ' p-medium'  ?>" href="<?= mtUrl(['prioridad'=>'media',  'estado'=>$fEstado]) ?>">Media</a>
        <a class="tk-filter-pill<?= $fPrioridad==='baja'?   ' active p-low':    ' p-low'    ?>" href="<?= mtUrl(['prioridad'=>'baja',   'estado'=>$fEstado]) ?>">Baja</a>
      </div>
      <div class="tk-filter-sep"></div>
      <div class="tk-filter-group">
        <a class="tk-filter-pill<?= $fSort==='desc'?' active':'' ?>" href="<?= mtUrl(['sort'=>'desc','page'=>1]) ?>" title="Más recientes primero">
          Más reciente
        </a>
        <a class="tk-filter-pill<?= $fSort==='asc'?' active':'' ?>" href="<?= mtUrl(['sort'=>'asc','page'=>1]) ?>" title="Más antiguos primero">
          Más antiguo
        </a>
      </div>
      <div class="tk-filter-sep"></div>
      <?php if (!empty($categorias)): ?>
      <div class="tk-filter-dropdown">
        <button type="button" class="tk-dropdown-btn">
          <span><?= $fCat ? htmlspecialchars(array_column($categorias,'nombre','id')[$fCat] ?? 'Categoría') : 'Categoría' ?></span>
          <span class="material-symbols-outlined">expand_more</span>
        </button>
        <div class="tk-dropdown-menu">
          <a class="tk-dropdown-item<?= $fCat===''?' active':'' ?>" href="<?= mtUrl(['estado'=>$fEstado,'prioridad'=>$fPrioridad]) ?>">Todas</a>
          <?php foreach ($categorias as $cat): ?>
            <a class="tk-dropdown-item<?= (string)$fCat===(string)$cat['id']?' active':'' ?>"
               href="<?= mtUrl(['estado'=>$fEstado,'prioridad'=>$fPrioridad,'categoria_id'=>$cat['id']]) ?>">
              <?= htmlspecialchars($cat['nombre']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <a href="index.php?controller=Ticket&action=misTickets" class="tk-reset-btn" id="tk-reset" title="Limpiar filtros">
        <span class="material-symbols-outlined">filter_alt_off</span>
      </a>
    </div>
    </form>

    <!-- Table -->
    <div class="tk-table-wrap" style="margin-top:16px">
      <div class="tk-table-scroll">
        <table class="tk-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Título</th>
              <th>Prioridad</th>
              <th>Estado</th>
              <th>Asignado</th>
              <th>Categoría</th>
              <th>Creado</th>
              <th></th>
            </tr>
          </thead>
          <?php
            $prioCls    = ['baja'=>'low','media'=>'medium','alta'=>'high','critica'=>'critical'];
            $prioLabel  = ['baja'=>'Baja','media'=>'Media','alta'=>'Alta','critica'=>'Crítica'];
            $statusCls  = ['sin_abrir'=>'badge-sin-abrir','abierta'=>'badge-sin-abrir','en_proceso'=>'badge-en-proceso','resuelta'=>'badge-resuelta','cerrada'=>'badge-cerrada'];
            $statusLbl  = ['sin_abrir'=>'Sin abrir','abierta'=>'Abierto','en_proceso'=>'En Proceso','resuelta'=>'Resuelto','cerrada'=>'Cerrado'];
            $tickets    ??= [];
          ?>
          <tbody>
            <?php if (empty($tickets)): ?>
              <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-secondary)"><?= $isCliente ? 'No tienes tickets vinculados a tu cuenta.' : 'No tienes tickets asignados.' ?></td></tr>
            <?php else: foreach ($tickets as $t):
              $tId   = (int)$t['id'];
              $prio  = $t['prioridad'];
              $est   = $t['estado'];
              $nomAs = $t['asignado_a_nombre'] ?? '';
              $parts = $nomAs ? explode(' ', trim($nomAs)) : [];
              $inic  = $nomAs ? strtoupper(substr($parts[0],0,1).(isset($parts[1])?substr($parts[1],0,1):'')) : '';
            ?>
              <tr data-href="index.php?controller=Ticket&action=detalle&id=<?= $tId ?>"
                  data-ticket-id="#<?= $tId ?>"
                  data-status="<?= htmlspecialchars($est) ?>"
                  data-priority="<?= htmlspecialchars($prio) ?>"
                  data-assigned="<?= ((int)($t['asignado_a'] ?? 0) === (int)$user['id']) ? 'true' : 'false' ?>">
                <td><span class="tk-ticket-id">#<?= $tId ?></span></td>
                <td><div class="tk-ticket-title"><?= htmlspecialchars($t['titulo']) ?></div></td>
                <td>
                  <span class="tk-priority <?= $prioCls[$prio] ?? 'medium' ?>">
                    <span class="tk-priority-dot"></span>
                    <?= $prioLabel[$prio] ?? htmlspecialchars($prio) ?>
                  </span>
                </td>
                <td><span class="tk-status-badge <?= $statusCls[$est] ?? '' ?>"><?= $statusLbl[$est] ?? htmlspecialchars($est) ?></span></td>
                <td>
                  <?php if ($nomAs): ?>
                    <div class="tk-assignee">
                      <div class="tk-assignee-avatar"><?= htmlspecialchars($inic) ?></div>
                      <span><?= htmlspecialchars($nomAs) ?></span>
                    </div>
                  <?php else: ?>
                    <?php if ($est !== 'cerrada' && !$isCliente): ?>
                      <button class="tk-claim-btn" data-id="<?= $tId ?>" type="button">
                        <span class="material-symbols-outlined">person_add</span>
                        <span>Sin asignar</span>
                      </button>
                    <?php else: ?>
                      <span style="color:var(--text-secondary);font-size:13px">Sin asignar</span>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
                <td><span class="tk-category"><?= htmlspecialchars($t['categoria_nombre'] ?? '—') ?></span></td>
                <td><span class="tk-created"><?= htmlspecialchars(substr($t['created_at'] ?? '', 0, 10)) ?></span></td>
                <td><?php if (!$isCliente): ?><button class="tk-action-btn" data-id="<?= $tId ?>"><span class="material-symbols-outlined">more_vert</span></button><?php endif; ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php $page ??= 1; $pages ??= 1; $total ??= 0; $shown = count($tickets ?? []); ?>
      <div class="tk-pagination">
        <p class="tk-pagination-info">
          Mostrando <strong><?= $shown > 0 ? (($page-1)*20+1) : 0 ?>–<?= ($page-1)*20+$shown ?></strong>
          de <strong><?= (int)$total ?></strong> tickets
        </p>
        <div class="tk-pagination-btns">
          <?php
            $qBase = array_filter(['controller'=>'Ticket','action'=>'misTickets','estado'=>$fEstado,'prioridad'=>$fPrioridad,'q'=>$fQ,'categoria_id'=>$fCat,'sort'=>$fSort],fn($v)=>$v!=='');
          ?>
          <a class="tk-page-btn<?= $page<=1?' disabled':'' ?>"
             href="index.php?<?= http_build_query(array_merge($qBase,['page'=>$page-1])) ?>">
            <span class="material-symbols-outlined">chevron_left</span>
          </a>
          <?php for ($p=1; $p<=$pages; $p++): ?>
            <a class="tk-page-btn<?= $p===$page?' active':'' ?>"
               href="index.php?<?= http_build_query(array_merge($qBase,['page'=>$p])) ?>">
              <?= $p ?>
            </a>
          <?php endfor; ?>
          <a class="tk-page-btn<?= $page>=$pages?' disabled':'' ?>"
             href="index.php?<?= http_build_query(array_merge($qBase,['page'=>$page+1])) ?>">
            <span class="material-symbols-outlined">chevron_right</span>
          </a>
        </div>
      </div>
    </div>
    </div><!-- /tk-results -->

  </div>

<!-- Menú contextual -->
<div class="tk-ctx-menu" id="tk-ctx-menu">
  <button class="tk-ctx-item" id="tk-ctx-reopen">
    <span class="material-symbols-outlined">lock_open</span>
    <span>Abrir ticket</span>
  </button>
  <button class="tk-ctx-item" id="tk-ctx-status">
    <span class="material-symbols-outlined">sync_alt</span>
    <span>Cambiar estado</span>
    <span class="material-symbols-outlined tk-ctx-arrow">chevron_right</span>
  </button>
  <div class="tk-ctx-sub" id="tk-ctx-sub">
    <button class="tk-ctx-sub-item" data-status="sin_abrir"><span class="tk-status-badge badge-sin-abrir">Sin abrir</span></button>
    <button class="tk-ctx-sub-item" data-status="abierta"><span class="tk-status-badge badge-sin-abrir">Abierto</span></button>
    <button class="tk-ctx-sub-item" data-status="en_proceso"><span class="tk-status-badge badge-en-proceso">En Proceso</span></button>
    <button class="tk-ctx-sub-item" data-status="resuelta"><span class="tk-status-badge badge-resuelta">Resuelto</span></button>
    <button class="tk-ctx-sub-item" data-status="cerrada"><span class="tk-status-badge badge-cerrada">Cerrado</span></button>
  </div>
  <button class="tk-ctx-item" id="tk-ctx-priority">
    <span class="material-symbols-outlined">flag</span>
    <span>Cambiar prioridad</span>
    <span class="material-symbols-outlined tk-ctx-arrow">chevron_right</span>
  </button>
  <div class="tk-ctx-sub" id="tk-ctx-sub-priority">
    <button class="tk-ctx-sub-item" data-priority="critica"><span class="tk-priority critical"><span class="tk-priority-dot"></span>Crítica</span></button>
    <button class="tk-ctx-sub-item" data-priority="alta"><span class="tk-priority high"><span class="tk-priority-dot"></span>Alta</span></button>
    <button class="tk-ctx-sub-item" data-priority="media"><span class="tk-priority medium"><span class="tk-priority-dot"></span>Media</span></button>
    <button class="tk-ctx-sub-item" data-priority="baja"><span class="tk-priority low"><span class="tk-priority-dot"></span>Baja</span></button>
  </div>
  <button class="tk-ctx-item" id="tk-ctx-reassign">
    <span class="material-symbols-outlined">swap_horiz</span>
    <span>Reasignar a...</span>
  </button>
  <button class="tk-ctx-item" id="tk-ctx-copy">
    <span class="material-symbols-outlined">link</span>
    <span>Copiar enlace</span>
  </button>
  <div class="tk-ctx-sep" id="tk-ctx-sep-manage"></div>
  <button class="tk-ctx-item" id="tk-ctx-resolve">
    <span class="material-symbols-outlined">check_circle</span>
    <span>Marcar como resuelto</span>
  </button>
  <button class="tk-ctx-item" id="tk-ctx-close-ticket">
    <span class="material-symbols-outlined">lock</span>
    <span>Cerrar ticket</span>
  </button>
  <div class="tk-ctx-sep" id="tk-ctx-sep-admin"></div>
  <button class="tk-ctx-item danger" id="tk-ctx-delete">
    <span class="material-symbols-outlined">delete</span>
    <span>Eliminar</span>
  </button>
</div>

<!-- Modal reasignación -->
<div class="tk-reassign-modal" id="tk-reassign-modal">
  <div class="tk-reassign-header">
    <span>Reasignar ticket</span>
    <button id="tk-reassign-close" class="tk-reassign-close" type="button">
      <span class="material-symbols-outlined">close</span>
    </button>
  </div>
  <input type="text" id="tk-reassign-search" class="tk-reassign-search" placeholder="Buscar agente..." autocomplete="off">
  <div id="tk-reassign-list" class="tk-reassign-list"></div>
</div>

</main>
<?php require_once "views/common/msg_panel.php"; ?>


<script>
/* AJAX fetch + history */
function tkFetch(url) {
  const wrap = document.getElementById('tk-results');
  wrap.style.opacity       = '0.45';
  wrap.style.pointerEvents = 'none';
  fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.text())
    .then(html => {
      const doc        = new DOMParser().parseFromString(html, 'text/html');
      const newContent = doc.getElementById('tk-results');
      if (newContent) { wrap.innerHTML = newContent.innerHTML; history.pushState(null, '', url); }
    })
    .catch(() => { window.location.href = url; })
    .finally(() => { wrap.style.opacity = ''; wrap.style.pointerEvents = ''; });
}
window.addEventListener('popstate', () => tkFetch(location.href));

let searchTimer;
document.addEventListener('input', e => {
  const inp = e.target.closest('#tk-search-input');
  if (!inp) return;
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    const params = new URLSearchParams(new FormData(inp.closest('form')));
    tkFetch('index.php?' + params.toString());
  }, 400);
});
document.addEventListener('submit', e => {
  if (e.target.id === 'tk-filter-form') {
    e.preventDefault();
    tkFetch('index.php?' + new URLSearchParams(new FormData(e.target)).toString());
  }
});

/* Stable DOM refs */
const ctxMenu        = document.getElementById('tk-ctx-menu');
const ctxSub         = document.getElementById('tk-ctx-sub');
const ctxStatus      = document.getElementById('tk-ctx-status');
const ctxSubPriority = document.getElementById('tk-ctx-sub-priority');
const ctxPriority    = document.getElementById('tk-ctx-priority');
const reassignModal  = document.getElementById('tk-reassign-modal');
const reassignSearch = document.getElementById('tk-reassign-search');
const reassignList   = document.getElementById('tk-reassign-list');

const USER_ROLE  = '<?= htmlspecialchars($user["rol"] ?? "user") ?>';
const CSRF_TOKEN = '<?= htmlspecialchars(Csrf::generate()) ?>';
const TK_AGENTES = <?= json_encode(array_values(array_map(fn($u) => ['id' => (int)$u['id'], 'nombre' => $u['nombre']], $usuarios))) ?>;
let activeRow = null;

/* Helpers */
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function tkToast(msg, isError = false) {
  const t = document.createElement('div');
  t.className = 'tk-toast' + (isError ? ' error' : '');
  t.textContent = msg;
  document.body.appendChild(t);
  requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 2400);
}
async function tkApi(action, data) {
  const body = new URLSearchParams({ ...data, csrf_token: CSRF_TOKEN });
  const res = await fetch('index.php?controller=Ticket&action=' + action, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString()
  });
  return res.json();
}
const STATUS_CLS = { sin_abrir:'badge-sin-abrir', abierta:'badge-sin-abrir', en_proceso:'badge-en-proceso', resuelta:'badge-resuelta', cerrada:'badge-cerrada' };
const STATUS_LBL = { sin_abrir:'Sin abrir', abierta:'Abierto', en_proceso:'En Proceso', resuelta:'Resuelto', cerrada:'Cerrado' };
const PRIO_CLS   = { baja:'low', media:'medium', alta:'high', critica:'critical' };
const PRIO_LBL   = { baja:'Baja', media:'Media', alta:'Alta', critica:'Crítica' };

async function doClaimTicket(btn) {
  btn.disabled = true;
  try {
    const res = await tkApi('ajaxClaimar', { ticket_id: btn.dataset.id });
    if (res.ok) {
      const cell = btn.closest('td');
      if (cell) cell.innerHTML = '<div class="tk-assignee"><div class="tk-assignee-avatar">' + escHtml(res.inic) + '</div><span>' + escHtml(res.nombre) + '</span></div>';
      tkToast('Ticket asignado');
    } else {
      tkToast(res.msg || 'Error', true);
      btn.disabled = false;
    }
  } catch { tkToast('Error de red', true); btn.disabled = false; }
}

async function doChangeStatus(nuevoEstado) {
  if (!activeRow) return;
  const ticketId = activeRow.dataset.ticketId?.replace('#', '');
  ctxMenu.classList.remove('open');
  try {
    const res = await tkApi('ajaxCambiarEstado', { ticket_id: ticketId, estado: nuevoEstado });
    if (res.ok) {
      const badge = activeRow.querySelector('.tk-status-badge');
      if (badge) { badge.className = 'tk-status-badge ' + (STATUS_CLS[nuevoEstado] ?? ''); badge.textContent = STATUS_LBL[nuevoEstado] ?? nuevoEstado; }
      activeRow.dataset.status = nuevoEstado;
      tkToast('Estado actualizado');
    } else { tkToast(res.msg || 'Error al cambiar estado', true); }
  } catch { tkToast('Error de red', true); }
}

async function doChangePriority(nuevaPrio) {
  if (!activeRow) return;
  const ticketId = activeRow.dataset.ticketId?.replace('#', '');
  ctxMenu.classList.remove('open');
  try {
    const res = await tkApi('ajaxCambiarPrioridad', { ticket_id: ticketId, prioridad: nuevaPrio });
    if (res.ok) {
      const prioEl = activeRow.querySelector('.tk-priority');
      if (prioEl) {
        prioEl.className = 'tk-priority ' + (PRIO_CLS[nuevaPrio] ?? 'medium');
        prioEl.innerHTML = '<span class="tk-priority-dot"></span>' + (PRIO_LBL[nuevaPrio] ?? nuevaPrio);
      }
      activeRow.dataset.priority = nuevaPrio;
      tkToast('Prioridad actualizada');
    } else { tkToast(res.msg || 'Error al cambiar prioridad', true); }
  } catch { tkToast('Error de red', true); }
}

/* Click delegation */
document.addEventListener('click', e => {
  const pill = e.target.closest('.tk-filter-pill[href]');
  if (pill && !pill.classList.contains('disabled')) { e.preventDefault(); tkFetch(pill.href); return; }

  const dropBtn = e.target.closest('.tk-dropdown-btn');
  if (dropBtn) { e.stopPropagation(); dropBtn.closest('.tk-filter-dropdown')?.classList.toggle('open'); return; }

  const dropItem = e.target.closest('.tk-dropdown-item[href]');
  if (dropItem) { e.preventDefault(); tkFetch(dropItem.href); return; }

  const pageBtn = e.target.closest('.tk-page-btn[href]:not(.disabled)');
  if (pageBtn) { e.preventDefault(); tkFetch(pageBtn.href); return; }

  const dropdown = document.querySelector('.tk-filter-dropdown');
  if (dropdown && !dropdown.contains(e.target)) dropdown.classList.remove('open');

  const row = e.target.closest('.tk-table tbody tr');
  if (row && !e.target.closest('.tk-action-btn') && !e.target.closest('.tk-claim-btn')) {
    if (row.dataset.href) window.location.href = row.dataset.href;
    return;
  }

  const claimBtn = e.target.closest('.tk-claim-btn');
  if (claimBtn) { e.stopPropagation(); doClaimTicket(claimBtn); return; }

  const actionBtn = e.target.closest('.tk-action-btn');
  if (actionBtn) {
    e.stopPropagation();
    const tr = actionBtn.closest('tr');
    if (ctxMenu.classList.contains('open') && activeRow === tr) { ctxMenu.classList.remove('open'); }
    else { openCtxMenu(actionBtn, tr); }
    return;
  }

  if (!ctxMenu.contains(e.target)) ctxMenu.classList.remove('open');
  if (!reassignModal.contains(e.target)) reassignModal.classList.remove('open');
});

/* Context menu */
function applyMenuVisibility(isAssigned, status) {
  const isAdmin   = USER_ROLE === 'admin';
  const canManage = isAdmin || isAssigned;
  const isClosed  = status === 'cerrada';
  document.getElementById('tk-ctx-reopen').style.display       = (isClosed && canManage)  ? '' : 'none';
  ctxStatus.style.display                                       = (!isClosed && canManage) ? '' : 'none';
  ctxPriority.style.display                                     = (!isClosed && canManage) ? '' : 'none';
  document.getElementById('tk-ctx-reassign').style.display     = (!isClosed && isAdmin)   ? '' : 'none';
  document.getElementById('tk-ctx-sep-manage').style.display   = (!isClosed && canManage) ? '' : 'none';
  document.getElementById('tk-ctx-resolve').style.display      = (!isClosed && canManage) ? '' : 'none';
  document.getElementById('tk-ctx-close-ticket').style.display = (!isClosed && canManage) ? '' : 'none';
  document.getElementById('tk-ctx-sep-admin').style.display    = isAdmin ? '' : 'none';
  document.getElementById('tk-ctx-delete').style.display       = isAdmin ? '' : 'none';
}
function openCtxMenu(btn, row) {
  activeRow = row;
  ctxSub.classList.remove('open');
  ctxSubPriority.classList.remove('open');
  ctxStatus.querySelector('.tk-ctx-arrow').style.transform = '';
  ctxPriority.querySelector('.tk-ctx-arrow').style.transform = '';
  applyMenuVisibility(row.dataset.assigned === 'true', row.dataset.status);
  ctxMenu.classList.add('open');
  const rect  = btn.getBoundingClientRect();
  const menuW = ctxMenu.offsetWidth || 195;
  const menuH = ctxMenu.offsetHeight;
  let top  = rect.bottom + 6;
  let left = rect.right - menuW;
  if (top + menuH > window.innerHeight - 10) top = rect.top - menuH - 6;
  if (left < 10) left = 10;
  ctxMenu.style.top  = top + 'px';
  ctxMenu.style.left = left + 'px';
}
ctxStatus.addEventListener('click', () => {
  ctxSubPriority.classList.remove('open');
  ctxPriority.querySelector('.tk-ctx-arrow').style.transform = '';
  ctxSub.classList.toggle('open');
  ctxStatus.querySelector('.tk-ctx-arrow').style.transform = ctxSub.classList.contains('open') ? 'rotate(90deg)' : '';
});
ctxPriority.addEventListener('click', () => {
  ctxSub.classList.remove('open');
  ctxStatus.querySelector('.tk-ctx-arrow').style.transform = '';
  ctxSubPriority.classList.toggle('open');
  ctxPriority.querySelector('.tk-ctx-arrow').style.transform = ctxSubPriority.classList.contains('open') ? 'rotate(90deg)' : '';
});

/* Sub-status items */
document.querySelectorAll('.tk-ctx-sub-item[data-status]').forEach(btn =>
  btn.addEventListener('click', () => doChangeStatus(btn.dataset.status))
);
document.querySelectorAll('.tk-ctx-sub-item[data-priority]').forEach(btn =>
  btn.addEventListener('click', () => doChangePriority(btn.dataset.priority))
);

/* Copy link */
document.getElementById('tk-ctx-copy').addEventListener('click', () => {
  if (!activeRow) return;
  const id  = activeRow.dataset.ticketId?.replace('#', '') ?? '';
  const url = location.origin + location.pathname + '?controller=Ticket&action=detalle&id=' + id;
  navigator.clipboard.writeText(url).then(() => {
    const span = document.querySelector('#tk-ctx-copy span:last-child');
    span.textContent = '¡Copiado!';
    setTimeout(() => { span.textContent = 'Copiar enlace'; }, 1500);
  });
  ctxMenu.classList.remove('open');
});

/* Status action buttons */
document.getElementById('tk-ctx-reopen').addEventListener('click',       () => doChangeStatus('abierta'));
document.getElementById('tk-ctx-resolve').addEventListener('click',      () => doChangeStatus('resuelta'));
document.getElementById('tk-ctx-close-ticket').addEventListener('click', () => doChangeStatus('cerrada'));

/* Delete */
document.getElementById('tk-ctx-delete').addEventListener('click', async () => {
  if (!activeRow) return;
  const label    = activeRow.dataset.ticketId ?? '';
  const ticketId = label.replace('#', '');
  if (!confirm('¿Eliminar ' + label + '? Esta acción no se puede deshacer.')) return;
  ctxMenu.classList.remove('open');
  try {
    const res = await tkApi('ajaxEliminar', { ticket_id: ticketId });
    if (res.ok) {
      activeRow.style.transition = 'opacity .25s';
      activeRow.style.opacity = '0';
      setTimeout(() => activeRow?.remove(), 260);
      tkToast('Ticket eliminado');
    } else { tkToast(res.msg || 'Error al eliminar', true); }
  } catch { tkToast('Error de red', true); }
});

/* Reassign modal */
function renderReassignList(query = '') {
  const q = query.toLowerCase();
  reassignList.innerHTML = '';

  const unBtn = document.createElement('button');
  unBtn.type = 'button'; unBtn.className = 'tk-reassign-user';
  unBtn.innerHTML = '<div class="tk-reassign-user-avatar unassign"><span class="material-symbols-outlined">person_off</span></div><span class="tk-reassign-user-name">Sin asignar</span>';
  unBtn.addEventListener('click', () => doReassign(null));
  reassignList.appendChild(unBtn);

  const sep = document.createElement('div');
  sep.className = 'tk-ctx-sep'; sep.style.margin = '4px 2px';
  reassignList.appendChild(sep);

  TK_AGENTES.filter(a => !q || a.nombre.toLowerCase().includes(q)).forEach(a => {
    const btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'tk-reassign-user';
    const parts = a.nombre.trim().split(' ');
    const inic  = parts[0].charAt(0).toUpperCase() + (parts[1] ? parts[1].charAt(0).toUpperCase() : '');
    btn.innerHTML = '<div class="tk-reassign-user-avatar">' + escHtml(inic) + '</div><span class="tk-reassign-user-name">' + escHtml(a.nombre) + '</span>';
    btn.addEventListener('click', () => doReassign(a.id));
    reassignList.appendChild(btn);
  });
}

function openReassignModal() {
  const rect = ctxMenu.getBoundingClientRect();
  reassignSearch.value = '';
  renderReassignList();
  reassignModal.classList.add('open');
  const mH = reassignModal.offsetHeight || 280;
  const mW = reassignModal.offsetWidth  || 220;
  let t = rect.top, l = rect.left;
  if (t + mH > window.innerHeight - 10) t = window.innerHeight - mH - 10;
  if (l + mW > window.innerWidth  - 10) l = window.innerWidth  - mW - 10;
  if (t < 10) t = 10; if (l < 10) l = 10;
  reassignModal.style.top = t + 'px'; reassignModal.style.left = l + 'px';
}

async function doReassign(assigneeId) {
  if (!activeRow) return;
  const ticketId = activeRow.dataset.ticketId?.replace('#', '');
  reassignModal.classList.remove('open');
  try {
    const res = await tkApi('ajaxReasignar', { ticket_id: ticketId, asignado_a: assigneeId ?? '' });
    if (res.ok) {
      const agente = assigneeId != null ? TK_AGENTES.find(a => a.id === assigneeId) ?? null : null;
      const cell   = activeRow.querySelectorAll('td')[4];
      if (cell) {
        if (agente) {
          const parts = agente.nombre.trim().split(' ');
          const inic  = parts[0].charAt(0).toUpperCase() + (parts[1] ? parts[1].charAt(0).toUpperCase() : '');
          cell.innerHTML = '<div class="tk-assignee"><div class="tk-assignee-avatar">' + escHtml(inic) + '</div><span>' + escHtml(agente.nombre) + '</span></div>';
        } else {
          const isClosed = activeRow.dataset.status === 'cerrada';
          const tId = activeRow.dataset.ticketId?.replace('#', '') ?? '';
          cell.innerHTML = isClosed
            ? '<span style="color:var(--text-secondary);font-size:13px">Sin asignar</span>'
            : '<button class="tk-claim-btn" data-id="' + tId + '" type="button"><span class="material-symbols-outlined">person_add</span><span>Sin asignar</span></button>';
        }
      }
      activeRow.dataset.asignadoId = assigneeId ?? '';
      tkToast('Reasignado correctamente');
    } else { tkToast(res.msg || 'Error al reasignar', true); }
  } catch { tkToast('Error de red', true); }
}

document.getElementById('tk-ctx-reassign').addEventListener('click', e => {
  e.stopPropagation();
  openReassignModal();
  ctxMenu.classList.remove('open');
});
document.getElementById('tk-reassign-close').addEventListener('click', () => reassignModal.classList.remove('open'));
reassignSearch.addEventListener('input', () => renderReassignList(reassignSearch.value));
</script>
<?php require_once "views/common/pie.php"; ?>

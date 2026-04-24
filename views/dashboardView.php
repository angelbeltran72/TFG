<?php
$user  ??= $_SESSION["usuario"] ?? ["nombre" => "Usuario", "rol" => "user"];
$cargo = ($user["rol"] ?? "user") === "admin" ? "Administrador" : "Agente de soporte";

$summary      ??= [];
$trendByRange ??= ["7" => [], "30" => []];
$priorities   ??= [];
$recentEvents ??= [];
$categories   ??= [];
$range        ??= 7;
$nav          ??= ["canKanban" => true, "canConfig" => false, "canUsers" => false];

$range = in_array((int)$range, [7, 30], true) ? (int)$range : 7;
$trend = $trendByRange[(string)$range] ?? [];
$trendByRangeJson = json_encode($trendByRange, JSON_UNESCAPED_UNICODE);

$priorityMap = [];
foreach ($priorities as $p) {
  $priorityMap[$p["key"]] = $p;
}
$priorityOrder = ["critica", "alta", "media", "baja"];

function db_time_ago(?string $date): string {
  if (!$date) return "Ahora mismo";
  $ts = strtotime($date);
  if (!$ts) return "Ahora mismo";
  $diff = time() - $ts;
  if ($diff < 60) return "Ahora mismo";
  if ($diff < 3600) return "Hace " . floor($diff / 60) . " min";
  if ($diff < 86400) return "Hace " . floor($diff / 3600) . " h";
  return "Hace " . floor($diff / 86400) . " d";
}

function db_event_meta(array $e): array {
  $actor = trim((string)($e["actor_nombre"] ?? ""));
  $actor = $actor !== "" ? $actor : "Alguien";
  $ticketId = (int)($e["ticket_id"] ?? 0);
  $ticketTitle = (string)($e["titulo"] ?? "");
  $eventType = trim((string)($e["event_type"] ?? ""));
  $content = trim((string)($e["contenido"] ?? ""));
  $isInternal = !empty($e["is_internal"]);

  if ($eventType === "state_change") {
    [$old, $new] = array_pad(explode("|", $content, 2), 2, "");
    $stateLabel = [
      "sin_abrir" => "Sin abrir",
      "abierta" => "Abierta",
      "en_proceso" => "En proceso",
      "resuelta" => "Resuelta",
      "cerrada" => "Cerrada",
    ];
    $to = $stateLabel[$new] ?? ($new !== "" ? $new : "nuevo estado");
    return [
      "icon" => "swap_horiz",
      "class" => "secondary",
      "text" => $actor . " cambió el estado del ticket #" . $ticketId . " a " . $to,
      "quote" => "",
      "internal" => false,
      "ticket_title" => $ticketTitle,
    ];
  }

  if ($eventType === "priority_change") {
    [$old, $new] = array_pad(explode("|", $content, 2), 2, "");
    $prioLabel = [
      "baja" => "Baja",
      "media" => "Media",
      "alta" => "Alta",
      "critica" => "Crítica",
    ];
    $to = $prioLabel[$new] ?? ($new !== "" ? $new : "nueva");
    return [
      "icon" => "flag",
      "class" => "warning",
      "text" => $actor . " cambió la prioridad del ticket #" . $ticketId . " a " . $to,
      "quote" => "",
      "internal" => false,
      "ticket_title" => $ticketTitle,
    ];
  }

  if ($eventType === "assignment") {
    $who = $content !== "" ? $content : "un agente";
    return [
      "icon" => "assignment_ind",
      "class" => "primary",
      "text" => $actor . " asignó el ticket #" . $ticketId . " a " . $who,
      "quote" => "",
      "internal" => false,
      "ticket_title" => $ticketTitle,
    ];
  }

  return [
    "icon" => "comment",
    "class" => $isInternal ? "secondary" : "primary",
    "text" => $actor . " comentó en el ticket #" . $ticketId,
    "quote" => $content,
    "internal" => $isInternal,
    "ticket_title" => $ticketTitle,
  ];
}
?>
<?php $__pageTitle = "Dashboard"; ?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/dashboard.css">

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
    <a class="active" aria-current="page" href="index.php?controller=Dashboard&action=index">
      <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
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
    <?php if (!empty($nav["canKanban"])): ?>
      <a href="index.php?controller=Kanban&action=index">
        <span class="material-symbols-outlined">view_kanban</span>
        <span>Kanban Board</span>
      </a>
    <?php endif; ?>
    <a href="index.php?controller=Ticket&action=nuevo">
      <span class="material-symbols-outlined">add_circle</span>
      <span>Nueva Incidencia</span>
    </a>
    <?php if (!empty($nav["canUsers"])): ?>
      <a href="index.php?controller=Perfil&action=listarUsuarios">
        <span class="material-symbols-outlined">group</span>
        <span>Usuarios</span>
      </a>
    <?php endif; ?>
    <a href="index.php?controller=Perfil&action=verPerfil">
      <span class="material-symbols-outlined">person</span>
      <span>Mi Perfil</span>
    </a>
    <?php if (!empty($nav["canConfig"])): ?>
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

    <!-- KPI Cards -->
    <div class="db-kpi-grid">
      <div class="db-kpi-card">
        <div class="db-kpi-top">
          <span class="db-kpi-icon primary"><span class="material-symbols-outlined">mark_email_unread</span></span>
          <span class="db-kpi-badge primary">NUEVO</span>
        </div>
        <div>
          <span class="db-kpi-value"><?= (int)($summary["sin_abrir"] ?? 0) ?></span>
          <span class="db-kpi-label">Tickets sin abrir</span>
        </div>
      </div>

      <div class="db-kpi-card border-secondary">
        <div class="db-kpi-top">
          <span class="db-kpi-icon secondary"><span class="material-symbols-outlined">inbox</span></span>
          <span class="db-kpi-badge neutral">ABIERTO</span>
        </div>
        <div>
          <span class="db-kpi-value"><?= (int)($summary["abiertas"] ?? 0) ?></span>
          <span class="db-kpi-label">Tickets abiertos</span>
        </div>
      </div>

      <div class="db-kpi-card border-secondary">
        <div class="db-kpi-top">
          <span class="db-kpi-icon secondary"><span class="material-symbols-outlined">pending</span></span>
          <span class="db-kpi-badge neutral">ACTIVO</span>
        </div>
        <div>
          <span class="db-kpi-value"><?= (int)($summary["en_proceso"] ?? 0) ?></span>
          <span class="db-kpi-label">Tickets en proceso</span>
        </div>
      </div>

      <div class="db-kpi-card border-success">
        <div class="db-kpi-top">
          <span class="db-kpi-icon success"><span class="material-symbols-outlined">check_circle</span></span>
          <span class="db-kpi-badge success">HOY</span>
        </div>
        <div>
          <span class="db-kpi-value"><?= (int)($summary["resueltas"] ?? 0) ?></span>
          <span class="db-kpi-label">Tickets resueltos</span>
        </div>
      </div>

      <div class="db-kpi-card border-error">
        <div class="db-kpi-top">
          <span class="db-kpi-icon error"><span class="material-symbols-outlined">error</span></span>
          <span class="db-kpi-badge error">PENDIENTE</span>
        </div>
        <div>
          <span class="db-kpi-value"><?= (int)($summary["sin_resolver"] ?? 0) ?></span>
          <span class="db-kpi-label">Tickets sin resolver</span>
        </div>
      </div>
    </div>

    <!-- Fila de gráficos -->
    <div class="db-charts-row">
      <div class="db-card">
        <div class="db-card-header">
          <div>
            <p class="db-card-title">Tendencia de volumen de tickets</p>
            <p class="db-card-subtitle">Volumen diario dentro del alcance visible</p>
          </div>
          <div class="db-chart-filters">
            <button type="button" class="db-chart-filter <?= (int)$range === 7 ? "active" : "" ?>" data-range="7">Últimos 7 días</button>
            <button type="button" class="db-chart-filter <?= (int)$range === 30 ? "active" : "" ?>" data-range="30">Últimos 30 días</button>
          </div>
        </div>
        <div class="db-chart-area">
          <div class="db-chart-svg-wrap">
            <div class="db-chart-svg-inner">
              <svg viewBox="0 0 1000 200" preserveAspectRatio="none" id="db-trend-svg">
                <defs>
                  <linearGradient id="db-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" style="stop-color:#4648d4;stop-opacity:1"></stop>
                    <stop offset="100%" style="stop-color:#4648d4;stop-opacity:0"></stop>
                  </linearGradient>
                </defs>
                <path id="db-trend-area" fill="url(#db-gradient)" opacity="0.13"></path>
                <path id="db-trend-line" fill="none" stroke="#4648d4" stroke-width="3" stroke-linecap="round"></path>
                <g id="db-trend-points"></g>
              </svg>
            </div>
          </div>
          <div class="db-chart-gridline"></div>
          <div class="db-chart-gridline"></div>
          <div class="db-chart-gridline"></div>
          <div class="db-chart-gridline"></div>
          <div class="db-chart-xaxis" id="db-chart-xaxis"></div>
        </div>
      </div>

      <div class="db-card db-priority-card">
        <div class="db-card-header" style="margin-bottom:0">
          <div>
            <p class="db-card-title">Prioridad de Tickets</p>
            <p class="db-card-subtitle">Distribución de la cola activa</p>
          </div>
        </div>
        <div style="margin-top:18px; flex:1; display:flex; flex-direction:column; justify-content:space-between;">
          <div class="db-priority-list">
            <?php foreach ($priorityOrder as $prioKey):
              $row = $priorityMap[$prioKey] ?? ["label" => ucfirst($prioKey), "count" => 0, "pct" => 0];
            ?>
            <div>
              <div class="db-priority-row">
                <span class="etiqueta"><?= htmlspecialchars((string)$row["label"]) ?></span>
                <span class="count-<?= htmlspecialchars((string)$prioKey) ?>"><?= (int)$row["count"] ?></span>
              </div>
              <div class="db-priority-track">
                <div class="db-priority-fill <?= htmlspecialchars((string)$prioKey) ?>" style="width:<?= (float)$row["pct"] ?>%"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <a href="index.php?controller=Ticket&action=listar" class="db-card-link">
            Ver todos los tickets
            <span class="material-symbols-outlined" style="font-size:16px">arrow_forward</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Fila inferior -->
    <div class="db-bottom-row">
      <div class="db-card">
        <div class="db-feed-header">
          <p class="db-card-title">Actividad Reciente</p>
          <a href="index.php?controller=Ticket&action=listar" class="db-btn-text">Ver tickets</a>
        </div>
        <div class="db-feed-list">
          <?php if (empty($recentEvents)): ?>
            <p class="db-empty">No hay actividad reciente visible.</p>
          <?php else: ?>
            <?php foreach ($recentEvents as $ev):
              $meta = db_event_meta($ev);
              $actorName = trim((string)($ev["actor_nombre"] ?? ""));
              $initial = strtoupper(substr($actorName !== "" ? $actorName : "N", 0, 1));
            ?>
            <div class="db-feed-item">
              <div class="db-feed-avatar">
                <div class="db-feed-avatar-circle <?= htmlspecialchars($meta["class"]) ?>">
                  <?= htmlspecialchars($initial) ?>
                </div>
              </div>
              <div class="db-feed-body">
                <p class="db-feed-text">
                  <a href="index.php?controller=Ticket&action=detalle&id=<?= (int)$ev["ticket_id"] ?>" class="negrita db-feed-link">
                    <?= htmlspecialchars($meta["text"]) ?>
                  </a>
                </p>
                <?php if (!empty($meta["quote"])): ?>
                  <p class="db-feed-quote">"<?= htmlspecialchars((string)$meta["quote"]) ?>"</p>
                <?php endif; ?>
                <p class="db-feed-time">
                  <?= htmlspecialchars(db_time_ago((string)($ev["created_at"] ?? null))) ?>
                  <?php if (!empty($meta["internal"])): ?>
                    · Nota interna
                  <?php endif; ?>
                </p>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="db-card">
        <p class="db-card-title" style="margin-bottom:24px">Principales Categorías</p>
        <?php if (empty($categories)): ?>
          <p class="db-empty">No hay categorías con tickets visibles.</p>
        <?php else: ?>
        <div class="db-cat-grid">
          <?php foreach ($categories as $cat): ?>
          <div class="db-cat-card">
            <div class="db-cat-top">
              <span class="material-symbols-outlined" style="color:<?= htmlspecialchars((string)($cat["color"] ?? "#64748b")) ?>">sell</span>
              <span class="db-cat-name"><?= htmlspecialchars((string)$cat["nombre"]) ?></span>
            </div>
            <div class="db-cat-bottom">
              <span class="db-cat-count"><?= (int)$cat["count"] ?></span>
              <span class="db-cat-pct" style="color:<?= htmlspecialchars((string)($cat["color"] ?? "#64748b")) ?>"><?= number_format((float)$cat["pct"], 1) ?>%</span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php require_once "views/common/msg_panel.php"; ?>
<?php require_once "views/common/pie.php"; ?>

<script>
(() => {
  const trendByRange = <?= $trendByRangeJson ?: "{\"7\":[],\"30\":[]}" ?>;
  let activeRange = <?= (int)$range ?>;
  const xAxis = document.getElementById("db-chart-xaxis");
  const linePath = document.getElementById("db-trend-line");
  const areaPath = document.getElementById("db-trend-area");
  const points = document.getElementById("db-trend-points");
  const filterButtons = document.querySelectorAll(".db-chart-filter[data-range]");

  if (!xAxis || !linePath || !areaPath || !points) return;

  const renderTrend = (series) => {
    const rows = Array.isArray(series) ? series : [];
    xAxis.innerHTML = "";
    points.innerHTML = "";

    if (rows.length === 0) {
      linePath.setAttribute("d", "");
      areaPath.setAttribute("d", "");
      return;
    }

    const values = rows.map(item => Number(item.count || 0));
    const max = Math.max(...values, 0);
    const safeMax = max > 0 ? max : 1;
    const w = 1000;
    const minY = 36;
    const maxY = 182;
    const stepX = rows.length > 1 ? w / (rows.length - 1) : w;

    const linePts = [];
    rows.forEach((item, idx) => {
      const x = rows.length > 1 ? idx * stepX : w / 2;
      const y = maxY - ((Number(item.count || 0) / safeMax) * (maxY - minY));
      linePts.push([x, y]);

      const label = document.createElement("span");
      label.textContent = item.shortLabel || item.label || "";
      xAxis.appendChild(label);
    });

    const lineD = linePts.map((pt, i) => `${i === 0 ? "M" : "L"}${pt[0]},${pt[1]}`).join(" ");
    const areaD = `${lineD} L${linePts[linePts.length - 1][0]},200 L${linePts[0][0]},200 Z`;

    linePath.setAttribute("d", lineD);
    areaPath.setAttribute("d", areaD);

    linePts.forEach((pt) => {
      const c = document.createElementNS("http://www.w3.org/2000/svg", "circle");
      c.setAttribute("cx", String(pt[0]));
      c.setAttribute("cy", String(pt[1]));
      c.setAttribute("r", "4");
      c.setAttribute("fill", "#4648d4");
      c.setAttribute("stroke", "white");
      c.setAttribute("stroke-width", "2");
      points.appendChild(c);
    });
  };

  const setRange = (range, syncUrl = true) => {
    const key = String(range);
    activeRange = range === 30 ? 30 : 7;
    filterButtons.forEach(btn => {
      btn.classList.toggle("active", Number(btn.dataset.range) === activeRange);
    });
    renderTrend(trendByRange[key] || []);

    if (!syncUrl) return;
    const url = new URL(window.location.href);
    url.searchParams.set("range", String(activeRange));
    window.history.replaceState({}, "", url.toString());
  };

  filterButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      const next = Number(btn.dataset.range);
      if (next === activeRange) return;
      setRange(next, true);
    });
  });

  setRange(activeRange, false);
})();
</script>

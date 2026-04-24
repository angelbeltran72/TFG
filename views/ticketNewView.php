<?php
$user          ??= $_SESSION["usuario"] ?? ["nombre" => "Usuario", "rol" => "user"];
$departamentos ??= [];
$isAdmin       ??= false;
$cargo           = ($user["rol"] ?? "user") === "admin" ? "Administrador" : "Agente de soporte";
$__pageTitle     = "Nueva incidencia";
?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/ticketNew.css">

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
    <a class="active" aria-current="page" href="index.php?controller=Ticket&action=nuevo">
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

  <!-- Content Canvas -->
  <div class="app-canvas">

    <!-- Form -->
    <!-- Banner de recuperación de borrador (oculto hasta detectar borrador en localStorage) -->
    <div id="tn-recovery" class="tn-recovery" style="display:none">
      <span class="material-symbols-outlined">restore_page</span>
      <p class="tn-recovery-text">Tienes un borrador guardado. ¿Quieres recuperarlo?</p>
      <div class="tn-recovery-actions">
        <button type="button" id="tn-recovery-restore" class="tn-recovery-restore">Recuperar</button>
        <button type="button" id="tn-recovery-dismiss" class="tn-recovery-dismiss">Descartar</button>
      </div>
    </div>

    <form id="tn-form" method="POST" action="index.php?controller=Ticket&action=crear">
      <?= Csrf::field() ?>

      <div class="tn-grid">

        <!-- Columna principal -->
        <div class="tn-col-primary">
          <div class="tn-card">

            <div class="tn-field">
              <label class="tn-label" for="tn-titulo">Título de la Incidencia</label>
              <input id="tn-titulo" class="tn-input" type="text" name="titulo"
                     placeholder="Resumen breve del problema..." autocomplete="off">
            </div>

            <div class="tn-field tn-field-grow">
              <label class="tn-label" for="tn-desc">Descripción</label>
              <textarea id="tn-desc" class="tn-textarea" name="descripcion" rows="8"
                        placeholder="Describe los pasos para reproducir el error, el comportamiento esperado y el resultado real..."></textarea>
            </div>

            <div class="tn-dropzone" id="tn-dropzone">
              <div class="tn-dropzone-icon">
                <span class="material-symbols-outlined">cloud_upload</span>
              </div>
              <div class="tn-dropzone-body">
                <p class="tn-dropzone-title">Adjuntar archivos</p>
                <p class="tn-dropzone-subtitle" id="tn-dropzone-subtitle">Imágenes · Docs · Texto · Comprimidos · máx. 10 archivos · 50 MB c/u</p>
              </div>
              <button type="button" class="tn-dropzone-btn" id="tn-upload-btn">Seleccionar</button>
              <input type="file" id="tn-file-input" name="adjuntos[]" multiple
                     accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.txt,.log,.zip,.rar"
                     style="display:none">
            </div>
            <div class="tn-file-list" id="tn-file-list"></div>

          </div>
        </div>

        <!-- Columna secundaria -->
        <div class="tn-col-secondary">
          <div class="tn-card-muted">

            <?php if (empty($departamentos)): ?>
            <div class="tn-field">
              <p style="color:var(--danger,#ef4444);font-size:.875rem;margin:0">No tienes departamentos asignados. Contacta con un administrador.</p>
            </div>
            <?php else: ?>
            <div class="tn-field">
              <label class="tn-label" for="tn-dept">Departamento</label>
              <select id="tn-dept" class="tn-select" name="departamento_id" required>
                <option value="">Selecciona un departamento...</option>
                <?php foreach ($departamentos as $dep): ?>
                  <option value="<?= (int)$dep['id'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>

            <div class="tn-field" id="tn-categoria-field">
              <label class="tn-label" for="tn-categoria">Categoría</label>
              <select id="tn-categoria" class="tn-select" name="categoria_id" required>
                <option value="">Selecciona una categoría...</option>
                <?php foreach ($categorias as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="tn-field">
              <label class="tn-label" for="tn-asignado">Asignar a</label>
              <select id="tn-asignado" class="tn-select" name="asignado_a">
                <option value="">Sin asignar</option>
                <?php foreach ($usuarios as $u): ?>
                  <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php if ($canAddCliente): ?>
            <?php
              $oldExterno = $_SESSION['old']['es_cliente_externo'] ?? '0';
              $oldClienteEmail = htmlspecialchars($_SESSION['old']['cliente_email'] ?? '');
            ?>
            <div class="tn-field">
              <label class="tn-label tn-label-check">
                <input type="checkbox" id="tn-es-cliente-externo" name="es_cliente_externo" value="1"
                       <?= $oldExterno === '1' ? 'checked' : '' ?>>
                <span>Ticket de cliente externo</span>
              </label>
            </div>
            <div class="tn-field" id="tn-cliente-email-field" style="display:<?= $oldExterno === '1' ? 'block' : 'none' ?>">
              <label class="tn-label" for="tn-cliente-email">Email del cliente <span class="tn-label-required">*</span></label>
              <input id="tn-cliente-email" class="tn-input" type="email" name="cliente_email"
                     value="<?= $oldClienteEmail ?>"
                     placeholder="cliente@empresa.com" autocomplete="off">
              <?php if (!empty($_SESSION['errors']['cliente_email'])): ?>
                <p class="tn-field-error"><?= htmlspecialchars($_SESSION['errors']['cliente_email']) ?></p>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="tn-field">
              <label class="tn-label" for="tn-due">Fecha límite <span class="tn-label-optional">(opcional)</span></label>
              <input id="tn-due" class="tn-input" type="date" name="due_date">
            </div>

            <div class="tn-field">
              <label class="tn-label">Prioridad</label>
              <div class="tn-priority-list">
                <label class="tn-priority-label p-critical">
                  <input type="radio" name="prioridad" value="critica">
                  <span class="tn-priority-text">Crítica</span>
                </label>
                <label class="tn-priority-label p-high">
                  <input type="radio" name="prioridad" value="alta" checked>
                  <span class="tn-priority-text">Alta</span>
                </label>
                <label class="tn-priority-label p-medium">
                  <input type="radio" name="prioridad" value="media">
                  <span class="tn-priority-text">Media</span>
                </label>
                <label class="tn-priority-label p-low">
                  <input type="radio" name="prioridad" value="baja">
                  <span class="tn-priority-text">Baja</span>
                </label>
              </div>
            </div>

            <div class="tn-actions">
              <button type="submit" class="tn-btn-submit">Crear Incidencia</button>
            </div>

          </div>
        </div>

      </div>
    </form>

  </div>
</main>
<?php require_once "views/common/msg_panel.php"; ?>

<script>
(function () {
  var KEY       = 'vc_ticketDraft';
  var FIELDS    = ['titulo', 'descripcion', 'departamento_id', 'categoria_id', 'asignado_a', 'due_date'];
  var form      = document.getElementById('tn-form');
  var banner    = document.getElementById('tn-recovery');
  var submitted = false;
  var t;

  function save() {
    if (submitted) return;
    var d = {};
    FIELDS.forEach(function (f) {
      var el = form.elements[f];
      d[f] = el ? el.value : '';
    });
    var r = form.querySelector('[name="prioridad"]:checked');
    d.prioridad = r ? r.value : '';
    d._ts = Date.now();
    try { localStorage.setItem(KEY, JSON.stringify(d)); } catch (e) {}
  }

  function restore() {
    var raw = localStorage.getItem(KEY);
    if (!raw) return;
    var d = JSON.parse(raw);
    ['titulo', 'descripcion', 'asignado_a', 'due_date'].forEach(function (f) {
      var el = form.elements[f];
      if (el) el.value = d[f] || '';
    });
    var deptEl = form.elements['departamento_id'];
    if (deptEl && d['departamento_id']) {
      deptEl.value = d['departamento_id'];
      deptEl.dispatchEvent(new Event('change'));
    }
    if (d['categoria_id']) {
      var catEl = form.elements['categoria_id'];
      if (catEl) catEl.value = d['categoria_id'];
    }
    if (d.prioridad) {
      var r = form.querySelector('[name="prioridad"][value="' + d.prioridad + '"]');
      if (r) r.checked = true;
    }
    banner.style.display = 'none';
  }

  function discard() {
    submitted = true;
    clearTimeout(t);
    try { localStorage.removeItem(KEY); } catch (e) {}
    banner.style.display = 'none';
  }

  // Muestra el banner si hay un borrador guardado no vacío y con menos de 7 días
  (function checkOnLoad() {
    try {
      var raw = localStorage.getItem(KEY);
      if (!raw) return;
      var d = JSON.parse(raw);
      if (Date.now() - (d._ts || 0) > 604800000) { discard(); return; }
      var hasData = FIELDS.some(function (f) { return d[f] && String(d[f]).trim(); });
      if (hasData) banner.style.display = 'flex';
    } catch (e) {}
  })();

  // Autoguardado al escribir (con retardo de 600 ms para no saturar)
  form.addEventListener('input',  function () { clearTimeout(t); t = setTimeout(save, 600); });
  form.addEventListener('change', save);
  // Borra el borrador al enviar el formulario; el flag submitted bloquea cualquier save posterior
  form.addEventListener('submit', discard);

  document.getElementById('tn-recovery-restore').addEventListener('click', restore);
  document.getElementById('tn-recovery-dismiss').addEventListener('click', discard);
})();

// Cliente externo toggle
(function () {
  var chk   = document.getElementById('tn-es-cliente-externo');
  var field = document.getElementById('tn-cliente-email-field');
  var inp   = document.getElementById('tn-cliente-email');
  if (!chk || !field) return;
  chk.addEventListener('change', function () {
    field.style.display = this.checked ? 'block' : 'none';
    if (!this.checked && inp) inp.value = '';
  });
})();

// Department → Category filtering
(function () {
  var ALL_CATS = <?= json_encode(array_values($categorias), JSON_HEX_TAG) ?>;
  var deptSel  = document.getElementById('tn-dept');
  var catSel   = document.getElementById('tn-categoria');

  if (!deptSel) return;

  function populate(deptId) {
    var prev = catSel.value;
    var list = deptId
      ? ALL_CATS.filter(function (c) {
          return c.departamento_id === null || parseInt(c.departamento_id, 10) === parseInt(deptId, 10);
        })
      : ALL_CATS;
    if (!list.length) list = ALL_CATS;
    catSel.innerHTML = '<option value="">Selecciona una categoría...</option>';
    list.forEach(function (c) {
      var o = document.createElement('option');
      o.value = c.id;
      o.textContent = c.nombre;
      if (String(c.id) === String(prev)) o.selected = true;
      catSel.appendChild(o);
    });
  }

  deptSel.addEventListener('change', function () { populate(this.value); });
  if (deptSel.value) populate(deptSel.value);
})();

// File attachment
(function () {
  var MAX_FILES          = 10;
  var MAX_PER_FILE       = 50  * 1024 * 1024;  // 50 MB
  var MAX_TOTAL          = 200 * 1024 * 1024;  // 200 MB
  var ACCEPTED_EXT       = /\.(jpe?g|png|webp|gif|bmp|tiff?|svg|ico|heic|heif|avif|pdf|docx?|xlsx?|pptx?|odt|ods|odp|rtf|csv|md|txt|log|json|xml|html?|css|js|ts|php|py|java|cpp?|c|h|sh|bat|ps1|ya?ml|ini|conf|zip|rar|7z|tar|gz|bz2|xz)$/i;

  var dropzone  = document.getElementById('tn-dropzone');
  var fileInput = document.getElementById('tn-file-input');
  var fileList  = document.getElementById('tn-file-list');
  var uploadBtn = document.getElementById('tn-upload-btn');
  var subtitle  = document.getElementById('tn-dropzone-subtitle');
  var files     = [];

  // Abre el selector de archivos del sistema
  uploadBtn.addEventListener('click', function () { fileInput.click(); });
  fileInput.addEventListener('change', function () { addFiles(this.files); this.value = ''; });

  // Arrastrar y soltar archivos sobre el dropzone
  dropzone.addEventListener('dragover',  function (e) { e.preventDefault(); dropzone.classList.add('drag-over'); });
  dropzone.addEventListener('dragleave', function ()  { dropzone.classList.remove('drag-over'); });
  dropzone.addEventListener('drop', function (e) {
    e.preventDefault();
    dropzone.classList.remove('drag-over');
    addFiles(e.dataTransfer.files);
  });

  function addFiles(incoming) {
    var errors = [];
    Array.from(incoming).forEach(function (f) {
      if (files.length >= MAX_FILES)               { errors.push('Máximo ' + MAX_FILES + ' archivos permitidos.'); return; }
      if (!ACCEPTED_EXT.test(f.name))              { errors.push('"' + f.name + '" — tipo no permitido.'); return; }
      if (f.size > MAX_PER_FILE)                   { errors.push('"' + f.name + '" supera el límite de 10 MB.'); return; }
      var total = files.reduce(function (s, x) { return s + x.size; }, 0);
      if (total + f.size > MAX_TOTAL)              { errors.push('El total de archivos supera 25 MB.'); return; }
      if (files.some(function (x) { return x.name === f.name && x.size === f.size; })) return; // duplicado
      files.push(f);
    });
    render();
    errors.length ? showError([...new Set(errors)].join(' ')) : clearError();
    syncInput();
  }

  function removeFile(idx) {
    files.splice(idx, 1);
    render();
    clearError();
    syncInput();
  }

  function render() {
    fileList.innerHTML = '';
    files.forEach(function (f, i) {
      var item = document.createElement('div');
      item.className = 'tn-file-item';

      // Miniatura para imágenes; icono genérico para el resto
      if (f.type.startsWith('image/')) {
        var img = document.createElement('img');
        img.className = 'tn-file-thumb';
        img.src = URL.createObjectURL(f);
        img.onload = function () { URL.revokeObjectURL(this.src); };
        item.appendChild(img);
      } else {
        var iconWrap = document.createElement('div');
        iconWrap.className = 'tn-file-icon';
        var ic = document.createElement('span');
        ic.className = 'material-symbols-outlined';
        ic.textContent = getIcon(f);
        iconWrap.appendChild(ic);
        item.appendChild(iconWrap);
      }

      var name = document.createElement('span');
      name.className = 'tn-file-name';
      name.textContent = f.name;

      var size = document.createElement('span');
      size.className = 'tn-file-size';
      size.textContent = fmtSize(f.size);

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'tn-file-remove';
      btn.innerHTML = '<span class="material-symbols-outlined">close</span>';
      btn.addEventListener('click', (function (idx) { return function () { removeFile(idx); }; })(i));

      item.appendChild(name);
      item.appendChild(size);
      item.appendChild(btn);
      fileList.appendChild(item);
    });

    // Actualiza el subtítulo del dropzone con el contador y tamaño total
    if (files.length > 0) {
      var tot = files.reduce(function (s, f) { return s + f.size; }, 0);
      subtitle.textContent = files.length + ' / ' + MAX_FILES + ' archivos · ' + fmtSize(tot) + ' total';
    } else {
      subtitle.textContent = 'Imágenes · Docs · Texto · Comprimidos · máx. 10 archivos · 50 MB c/u';
    }
  }

  function syncInput() {
    try {
      var dt = new DataTransfer();
      files.forEach(function (f) { dt.items.add(f); });
      fileInput.files = dt.files;
    } catch (e) {}
  }

  function showError(msg) {
    var el = document.getElementById('tn-file-error');
    if (!el) {
      el = document.createElement('p');
      el.id = 'tn-file-error';
      el.className = 'tn-file-error';
      fileList.after(el);
    }
    el.textContent = msg;
  }

  function clearError() {
    var el = document.getElementById('tn-file-error');
    if (el) el.remove();
  }

  function fmtSize(b) {
    if (b < 1024)        return b + ' B';
    if (b < 1048576)     return (b / 1024).toFixed(0) + ' KB';
    return (b / 1048576).toFixed(1) + ' MB';
  }

  function getIcon(f) {
    if (f.type === 'application/pdf')            return 'picture_as_pdf';
    if (/\.(zip|rar)$/i.test(f.name))            return 'folder_zip';
    if (/\.(txt|log)$/i.test(f.name))            return 'description';
    return 'attach_file';
  }
})();
</script>

<?php require_once "views/common/pie.php"; ?>

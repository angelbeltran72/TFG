<?php
$user  ??= $_SESSION["usuario"] ?? ["nombre" => "Usuario", "rol" => "user"];
$cargo = ($user["rol"] ?? "user") === "admin" ? "Administrador" : "Agente de soporte";
$ticket    ??= [];
$categorias ??= [];
$usuarios   ??= [];
$__pageTitle = isset($ticket["titulo"]) ? "Editar ticket #" . (int)($ticket["id"] ?? 0) : "Editar ticket";
?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/ticketNew.css">
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
    <a href="index.php?controller=Dashboard&action=index">
      <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
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
    <a href="index.php?controller=Ticket&action=detalle&id=<?= (int)($ticket['id'] ?? 0) ?>" class="td-back-btn">
      <span class="material-symbols-outlined">arrow_back</span>
      <span>Volver al ticket</span>
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

    <form id="te-form" method="POST" action="index.php?controller=Ticket&action=actualizar"
          enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <input type="hidden" name="ticket_id" value="<?= (int)($ticket['id'] ?? 0) ?>">

      <div class="tn-grid">

        <!-- Columna principal -->
        <div class="tn-col-primary">
          <div class="tn-card">

            <div class="tn-field">
              <label class="tn-label" for="te-titulo">Título de la Incidencia</label>
              <input id="te-titulo" class="tn-input" type="text" name="titulo"
                     value="<?= htmlspecialchars($ticket['titulo'] ?? '') ?>"
                     placeholder="Resumen breve del problema..." autocomplete="off">
            </div>

            <div class="tn-field tn-field-grow">
              <label class="tn-label" for="te-desc">Descripción</label>
              <textarea id="te-desc" class="tn-textarea" name="descripcion" rows="8"
                        placeholder="Describe el problema..."><?= htmlspecialchars($ticket['descripcion'] ?? '') ?></textarea>
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

            <div class="tn-field">
              <label class="tn-label" for="te-categoria">Categoría</label>
              <select id="te-categoria" class="tn-select" name="categoria_id">
                <option value="">— Seleccionar —</option>
                <?php foreach ($categorias as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>"
                    <?= ((int)$cat['id'] === (int)($ticket['categoria_id'] ?? 0)) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="tn-field">
              <label class="tn-label" for="te-asignado">Asignar a</label>
              <select id="te-asignado" class="tn-select" name="asignado_a">
                <option value="">Sin asignar</option>
                <?php foreach ($usuarios as $u): ?>
                  <option value="<?= (int)$u['id'] ?>"
                    <?= ((int)$u['id'] === (int)($ticket['asignado_a'] ?? 0)) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php $hasCliente = !empty($ticket['cliente_email']); ?>
            <div class="tn-field">
              <label class="tn-label tn-label-check">
                <input type="checkbox" id="te-es-cliente-externo" name="es_cliente_externo" value="1"
                       <?= $hasCliente ? 'checked' : '' ?>>
                <span>Ticket de cliente externo</span>
              </label>
            </div>
            <div class="tn-field" id="te-cliente-email-field" style="display:<?= $hasCliente ? 'block' : 'none' ?>">
              <label class="tn-label" for="te-cliente-email">Email del cliente <span class="tn-label-required">*</span></label>
              <input id="te-cliente-email" class="tn-input" type="email" name="cliente_email"
                     value="<?= htmlspecialchars($ticket['cliente_email'] ?? '') ?>"
                     placeholder="cliente@empresa.com" autocomplete="off">
            </div>

            <div class="tn-field">
              <label class="tn-label" for="te-due">Fecha límite <span class="tn-label-optional">(opcional)</span></label>
              <input id="te-due" class="tn-input" type="date" name="due_date"
                     value="<?= htmlspecialchars($ticket['due_date'] ?? '') ?>">
            </div>

            <div class="tn-field">
              <label class="tn-label" for="te-estado">Estado</label>
              <select id="te-estado" class="tn-select" name="estado">
                <?php
                  $estados = ['sin_abrir'=>'Sin abrir','abierta'=>'Abierta','en_proceso'=>'En proceso','resuelta'=>'Resuelta','cerrada'=>'Cerrada'];
                  foreach ($estados as $val => $label):
                ?>
                  <option value="<?= $val ?>" <?= ($ticket['estado'] ?? '') === $val ? 'selected' : '' ?>>
                    <?= $label ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="tn-field">
              <label class="tn-label">Prioridad</label>
              <div class="tn-priority-list">
                <?php
                  $prios = ['critica'=>['cls'=>'p-critical','label'=>'Crítica'],'alta'=>['cls'=>'p-high','label'=>'Alta'],'media'=>['cls'=>'p-medium','label'=>'Media'],'baja'=>['cls'=>'p-low','label'=>'Baja']];
                  foreach ($prios as $val => $p):
                ?>
                  <label class="tn-priority-label <?= $p['cls'] ?>">
                    <input type="radio" name="prioridad" value="<?= $val ?>"
                      <?= ($ticket['prioridad'] ?? 'media') === $val ? 'checked' : '' ?>>
                    <span class="tn-priority-text"><?= $p['label'] ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="tn-actions">
              <button type="submit" class="tn-btn-submit">Guardar Cambios</button>
              <button type="submit" form="te-delete-form" class="tn-btn-danger"
                      onclick="return confirm('¿Eliminar este ticket? Esta acción no se puede deshacer.')">
                <span class="material-symbols-outlined">delete</span>
                Eliminar Ticket
              </button>
            </div>

          </div>
        </div>

      </div>
    </form>

    <!-- Formulario de eliminación separado para evitar anidamiento de forms -->
    <form id="te-delete-form" method="POST"
          action="index.php?controller=Ticket&action=eliminar"
          style="display:none">
      <?= Csrf::field() ?>
      <input type="hidden" name="ticket_id" value="<?= (int)($ticket['id'] ?? 0) ?>">
    </form>

  </div>
</main>
<?php require_once "views/common/msg_panel.php"; ?>

<script>
(function () {
  var MAX_FILES    = 10;
  var MAX_PER_FILE = 50  * 1024 * 1024;
  var MAX_TOTAL    = 200 * 1024 * 1024;
  var ACCEPTED_EXT = /\.(jpe?g|png|webp|gif|bmp|tiff?|svg|ico|heic|heif|avif|pdf|docx?|xlsx?|pptx?|odt|ods|odp|rtf|csv|md|txt|log|json|xml|html?|css|js|ts|php|py|java|cpp?|c|h|sh|bat|ps1|ya?ml|ini|conf|zip|rar|7z|tar|gz|bz2|xz)$/i;

  var dropzone  = document.getElementById('tn-dropzone');
  var fileInput = document.getElementById('tn-file-input');
  var fileList  = document.getElementById('tn-file-list');
  var uploadBtn = document.getElementById('tn-upload-btn');
  var subtitle  = document.getElementById('tn-dropzone-subtitle');
  var files     = [];

  uploadBtn.addEventListener('click', function () { fileInput.click(); });
  fileInput.addEventListener('change', function () { addFiles(this.files); this.value = ''; });

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
      if (files.length >= MAX_FILES)              { errors.push('Máximo ' + MAX_FILES + ' archivos permitidos.'); return; }
      if (!ACCEPTED_EXT.test(f.name))             { errors.push('"' + f.name + '" — tipo no permitido.'); return; }
      if (f.size > MAX_PER_FILE)                  { errors.push('"' + f.name + '" supera el límite de 50 MB.'); return; }
      var total = files.reduce(function (s, x) { return s + x.size; }, 0);
      if (total + f.size > MAX_TOTAL)             { errors.push('El total de archivos supera 200 MB.'); return; }
      if (files.some(function (x) { return x.name === f.name && x.size === f.size; })) return;
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
    if (b < 1024)    return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(0) + ' KB';
    return (b / 1048576).toFixed(1) + ' MB';
  }

  function getIcon(f) {
    if (f.type === 'application/pdf')   return 'picture_as_pdf';
    if (/\.(zip|rar)$/i.test(f.name))  return 'folder_zip';
    if (/\.(txt|log)$/i.test(f.name))  return 'description';
    return 'attach_file';
  }
})();
</script>

<script>
(function () {
  var chk   = document.getElementById('te-es-cliente-externo');
  var field = document.getElementById('te-cliente-email-field');
  var inp   = document.getElementById('te-cliente-email');
  if (!chk || !field) return;
  chk.addEventListener('change', function () {
    field.style.display = this.checked ? 'block' : 'none';
    if (!this.checked && inp) inp.value = '';
  });
})();
</script>

<?php require_once "views/common/pie.php"; ?>

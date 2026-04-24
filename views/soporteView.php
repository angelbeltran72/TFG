<?php
$user  ??= $_SESSION["usuario"] ?? ["nombre" => "Usuario", "rol" => "user"];
$cargo = ($user["rol"] ?? "user") === "admin" ? "Administrador" : "Agente de soporte";
$statusItems ??= [];
$__pageTitle = "Soporte";
?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/soporte.css">

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
    <a class="active" aria-current="page" href="index.php?controller=Soporte&action=index">
      <span class="material-symbols-outlined" aria-hidden="true">contact_support</span>
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
  <div class="app-canvas sp-canvas">

    <!-- Encabezado -->
    <div class="sp-page-header">
      <div class="sp-page-header-icon">
        <span class="material-symbols-outlined">contact_support</span>
      </div>
      <div>
        <h2>Centro de Soporte</h2>
        <p>Ayuda, guías y contacto con el equipo técnico</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="sp-tabs">
      <button class="sp-tab active" data-tab="ayuda">
        <span class="material-symbols-outlined">menu_book</span>
        Ayuda & Uso
      </button>
      <button class="sp-tab" data-tab="contacto">
        <span class="material-symbols-outlined">mail</span>
        Contacto & Soporte
      </button>
    </div>

    <!-- Tab 1: Ayuda y uso -->
    <div class="sp-tab-content active" id="tab-ayuda">

      <!-- Primeros pasos -->
      <div class="sp-section-title">
        <span class="material-symbols-outlined">rocket_launch</span>
        Primeros pasos
      </div>
      <div class="sp-steps-grid">
        <div class="sp-step-card">
          <div class="sp-step-num">01</div>
          <div class="sp-step-icon">
            <span class="material-symbols-outlined">add_circle</span>
          </div>
          <h3>Crear una incidencia</h3>
          <p>Haz clic en <strong>Nueva Incidencia</strong> en el menú lateral. Rellena el título, categoría, prioridad y descripción detallada del problema.</p>
          <a href="index.php?controller=Ticket&action=nuevo" class="sp-step-link">
            Crear ahora
            <span class="material-symbols-outlined">arrow_forward</span>
          </a>
        </div>

        <div class="sp-step-card">
          <div class="sp-step-num">02</div>
          <div class="sp-step-icon secondary">
            <span class="material-symbols-outlined">manage_search</span>
          </div>
          <h3>Hacer seguimiento</h3>
          <p>Ve a <strong>Tickets</strong> para ver el estado de tus incidencias. Puedes filtrar por estado, prioridad o fecha de creación.</p>
          <a href="index.php?controller=Ticket&action=listar" class="sp-step-link secondary">
            Ver tickets
            <span class="material-symbols-outlined">arrow_forward</span>
          </a>
        </div>

        <div class="sp-step-card">
          <div class="sp-step-num">03</div>
          <div class="sp-step-icon success">
            <span class="material-symbols-outlined">check_circle</span>
          </div>
          <h3>Resolver y cerrar</h3>
          <p>Cuando el problema esté solucionado, abre el ticket y cambia el estado a <strong>Resuelto</strong>. Añade un comentario final si es necesario.</p>
          <a href="index.php?controller=Ticket&action=listar" class="sp-step-link success">
            Ver mis tickets
            <span class="material-symbols-outlined">arrow_forward</span>
          </a>
        </div>
      </div>

      <!-- FAQ -->
      <div class="sp-section-title" style="margin-top:28px">
        <span class="material-symbols-outlined">quiz</span>
        Preguntas frecuentes
      </div>
      <div class="sp-faq">

        <div class="sp-faq-item">
          <button class="sp-faq-q">
            <span>¿Cómo creo un nuevo ticket de incidencia?</span>
            <span class="material-symbols-outlined sp-faq-arrow">expand_more</span>
          </button>
          <div class="sp-faq-a">
            <p>Haz clic en <strong>Nueva Incidencia</strong> en el menú lateral izquierdo o en el botón del dashboard. Rellena todos los campos obligatorios: título, categoría, prioridad y una descripción clara del problema. Una vez enviado, el ticket aparecerá en la lista con estado <em>Abierto</em>.</p>
          </div>
        </div>

        <div class="sp-faq-item">
          <button class="sp-faq-q">
            <span>¿Qué significan los niveles de prioridad?</span>
            <span class="material-symbols-outlined sp-faq-arrow">expand_more</span>
          </button>
          <div class="sp-faq-a">
            <p><strong>Alta:</strong> el sistema o servicio está caído, afecta a múltiples usuarios. Requiere atención inmediata.<br>
            <strong>Media:</strong> funcionalidad degradada pero hay solución temporal. Atención en el mismo día.<br>
            <strong>Baja:</strong> inconveniente menor o consulta no urgente. Se atiende según disponibilidad.</p>
          </div>
        </div>

        <div class="sp-faq-item">
          <button class="sp-faq-q">
            <span>¿Quién puede cambiar el estado de un ticket?</span>
            <span class="material-symbols-outlined sp-faq-arrow">expand_more</span>
          </button>
          <div class="sp-faq-a">
            <p>Cualquier agente puede actualizar el estado de los tickets que tiene asignados. Los administradores pueden modificar cualquier ticket. Si un ticket lleva mucho tiempo sin actividad, contacta con un administrador.</p>
          </div>
        </div>

        <div class="sp-faq-item">
          <button class="sp-faq-q">
            <span>¿Cómo cambio mi contraseña?</span>
            <span class="material-symbols-outlined sp-faq-arrow">expand_more</span>
          </button>
          <div class="sp-faq-a">
            <p>Ve a <strong>Mi Perfil</strong> desde el menú lateral. En la sección de seguridad encontrarás la opción para cambiar tu contraseña. Si has olvidado la contraseña, usa el enlace <em>"¿Olvidaste tu contraseña?"</em> en la pantalla de inicio de sesión.</p>
          </div>
        </div>

        <div class="sp-faq-item">
          <button class="sp-faq-q">
            <span>¿Qué hago si un ticket se queda bloqueado sin respuesta?</span>
            <span class="material-symbols-outlined sp-faq-arrow">expand_more</span>
          </button>
          <div class="sp-faq-a">
            <p>Si un ticket lleva más de 48 horas sin actividad y es de prioridad alta, usa el formulario de contacto de esta página para notificarlo al equipo técnico. Indica el número de ticket y el problema para que podamos escalarlo.</p>
          </div>
        </div>

        <div class="sp-faq-item">
          <button class="sp-faq-q">
            <span>¿Cómo puedo ver los tickets de otros usuarios?</span>
            <span class="material-symbols-outlined sp-faq-arrow">expand_more</span>
          </button>
          <div class="sp-faq-a">
            <p>Los agentes pueden ver todos los tickets del sistema en la sección <strong>Tickets</strong>. Usa los filtros de búsqueda para encontrar tickets por usuario, categoría o estado. Si no tienes acceso, contacta con un administrador.</p>
          </div>
        </div>

      </div>
    </div>

    <!-- Tab 2: Contacto y soporte -->
    <div class="sp-tab-content" id="tab-contacto">

      <!-- Contacto + Formulario -->
      <div class="sp-contact-row">

        <!-- Info de contacto -->
        <div class="sp-contact-card">
          <div class="sp-section-title" style="margin-bottom:20px">
            <span class="material-symbols-outlined">contacts</span>
            Información de contacto
          </div>

          <div class="sp-contact-item">
            <div class="sp-contact-item-icon">
              <span class="material-symbols-outlined">mail</span>
            </div>
            <div>
              <span class="sp-contact-item-label">Correo electrónico</span>
              <a href="mailto:gestiondeincidenciasdaw@gmail.com" class="sp-contact-item-value">
                gestiondeincidenciasdaw@gmail.com
              </a>
            </div>
          </div>

          <div class="sp-contact-item">
            <div class="sp-contact-item-icon secondary">
              <span class="material-symbols-outlined">schedule</span>
            </div>
            <div>
              <span class="sp-contact-item-label">Horario de atención</span>
              <span class="sp-contact-item-value">Lunes a Viernes, 9:00 – 18:00</span>
            </div>
          </div>

          <div class="sp-contact-item">
            <div class="sp-contact-item-icon success">
              <span class="material-symbols-outlined">timer</span>
            </div>
            <div>
              <span class="sp-contact-item-label">Tiempo de respuesta</span>
              <span class="sp-contact-item-value">Menos de 24 horas hábiles</span>
            </div>
          </div>
        </div>

        <!-- Formulario -->
        <div class="sp-form-card">
          <div class="sp-section-title" style="margin-bottom:20px">
            <span class="material-symbols-outlined">edit_note</span>
            Reportar un problema
          </div>

          <form method="POST" action="index.php?controller=Soporte&action=enviarMensaje" class="sp-form" enctype="multipart/form-data">
            <?= Csrf::field() ?>

            <div class="sp-form-row">
              <div class="sp-form-group">
                <label for="sp-nombre">Nombre</label>
                <input
                  type="text"
                  id="sp-nombre"
                  name="nombre"
                  value="<?= htmlspecialchars($user["nombre"] ?? "") ?>"
                  placeholder="Tu nombre"
                  required>
              </div>
              <div class="sp-form-group">
                <label for="sp-email">Email de respuesta</label>
                <input
                  type="email"
                  id="sp-email"
                  name="email"
                  value="<?= htmlspecialchars($user["email"] ?? "") ?>"
                  placeholder="tu@email.com"
                  required>
              </div>
            </div>

            <div class="sp-form-group">
              <label for="sp-asunto">Asunto</label>
              <select id="sp-asunto" name="asunto" required>
                <option value="">Selecciona un tipo de problema...</option>
                <option value="Error en la plataforma">Error en la plataforma</option>
                <option value="Problema de acceso o login">Problema de acceso o login</option>
                <option value="Ticket bloqueado sin respuesta">Ticket bloqueado sin respuesta</option>
                <option value="Solicitud de nueva funcionalidad">Solicitud de nueva funcionalidad</option>
                <option value="Problema con permisos o roles">Problema con permisos o roles</option>
                <option value="Otro">Otro</option>
              </select>
            </div>

            <div class="sp-form-group">
              <label for="sp-mensaje">Descripción del problema</label>
              <textarea
                id="sp-mensaje"
                name="mensaje"
                rows="5"
                placeholder="Describe el problema con el mayor detalle posible: qué hiciste, qué esperabas que pasara y qué ocurrió realmente..."
                required></textarea>
              <span class="sp-form-hint">Mínimo 10 caracteres</span>
            </div>

            <!-- Adjuntos -->
            <div class="sp-form-group">
              <label>Adjuntar archivos <span class="sp-form-hint" style="text-transform:none;letter-spacing:0">(máx. 3 archivos · 5 MB c/u · 10 MB total)</span></label>
              <div class="sp-dropzone" id="sp-dropzone">
                <span class="material-symbols-outlined sp-dropzone-icon">attach_file</span>
                <span class="sp-dropzone-text">Arrastra archivos aquí o</span>
                <button type="button" class="sp-dropzone-btn" id="sp-upload-btn">Seleccionar</button>
                <input type="file" id="sp-file-input" name="adjuntos[]" multiple
                       accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.txt,.log,.zip,.rar"
                       style="display:none">
              </div>
              <div class="sp-file-list" id="sp-file-list"></div>
            </div>

            <button type="submit" class="sp-submit-btn">
              <span class="material-symbols-outlined">send</span>
              Enviar mensaje
            </button>
          </form>
        </div>

      </div>
    </div>

    <!-- Licencia y atribuciones -->
    <div class="sp-license-block">
      <div>
        <p class="sp-license-title">Licencia y atribuciones</p>
        <p class="sp-license-body">
          AlertHub está distribuido bajo la licencia
          <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer">
            Creative Commons Atribución-Compartir Igual 4.0 Internacional (CC BY-SA 4.0)
          </a>.
          Incluye componentes de terceros: Three.js (MIT), Google Fonts — Syne, Inter, Manrope
          (SIL OFL 1.1), Material Symbols (Apache 2.0), PHPMailer (LGPL-2.1) y
          phpdotenv (MIT).
        </p>
      </div>
    </div>

  </div>
</main>

<?php require_once "views/common/msg_panel.php"; ?>

<script>
// Cambio ventana
document.querySelectorAll(".sp-tab").forEach(btn => {
  btn.addEventListener("click", () => {
    const target = btn.dataset.tab;
    document.querySelectorAll(".sp-tab").forEach(t => t.classList.remove("active"));
    document.querySelectorAll(".sp-tab-content").forEach(c => c.classList.remove("active"));
    btn.classList.add("active");
    document.getElementById("tab-" + target).classList.add("active");
  });
});

// Preguntas frecuentes
document.querySelectorAll(".sp-faq-q").forEach(btn => {
  btn.addEventListener("click", () => {
    const item   = btn.closest(".sp-faq-item");
    const isOpen = item.classList.contains("open");
    document.querySelectorAll(".sp-faq-item").forEach(i => i.classList.remove("open"));
    if (!isOpen) item.classList.add("open");
  });
});

// Abre el tab de contacto si viene flash de error del formulario
<?php if (!empty($_SESSION["flash"]["type"]) && $_SESSION["flash"]["type"] === "error"): ?>
  (function() {
    const contactBtn = document.querySelector('[data-tab="contacto"]');
    if (contactBtn) contactBtn.click();
  })();
<?php endif; ?>

// Adjuntar archivos
(function () {
  var MAX_FILES    = 3;
  var MAX_PER_FILE = 5  * 1024 * 1024;   // 5 MB
  var MAX_TOTAL    = 10 * 1024 * 1024;   // 10 MB
  var ACCEPTED_EXT = /\.(jpe?g|png|webp|gif|bmp|tiff?|svg|ico|heic|heif|avif|pdf|docx?|xlsx?|pptx?|odt|ods|odp|rtf|csv|md|txt|log|json|xml|html?|css|js|ts|php|py|java|cpp?|c|h|sh|bat|ps1|ya?ml|ini|conf|zip|rar|7z|tar|gz|bz2|xz)$/i;

  var dropzone  = document.getElementById('sp-dropzone');
  var fileInput = document.getElementById('sp-file-input');
  var fileList  = document.getElementById('sp-file-list');
  var uploadBtn = document.getElementById('sp-upload-btn');
  var files     = [];

  uploadBtn.addEventListener('click', function () { fileInput.click(); });
  fileInput.addEventListener('change', function () { addFiles(this.files); this.value = ''; });

  dropzone.addEventListener('dragover',  function (e) { e.preventDefault(); dropzone.classList.add('sp-drag-over'); });
  dropzone.addEventListener('dragleave', function ()  { dropzone.classList.remove('sp-drag-over'); });
  dropzone.addEventListener('drop', function (e) {
    e.preventDefault();
    dropzone.classList.remove('sp-drag-over');
    addFiles(e.dataTransfer.files);
  });

  function addFiles(incoming) {
    var errors = [];
    Array.from(incoming).forEach(function (f) {
      if (files.length >= MAX_FILES)  { errors.push('Máximo ' + MAX_FILES + ' archivos permitidos.'); return; }
      if (!ACCEPTED_EXT.test(f.name)) { errors.push('"' + f.name + '" — tipo no permitido.'); return; }
      if (f.size > MAX_PER_FILE)      { errors.push('"' + f.name + '" supera el límite de 5 MB.'); return; }
      var total = files.reduce(function (s, x) { return s + x.size; }, 0);
      if (total + f.size > MAX_TOTAL) { errors.push('El total supera 10 MB.'); return; }
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
      item.className = 'sp-file-item';

      var iconWrap = document.createElement('div');
      iconWrap.className = 'sp-file-icon';
      var ic = document.createElement('span');
      ic.className = 'material-symbols-outlined';
      ic.textContent = getIcon(f);
      iconWrap.appendChild(ic);

      var name = document.createElement('span');
      name.className = 'sp-file-name';
      name.textContent = f.name;

      var size = document.createElement('span');
      size.className = 'sp-file-size';
      size.textContent = fmtSize(f.size);

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'sp-file-remove';
      btn.innerHTML = '<span class="material-symbols-outlined">close</span>';
      btn.addEventListener('click', (function (idx) { return function () { removeFile(idx); }; })(i));

      item.appendChild(iconWrap);
      item.appendChild(name);
      item.appendChild(size);
      item.appendChild(btn);
      fileList.appendChild(item);
    });

    // Actualiza estado del dropzone
    dropzone.classList.toggle('sp-has-files', files.length > 0);
  }

  function syncInput() {
    try {
      var dt = new DataTransfer();
      files.forEach(function (f) { dt.items.add(f); });
      fileInput.files = dt.files;
    } catch (e) {}
  }

  function showError(msg) {
    var el = document.getElementById('sp-file-error');
    if (!el) {
      el = document.createElement('p');
      el.id = 'sp-file-error';
      el.className = 'sp-file-error';
      fileList.after(el);
    }
    el.textContent = msg;
  }

  function clearError() {
    var el = document.getElementById('sp-file-error');
    if (el) el.remove();
  }

  function fmtSize(b) {
    if (b < 1024)    return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(0) + ' KB';
    return (b / 1048576).toFixed(1) + ' MB';
  }

  function getIcon(f) {
    if (f.type.startsWith('image/'))           return 'image';
    if (f.type === 'application/pdf')          return 'picture_as_pdf';
    if (/\.(zip|rar|7z|tar|gz)$/i.test(f.name)) return 'folder_zip';
    if (/\.(txt|log|md)$/i.test(f.name))       return 'description';
    return 'attach_file';
  }
})();
</script>

<?php require_once "views/common/pie.php"; ?>

// sidebar.js — Hamburguesa y overlay para el sidebar en móvil/tablet
(function () {
  // Solo actuar si existe el sidebar (vistas autenticadas)
  var sidebar = document.querySelector('.app-sidebar');
  if (!sidebar) return;

  // Crear botón hamburguesa que se inserta en el topbar
  var btn = document.createElement('button');
  btn.className = 'app-menu-btn';
  btn.setAttribute('aria-label', 'Abrir menú de navegación');
  btn.setAttribute('type', 'button');
  btn.innerHTML = '<span class="material-symbols-outlined">menu</span>';

  // Crear overlay semitransparente que cierra el sidebar al hacer clic
  var overlay = document.createElement('div');
  overlay.className = 'app-sidebar-overlay';
  document.body.appendChild(overlay);

  // Insertar el botón al inicio del topbar DESPUÉS del tick actual para que
  // quede siempre a la izquierda de cualquier botón que otros scripts hayan inyectado
  // (theme.js también hace insertBefore al firstChild en el mismo tick)
  setTimeout(function () {
    var topbar = document.querySelector('.app-topbar');
    if (!topbar) return;
    topbar.insertBefore(btn, topbar.firstChild);
    // Asegurar que el panel derecho quede pegado a la derecha en móvil
    var rightPanel = topbar.querySelector('.app-topbar-right');
    if (rightPanel) rightPanel.style.marginLeft = 'auto';
  }, 0);

  function abrirSidebar() {
    document.body.classList.add('sidebar-open');
  }

  function cerrarSidebar() {
    document.body.classList.remove('sidebar-open');
  }

  // Alterna el sidebar al pulsar el botón
  btn.addEventListener('click', function () {
    document.body.classList.toggle('sidebar-open');
  });

  // Cierra el sidebar al hacer clic en el overlay
  overlay.addEventListener('click', cerrarSidebar);

  // Cierra el sidebar al navegar (solo en móvil; en tablet el sidebar siempre está visible)
  sidebar.querySelectorAll('a, button').forEach(function (el) {
    el.addEventListener('click', function () {
      if (window.innerWidth < 640) cerrarSidebar();
    });
  });

  // Cierra el sidebar al salir del rango móvil (en tablet el sidebar siempre es visible)
  window.addEventListener('resize', function () {
    if (window.innerWidth >= 640) cerrarSidebar();
  });
}());

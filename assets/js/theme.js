// theme.js — Toggle oscuro/claro; inyecta botón en .app-topbar y gestiona data-theme en <html>.
(function () {
  var STORAGE_KEY = 'theme';
  var DARK        = 'dark';

  function isDark() {
    return document.documentElement.getAttribute('data-theme') === DARK;
  }

  function applyTheme(dark) {
    if (dark) {
      document.documentElement.setAttribute('data-theme', DARK);
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
    updateIcon();
    try { localStorage.setItem(STORAGE_KEY, dark ? DARK : 'light'); } catch (e) {}
  }

  function updateIcon() {
    var btn  = document.getElementById('theme-toggle');
    var icon = btn && btn.querySelector('.material-symbols-outlined');
    if (!icon) return;
    icon.textContent = isDark() ? 'light_mode' : 'dark_mode';
    btn.title = isDark() ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
  }

  function injectButton() {
    var topbar = document.querySelector('.app-topbar');
    if (!topbar || document.getElementById('theme-toggle')) return;

    var btn = document.createElement('button');
    btn.id        = 'theme-toggle';
    btn.type      = 'button';
    btn.className = 'app-icon-btn';
    btn.title     = isDark() ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';

    var icon = document.createElement('span');
    icon.className   = 'material-symbols-outlined';
    icon.textContent = isDark() ? 'light_mode' : 'dark_mode';

    btn.appendChild(icon);
    btn.addEventListener('click', function () { applyTheme(!isDark()); });

    topbar.insertBefore(btn, topbar.firstChild);
  }

  // Inyectar botón cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectButton);
  } else {
    injectButton();
  }
})();

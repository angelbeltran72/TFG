(function () {
  var btn        = document.getElementById('notif-btn');
  var panel      = document.getElementById('notif-panel');
  var badge      = document.getElementById('notif-count');
  var markAllBtn = document.getElementById('notif-mark-all');
  var listEl     = document.getElementById('notif-list');

  if (!btn || !panel || !listEl) return;

  var BASE      = (window.__BASE || '') + '/index.php?controller=Notification&action=';
  var pollTimer = null;

  /* Mapas de icono y color por tipo */
  var ICON_MAP = {
    ticket_assigned: ['assignment_ind', 'primary'],
    ticket_comment:  ['comment',        'warning'],
    ticket_status:   ['swap_horiz',     'secondary'],
    ticket_created:  ['add_circle',     'primary'],
    ticket_overdue:  ['schedule',       'error'],
  };

  /* Helpers */
  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function timeAgo(dateStr) {
    var diff = Math.floor((Date.now() - new Date(dateStr.replace(' ','T')).getTime()) / 1000);
    if (diff < 60)    return 'Ahora mismo';
    if (diff < 3600)  return 'Hace ' + Math.floor(diff / 60)   + ' min';
    if (diff < 86400) return 'Hace ' + Math.floor(diff / 3600) + ' h';
    return 'Hace '    + Math.floor(diff / 86400) + ' d';
  }

  function buildUrl(n) {
    if (n.resource_type === 'ticket' && n.resource_id) {
      return (window.__BASE || '') + '/index.php?controller=Ticket&action=ver&id=' + n.resource_id;
    }
    return null;
  }

  /* Badge */
  function updateBadge(n) {
    if (!badge) return;
    badge.textContent = n;
    badge.style.display = n > 0 ? 'flex' : 'none';
  }

  /* Renderizar lista */
  function renderList(notifs) {
    listEl.innerHTML = '';

    if (!notifs || notifs.length === 0) {
      listEl.innerHTML = '<div class="notif-empty">No tienes notificaciones</div>';
      return;
    }

    notifs.forEach(function (n) {
      var unread  = !n.read_at;
      var map     = ICON_MAP[n.type] || ['notifications', 'primary'];
      var url     = buildUrl(n);
      var hora    = n.created_at ? timeAgo(n.created_at) : '';

      var item = document.createElement('div');
      item.className = 'notif-item' + (unread ? ' no-leido' : '');
      item.dataset.id = n.id;
      if (url) {
        item.dataset.url = url;
        item.style.cursor = 'pointer';
      }
      item.innerHTML =
        '<div class="notif-icon ' + map[1] + '">' +
          '<span class="material-symbols-outlined">' + map[0] + '</span>' +
        '</div>' +
        '<div class="notif-body">' +
          '<p class="notif-text">'  + escHtml(n.message) + '</p>' +
          '<p class="notif-hora">'  + escHtml(hora)      + '</p>' +
        '</div>' +
        (unread ? '<div class="notif-punto"></div>' : '');

      item.addEventListener('click', function (e) {
        e.stopPropagation();
        if (unread) {
          var fd = new FormData();
          fd.append('id', n.id);
          fetch(BASE + 'markRead', { method: 'POST', body: fd }).catch(function(){});
          item.classList.remove('no-leido');
          var dot = item.querySelector('.notif-punto');
          if (dot) dot.remove();
          unread = false;
          updateBadge(listEl.querySelectorAll('.notif-item.no-leido').length);
        }
        if (url) window.location.href = url;
      });

      listEl.appendChild(item);
    });
  }

  /* Cargar notificaciones del servidor */
  function loadNotifications() {
    fetch(BASE + 'getAll')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.notifications) renderList(d.notifications);
        var unreadCount = listEl.querySelectorAll('.notif-item.no-leido').length;
        updateBadge(unreadCount);
      })
      .catch(function () {});
  }

  /* Polling mientras el panel está abierto */
  function startPoll() {
    stopPoll();
    loadNotifications();
    pollTimer = setInterval(loadNotifications, 30000);
  }

  function stopPoll() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  }

  /* Posición del panel (fixed) */
  function positionPanel() {
    var rect = btn.getBoundingClientRect();
    panel.style.top   = (rect.bottom + 8) + 'px';
    panel.style.right = Math.max(8, window.innerWidth - rect.right) + 'px';
    panel.style.left  = 'auto';
  }

  function openPanel() {
    positionPanel();
    panel.classList.add('open');
    panel.style.opacity       = '1';
    panel.style.transform     = 'none';
    panel.style.pointerEvents = 'all';
    btn.setAttribute('aria-expanded', 'true');
    startPoll();
  }

  function closePanel() {
    panel.classList.remove('open');
    panel.style.opacity       = '';
    panel.style.transform     = '';
    panel.style.pointerEvents = '';
    btn.setAttribute('aria-expanded', 'false');
    stopPoll();
  }

  /* Eventos */
  btn.addEventListener('click', function (e) {
    e.stopImmediatePropagation();
    e.preventDefault();
    panel.classList.contains('open') ? closePanel() : openPanel();
  });

  document.addEventListener('click', function (e) {
    if (panel.classList.contains('open') && !panel.contains(e.target) && e.target !== btn) {
      closePanel();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closePanel();
  });

  window.addEventListener('resize', function () {
    if (panel.classList.contains('open')) positionPanel();
  });

  if (markAllBtn) {
    markAllBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      fetch(BASE + 'markAllRead', { method: 'POST' }).catch(function(){});
      listEl.querySelectorAll('.notif-item.no-leido').forEach(function (item) {
        item.classList.remove('no-leido');
        var dot = item.querySelector('.notif-punto');
        if (dot) dot.remove();
      });
      updateBadge(0);
    });
  }

  /* Badge inicial y polling de fondo (badge cada 30s) */
  updateBadge(document.querySelectorAll('#notif-list .notif-item.no-leido').length);

  setInterval(function () {
    if (!panel.classList.contains('open')) {
      fetch(BASE + 'count')
        .then(function (r) { return r.json(); })
        .then(function (d) { if (typeof d.count === 'number') updateBadge(d.count); })
        .catch(function () {});
    }
  }, 30000);
})();

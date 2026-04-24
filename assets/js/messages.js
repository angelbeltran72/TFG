(function () {
  'use strict';

  /* Config */
  var BASE      = (window.__BASE || '') + '/index.php?controller=Message&action=';
  var STORE_KEY = 'vops_msg_panel';
  var POLL_MS   = 5000;
  var CONV_POLL_MS = 30000;
  var DEFAULT   = { bottom: 0, right: 48, width: 400, maxHeight: 540 };

  /* Elementos */
  var msgTabBtn  = document.getElementById('pf-msg-tab-btn');
  var msgPanel   = document.getElementById('pf-msg-panel');
  var msgClose   = document.getElementById('pf-msg-close-btn');
  var msgMove    = document.getElementById('pf-msg-move-btn');
  var msgHeader  = msgPanel.querySelector('.pf-msg-panel-header');
  var msgBackBtn = document.getElementById('pf-msg-back-btn');
  var msgTitle   = document.getElementById('pf-msg-panel-title');
  var tabBadge   = document.getElementById('pf-msg-tab-badge');

  var viewList = document.getElementById('pf-msg-view-list');
  var viewNew  = document.getElementById('pf-msg-view-new');
  var viewChat = document.getElementById('pf-msg-view-chat');

  var msgNewBtn      = document.getElementById('pf-msg-new-btn');
  var msgSearchInput = document.getElementById('pf-msg-search-input');
  var msgNewSearch   = document.getElementById('pf-msg-new-search');
  var msgUsersList   = document.getElementById('pf-msg-users-list');
  var msgList        = document.getElementById('pf-msg-list');
  var msgMessages    = document.getElementById('pf-msg-messages');
  var msgInput       = document.getElementById('pf-msg-input');
  var msgSendBtn     = document.getElementById('pf-msg-send-btn');

  /* Estado */
  var currentConvId    = null;
  var currentOtherName = '';
  var pollTimer        = null;
  var convPollTimer    = null;
  var lastMsgId        = 0;
  var myId             = parseInt(window.__MY_ID || '0');
  var avColors         = ['a', 'b', 'c', 'd', 'e'];

  /* Badge */
  function updateTabBadge(n) {
    tabBadge.textContent = n;
    tabBadge.style.display = n > 0 ? 'inline-flex' : 'none';
  }

  function refreshBadge() {
    fetch(BASE + 'unreadCount')
      .then(function (r) { return r.json(); })
      .then(function (d) { if (typeof d.count === 'number') updateTabBadge(d.count); })
      .catch(function () {});
  }

  /* Vistas */
  function showView(view) {
    [viewList, viewNew, viewChat].forEach(function (v) {
      v.style.display = 'none';
    });
    view.style.display = 'flex';
  }

  function showListView() {
    showView(viewList);
    msgBackBtn.style.display = 'none';
    msgTitle.textContent = 'Mensajes';
    stopPoll();
    currentConvId = null;
    loadConversations();
  }

  function showNewView() {
    showView(viewNew);
    msgBackBtn.style.display = 'flex';
    msgTitle.textContent = 'Nueva conversación';
    stopConvPoll();
    loadUsers();
  }

  function showChatView(convId, otherName) {
    currentConvId    = convId;
    currentOtherName = otherName;
    showView(viewChat);
    msgBackBtn.style.display = 'flex';
    msgTitle.textContent = otherName;
    msgMessages.innerHTML = '';
    lastMsgId = 0;
    stopConvPoll();
    loadMessages();
    startPoll();
  }

  /* Conversaciones: cargar y renderizar */
  function renderConversationItem(c) {
    var avCls    = avColors[(parseInt(c.other_id) || 0) % 5];
    var parts    = (c.other_nombre || '').split(' ').slice(0, 2);
    var initials = parts.map(function (p) { return p && p[0] ? p[0].toUpperCase() : ''; }).join('');
    var preview  = c.last_content
      ? (c.last_content.length > 40 ? c.last_content.substring(0, 40) + '…' : c.last_content)
      : '...';
    var timeStr  = '';
    if (c.last_message_at) {
      var ts   = new Date(c.last_message_at.replace(' ', 'T'));
      var diff = Math.floor((Date.now() - ts.getTime()) / 1000);
      if (diff < 3600)       timeStr = Math.floor(diff / 60) + ' min';
      else if (diff < 86400) timeStr = Math.floor(diff / 3600) + ' h';
      else                   timeStr = ts.toLocaleDateString('es', { day: '2-digit', month: '2-digit' });
    }
    var avHtml  = c.other_avatar ? '<img src="' + escHtml(c.other_avatar) + '" alt="">' : initials;
    var unread  = parseInt(c.unread) || 0;
    var item    = document.createElement('div');
    item.className = 'pf-msg-item' + (unread > 0 ? ' no-leido' : '');
    item.dataset.convId    = c.id;
    item.dataset.otherId   = c.other_id;
    item.dataset.otherName = c.other_nombre;
    item.innerHTML =
      '<div class="pf-msg-avatar ' + avCls + '">' + avHtml + '</div>' +
      '<div class="pf-msg-body">' +
        '<p class="pf-msg-name">'    + escHtml(c.other_nombre) + '</p>' +
        '<p class="pf-msg-preview">' + escHtml(preview) + '</p>' +
      '</div>' +
      '<div class="pf-msg-meta">' +
        '<span class="pf-msg-time">' + escHtml(timeStr) + '</span>' +
        (unread > 0 ? '<span class="pf-msg-punto-no-leido"></span>' : '') +
      '</div>';
    item.addEventListener('click', function () {
      item.classList.remove('no-leido');
      var dot = item.querySelector('.pf-msg-punto-no-leido');
      if (dot) dot.remove();
      showChatView(parseInt(c.id), c.other_nombre);
    });
    return item;
  }

  function loadConversations() {
    fetch(BASE + 'conversations')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.conversations) return;
        msgList.innerHTML = '';
        if (d.conversations.length === 0) {
          msgList.innerHTML = '<div class="pf-msg-empty">Sin conversaciones. ¡Inicia una nueva!</div>';
        } else {
          d.conversations.forEach(function (c) {
            msgList.appendChild(renderConversationItem(c));
          });
          // Reaplicar filtro de búsqueda si está activo
          if (msgSearchInput && msgSearchInput.value) {
            var q = msgSearchInput.value.toLowerCase();
            msgList.querySelectorAll('.pf-msg-item').forEach(function (it) {
              var name = (it.dataset.otherName || '').toLowerCase();
              it.style.display = name.includes(q) ? '' : 'none';
            });
          }
        }
        refreshBadge();
      })
      .catch(function () {});
  }

  function startConvPoll() {
    stopConvPoll();
    convPollTimer = setInterval(loadConversations, CONV_POLL_MS);
  }

  function stopConvPoll() {
    if (convPollTimer) { clearInterval(convPollTimer); convPollTimer = null; }
  }

  /* Lista: filtro búsqueda */
  if (msgSearchInput) {
    msgSearchInput.addEventListener('input', function () {
      var q = this.value.toLowerCase();
      msgList.querySelectorAll('.pf-msg-item').forEach(function (item) {
        var name = (item.dataset.otherName || '').toLowerCase();
        item.style.display = name.includes(q) ? '' : 'none';
      });
    });
  }

  /* Nueva conversación */
  msgNewBtn.addEventListener('click', showNewView);

  function loadUsers() {
    msgUsersList.innerHTML = '<div class="pf-msg-loading">Cargando...</div>';
    fetch(BASE + 'users')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        msgUsersList.innerHTML = '';
        if (!d.users || d.users.length === 0) {
          msgUsersList.innerHTML = '<div class="pf-msg-empty">No hay usuarios disponibles</div>';
          return;
        }
        d.users.forEach(function (u) {
          var initials = (u.nombre || '?').split(' ').slice(0, 2)
            .map(function (p) { return p[0].toUpperCase(); }).join('');
          var avClass = avColors[u.id % 5];
          var avHtml  = u.avatar_path
            ? '<img src="' + escHtml(u.avatar_path) + '" alt="">'
            : initials;
          var rolLabel = u.rol === 'admin' ? 'Administrador' : 'Agente';
          var item = document.createElement('div');
          item.className = 'pf-msg-item';
          item.dataset.userId   = u.id;
          item.dataset.userName = u.nombre;
          item.innerHTML =
            '<div class="pf-msg-avatar ' + avClass + '">' + avHtml + '</div>' +
            '<div class="pf-msg-body">' +
              '<p class="pf-msg-name">'    + escHtml(u.nombre)   + '</p>' +
              '<p class="pf-msg-preview">' + escHtml(rolLabel) + '</p>' +
            '</div>';
          item.addEventListener('click', function () {
            openConversationWith(parseInt(u.id), u.nombre);
          });
          msgUsersList.appendChild(item);
        });
      })
      .catch(function () {
        msgUsersList.innerHTML = '<div class="pf-msg-empty">Error al cargar usuarios</div>';
      });
  }

  if (msgNewSearch) {
    msgNewSearch.addEventListener('input', function () {
      var q = this.value.toLowerCase();
      msgUsersList.querySelectorAll('.pf-msg-item').forEach(function (item) {
        var name = (item.dataset.userName || '').toLowerCase();
        item.style.display = name.includes(q) ? '' : 'none';
      });
    });
  }

  function openConversationWith(userId, userName) {
    fetch(BASE + 'create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.conversation) showChatView(d.conversation.id, userName);
      })
      .catch(function () {});
  }

  /* Botón volver */
  msgBackBtn.addEventListener('click', function () {
    showListView();
    startConvPoll();
  });

  /* Mensajes: cargar y renderizar */
  function loadMessages() {
    if (!currentConvId) return;
    fetch(BASE + 'getMessages&id=' + currentConvId)
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.messages) return;
        msgMessages.innerHTML = '';
        lastMsgId = 0;
        d.messages.forEach(function (m) {
          lastMsgId = Math.max(lastMsgId, parseInt(m.id));
          appendMessage(m);
        });
        msgMessages.scrollTop = msgMessages.scrollHeight;
        refreshBadge();
      })
      .catch(function () {});
  }

  function appendMessage(m) {
    var isMine = parseInt(m.sender_id) === myId;
    var ts     = new Date(m.created_at.replace(' ', 'T'));
    var time   = ts.getHours() + ':' + String(ts.getMinutes()).padStart(2, '0');
    var div    = document.createElement('div');
    div.className = 'pf-chat-msg ' + (isMine ? 'mine' : 'theirs');
    div.innerHTML =
      '<div class="pf-chat-bubble">' + escHtml(m.content) + '</div>' +
      '<div class="pf-chat-time">' + time + '</div>';
    msgMessages.appendChild(div);
  }

  function startPoll() {
    stopPoll();
    pollTimer = setInterval(function () {
      if (!currentConvId) return;
      fetch(BASE + 'getMessages&id=' + currentConvId)
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d.messages) return;
          var newMsgs = d.messages.filter(function (m) {
            return parseInt(m.id) > lastMsgId;
          });
          newMsgs.forEach(function (m) {
            lastMsgId = Math.max(lastMsgId, parseInt(m.id));
            appendMessage(m);
          });
          if (newMsgs.length) msgMessages.scrollTop = msgMessages.scrollHeight;
        })
        .catch(function () {});
    }, POLL_MS);
  }

  function stopPoll() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  }

  /* Enviar mensaje */
  function sendMessage() {
    var content = msgInput.value.trim();
    if (!content || !currentConvId) return;
    msgInput.value = '';
    fetch(BASE + 'send', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ conversation_id: currentConvId, content: content })
    })
      .then(function (r) { return r.json(); })
      .then(function () { loadMessages(); })
      .catch(function () {});
  }

  msgSendBtn.addEventListener('click', sendMessage);
  msgInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });

  /* Helpers */
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* Tab / cerrar */
  var moveMode = false, isDragging = false, isResizing = false, resizeDir = '';
  var sX, sY, sL, sT, sW, sH;

  function save() {
    var s = { open: msgPanel.classList.contains('open'), movable: moveMode };
    if (moveMode) {
      s.left   = msgPanel.style.left;   s.top    = msgPanel.style.top;
      s.width  = msgPanel.style.width;  s.height = msgPanel.style.height;
    }
    try { localStorage.setItem(STORE_KEY, JSON.stringify(s)); } catch (e) {}
  }

  function restore() {
    var s;
    try { s = JSON.parse(localStorage.getItem(STORE_KEY)); } catch (e) {}
    if (!s) return;
    if (s.movable && s.left) {
      moveMode = true;
      msgPanel.style.transition = 'none';
      msgPanel.style.bottom    = 'auto';  msgPanel.style.right     = 'auto';
      msgPanel.style.left      = s.left;  msgPanel.style.top       = s.top;
      msgPanel.style.width     = s.width; msgPanel.style.maxHeight = 'none';
      msgPanel.style.height    = s.height;
      msgPanel.classList.add('movible');
      msgMove.querySelector('.material-symbols-outlined').textContent = 'restart_alt';
      msgMove.title = 'Restablecer posición';
      requestAnimationFrame(function () { msgPanel.style.transition = ''; });
    }
    if (s.open) {
      msgPanel.style.transition = 'none';
      msgPanel.classList.add('open');
      msgTabBtn.style.opacity      = '0';
      msgTabBtn.style.pointerEvents = 'none';
      requestAnimationFrame(function () { msgPanel.style.transition = ''; });
      startConvPoll();
    }
  }

  function showTab() { msgTabBtn.style.opacity = '1'; msgTabBtn.style.pointerEvents = 'auto'; }
  function hideTab() { msgTabBtn.style.opacity = '0'; msgTabBtn.style.pointerEvents = 'none'; }

  msgTabBtn.addEventListener('click', function () {
    msgPanel.classList.add('open');
    hideTab();
    save();
    loadConversations();
    startConvPoll();
  });

  msgClose.addEventListener('click', function (e) {
    e.stopPropagation();
    msgPanel.classList.remove('open');
    showTab();
    stopPoll();
    stopConvPoll();
    if (moveMode) resetMoveMode(); else save();
  });

  function enterMoveMode() {
    moveMode = true;
    var r = msgPanel.getBoundingClientRect();
    msgPanel.style.bottom    = 'auto';       msgPanel.style.right  = 'auto';
    msgPanel.style.left      = r.left + 'px'; msgPanel.style.top   = r.top + 'px';
    msgPanel.style.width     = r.width + 'px'; msgPanel.style.maxHeight = 'none';
    msgPanel.style.height    = r.height + 'px';
    msgPanel.classList.add('movible');
    msgMove.querySelector('.material-symbols-outlined').textContent = 'restart_alt';
    msgMove.title = 'Restablecer posición';
    save();
  }

  function resetMoveMode() {
    moveMode = false;
    msgPanel.style.bottom    = DEFAULT.bottom + 'px';
    msgPanel.style.right     = DEFAULT.right + 'px';
    msgPanel.style.left = msgPanel.style.top = msgPanel.style.height = '';
    msgPanel.style.width     = DEFAULT.width + 'px';
    msgPanel.style.maxHeight = DEFAULT.maxHeight + 'px';
    msgPanel.classList.remove('movible');
    msgMove.querySelector('.material-symbols-outlined').textContent = 'open_with';
    msgMove.title = 'Mover y redimensionar';
    save();
  }

  msgMove.addEventListener('click', function () { moveMode ? resetMoveMode() : enterMoveMode(); });

  msgHeader.addEventListener('mousedown', function (e) {
    if (!moveMode || e.target.closest('button')) return;
    isDragging = true;
    sX = e.clientX; sY = e.clientY;
    sL = parseInt(msgPanel.style.left) || 0;
    sT = parseInt(msgPanel.style.top)  || 0;
    e.preventDefault();
  });

  msgPanel.querySelectorAll('.pf-msg-rh').forEach(function (h) {
    h.addEventListener('mousedown', function (e) {
      if (!moveMode) return;
      isResizing = true; resizeDir = h.dataset.dir;
      sX = e.clientX; sY = e.clientY;
      sW = msgPanel.offsetWidth;  sH = msgPanel.offsetHeight;
      sL = parseInt(msgPanel.style.left) || 0;
      sT = parseInt(msgPanel.style.top)  || 0;
      e.preventDefault(); e.stopPropagation();
    });
  });

  document.addEventListener('mousemove', function (e) {
    if (isDragging) {
      msgPanel.style.left = (sL + e.clientX - sX) + 'px';
      msgPanel.style.top  = (sT + e.clientY - sY) + 'px';
    }
    if (isResizing) {
      var dx = e.clientX - sX, dy = e.clientY - sY;
      if (resizeDir.includes('e')) msgPanel.style.width  = Math.max(300, sW + dx) + 'px';
      if (resizeDir.includes('s')) msgPanel.style.height = Math.max(200, sH + dy) + 'px';
      if (resizeDir.includes('w')) {
        var w = Math.max(300, sW - dx);
        msgPanel.style.width = w + 'px'; msgPanel.style.left = (sL + sW - w) + 'px';
      }
      if (resizeDir.includes('n')) {
        var h2 = Math.max(200, sH - dy);
        msgPanel.style.height = h2 + 'px'; msgPanel.style.top = (sT + sH - h2) + 'px';
      }
    }
  });

  document.addEventListener('mouseup', function () {
    if (isDragging || isResizing) save();
    isDragging = isResizing = false;
  });

  restore();

  /* API pública */
  window.msgOpenWith = function (userId, userName) {
    if (!msgPanel.classList.contains('open')) {
      msgPanel.classList.add('open');
      hideTab();
    }
    openConversationWith(parseInt(userId), userName);
  };
})();

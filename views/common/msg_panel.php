<?php
$_msguid    = (int)($_SESSION['usuario']['id'] ?? 0);
$_convs     = $_msguid ? MessageModel::getConversationsForUser($_msguid) : [];
$_msgUnread = 0;
foreach ($_convs as $_c) { $_msgUnread += (int)($_c['unread'] ?? 0); }
$_avColors  = ['a', 'b', 'c', 'd', 'e'];
?>
<script>window.__MY_ID = <?= $_msguid ?>;</script>

<!-- Tab flotante -->
<button class="pf-msg-tab" id="pf-msg-tab-btn">
  <span class="material-symbols-outlined">chat_bubble</span>
  Mensajes
  <span class="pf-msg-tab-badge" id="pf-msg-tab-badge"
        style="display:<?= $_msgUnread > 0 ? 'inline-flex' : 'none' ?>"><?= $_msgUnread ?></span>
</button>

<!-- Panel principal -->
<div class="pf-msg-panel" id="pf-msg-panel">

  <!-- Cabecera compartida -->
  <div class="pf-msg-panel-header">
    <button class="pf-msg-panel-btn pf-msg-back-btn" id="pf-msg-back-btn"
            title="Volver" style="display:none">
      <span class="material-symbols-outlined">arrow_back</span>
    </button>
    <span class="pf-msg-panel-title" id="pf-msg-panel-title">Mensajes</span>
    <div class="pf-msg-panel-actions">
      <button class="pf-msg-panel-btn" id="pf-msg-move-btn" title="Mover y redimensionar">
        <span class="material-symbols-outlined">open_with</span>
      </button>
      <button class="pf-msg-panel-btn" id="pf-msg-close-btn" title="Cerrar">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
  </div>

  <!-- VISTA: Lista de conversaciones -->
  <div class="pf-msg-view" id="pf-msg-view-list">
    <div class="pf-msg-search">
      <span class="material-symbols-outlined">search</span>
      <input type="text" id="pf-msg-search-input" placeholder="Buscar conversación...">
    </div>

    <div class="pf-msg-list" id="pf-msg-list">
      <?php if (empty($_convs)): ?>
        <div class="pf-msg-empty">Sin conversaciones. ¡Inicia una nueva!</div>
      <?php else: ?>
        <?php foreach ($_convs as $_c):
          $_avCls   = $_avColors[(int)$_c['other_id'] % 5];
          $_parts   = array_slice(explode(' ', (string)$_c['other_nombre']), 0, 2);
          $_initials = implode('', array_map(fn($p) => strtoupper($p[0]), $_parts));
          $_preview  = mb_strimwidth((string)($_c['last_content'] ?? '...'), 0, 40, '…');
          $_diff     = $_c['last_message_at'] ? time() - strtotime($_c['last_message_at']) : null;
          $_time     = $_diff === null ? '' : ($_diff < 3600
            ? floor($_diff / 60) . ' min'
            : ($_diff < 86400
                ? floor($_diff / 3600) . ' h'
                : date('d/m', strtotime($_c['last_message_at']))));
        ?>
        <div class="pf-msg-item<?= (int)$_c['unread'] > 0 ? ' no-leido' : '' ?>"
             data-conv-id="<?= (int)$_c['id'] ?>"
             data-other-id="<?= (int)$_c['other_id'] ?>"
             data-other-name="<?= htmlspecialchars($_c['other_nombre']) ?>">
          <div class="pf-msg-avatar <?= $_avCls ?>">
            <?php if (!empty($_c['other_avatar'])): ?>
              <img src="<?= htmlspecialchars($_c['other_avatar']) ?>" alt="">
            <?php else: ?>
              <?= htmlspecialchars($_initials) ?>
            <?php endif; ?>
          </div>
          <div class="pf-msg-body">
            <p class="pf-msg-name"><?= htmlspecialchars($_c['other_nombre']) ?></p>
            <p class="pf-msg-preview"><?= htmlspecialchars($_preview) ?></p>
          </div>
          <div class="pf-msg-meta">
            <span class="pf-msg-time"><?= htmlspecialchars($_time) ?></span>
            <?php if ((int)$_c['unread'] > 0): ?>
              <span class="pf-msg-punto-no-leido"></span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="pf-msg-panel-footer">
      <button class="pf-msg-panel-new" id="pf-msg-new-btn">
        <span class="material-symbols-outlined">add</span>
        Nueva conversación
      </button>
    </div>
  </div>

  <!-- VISTA: Nueva conversación -->
  <div class="pf-msg-view" id="pf-msg-view-new" style="display:none">
    <div class="pf-msg-search">
      <span class="material-symbols-outlined">search</span>
      <input type="text" id="pf-msg-new-search" placeholder="Buscar usuario...">
    </div>
    <div class="pf-msg-list" id="pf-msg-users-list">
      <div class="pf-msg-loading">Cargando usuarios...</div>
    </div>
  </div>

  <!-- VISTA: Chat -->
  <div class="pf-msg-view" id="pf-msg-view-chat" style="display:none">
    <div class="pf-msg-messages" id="pf-msg-messages"></div>
    <div class="pf-msg-composer">
      <input type="text" id="pf-msg-input" placeholder="Escribe un mensaje..." autocomplete="off">
      <button class="pf-msg-send-btn" id="pf-msg-send-btn">
        <span class="material-symbols-outlined">send</span>
      </button>
    </div>
  </div>

  <!-- Asas de redimensionado -->
  <div class="pf-msg-rh" data-dir="n"></div>
  <div class="pf-msg-rh" data-dir="s"></div>
  <div class="pf-msg-rh" data-dir="e"></div>
  <div class="pf-msg-rh" data-dir="w"></div>
  <div class="pf-msg-rh" data-dir="ne"></div>
  <div class="pf-msg-rh" data-dir="nw"></div>
  <div class="pf-msg-rh" data-dir="se"></div>
  <div class="pf-msg-rh" data-dir="sw"></div>
</div>

<script src="<?= BASE ?>/assets/js/messages.js?v=<?= filemtime(__DIR__ . '/../../assets/js/messages.js') ?>"></script>

<?php
$__navPerms = $_SESSION["nav_permissions"] ?? null;
$__title    = isset($__pageTitle) ? htmlspecialchars($__pageTitle) . ' — AlertHub' : 'AlertHub · Gestor de Incidencias';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $__title ?></title>
  <style>
    .skip-link{position:absolute;top:-40px;left:0;background:#4648d4;color:#fff;padding:8px 16px;z-index:10000;border-radius:0 0 6px 0;font-size:.875rem;text-decoration:none;transition:top .15s}
    .skip-link:focus{top:0}
    .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  </style>
  <script>(function(){try{if(localStorage.getItem('theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}}());</script>
  <?php if (is_array($__navPerms)): ?>
  <script>
    window.__NAV_PERMISSIONS = <?= json_encode($__navPerms, JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <?php endif; ?>
</head>
<body style="margin:0;background:#020617;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;">
<a class="skip-link" href="#main-content">Ir al contenido principal</a>

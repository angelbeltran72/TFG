<?php $__pageTitle = "Establecer contraseña"; ?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/login.css">

<div class="autenticacion" id="main-content">
  <div class="autenticacion-tarjeta">

    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
      <div style="
        width:40px;height:40px;border-radius:10px;
        background:rgba(70,72,212,0.12);
        display:flex;align-items:center;justify-content:center;
        flex-shrink:0;
      ">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4648d4" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
      </div>
      <div>
        <h1 style="font-size:20px;margin-bottom:2px">Establece tu contraseña</h1>
      </div>
    </div>

    <p style="font-size:13px;color:#64748b;line-height:1.55;margin-bottom:24px">
      Tu cuenta fue creada por un administrador con una contraseña temporal.
      Por seguridad, debes establecer una contraseña personal antes de continuar.
    </p>

    <form id="changePassForm" method="POST" action="index.php?controller=Auth&action=cambiarPasswordInicial">
      <?= Csrf::field() ?>

      <div class="autenticacion-campo">
        <label for="password" class="sr-only">Nueva contraseña</label>
        <div class="input-wrap">
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Nueva contraseña"
            autofocus
            required aria-required="true"
            aria-describedby="passwordRules">
          <button type="button" class="toggle-pass" aria-label="Mostrar contraseña" onclick="togglePassword('password')">👁️</button>
        </div>

        <div class="reglas-contrasena" id="passwordRules" aria-live="polite">
          <div class="regla" data-rule="length">Mínimo 8 caracteres</div>
          <div class="regla" data-rule="upper">Al menos una mayúscula</div>
          <div class="regla" data-rule="lower">Al menos una minúscula</div>
          <div class="regla" data-rule="number">Al menos un número</div>
        </div>
      </div>

      <div class="autenticacion-campo">
        <label for="password2" class="sr-only">Repetir contraseña</label>
        <div class="input-wrap">
          <input
            type="password"
            id="password2"
            name="password2"
            placeholder="Repite la contraseña"
            required aria-required="true"
            aria-describedby="passwordMatch">
          <button type="button" class="toggle-pass" aria-label="Mostrar contraseña" onclick="togglePassword('password2')">👁️</button>
        </div>
        <div class="coincidencia" id="passwordMatch" aria-live="polite"></div>
      </div>

      <button id="changePassBtn" class="btn" type="submit">Establecer contraseña y entrar</button>
    </form>

  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="<?= BASE ?>/assets/js/liquid-gradient.js"></script>
<script src="<?= BASE ?>/assets/js/auth.js"></script>

<?php require_once "views/common/pie.php"; ?>

<?php $__pageTitle = "Nueva contraseña"; ?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/login.css">
<meta name="referrer" content="no-referrer">

<?php
// Recupera errores del intento anterior
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["errors"]);
?>

<div class="autenticacion" id="main-content">
  <div class="autenticacion-tarjeta">
    <h1>Nueva contraseña</h1>

    <?php if (!empty($_SESSION["flash"])):
      $f = $_SESSION["flash"]; unset($_SESSION["flash"]); ?>
      <div class="mensaje <?= htmlspecialchars($f["type"]) ?>" role="alert" aria-live="polite">
        <?= htmlspecialchars($f["msg"]) ?>
      </div>
    <?php endif; ?>

    <form id="resetForm" method="POST" action="index.php?controller=Auth&action=procesarRestablecimiento">
      <?= Csrf::field() // Token oculto para evitar ataques CSRF ?>
      <!-- Token del enlace de recuperación — se valida en el controlador -->
      <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? "") ?>">

      <div class="autenticacion-campo">
        <label for="password" class="sr-only">Nueva contraseña</label>
        <div class="input-wrap">
          <input type="password" id="password" name="password" placeholder="Nueva contraseña"
            class="<?= !empty($errors["password"]) ? 'invalido' : '' ?>" autofocus required
            aria-required="true" aria-describedby="passwordRules">
          <button type="button" class="toggle-pass" aria-label="Mostrar contraseña" onclick="togglePassword('password')">👁️</button>
        </div>

        <!-- Indicadores en tiempo real de los requisitos de la contraseña -->
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
          <input type="password" id="password2" name="password2" placeholder="Repite contraseña"
          class="<?= !empty($errors["password2"]) ? 'invalido' : '' ?>" required
          aria-required="true" aria-describedby="passwordMatch">
          <button type="button" class="toggle-pass" aria-label="Mostrar contraseña" onclick="togglePassword('password2')">👁️</button>
        </div>

        <!-- Mensaje de coincidencia entre contraseña y su confirmación -->
        <div class="coincidencia" id="passwordMatch" aria-live="polite"></div>
      </div>

      <button id="resetBtn" class="btn" type="submit">Actualizar contraseña</button>
    </form>

    <div class="autenticacion-pie">
      <a href="index.php?controller=Auth&action=iniciarSesion">Volver al inicio de sesión</a>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="<?= BASE ?>/assets/js/liquid-gradient.js"></script>
<script src="<?= BASE ?>/assets/js/auth.js"></script>

<?php require_once "views/common/pie.php"; ?>

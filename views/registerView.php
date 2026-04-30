<?php $__pageTitle = "Crear cuenta"; ?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/login.css">

<?php
// Recupera los valores del formulario y errores del intento anterior
$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["old"], $_SESSION["errors"]);
?>

<div class="autenticacion" id="main-content">
  <div class="autenticacion-tarjeta">
    <h1>Crear cuenta</h1>

    <?php if (!empty($_SESSION["flash"])):
      $f = $_SESSION["flash"]; unset($_SESSION["flash"]); ?>
      <div class="mensaje <?= htmlspecialchars($f["type"]) ?>" role="alert" aria-live="polite">
        <?= htmlspecialchars($f["msg"]) ?>
      </div>
    <?php endif; ?>

    <form id="registerForm" method="POST" action="index.php?controller=Auth&action=procesarRegistro">
      <?= Csrf::field() // Token oculto para evitar ataques CSRF ?>

      <div class="autenticacion-campo">
        <label for="reg_nombre" class="sr-only">Nombre Completo</label>
        <input type="text" id="reg_nombre" name="nombre" placeholder="Nombre Completo" value="<?= htmlspecialchars($old["nombre"] ?? "") ?>"
          class="<?= !empty($errors["nombre"]) ? 'invalido' : '' ?>" autofocus required aria-required="true">
      </div>

      <div class="autenticacion-campo">
        <label for="reg_email" class="sr-only">Email</label>
        <input type="email" id="reg_email" name="email" placeholder="Email" value="<?= htmlspecialchars($old["email"] ?? "") ?>"
          class="<?= !empty($errors["email"]) ? 'invalido' : '' ?>" required aria-required="true">
      </div>

      <div class="autenticacion-campo">
        <label for="password" class="sr-only">Contraseña</label>
        <div class="input-wrap">
          <input type="password" id="password" name="password" placeholder="Contraseña"
            class="<?= !empty($errors["password"]) ? 'invalido' : '' ?>" required aria-required="true"
            aria-describedby="passwordRules">
          <button type="button" class="toggle-pass" aria-label="Mostrar u ocultar contraseña" onclick="togglePassword('password')">👁️</button>
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
          <input type="password" id="password2" name="password2" placeholder="Repite la contraseña"
            class="<?= !empty($errors["password2"]) ? 'invalido' : '' ?>" required aria-required="true"
            aria-describedby="passwordMatch">
          <button type="button" class="toggle-pass" aria-label="Mostrar u ocultar contraseña" onclick="togglePassword('password2')">👁️</button>
        </div>
        <!-- Mensaje de coincidencia entre contraseña y su confirmación -->
        <div class="coincidencia" id="passwordMatch" aria-live="polite"></div>
      </div>

      <button id="registerBtn" class="btn" type="submit">Crear cuenta</button>
    </form>

    <div class="autenticacion-pie">
      ¿Ya tienes cuenta?
      <a href="index.php?controller=Auth&action=iniciarSesion">Iniciar sesión</a>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="<?= BASE ?>/assets/js/liquid-gradient.js"></script>
<script src="<?= BASE ?>/assets/js/auth.js"></script>

<?php require_once "views/common/pie.php"; ?>

<?php $__pageTitle = "Iniciar sesión"; ?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/login.css">

<?php
// Recupera los valores del formulario y errores del intento anterior
$old    = $_SESSION["old"]    ?? [];
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["old"], $_SESSION["errors"]);
?>

<div class="autenticacion" id="main-content">
  <div class="autenticacion-tarjeta">
    <h1>Iniciar sesión</h1>

    <?php if (!empty($_SESSION["flash"])):
      $f = $_SESSION["flash"]; unset($_SESSION["flash"]); ?>
      <div class="mensaje <?= htmlspecialchars($f["type"]) ?>" role="alert" aria-live="polite">
        <?= htmlspecialchars($f["msg"]) ?>
      </div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="index.php?controller=Auth&action=procesarInicioSesion">
      <?= Csrf::field() // Token oculto para evitar ataques CSRF ?>

      <div class="autenticacion-campo">
        <label for="login_email" class="sr-only">Email</label>
        <input type="email" id="login_email" name="email" placeholder="Email"
          value="<?= htmlspecialchars($old["email"] ?? "") ?>"
          class="<?= !empty($errors["email"]) ? "invalido" : "" ?>"
          autocomplete="username" spellcheck="false" autofocus required
          aria-required="true">
      </div>

      <div class="autenticacion-campo">
        <label for="login_password" class="sr-only">Contraseña</label>
        <div class="input-wrap">
          <input type="password" id="login_password" name="password" placeholder="Contraseña"
            class="<?= !empty($errors["password"]) ? "invalido" : "" ?>"
            autocomplete="current-password" required aria-required="true">
          <button type="button" class="toggle-pass" aria-label="Mostrar u ocultar contraseña"
            onclick="togglePassword('login_password')">👁️</button>
        </div>
      </div>

      <div class="autenticacion-fila">
        <label class="recordar-sesion">
          <input type="checkbox" name="remember" value="1">
          Recuérdame
        </label>
        <a href="index.php?controller=Auth&action=recuperarContrasena">¿Olvidaste tu contraseña?</a>
      </div>

      <button id="loginBtn" class="btn" type="submit">Entrar</button>
    </form>

    <div class="autenticacion-pie">
      ¿No tienes cuenta?
      <a href="index.php?controller=Auth&action=registrar">Crear cuenta</a>
    </div>
  </div>
</div>

<script>
function togglePassword(id) {
  const input = document.getElementById(id);
  input.type = (input.type === "password") ? "text" : "password";
}
document.getElementById("loginForm").addEventListener("submit", () => {
  const btn = document.getElementById("loginBtn");
  btn.disabled = true;
  btn.textContent = "Entrando...";
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="<?= BASE ?>/assets/js/liquid-gradient.js"></script>

<?php require_once "views/common/pie.php"; ?>

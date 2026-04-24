<?php $__pageTitle = "Recuperar contraseña"; ?>
<?php require_once "views/common/cabecera.php"; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/login.css">

<?php
// Recupera email y errores del intento anterior
$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["old"], $_SESSION["errors"]);
?>

<div class="autenticacion" id="main-content">
  <div class="autenticacion-tarjeta">
    <h1>Recuperar contraseña</h1>

    <?php if (!empty($_SESSION["flash"])):
      $f = $_SESSION["flash"]; unset($_SESSION["flash"]); ?>
      <div class="mensaje <?= htmlspecialchars($f["type"]) ?>" role="alert" aria-live="polite">
        <?= htmlspecialchars($f["msg"]) ?>
      </div>
    <?php endif; ?>

    <form id="forgotForm" method="POST" action="index.php?controller=Auth&action=enviarRecuperacion">
      <?= Csrf::field() // Token oculto para evitar ataques CSRF ?>
      <div class="autenticacion-campo">
        <label for="forgot_email" class="sr-only">Tu email</label>
        <input type="email" id="forgot_email" name="email" placeholder="Tu email" value="<?= htmlspecialchars($old["email"] ?? "") ?>"
        class="<?= !empty($errors["email"]) ? 'is-invalid' : '' ?>" autofocus required aria-required="true">
      </div>
      <button id="forgotBtn" class="btn" type="submit">Enviar enlace</button>
    </form>

    <div class="autenticacion-pie">
      <a href="index.php?controller=Auth&action=iniciarSesion">
        Volver al inicio de sesión
      </a>
    </div>
  </div>
</div>

<script>
document.getElementById("forgotForm").addEventListener("submit", () => {
  const btn = document.getElementById("forgotBtn");
  btn.disabled = true;
  btn.textContent = "Enviando...";
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="<?= BASE ?>/assets/js/liquid-gradient.js"></script>

<?php require_once "views/common/pie.php"; ?>

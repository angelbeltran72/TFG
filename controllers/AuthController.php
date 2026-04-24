<?php
class AuthController extends AppController {

  // helpers

  private function verificarCsrf(string $fallback): void {
    $token = $_POST["csrf_token"] ?? "";
    if (!Csrf::validate($token)) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Token de seguridad inválido. Recarga la página."];
      header("Location: " . $fallback);
      exit;
    }
  }

  private function passwordStrong(string $pass): bool {
    if (strlen($pass) > 72) return false;
    return
      strlen($pass) >= 8 &&
      preg_match("/[A-Z]/", $pass) &&
      preg_match("/[a-z]/", $pass) &&
      preg_match("/[0-9]/", $pass);
  }

  private function getIp(): string {
    return $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";
  }

  private function redirectByRole(string $role): void {
    $target = ($role === "cliente")
      ? "index.php?controller=Ticket&action=misTickets"
      : "index.php?controller=Dashboard&action=index";
    header("Location: " . $target);
    exit;
  }

  private function linkClientTickets(array $user): void {
    if (($user["rol"] ?? "user") !== "cliente") {
      return;
    }
    TicketModel::linkClientTicketsByEmail((int)$user["id"], (string)($user["email"] ?? ""));
  }

  // login

  public function iniciarSesion() {
    if (isset($_SESSION["usuario"])) {
      $this->redirectByRole($_SESSION["usuario"]["rol"] ?? "user");
    }
    $this->view->show("loginView");
  }

  public function procesarInicioSesion() {
    $this->verificarCsrf("index.php?controller=Auth&action=iniciarSesion");

    $email = trim($_POST["email"] ?? "");
    $pass  = $_POST["password"] ?? "";

    $_SESSION["old"]    = ["email" => $email];
    $_SESSION["errors"] = [];

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $_SESSION["errors"]["email"] = true;
    }
    if ($pass === "") {
      $_SESSION["errors"]["password"] = true;
    }

    if (!empty($_SESSION["errors"])) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Revisa los campos marcados."];
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }

    $u = UsuarioModel::findByEmail($email);

    if (!$u || !password_verify($pass, $u["password_hash"])) {
      $_SESSION["errors"] = ["email" => true, "password" => true];
      $_SESSION["flash"]  = ["type" => "error", "msg" => "Email o contraseña incorrectos."];
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }

    if (!(int)$u["is_active"]) {
      $_SESSION["errors"] = ["email" => true];
      $_SESSION["flash"]  = ["type" => "error", "msg" => "Tu cuenta está desactivada. Contacta con el administrador."];
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }

    session_regenerate_id(true);
    Csrf::regenerate();

    $_SESSION["usuario"] = [
      "id"          => $u["id"],
      "nombre"      => $u["nombre"],
      "email"       => $u["email"],
      "rol"         => $u["rol"] ?? "user",
      "avatar_path" => $u["avatar_path"] ?? null,
    ];
    $_SESSION["login_time"] = time();

    UsuarioModel::touchLastLogin((int)$u["id"]);
    UsuarioModel::touchLastSeen((int)$u["id"]);

    if (!empty($_POST["remember"])) {
      $token  = bin2hex(random_bytes(32));
      $hash   = hash("sha256", $token);
      $secure = filter_var($_ENV["COOKIE_SECURE"] ?? "false", FILTER_VALIDATE_BOOLEAN);
      UsuarioModel::saveRememberToken($u["id"], $hash);
      setcookie("remember_token", $token, time() + (60 * 60 * 24 * 30), "/", "", $secure, true);
    }

    $this->linkClientTickets($u);

    unset($_SESSION["old"], $_SESSION["errors"]);

    // Si la cuenta tiene contraseña temporal, forzar cambio antes del dashboard
    if ((int)($u["must_change_password"] ?? 0)) {
      header("Location: index.php?controller=Auth&action=mostrarCambioPasswordInicial");
      exit;
    }

    ActivityLogModel::log((int)$u["id"], "auth", "Inicio de sesión desde " . $this->getIp(), $this->getIp());

    $_SESSION["flash"] = ["type" => "ok", "msg" => "Login correcto."];
    $this->redirectByRole($u["rol"] ?? "user");
  }

  // cambio de contraseña inicial (cuenta creada por admin)

  public function mostrarCambioPasswordInicial() {
    if (!isset($_SESSION["usuario"])) {
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }
    $this->view->show("changePasswordView");
  }

  public function cambiarPasswordInicial() {
    if (!isset($_SESSION["usuario"])) {
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }

    $this->verificarCsrf("index.php?controller=Auth&action=mostrarCambioPasswordInicial");

    $p1 = $_POST["password"]  ?? "";
    $p2 = $_POST["password2"] ?? "";

    $_SESSION["errors"] = [];

    if (!$this->passwordStrong($p1)) $_SESSION["errors"]["password"] = true;
    if ($p1 !== $p2)                 $_SESSION["errors"]["password2"] = true;

    if (!empty($_SESSION["errors"])) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Revisa los campos marcados."];
      header("Location: index.php?controller=Auth&action=mostrarCambioPasswordInicial");
      exit;
    }

    $userId = (int)$_SESSION["usuario"]["id"];
    UsuarioModel::updatePassword($userId, password_hash($p1, PASSWORD_DEFAULT));
    UsuarioModel::setMustChangePassword($userId, false);

    ActivityLogModel::log($userId, "auth", "Contraseña inicial establecida", $this->getIp());

    unset($_SESSION["errors"]);
    $_SESSION["flash"] = ["type" => "ok", "msg" => "Contraseña establecida. Bienvenido/a."];
    $this->linkClientTickets($_SESSION["usuario"]);
    $this->redirectByRole($_SESSION["usuario"]["rol"] ?? "user");
  }

  // logout

  public function cerrarSesion() {
    $token = $_POST["csrf_token"] ?? "";
    if (!Csrf::validate($token)) {
      header("Location: index.php?controller=Dashboard&action=index");
      exit;
    }

    if (isset($_SESSION["usuario"]["id"])) {
      UsuarioModel::saveRememberToken($_SESSION["usuario"]["id"], null);
    }

    $secure = filter_var($_ENV["COOKIE_SECURE"] ?? "false", FILTER_VALIDATE_BOOLEAN);
    if (!empty($_COOKIE["remember_token"])) {
      setcookie("remember_token", "", time() - 3600, "/", "", $secure, true);
    }

    session_destroy();
    header("Location: index.php?controller=Auth&action=iniciarSesion");
    exit;
  }

  // registro

  public function registrar() {
    if (isset($_SESSION["usuario"])) {
      $this->redirectByRole($_SESSION["usuario"]["rol"] ?? "user");
    }
    if (!SystemSettingModel::getBool('registro_abierto', true)) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "El registro de nuevas cuentas está cerrado temporalmente."];
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }
    $this->view->show("registerView");
  }

  public function procesarRegistro() {
    $this->verificarCsrf("index.php?controller=Auth&action=registrar");

    if (!SystemSettingModel::getBool('registro_abierto', true)) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "El registro de nuevas cuentas está cerrado temporalmente."];
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }

    $_SESSION["errors"] = [];
    $_SESSION["old"]    = [];

    $nombre = trim($_POST["nombre"] ?? "");
    $email  = trim($_POST["email"]  ?? "");
    $pass   = $_POST["password"]    ?? "";
    $pass2  = $_POST["password2"]   ?? "";

    $_SESSION["old"] = ["nombre" => $nombre, "email" => $email];

    if ($nombre === "") $_SESSION["errors"]["nombre"] = true;
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) $_SESSION["errors"]["email"] = true;
    if (!$this->passwordStrong($pass)) $_SESSION["errors"]["password"] = true;
    if ($pass !== $pass2) $_SESSION["errors"]["password2"] = true;

    if (UsuarioModel::existsByEmail($email)) {
      $_SESSION["errors"]["email"] = true;
      $_SESSION["flash"] = ["type" => "error", "msg" => "Revisa los campos del formulario."];
    }

    if (!empty($_SESSION["errors"])) {
      if (empty($_SESSION["flash"])) {
        $_SESSION["flash"] = ["type" => "error", "msg" => "Revisa los campos marcados."];
      }
      header("Location: index.php?controller=Auth&action=registrar");
      exit;
    }

    $newUserId = UsuarioModel::create($nombre, $email, password_hash($pass, PASSWORD_DEFAULT), "cliente");
    TicketModel::linkClientTicketsByEmail($newUserId, $email);

    unset($_SESSION["errors"], $_SESSION["old"]);
    $_SESSION["flash"] = ["type" => "ok", "msg" => "Cuenta creada correctamente. Inicia sesión."];
    header("Location: index.php?controller=Auth&action=iniciarSesion");
    exit;
  }

  // recuperar contraseña

  public function recuperarContrasena() {
    $this->view->show("forgotView");
  }

  public function enviarRecuperacion() {
    $this->verificarCsrf("index.php?controller=Auth&action=recuperarContrasena");

    $email = trim($_POST["email"] ?? "");

    $_SESSION["old"]    = ["email" => $email];
    $_SESSION["errors"] = [];

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $_SESSION["errors"]["email"] = true;
      $_SESSION["flash"] = ["type" => "error", "msg" => "Revisa el email."];
      header("Location: index.php?controller=Auth&action=recuperarContrasena");
      exit;
    }

    $u = UsuarioModel::findByEmail($email);

    $_SESSION["flash"] = ["type" => "ok", "msg" => "Si el email existe, recibirás un enlace en unos minutos."];

    if ($u) {
      if (!PasswordResetModel::hasActiveToken((int)$u["id"])) {
        $token   = bin2hex(random_bytes(32));
        $hash    = hash("sha256", $token);
        $expires = date("Y-m-d H:i:s", time() + 60 * 30);

        PasswordResetModel::deleteByUser((int)$u["id"]);
        PasswordResetModel::create($u["id"], $hash, $expires);

        $baseUrl   = rtrim($_ENV["APP_URL"], "/");
        $resetLink = "{$baseUrl}/index.php?controller=Auth&action=restablecerContrasena&token=" . urlencode($token);

        try {
          Mailer::enviarRecuperacion($u["email"], $u["nombre"] ?? "", $resetLink);
        } catch (Throwable $e) {
          error_log("[Mailer] " . $e->getMessage());
        }
      }
    }

    unset($_SESSION["errors"]);
    header("Location: index.php?controller=Auth&action=iniciarSesion");
    exit;
  }

  // envío de recuperación desde perfil (usuario ya autenticado)

  public function enviarRecuperacionDesdePerfil() {
    if (!isset($_SESSION["usuario"])) {
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }
    $this->verificarCsrf("index.php?controller=Perfil&action=verPerfil");

    $u = UsuarioModel::findByEmail($_SESSION["usuario"]["email"] ?? "");

    if ($u && !PasswordResetModel::hasActiveToken((int)$u["id"])) {
      $token   = bin2hex(random_bytes(32));
      $hash    = hash("sha256", $token);
      $expires = date("Y-m-d H:i:s", time() + 60 * 30);

      PasswordResetModel::deleteByUser((int)$u["id"]);
      PasswordResetModel::create($u["id"], $hash, $expires);

      $baseUrl   = rtrim($_ENV["APP_URL"], "/");
      $resetLink = "{$baseUrl}/index.php?controller=Auth&action=restablecerContrasena&token=" . urlencode($token);

      try {
        Mailer::enviarRecuperacion($u["email"], $u["nombre"] ?? "", $resetLink);
      } catch (Throwable $e) {
        error_log("[Mailer] " . $e->getMessage());
      }
    }

    $_SESSION["flash"] = ["type" => "ok", "msg" => "Enlace de recuperación enviado a tu correo."];
    header("Location: index.php?controller=Perfil&action=verPerfil");
    exit;
  }

  // restablecer contraseña

  public function restablecerContrasena() {
    $token = $_GET["token"] ?? "";
    if ($token === "") {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Enlace no válido."];
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }
    $this->view->show("resetView", ["token" => $token]);
  }

  public function procesarRestablecimiento() {
    $this->verificarCsrf("index.php?controller=Auth&action=iniciarSesion");

    $token = $_POST["token"] ?? "";
    $p1    = $_POST["password"]  ?? "";
    $p2    = $_POST["password2"] ?? "";

    $_SESSION["errors"] = [];

    if ($token === "") {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Enlace no válido."];
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }

    if (!$this->passwordStrong($p1)) $_SESSION["errors"]["password"] = true;
    if ($p1 !== $p2) $_SESSION["errors"]["password2"] = true;

    if (!empty($_SESSION["errors"])) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Revisa los campos marcados."];
      header("Location: index.php?controller=Auth&action=restablecerContrasena&token=" . urlencode($token));
      exit;
    }

    $hash = hash("sha256", $token);
    $row  = PasswordResetModel::findValidByTokenHash($hash);

    if (!$row) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Token inválido o caducado."];
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }

    UsuarioModel::updatePassword($row["user_id"], password_hash($p1, PASSWORD_DEFAULT));
    PasswordResetModel::markUsed($row["id"]);

    unset($_SESSION["errors"]);
    $_SESSION["flash"] = ["type" => "ok", "msg" => "Contraseña actualizada. Ya puedes iniciar sesión."];
    header("Location: index.php?controller=Auth&action=iniciarSesion");
    exit;
  }
}

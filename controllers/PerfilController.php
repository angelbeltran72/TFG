<?php
// Controlador de perfil — gestiona el perfil del usuario logueado y la lista de usuarios (admin)
class PerfilController extends AppController {

  private function requireLogin(): array {
    if (!isset($_SESSION["usuario"])) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Debes iniciar sesión."];
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }
    return $_SESSION["usuario"];
  }

  private function requireAdmin(): array {
    $u = $this->requireLogin();
    if (($u["rol"] ?? "user") !== "admin") {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Acceso restringido."];
      header("Location: index.php?controller=Dashboard&action=verPerfil");
      exit;
    }
    return $u;
  }

  public function verPerfil() {
    $user = $this->requireLogin();
    $isAdmin = (($user["rol"] ?? "user") === "admin");

    $dbUser = UsuarioModel::findById((int)$user["id"]);
    if ($dbUser) {
      $_SESSION["usuario"]["avatar_path"]  = $dbUser["avatar_path"] ?? null;
      $_SESSION["usuario"]["last_seen_at"] = $dbUser["last_seen_at"] ?? null;
      $user = $_SESSION["usuario"];
    }

    $userDepts      = UserDepartamentoModel::getAllForUser((int)$user["id"]);
    $primaryDept    = null;
    if (!empty($dbUser["departamento_id"])) {
      $primaryDept = DepartamentoModel::findById((int)$dbUser["departamento_id"]);
    }
    if (!$primaryDept && !empty($userDepts)) {
      $primaryDept = $userDepts[0];
    }

    $stats        = TicketModel::getUserStats((int)$user["id"]);
    $recentActivity = TicketModel::listRecentActivityForUser((int)$user["id"], 8);
    $notifPrefs   = UsuarioModel::getNotificationPrefs((int)$user["id"]);
    $createdAt    = $dbUser["created_at"] ?? null;

    $this->view->show("perfilView", [
      "user"           => $user,
      "isAdmin"        => $isAdmin,
      "stats"          => $stats,
      "recentActivity" => $recentActivity,
      "primaryDept"    => $primaryDept,
      "userDepts"      => $userDepts,
      "notifPrefs"     => $notifPrefs,
      "createdAt"      => $createdAt,
    ]);
  }

  // Sube y guarda un nuevo avatar (JPG/PNG/WEBP, máx. 2 MB)
  public function actualizarAvatar() {
    $user = $this->requireLogin();

    if (!Csrf::validate($_POST["csrf_token"] ?? "")) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Token CSRF inválido."];
      header("Location: index.php?controller=Perfil&action=verPerfil");
      exit;
    }

    if (!isset($_FILES["avatar"]) || $_FILES["avatar"]["error"] === UPLOAD_ERR_NO_FILE) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "No has seleccionado ningún archivo."];
      header("Location: index.php?controller=Perfil&action=verPerfil");
      exit;
    }

    if ($_FILES["avatar"]["error"] !== UPLOAD_ERR_OK) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Error al subir el archivo."];
      header("Location: index.php?controller=Perfil&action=verPerfil");
      exit;
    }

    $maxBytes = 2 * 1024 * 1024;
    if ($_FILES["avatar"]["size"] > $maxBytes) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Máximo 2MB."];
      header("Location: index.php?controller=Perfil&action=verPerfil");
      exit;
    }

    $tmp = $_FILES["avatar"]["tmp_name"];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);

    $allowed = [
      "image/jpeg" => "jpg",
      "image/png"  => "png",
      "image/webp" => "webp",
    ];

    if (!isset($allowed[$mime])) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Formato no permitido (solo JPG/PNG/WEBP)."];
      header("Location: index.php?controller=Perfil&action=verPerfil");
      exit;
    }

    $ext = $allowed[$mime];
    $dirFs = __DIR__ . "/../uploads/avatars";
    if (!is_dir($dirFs)) mkdir($dirFs, 0755, true);

    $filename = "u" . (int)$user["id"] . "_" . time() . "." . $ext;
    $destFs = $dirFs . "/" . $filename;

    if (!move_uploaded_file($tmp, $destFs)) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "No se pudo guardar el avatar."];
      header("Location: index.php?controller=Perfil&action=verPerfil");
      exit;
    }

    $publicPath = BASE . "/uploads/avatars/" . $filename;

    UsuarioModel::updateAvatar((int)$user["id"], $publicPath);
    $_SESSION["usuario"]["avatar_path"] = $publicPath;

    $_SESSION["flash"] = ["type" => "success", "msg" => "Avatar actualizado."];
    header("Location: index.php?controller=Perfil&action=verPerfil");
    exit;
  }

  public function quitarAvatar() {
    $user = $this->requireLogin();

    if (!Csrf::validate($_POST["csrf_token"] ?? "")) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Token CSRF inválido."];
      header("Location: index.php?controller=Perfil&action=verPerfil");
      exit;
    }

    // Eliminar el archivo anterior del disco si existe
    if (!empty($user["avatar_path"])) {
      $fsPath = __DIR__ . "/../" . ltrim(substr($user["avatar_path"], strlen(BASE)), "/");
      if (file_exists($fsPath)) @unlink($fsPath);
    }

    UsuarioModel::updateAvatar((int)$user["id"], "");
    $_SESSION["usuario"]["avatar_path"] = "";

    $_SESSION["flash"] = ["type" => "success", "msg" => "Avatar eliminado."];
    header("Location: index.php?controller=Perfil&action=verPerfil");
    exit;
  }

  public function guardarPreferenciasNotificacion() {
    $user = $this->requireLogin();
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token CSRF inválido.'];
      header('Location: index.php?controller=Perfil&action=verPerfil');
      exit;
    }
    $allowed = ['ticket_assigned', 'ticket_comment', 'ticket_status', 'ticket_overdue'];
    $prefs   = [];
    foreach ($allowed as $key) {
      $prefs[$key] = !empty($_POST['notif'][$key]);
    }
    UsuarioModel::saveNotificationPrefs((int)$user['id'], $prefs);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Preferencias de notificación guardadas.'];
    header('Location: index.php?controller=Perfil&action=verPerfil');
    exit;
  }

  public function guardarPerfil() {
    $user   = $this->requireLogin();
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token CSRF inválido.'];
      header('Location: index.php?controller=Perfil&action=verPerfil');
      exit;
    }
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email']  ?? '');

    $errors = [];
    if ($nombre === '' || mb_strlen($nombre) < 2) $errors[] = 'El nombre es obligatorio (mín. 2 caracteres).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'El email no es válido.';
    if ($email !== $user['email'] && UsuarioModel::existsByEmail($email)) $errors[] = 'Ese email ya está en uso.';

    if (!empty($errors)) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => implode(' ', $errors)];
      header('Location: index.php?controller=Perfil&action=verPerfil');
      exit;
    }

    UsuarioModel::updateProfile((int)$user['id'], $nombre, $email);
    $_SESSION['usuario']['nombre'] = $nombre;
    $_SESSION['usuario']['email']  = $email;

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Perfil actualizado.'];
    header('Location: index.php?controller=Perfil&action=verPerfil');
    exit;
  }

  public function cambiarPasswordPerfil() {
    $user    = $this->requireLogin();
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token CSRF inválido.'];
      header('Location: index.php?controller=Perfil&action=verPerfil');
      exit;
    }
    $actual  = $_POST['password_actual']  ?? '';
    $nuevo   = $_POST['password_nuevo']   ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    $dbUser = UsuarioModel::findById((int)$user['id']);
    if (!$dbUser || !password_verify($actual, $dbUser['password_hash'])) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'La contraseña actual es incorrecta.'];
      header('Location: index.php?controller=Perfil&action=verPerfil');
      exit;
    }

    if (mb_strlen($nuevo) < 8) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'La nueva contraseña debe tener al menos 8 caracteres.'];
      header('Location: index.php?controller=Perfil&action=verPerfil');
      exit;
    }

    if ($nuevo !== $confirm) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Las contraseñas no coinciden.'];
      header('Location: index.php?controller=Perfil&action=verPerfil');
      exit;
    }

    UsuarioModel::updatePassword((int)$user['id'], password_hash($nuevo, PASSWORD_DEFAULT));
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Contraseña actualizada.'];
    header('Location: index.php?controller=Perfil&action=verPerfil');
    exit;
  }

  public function listarUsuarios() {
    $this->requireAdmin();

    $q = trim($_GET["q"] ?? "");
    $usuarios = ($q === "") ? UsuarioModel::listAll() : UsuarioModel::searchAll($q);

    $this->view->show("usuariosListView", [
      "usuarios" => $usuarios,
      "q" => $q
    ]);
  }

  public function verPerfilUsuario() {
    $this->requireAdmin();

    $id = (int)($_GET["id"] ?? 0);
    if ($id <= 0) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Usuario inválido."];
      header("Location: index.php?controller=Perfil&action=listarUsuarios");
      exit;
    }

    $u = UsuarioModel::findById($id);
    if (!$u) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Usuario no encontrado."];
      header("Location: index.php?controller=Perfil&action=listarUsuarios");
      exit;
    }

    $stats       = TicketModel::getUserStats((int)$u["id"]);
    $ultimos     = TicketModel::listRecentForUser((int)$u["id"], false, 6);
    $targetDepts = UserDepartamentoModel::getAllForUser((int)$u["id"]);
    $allDepts    = DepartamentoModel::getAllActive();

    $this->view->show("perfilUsuarioView", [
      "target"      => $u,
      "isAdmin"     => true,
      "stats"       => $stats,
      "ultimos"     => $ultimos,
      "targetDepts" => $targetDepts,
      "allDepts"    => $allDepts,
      "createdAt"   => $u["created_at"] ?? null,
    ]);
  }

  public function editarPerfilUsuario() {
    $admin = $this->requireAdmin();

    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token CSRF inválido.'];
      header('Location: index.php?controller=Perfil&action=listarUsuarios');
      exit;
    }

    $id     = (int)($_POST['user_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $rol    = in_array($_POST['rol'] ?? '', ['admin', 'user', 'cliente']) ? $_POST['rol'] : 'user';
    $depts  = array_values(array_filter(array_map('intval', $_POST['departamentos'] ?? [])));

    if ($id <= 0 || mb_strlen($nombre) < 2) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Datos inválidos.'];
      header("Location: index.php?controller=Perfil&action=verPerfilUsuario&id=$id");
      exit;
    }

    $u = UsuarioModel::findById($id);
    if (!$u) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Usuario no encontrado.'];
      header('Location: index.php?controller=Perfil&action=listarUsuarios');
      exit;
    }

    UsuarioModel::updateProfile($id, $nombre, $u['email']);
    UsuarioModel::updateRol($id, $rol);

    UserDepartamentoModel::removeAllForUser($id);
    foreach ($depts as $deptId) {
      UserDepartamentoModel::assign($id, $deptId, (int)$admin['id']);
    }
    $primaryDeptId = !empty($depts) ? $depts[0] : null;
    UsuarioModel::updateDepartamento($id, $primaryDeptId);

    ActivityLogModel::log((int)$admin['id'], 'usuario', "Editó perfil del usuario #$id ($nombre)", $_SERVER['REMOTE_ADDR'] ?? '');

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Perfil actualizado correctamente.'];
    header("Location: index.php?controller=Perfil&action=verPerfilUsuario&id=$id");
    exit;
  }

  public function toggleActivarUsuario() {
    $admin = $this->requireAdmin();

    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token CSRF inválido.'];
      header('Location: index.php?controller=Perfil&action=listarUsuarios');
      exit;
    }

    $id = (int)($_POST['user_id'] ?? 0);
    if ($id <= 0) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Usuario inválido.'];
      header('Location: index.php?controller=Perfil&action=listarUsuarios');
      exit;
    }

    if ($id === (int)$admin['id']) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'No puedes desactivar tu propia cuenta.'];
      header("Location: index.php?controller=Perfil&action=verPerfilUsuario&id=$id");
      exit;
    }

    $u = UsuarioModel::findById($id);
    if (!$u) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Usuario no encontrado.'];
      header('Location: index.php?controller=Perfil&action=listarUsuarios');
      exit;
    }

    $newActive = $u['is_active'] ? 0 : 1;
    UsuarioModel::updateActive($id, $newActive);

    $accion = $newActive ? 'activó' : 'desactivó';
    ActivityLogModel::log((int)$admin['id'], 'usuario', "Admin $accion cuenta del usuario #$id", $_SERVER['REMOTE_ADDR'] ?? '');

    $msg = $newActive ? 'Cuenta activada.' : 'Cuenta desactivada.';
    $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
    header("Location: index.php?controller=Perfil&action=verPerfilUsuario&id=$id");
    exit;
  }
}

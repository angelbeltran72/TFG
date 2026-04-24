<?php
class ConfigController extends AppController {

  private function requireAdmin(): array {
    if (!isset($_SESSION["usuario"])) {
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }
    $user = $_SESSION["usuario"];
    if (($user["rol"] ?? "user") !== "admin") {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Acceso restringido a administradores."];
      header("Location: index.php?controller=Dashboard&action=index");
      exit;
    }
    return $user;
  }

  private function verificarCsrf(string $fallback): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token CSRF inválido.'];
      header('Location: ' . $fallback);
      exit;
    }
  }

  public function index() {
    $user    = $this->requireAdmin();
    $usuarios = UsuarioModel::listAll();

    $permisosPorUsuario = [];
    foreach ($usuarios as $u) {
      $permisosPorUsuario[(int)$u['id']] = UserPermissionModel::getForUser((int)$u['id']);
    }

    $logFilters = [];
    if (!empty($_GET['log_user'])) $logFilters['user_id'] = (int)$_GET['log_user'];
    if (!empty($_GET['log_type']) && in_array($_GET['log_type'], ['permisos','usuario','ticket','sistema','auth'], true)) {
      $logFilters['type'] = $_GET['log_type'];
    }
    $logDays = isset($_GET['log_days']) ? (int)$_GET['log_days'] : 7;
    if ($logDays > 0) $logFilters['days'] = $logDays;

    $this->view->show("configView", [
      "user"               => $user,
      "settings"           => SystemSettingModel::getAll(),
      "categorias"         => CategoriaModel::listAllWithCounts(),
      "etiquetas"          => EtiquetaModel::listAll(),
      "departamentos"      => DepartamentoModel::getAllWithMemberCount(),
      "usuarios"           => $usuarios,
      "permisosPorUsuario" => $permisosPorUsuario,
      "activityLog"        => ActivityLogModel::getRecent($logFilters, 100),
      "logFiltros"         => [
        'user_id' => $logFilters['user_id'] ?? '',
        'type'    => $logFilters['type']    ?? '',
        'days'    => $logDays,
      ],
    ]);
  }

  // Configuración general

  public function guardarSettings() {
    $user = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#sistema');

    $boolKeys = ['registro_abierto', 'incluir_admins_rotacion', 'modo_mantenimiento'];
    $textKeys = [
      'max_tickets_por_usuario',
      'zona_horaria', 'formato_fecha', 'modo_asignacion',
    ];

    foreach ($boolKeys as $key) {
      SystemSettingModel::set($key, isset($_POST[$key]) ? '1' : '0', (int)$user['id']);
    }
    foreach ($textKeys as $key) {
      if (isset($_POST[$key])) {
        SystemSettingModel::set($key, trim($_POST[$key]), (int)$user['id']);
      }
    }

    ActivityLogModel::log((int)$user['id'], 'sistema', 'Configuración del sistema actualizada', $_SERVER['REMOTE_ADDR'] ?? null);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Configuración guardada.'];
    header('Location: index.php?controller=Config&action=index#sistema');
    exit;
  }

  // Categorías

  public function crearCategoria() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#categorias');

    $nombre      = trim($_POST['nombre'] ?? '');
    $color       = trim($_POST['color']  ?? '#6366f1');
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
    $deptId      = ($_POST['departamento_id'] ?? '') !== '' ? (int)$_POST['departamento_id'] : null;

    if ($nombre === '') {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'El nombre es obligatorio.'];
      header('Location: index.php?controller=Config&action=index#categorias');
      exit;
    }

    CategoriaModel::create($nombre, $color, $descripcion, $deptId);
    ActivityLogModel::log((int)$admin['id'], 'sistema', "Categoría creada: {$nombre}", $_SERVER['REMOTE_ADDR'] ?? null);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Categoría creada.'];
    header('Location: index.php?controller=Config&action=index#categorias');
    exit;
  }

  public function editarCategoria() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#categorias');

    $id          = (int)($_POST['id'] ?? 0);
    $nombre      = trim($_POST['nombre'] ?? '');
    $color       = trim($_POST['color']  ?? '#6366f1');
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
    $deptId      = ($_POST['departamento_id'] ?? '') !== '' ? (int)$_POST['departamento_id'] : null;

    if ($id <= 0 || $nombre === '') {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Datos inválidos.'];
      header('Location: index.php?controller=Config&action=index#categorias');
      exit;
    }

    CategoriaModel::update($id, $nombre, $color, $descripcion, $deptId);
    ActivityLogModel::log((int)$admin['id'], 'sistema', "Categoría actualizada: {$nombre}", $_SERVER['REMOTE_ADDR'] ?? null);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Categoría actualizada.'];
    header('Location: index.php?controller=Config&action=index#categorias');
    exit;
  }

  public function eliminarCategoria() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#categorias');

    $id    = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $cat          = CategoriaModel::findById($id);
      $ticketCount  = (int)($_POST['ticket_count'] ?? 0);
      CategoriaModel::delete($id);
      if ($cat) {
        $detail = "Categoría eliminada: {$cat['nombre']}";
        if ($ticketCount > 0) $detail .= " ({$ticketCount} ticket(s) eliminados)";
        ActivityLogModel::log((int)$admin['id'], 'sistema', $detail, $_SERVER['REMOTE_ADDR'] ?? null);
      }
      $msg = 'Categoría eliminada.';
      if ($ticketCount > 0) $msg .= " Se eliminaron también {$ticketCount} ticket(s) asociados.";
      $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
    } else {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'ID inválido.'];
    }
    header('Location: index.php?controller=Config&action=index#categorias');
    exit;
  }

  public function toggleCategoria() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#categorias');

    $id    = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $cat = CategoriaModel::findById($id);
      CategoriaModel::toggle($id);
      if ($cat) {
        $estado = $cat['is_active'] ? 'desactivada' : 'activada';
        ActivityLogModel::log((int)$admin['id'], 'sistema', "Categoría {$estado}: {$cat['nombre']}", $_SERVER['REMOTE_ADDR'] ?? null);
      }
    }
    header('Location: index.php?controller=Config&action=index#categorias');
    exit;
  }

  // Departamentos

  public function crearDepartamento() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#departamentos');

    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
    $color       = trim($_POST['color'] ?? '#4648d4');

    if ($nombre === '') {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'El nombre es obligatorio.'];
      header('Location: index.php?controller=Config&action=index#departamentos');
      exit;
    }

    DepartamentoModel::create($nombre, $descripcion, $color);
    ActivityLogModel::log((int)$admin['id'], 'sistema', "Departamento creado: {$nombre}", $_SERVER['REMOTE_ADDR'] ?? null);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Departamento creado.'];
    header('Location: index.php?controller=Config&action=index#departamentos');
    exit;
  }

  public function editarDepartamento() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#departamentos');

    $id          = (int)($_POST['id'] ?? 0);
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
    $color       = trim($_POST['color'] ?? '#4648d4');
    $isActive    = isset($_POST['is_active']) ? 1 : 0;

    if ($id <= 0 || $nombre === '') {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Datos inválidos.'];
      header('Location: index.php?controller=Config&action=index#departamentos');
      exit;
    }

    DepartamentoModel::update($id, $nombre, $descripcion, $color, $isActive);
    ActivityLogModel::log((int)$admin['id'], 'sistema', "Departamento actualizado: {$nombre}", $_SERVER['REMOTE_ADDR'] ?? null);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Departamento actualizado.'];
    header('Location: index.php?controller=Config&action=index#departamentos');
    exit;
  }

  public function toggleDepartamento() {
    $admin    = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#departamentos');

    $id       = (int)($_POST['id'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 0);
    if ($id > 0) {
      DepartamentoModel::setActive($id, !$isActive);
      $dep    = DepartamentoModel::findById($id);
      $estado = $isActive ? 'desactivado' : 'activado';
      if ($dep) ActivityLogModel::log((int)$admin['id'], 'sistema', "Departamento {$estado}: {$dep['nombre']}", $_SERVER['REMOTE_ADDR'] ?? null);
    }
    header('Location: index.php?controller=Config&action=index#departamentos');
    exit;
  }

  public function eliminarDepartamento() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#departamentos');

    $id    = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $dep = DepartamentoModel::findById($id);
      DepartamentoModel::delete($id);
      if ($dep) ActivityLogModel::log((int)$admin['id'], 'sistema', "Departamento eliminado: {$dep['nombre']}", $_SERVER['REMOTE_ADDR'] ?? null);
    }
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Departamento eliminado.'];
    header('Location: index.php?controller=Config&action=index#departamentos');
    exit;
  }

  // Usuarios

  public function crearUsuario() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#usuarios');

    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email']  ?? '');
    $rol      = trim($_POST['rol']    ?? 'user');

    $errors = [];
    if ($nombre === '')                               $errors[] = 'Nombre obligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Email inválido.';
    if (UsuarioModel::existsByEmail($email))          $errors[] = 'Email ya registrado.';
    if (!in_array($rol, ['admin','user','cliente'], true))     $errors[] = 'Rol inválido.';

    if (!empty($errors)) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => implode(' ', $errors)];
      header('Location: index.php?controller=Config&action=index#usuarios');
      exit;
    }

    UsuarioModel::createByAdmin($nombre, $email, password_hash('1234', PASSWORD_DEFAULT), $rol);
    ActivityLogModel::log((int)$admin['id'], 'usuario', "Nueva cuenta creada: {$nombre} ({$rol})", $_SERVER['REMOTE_ADDR'] ?? null);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Usuario creado.'];
    header('Location: index.php?controller=Config&action=index#usuarios');
    exit;
  }

  public function editarUsuario() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#usuarios');

    $id       = (int)($_POST['id']       ?? 0);
    $nombre   = trim($_POST['nombre']    ?? '');
    $email    = trim($_POST['email']     ?? '');
    $rol      = trim($_POST['rol']       ?? 'user');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $errors = [];
    if ($id <= 0)                                     $errors[] = 'ID inválido.';
    if ($nombre === '')                               $errors[] = 'Nombre obligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Email inválido.';
    if (!in_array($rol, ['admin','user','cliente'], true))     $errors[] = 'Rol inválido.';

    $existing = UsuarioModel::findById($id);
    if ($existing && $existing['email'] !== $email && UsuarioModel::existsByEmail($email)) {
      $errors[] = 'Email ya en uso.';
    }

    if (!empty($errors)) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => implode(' ', $errors)];
      header('Location: index.php?controller=Config&action=index#usuarios');
      exit;
    }

    UsuarioModel::updateProfile($id, $nombre, $email);
    UsuarioModel::updateRol($id, $rol);
    UsuarioModel::updateActive($id, $isActive);

    if ($id === (int)$admin['id']) {
      $_SESSION['usuario']['nombre'] = $nombre;
      $_SESSION['usuario']['email']  = $email;
      $_SESSION['usuario']['rol']    = $rol;
    }

    ActivityLogModel::log((int)$admin['id'], 'usuario', "Usuario editado: {$nombre}", $_SERVER['REMOTE_ADDR'] ?? null);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Usuario actualizado.'];
    header('Location: index.php?controller=Config&action=index#usuarios');
    exit;
  }

  public function eliminarUsuario() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#usuarios');

    $id    = (int)($_POST['id'] ?? 0);

    if ($id <= 0 || $id === (int)$admin['id']) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'No puedes eliminar tu propia cuenta.'];
      header('Location: index.php?controller=Config&action=index#usuarios');
      exit;
    }

    $target      = UsuarioModel::findById($id);
    $ticketCount = (int)($_POST['ticket_count'] ?? 0);

    if ($target) {
      UsuarioModel::delete($id);
      $detail = "Cuenta eliminada: {$target['nombre']} ({$target['email']})";
      if ($ticketCount > 0) $detail .= " — {$ticketCount} ticket(s) eliminados";
      ActivityLogModel::log((int)$admin['id'], 'usuario', $detail, $_SERVER['REMOTE_ADDR'] ?? null);
      $msg = "Cuenta de {$target['nombre']} eliminada.";
      if ($ticketCount > 0) $msg .= " Se eliminaron también {$ticketCount} ticket(s) asociados.";
      $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
    } else {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Usuario no encontrado.'];
    }

    header('Location: index.php?controller=Config&action=index#usuarios');
    exit;
  }

  public function toggleUsuario() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#usuarios');

    $id    = (int)($_POST['id'] ?? 0);
    if ($id > 0 && $id !== (int)$admin['id']) {
      $target = UsuarioModel::findById($id);
      if ($target) {
        $newActive = $target['is_active'] ? 0 : 1;
        UsuarioModel::updateActive($id, $newActive);
        $estado = $newActive ? 'reactivada' : 'desactivada';
        ActivityLogModel::log((int)$admin['id'], 'usuario', "Cuenta de {$target['nombre']} {$estado}", $_SERVER['REMOTE_ADDR'] ?? null);
      }
    }
    header('Location: index.php?controller=Config&action=index#usuarios');
    exit;
  }

  public function cambiarRolUsuario() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#usuarios');

    $id    = (int)($_POST['id'] ?? 0);
    if ($id > 0 && $id !== (int)$admin['id']) {
      $target = UsuarioModel::findById($id);
      if ($target) {
        $cycle    = ['admin' => 'user', 'user' => 'cliente', 'cliente' => 'admin'];
        $newRol   = $cycle[$target['rol']] ?? 'user';
        $rolLabels = ['admin' => 'Administrador', 'user' => 'Agente', 'cliente' => 'Cliente'];
        $rolLabel = $rolLabels[$newRol] ?? 'Agente';
        UsuarioModel::updateRol($id, $newRol);
        ActivityLogModel::log((int)$admin['id'], 'usuario', "Rol de {$target['nombre']} cambiado a {$rolLabel}", $_SERVER['REMOTE_ADDR'] ?? null);
      }
    }
    header('Location: index.php?controller=Config&action=index#usuarios');
    exit;
  }

  public function forzarCambioPassword() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#usuarios');

    $id    = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $target = UsuarioModel::findById($id);
      if ($target) {
        UsuarioModel::setMustChangePassword($id, true);
        ActivityLogModel::log((int)$admin['id'], 'usuario', "Cambio de contraseña forzado para {$target['nombre']}", $_SERVER['REMOTE_ADDR'] ?? null);
      }
    }
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Se forzará el cambio de contraseña en el próximo inicio de sesión.'];
    header('Location: index.php?controller=Config&action=index#usuarios');
    exit;
  }

  public function quitarAvatarUsuario() {
    $admin = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#usuarios');

    $id    = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $target = UsuarioModel::findById($id);
      if ($target && $target['avatar_path']) {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $target['avatar_path'];
        if (file_exists($filePath)) @unlink($filePath);
        UsuarioModel::removeAvatar($id);
        ActivityLogModel::log((int)$admin['id'], 'usuario', "Avatar eliminado de {$target['nombre']}", $_SERVER['REMOTE_ADDR'] ?? null);
      }
    }
    header('Location: index.php?controller=Config&action=index#usuarios');
    exit;
  }

  // Departamento: gestión rápida de miembros

  public function ajaxMiembrosDepartamento() {
    $this->requireAdmin();
    header('Content-Type: application/json');
    $depId = (int)($_GET['dep_id'] ?? 0);
    if ($depId <= 0) { echo json_encode(['ok' => false]); exit; }

    $miembros    = UserDepartamentoModel::getUsersByDepto($depId);
    $miembroIds  = array_map(fn($u) => (int)$u['id'], $miembros);
    $todos        = UsuarioModel::listAll();
    $disponibles  = array_values(array_filter($todos, fn($u) => !in_array((int)$u['id'], $miembroIds)));

    echo json_encode(['ok' => true, 'miembros' => array_values($miembros), 'disponibles' => $disponibles]);
    exit;
  }

  public function ajaxAsignarUsuarioDepartamento() {
    $admin  = $this->requireAdmin();
    header('Content-Type: application/json');
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) { echo json_encode(['ok' => false, 'msg' => 'Token inválido.']); exit; }
    $userId = (int)($_POST['user_id'] ?? 0);
    $depId  = (int)($_POST['dep_id']  ?? 0);
    if ($userId <= 0 || $depId <= 0) { echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.']); exit; }

    UserDepartamentoModel::assign($userId, $depId, (int)$admin['id']);
    $u = UsuarioModel::findById($userId);
    ActivityLogModel::log((int)$admin['id'], 'usuario', "Usuario {$u['nombre']} asignado al departamento ID {$depId}", $_SERVER['REMOTE_ADDR'] ?? null);
    echo json_encode(['ok' => true]);
    exit;
  }

  public function ajaxQuitarUsuarioDepartamento() {
    $admin  = $this->requireAdmin();
    header('Content-Type: application/json');
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) { echo json_encode(['ok' => false, 'msg' => 'Token inválido.']); exit; }
    $userId = (int)($_POST['user_id'] ?? 0);
    $depId  = (int)($_POST['dep_id']  ?? 0);
    if ($userId <= 0 || $depId <= 0) { echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.']); exit; }

    UserDepartamentoModel::remove($userId, $depId);
    $u = UsuarioModel::findById($userId);
    ActivityLogModel::log((int)$admin['id'], 'usuario', "Usuario {$u['nombre']} retirado del departamento ID {$depId}", $_SERVER['REMOTE_ADDR'] ?? null);
    echo json_encode(['ok' => true]);
    exit;
  }

  // Permisos

  public function guardarPermisos() {
    $admin  = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#permisos');

    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Usuario inválido.'];
      header('Location: index.php?controller=Config&action=index#permisos');
      exit;
    }

    $target = UsuarioModel::findById($userId);
    if (!$target) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Usuario no encontrado.'];
      header('Location: index.php?controller=Config&action=index#permisos');
      exit;
    }

    if ($target['rol'] === 'admin') {
      $_SESSION['flash'] = ['type' => 'info', 'msg' => 'Los administradores tienen todos los permisos por rol.'];
      header('Location: index.php?controller=Config&action=index#permisos');
      exit;
    }

    $allowed = [
      'crear_tickets', 'ver_tickets_departamento', 'comentar_tickets',
      'ver_todos_tickets', 'cambiar_estado_ajenos', 'reasignar_tickets',
      'cerrar_tickets_ajenos', 'crear_en_nombre_de', 'acceso_kanban', 'acceso_configuracion',
    ];

    $postPerms = $_POST['perms'] ?? [];
    foreach ($allowed as $perm) {
      $granted = isset($postPerms[$perm]);
      UserPermissionModel::set($userId, $perm, $granted, (int)$admin['id']);
    }

    ActivityLogModel::log((int)$admin['id'], 'permisos', "Permisos actualizados para {$target['nombre']}", $_SERVER['REMOTE_ADDR'] ?? null);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Permisos guardados.'];
    header('Location: index.php?controller=Config&action=index#permisos');
    exit;
  }

  public function resetearPermisos() {
    $admin  = $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#permisos');

    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId > 0) {
      $target = UsuarioModel::findById($userId);
      UserPermissionModel::resetForUser($userId);
      if ($target) ActivityLogModel::log((int)$admin['id'], 'permisos', "Permisos de {$target['nombre']} restablecidos a valores por defecto", $_SERVER['REMOTE_ADDR'] ?? null);
    }
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Permisos restablecidos a valores por defecto.'];
    header('Location: index.php?controller=Config&action=index#permisos');
    exit;
  }

  // Etiquetas

  public function crearEtiqueta() {
    $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#etiquetas');

    $nombre = trim($_POST['nombre'] ?? '');
    $color  = trim($_POST['color']  ?? '#6366f1');

    if ($nombre === '') {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'El nombre es obligatorio.'];
      header('Location: index.php?controller=Config&action=index#etiquetas');
      exit;
    }

    EtiquetaModel::create($nombre, $color);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Etiqueta creada.'];
    header('Location: index.php?controller=Config&action=index#etiquetas');
    exit;
  }

  public function editarEtiqueta() {
    $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#etiquetas');

    $id     = (int)($_POST['id']     ?? 0);
    $nombre = trim($_POST['nombre']  ?? '');
    $color  = trim($_POST['color']   ?? '#6366f1');

    if ($id <= 0 || $nombre === '') {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Datos inválidos.'];
      header('Location: index.php?controller=Config&action=index#etiquetas');
      exit;
    }

    EtiquetaModel::update($id, $nombre, $color);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Etiqueta actualizada.'];
    header('Location: index.php?controller=Config&action=index#etiquetas');
    exit;
  }

  public function eliminarEtiqueta() {
    $this->requireAdmin();
    $this->verificarCsrf('index.php?controller=Config&action=index#etiquetas');

    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) EtiquetaModel::delete($id);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Etiqueta eliminada.'];
    header('Location: index.php?controller=Config&action=index#etiquetas');
    exit;
  }
}

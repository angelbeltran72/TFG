<?php
require_once __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->safeLoad();

define('BASE', rtrim(parse_url($_ENV['APP_URL'] ?? 'http://localhost', PHP_URL_PATH) ?? '', '/'));

// Configurar sesion ANTES de session_start()
$cookieSecure = filter_var($_ENV["COOKIE_SECURE"] ?? "false", FILTER_VALIDATE_BOOLEAN);
ini_set("session.gc_maxlifetime", 28800); // 8 horas
session_set_cookie_params([
  "lifetime" => 28800,
  "path"     => "/",
  "secure"   => $cookieSecure,
  "httponly" => true,
  "samesite" => "Lax",
]);
session_start();

require_once "libs/Config.php";
require_once "libs/SPDO.php";
require_once "libs/View.php";
require_once "libs/FrontController.php";
require_once "libs/Csrf.php";
require_once __DIR__ . "/Mailer.php";

require_once "controllers/AppController.php";
require_once "controllers/AuthController.php";
require_once "controllers/DashboardController.php";
require_once "controllers/TicketController.php";
require_once "controllers/PerfilController.php";
require_once "controllers/PresenceController.php";
require_once "controllers/KanbanController.php";
require_once "controllers/SoporteController.php";
require_once "controllers/ConfigController.php";
require_once "controllers/NotificationController.php";
require_once "controllers/MessageController.php";

require_once "models/UsuarioModel.php";
require_once "models/PasswordResetModel.php";
require_once "models/TicketModel.php";
require_once "models/DepartamentoModel.php";
require_once "models/UserDepartamentoModel.php";
require_once "models/NotificationModel.php";
require_once "models/UserPermissionModel.php";
require_once "models/SystemSettingModel.php";
require_once "models/ActivityLogModel.php";
require_once "models/TicketCommentModel.php";
require_once "models/TicketAttachmentModel.php";
require_once "models/CategoriaModel.php";
require_once "models/EtiquetaModel.php";
require_once "models/MessageModel.php";

// Expirar sesion a las 8 horas absolutas desde el login
if (isset($_SESSION["usuario"])) {
  if (!isset($_SESSION["login_time"])) {
    $_SESSION["login_time"] = time();
  } elseif ((time() - $_SESSION["login_time"]) > 28800) {
    if (!empty($_COOKIE["remember_token"])) {
      setcookie("remember_token", "", time() - 3600, "/", "", $cookieSecure, true);
    }
    session_destroy();
    header("Location: index.php?controller=Auth&action=iniciarSesion");
    exit;
  }
}

// Auto-login via cookie remember_me
if (!isset($_SESSION["usuario"]) && !empty($_COOKIE["remember_token"])) {
  $hash = hash("sha256", $_COOKIE["remember_token"]);
  $u    = UsuarioModel::findByRememberToken($hash);

  if ($u && (int)$u["is_active"]) {
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
  } else {
    setcookie("remember_token", "", time() - 3600, "/", "", $cookieSecure, true);
  }
}

// Refrescar rol e is_active en cada peticion autenticada
if (isset($_SESSION["usuario"])) {
  $fresh = UsuarioModel::findRolById((int)$_SESSION["usuario"]["id"]);
  if (!$fresh || !(int)$fresh["is_active"]) {
    if (!empty($_COOKIE["remember_token"])) {
      setcookie("remember_token", "", time() - 3600, "/", "", $cookieSecure, true);
    }
    session_destroy();
    header("Location: index.php?controller=Auth&action=iniciarSesion");
    exit;
  }
  $_SESSION["usuario"]["rol"] = $fresh["rol"];

  $uid = (int)$_SESSION["usuario"]["id"];
  $role = $_SESSION["usuario"]["rol"] ?? "user";
  $isAdmin = ($role === "admin");
  $_SESSION["nav_permissions"] = [
    "can_kanban" => UserPermissionModel::check($uid, "acceso_kanban", $role),
    "can_config" => $isAdmin && UserPermissionModel::check($uid, "acceso_configuracion", $role),
    "can_users"  => $isAdmin,
  ];

  // Actualizar last_seen_at como máximo una vez por minuto para no saturar la BD
  $lastPing = $_SESSION["_presence_ping"] ?? 0;
  if ((time() - $lastPing) >= 60) {
    UsuarioModel::touchLastSeen((int)$_SESSION["usuario"]["id"]);
    $_SESSION["_presence_ping"] = time();
  }
}

// Zona horaria desde configuración
date_default_timezone_set(SystemSettingModel::get('zona_horaria') ?? 'Europe/Madrid');

// Helper global para formatear fechas según formato configurado
function fmtDate(?string $dt, bool $withTime = true): string {
  if (!$dt) return '—';
  static $fmt = null;
  if ($fmt === null) $fmt = SystemSettingModel::get('formato_fecha') ?? 'DD/MM/YYYY';
  $ts = strtotime($dt);
  if (!$ts) return $dt;
  $date = match(true) {
    str_starts_with($fmt, 'MM/DD') => date('m/d/Y', $ts),
    str_starts_with($fmt, 'YYYY')  => date('Y-m-d', $ts),
    default                        => date('d/m/Y', $ts),
  };
  return $withTime ? $date . ' ' . date('H:i', $ts) : $date;
}

// Modo mantenimiento: bloquea a no-admins excepto en rutas de Auth
if (SystemSettingModel::getBool('modo_mantenimiento')) {
  $ctrlParam = strtolower($_GET['controller'] ?? 'auth');
  $isAdmin   = (($_SESSION['usuario']['rol'] ?? '') === 'admin');
  if ($ctrlParam !== 'auth' && !$isAdmin) {
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Mantenimiento</title>
    <style>body{margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0f172a;font-family:sans-serif;color:#e2e8f0}
    .box{text-align:center;padding:3rem 2rem}.icon{font-size:4rem;margin-bottom:1rem}h1{font-size:1.8rem;margin:0 0 .5rem}p{color:#94a3b8;margin:0}</style></head>
    <body><div class="box"><div class="icon">🔧</div><h1>Sitio en mantenimiento</h1><p>Volvemos pronto. Contacta con el administrador si necesitas acceso.</p></div></body></html>';
    exit;
  }
}

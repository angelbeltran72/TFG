-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-04-2026 a las 14:24:38
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `incidencias`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('permisos','usuario','ticket','sistema','auth') NOT NULL,
  `detail` varchar(500) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `type`, `detail`, `ip`, `created_at`) VALUES
(1, 4, 'auth', 'Inicio de sesión desde ::1', '::1', '2026-04-22 09:57:32'),
(2, 4, 'usuario', 'Admin desactivó cuenta del usuario #6', '::1', '2026-04-22 10:09:55'),
(3, 4, 'usuario', 'Admin activó cuenta del usuario #6', '::1', '2026-04-22 10:10:08'),
(4, 6, 'auth', 'Inicio de sesión desde ::1', '::1', '2026-04-22 10:10:56'),
(5, 4, 'auth', 'Inicio de sesión desde ::1', '::1', '2026-04-22 10:11:57'),
(6, 4, 'usuario', 'Admin desactivó cuenta del usuario #6', '::1', '2026-04-22 10:12:50'),
(7, 4, 'auth', 'Inicio de sesión desde ::1', '::1', '2026-04-22 10:13:08'),
(8, 4, 'usuario', 'Rol de Angel cambiado a Administrador', '::1', '2026-04-22 10:17:00'),
(9, 4, 'usuario', 'Cuenta de Angel reactivada', '::1', '2026-04-22 10:17:07'),
(10, 4, 'usuario', 'Rol de Angel cambiado a Administrador', '::1', '2026-04-22 10:17:11'),
(11, 4, 'usuario', 'Rol de Angel cambiado a Agente', '::1', '2026-04-22 10:17:17'),
(12, 4, 'usuario', 'Rol de Angel cambiado a Agente', '::1', '2026-04-22 10:17:20'),
(13, 4, 'permisos', 'Permisos de Angel restablecidos a valores por defecto', '::1', '2026-04-22 10:18:30'),
(14, 4, 'sistema', 'Categoría desactivada: Acceso / Login', '::1', '2026-04-22 10:18:35'),
(15, 4, 'sistema', 'Categoría activada: Acceso / Login', '::1', '2026-04-22 10:18:38'),
(16, 4, 'sistema', 'Departamento eliminado: Administraci├│n', '::1', '2026-04-22 10:19:54'),
(17, 4, 'sistema', 'Departamento eliminado: Comercial', '::1', '2026-04-22 10:19:57'),
(18, 4, 'sistema', 'Departamento eliminado: Direcci├│n', '::1', '2026-04-22 10:19:59'),
(19, 4, 'sistema', 'Departamento eliminado: Inform├ítica', '::1', '2026-04-22 10:20:02'),
(20, 4, 'sistema', 'Departamento eliminado: Recursos Humanos', '::1', '2026-04-22 10:20:18'),
(21, 4, 'sistema', 'Departamento creado: Informática', '::1', '2026-04-22 10:20:40'),
(22, 4, 'sistema', 'Configuración del sistema actualizada', '::1', '2026-04-22 10:21:22'),
(23, 6, 'auth', 'Inicio de sesión desde ::1', '::1', '2026-04-22 10:21:38'),
(24, 4, 'sistema', 'Configuración del sistema actualizada', '::1', '2026-04-22 10:22:09'),
(25, 4, 'sistema', 'Categoría eliminada: Acceso / Login (2 ticket(s) eliminados)', '::1', '2026-04-22 10:22:29'),
(26, 4, 'sistema', 'Categoría eliminada: Correo (3 ticket(s) eliminados)', '::1', '2026-04-22 10:23:01'),
(27, 4, 'sistema', 'Categoría eliminada: Hardware (3 ticket(s) eliminados)', '::1', '2026-04-22 10:23:03'),
(28, 4, 'sistema', 'Categoría eliminada: Impresoras (2 ticket(s) eliminados)', '::1', '2026-04-22 10:23:06'),
(29, 4, 'sistema', 'Categoría eliminada: Red / Conectividad (2 ticket(s) eliminados)', '::1', '2026-04-22 10:23:09'),
(30, 4, 'sistema', 'Categoría actualizada: Otros', '::1', '2026-04-22 10:23:24'),
(31, 4, 'usuario', 'Nueva cuenta creada: Pepito (user)', '::1', '2026-04-22 10:25:18'),
(32, 4, 'usuario', 'Cuenta eliminada: awdwa (awdawd@aaa.com)', '::1', '2026-04-22 10:30:38'),
(33, 4, 'usuario', 'Cuenta eliminada: Angel (admin@admind.com)', '::1', '2026-04-22 10:30:41'),
(34, 4, 'usuario', 'Cuenta eliminada: Angel (proazos7@gmail.com)', '::1', '2026-04-22 10:30:45'),
(35, 4, 'usuario', 'Cuenta eliminada: Angel (prueba@gmail.com)', '::1', '2026-04-22 10:30:47'),
(36, 4, 'usuario', 'Cuenta eliminada: Paco (paco@gmail.com)', '::1', '2026-04-22 10:30:50'),
(37, 4, 'usuario', 'Cuenta eliminada: Pepito (pepito@gmail.com)', '::1', '2026-04-22 10:30:53'),
(38, 4, 'usuario', 'Nueva cuenta creada: Pepe (user)', '::1', '2026-04-22 10:31:27'),
(39, 12, 'auth', 'Contraseña inicial establecida', '::1', '2026-04-22 10:31:51'),
(40, 4, 'sistema', 'Categoría eliminada: Otros (2 ticket(s) eliminados)', '::1', '2026-04-22 10:32:32'),
(41, 4, 'sistema', 'Categoría creada: Hadware', '::1', '2026-04-22 10:32:45'),
(42, 4, 'sistema', 'Categoría actualizada: Software', '::1', '2026-04-22 10:32:55'),
(43, 4, 'sistema', 'Categoría actualizada: Hadware', '::1', '2026-04-22 10:33:00'),
(44, 4, 'sistema', 'Departamento creado: Recursos Humanos', '::1', '2026-04-22 11:55:24'),
(45, 4, 'sistema', 'Categoría creada: Horarios', '::1', '2026-04-22 11:55:42'),
(46, 4, 'sistema', 'Categoría creada: Incidencias Trabajo', '::1', '2026-04-22 11:55:55'),
(47, 4, 'sistema', 'Configuración del sistema actualizada', '::1', '2026-04-22 14:05:43'),
(48, 4, 'sistema', 'Configuración del sistema actualizada', '::1', '2026-04-22 14:06:41'),
(49, 4, 'usuario', 'Usuario editado: Pepe', '::1', '2026-04-22 14:08:33'),
(50, 4, 'usuario', 'Usuario editado: Pepe', '::1', '2026-04-22 14:08:37'),
(51, 4, 'auth', 'Inicio de sesión desde ::1', '::1', '2026-04-22 14:22:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `departamento_id` int(11) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6366f1',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `departamento_id`, `descripcion`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'Software', 6, NULL, '#0055ff', 1, '2026-03-18 17:09:52', '2026-04-22 10:32:55'),
(8, 'Hadware', 6, NULL, '#00f010', 1, '2026-04-22 10:32:45', '2026-04-22 10:33:00'),
(9, 'Horarios', 7, NULL, '#878792', 1, '2026-04-22 11:55:42', NULL),
(10, 'Incidencias Trabajo', 7, NULL, '#6e6f87', 1, '2026-04-22 11:55:55', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `conversations`
--

INSERT INTO `conversations` (`id`, `user1_id`, `user2_id`, `last_message_at`, `created_at`) VALUES
(2, 4, 12, '2026-04-22 14:23:54', '2026-04-22 14:23:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#4648d4',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id`, `nombre`, `descripcion`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
(6, 'Informática', NULL, '#0004ff', 1, '2026-04-22 10:20:40', NULL),
(7, 'Recursos Humanos', NULL, '#e1c6d6', 1, '2026-04-22 11:55:24', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etiquetas`
--

CREATE TABLE `etiquetas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6366f1',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `etiquetas`
--

INSERT INTO `etiquetas` (`id`, `nombre`, `color`, `created_at`, `updated_at`) VALUES
(1, 'urgente', '#ef4444', '2026-03-18 18:05:40', NULL),
(2, 'pendiente-proveedor', '#f97316', '2026-03-18 18:05:40', NULL),
(3, 'requiere-presencia', '#eab308', '2026-03-18 18:05:40', NULL),
(4, 'resuelto-remoto', '#22c55e', '2026-03-18 18:05:40', NULL),
(5, 'reincidente', '#8b5cf6', '2026-03-18 18:05:40', NULL),
(6, 'bajo-seguimiento', '#06b6d4', '2026-03-18 18:05:40', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `content`, `read_at`, `created_at`) VALUES
(3, 2, 4, 'Hola', NULL, '2026-04-22 14:23:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(60) NOT NULL,
  `message` varchar(500) NOT NULL,
  `resource_type` varchar(40) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `resource_type`, `resource_id`, `read_at`, `created_at`) VALUES
(2, 4, 'ticket_assigned', 'Notificación de prueba — 10:14:55', 'ticket', 1, '2026-04-22 10:15:09', '2026-04-22 10:14:55'),
(3, 4, 'ticket_assigned', 'Notificación de prueba — 10:24:21', 'ticket', 1, '2026-04-22 10:24:34', '2026-04-22 10:24:21'),
(4, 4, 'ticket_assigned', 'Notificación de prueba — 10:24:23', 'ticket', 1, '2026-04-22 10:24:34', '2026-04-22 10:24:23'),
(5, 4, 'ticket_assigned', 'Notificación de prueba — 10:24:26', 'ticket', 1, '2026-04-22 10:24:34', '2026-04-22 10:24:26'),
(6, 6, 'ticket_assigned', 'Se te ha asignado el ticket #18: awdawdawwdawawd', 'ticket', 18, NULL, '2026-04-22 14:07:00'),
(7, 12, 'ticket_assigned', 'Se te ha asignado el ticket #19: awddwadawdawadwdawdaw', 'ticket', 19, NULL, '2026-04-22 14:15:16'),
(8, 6, 'ticket_status', 'El estado del ticket #18 cambió a resuelta', 'ticket', 18, NULL, '2026-04-22 14:19:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token_hash`, `expires_at`, `used`, `created_at`, `ip`) VALUES
(23, 6, '80abca1b896bf76243705565c9d48aae54627e6527c2a829170224576be79f3e', '2026-04-22 10:41:30', 0, '2026-04-22 10:11:30', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(80) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('bool','int','string','select') NOT NULL DEFAULT 'string',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `value`, `type`, `updated_by`, `updated_at`) VALUES
(1, 'registro_abierto', '1', 'bool', 4, '2026-04-22 10:21:22'),
(2, 'max_tickets_por_usuario', '0', 'int', 4, '2026-04-22 10:21:22'),
(3, 'inactividad_minutos', '60', 'int', 4, '2026-04-22 10:21:22'),
(4, 'zona_horaria', 'Europe/Madrid', 'select', 4, '2026-04-22 10:21:22'),
(5, 'formato_fecha', 'DD/MM/YYYY', 'select', 4, '2026-04-22 10:21:22'),
(6, 'modo_asignacion', 'roundrobin', 'select', 4, '2026-04-22 14:06:41'),
(7, 'incluir_admins_rotacion', '0', 'bool', 4, '2026-04-22 10:21:22'),
(8, 'modo_mantenimiento', '0', 'bool', 4, '2026-04-22 10:22:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `titulo` varchar(160) NOT NULL,
  `descripcion` text NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `departamento_id` int(11) DEFAULT NULL,
  `prioridad` enum('baja','media','alta','critica') NOT NULL DEFAULT 'media',
  `estado` enum('sin_abrir','abierta','en_proceso','resuelta','cerrada') NOT NULL DEFAULT 'sin_abrir',
  `creado_por` int(11) NOT NULL,
  `asignado_a` int(11) DEFAULT NULL,
  `cliente_email` varchar(120) DEFAULT NULL,
  `cliente_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tickets`
--

INSERT INTO `tickets` (`id`, `titulo`, `descripcion`, `categoria_id`, `departamento_id`, `prioridad`, `estado`, `creado_por`, `asignado_a`, `cliente_email`, `cliente_user_id`, `created_at`, `updated_at`, `resolved_at`, `due_date`, `deleted_at`) VALUES
(8, 'Solicitud instalaci¾n Adobe Acrobat', 'Necesito Adobe Acrobat Pro para firmar documentos PDF. El equipo actual solo tiene el lector gratuito.', 4, NULL, 'alta', 'sin_abrir', 6, 4, NULL, NULL, '2026-03-14 18:05:40', '2026-04-22 11:53:30', NULL, NULL, '2026-04-22 11:53:30'),
(16, 'awdadaa', 'awddawdwaa', 4, 6, 'critica', 'sin_abrir', 4, 4, NULL, NULL, '2026-04-22 11:52:01', '2026-04-22 11:53:48', NULL, NULL, '2026-04-22 11:53:48'),
(17, 'awdadawawdwada', 'adwadwdawawdadwawdwd', 10, 7, 'alta', 'abierta', 4, 4, NULL, NULL, '2026-04-22 14:06:23', '2026-04-22 14:18:47', NULL, NULL, NULL),
(18, 'awdawdawwdawawd', 'aadwwadwadwadwadwadw', 8, 6, 'critica', 'resuelta', 4, 6, NULL, NULL, '2026-04-22 14:07:00', '2026-04-22 14:19:04', NULL, NULL, NULL),
(19, 'awddwadawdawadwdawdaw', 'addwdaadwdawdwadwadaw', 4, 6, 'critica', 'sin_abrir', 4, 12, NULL, NULL, '2026-04-22 14:15:16', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_attachments`
--

CREATE TABLE `ticket_attachments` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `storage_path` varchar(500) NOT NULL DEFAULT '',
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size_bytes` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_comments`
--

CREATE TABLE `ticket_comments` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `contenido` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `event_type` varchar(30) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ticket_comments`
--

INSERT INTO `ticket_comments` (`id`, `ticket_id`, `user_id`, `contenido`, `is_internal`, `event_type`, `created_at`, `updated_at`) VALUES
(4, 8, 4, 'La licencia de Adobe estß pendiente de aprobaci¾n por parte de direcci¾n. Se avisarß cuando estÚ disponible.', 0, NULL, '2026-03-18 18:06:07', NULL),
(5, 8, 6, 'Entendido, ┐hay alguna alternativa temporal que pueda usar mientras tanto?', 0, NULL, '2026-03-18 18:06:07', NULL),
(6, 8, 4, 'Puedes usar LibreOffice Draw para firmas bßsicas en PDF.', 0, NULL, '2026-03-18 18:06:07', NULL),
(18, 16, 4, '', 0, 'assignment', '2026-04-22 11:53:36', NULL),
(19, 16, 4, 'Admin', 0, 'assignment', '2026-04-22 11:53:37', NULL),
(20, 17, 4, 'Admin', 0, 'assignment', '2026-04-22 14:14:38', NULL),
(21, 17, 4, 'sin_abrir|abierta', 0, 'state_change', '2026-04-22 14:18:47', NULL),
(22, 18, 4, 'sin_abrir|abierta', 0, 'state_change', '2026-04-22 14:18:50', NULL),
(23, 18, 4, 'abierta|resuelta', 0, 'state_change', '2026-04-22 14:19:04', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_etiquetas`
--

CREATE TABLE `ticket_etiquetas` (
  `ticket_id` int(11) NOT NULL,
  `etiqueta_id` int(11) NOT NULL,
  `added_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ticket_etiquetas`
--

INSERT INTO `ticket_etiquetas` (`ticket_id`, `etiqueta_id`, `added_by`, `created_at`) VALUES
(8, 2, 4, '2026-03-18 18:06:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_history`
--

CREATE TABLE `ticket_history` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `campo` varchar(50) NOT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_nuevo` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ticket_history`
--

INSERT INTO `ticket_history` (`id`, `ticket_id`, `user_id`, `campo`, `valor_anterior`, `valor_nuevo`, `created_at`) VALUES
(3, 8, 4, 'estado', 'abierta', 'en_proceso', '2026-03-18 18:06:07'),
(4, 8, 4, 'prioridad', 'baja', 'media', '2026-03-18 18:06:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','user','cliente') NOT NULL DEFAULT 'user',
  `departamento_id` int(11) DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token_expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `api_token_hash` varchar(64) DEFAULT NULL,
  `notification_prefs` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `nombre`, `email`, `password_hash`, `rol`, `departamento_id`, `remember_token`, `avatar_path`, `last_login_at`, `last_seen_at`, `is_active`, `must_change_password`, `remember_token_expires_at`, `created_at`, `updated_at`, `api_token_hash`, `notification_prefs`) VALUES
(4, 'Admin', 'admin@admin.com', '$2y$10$DPEjtTlG.CqYovcLDz7Zlekxnp6kyr/dqOEdoBz6mAFkx6A0MzfYW', 'admin', NULL, NULL, '/MVC/uploads/avatars/u4_1775741554.png', '2026-04-22 14:22:58', '2026-04-22 14:24:17', 1, 0, NULL, '2026-03-18 17:09:52', '2026-04-22 14:24:17', '4be56e51d912da2c9dd451d5b38e1c3d958778308b62c2cc913c1f683f606a0e', '{\"ticket_assigned\":true,\"ticket_comment\":true,\"ticket_status\":true,\"ticket_overdue\":true}'),
(6, 'Angel', 'angel72beltran@gmail.com', '$2y$10$AbBSsVy.zFnX6TbfqnHA8.JiAqytC8o0cDTesfruWaiWk.PJcWP42', 'user', NULL, NULL, NULL, '2026-04-22 10:21:38', '2026-04-22 10:21:38', 1, 0, NULL, '2026-03-18 17:09:52', '2026-04-22 10:21:38', NULL, '{\"ticket_assigned\":true,\"ticket_comment\":true,\"ticket_status\":true,\"ticket_overdue\":true}'),
(12, 'Pepe', 'pepito@gmail.com', '$2y$10$8v3lZupxQlv/OxpL4P29e.HXTLr1mTFwkXY6cLLGgI2SMC6SDtnle', 'user', NULL, NULL, NULL, '2026-04-22 10:31:38', '2026-04-22 10:31:38', 1, 0, NULL, '2026-04-22 10:31:27', '2026-04-22 14:08:37', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_departamentos`
--

CREATE TABLE `user_departamentos` (
  `user_id` int(11) NOT NULL,
  `departamento_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission` varchar(80) NOT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT 1,
  `granted_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_actlog_user` (`user_id`),
  ADD KEY `idx_actlog_type` (`type`),
  ADD KEY `idx_actlog_created` (`created_at`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cat_nombre_depto` (`nombre`,`departamento_id`),
  ADD KEY `fk_cat_depto` (`departamento_id`);

--
-- Indices de la tabla `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_conv_users` (`user1_id`,`user2_id`),
  ADD KEY `fk_conv_user1` (`user1_id`),
  ADD KEY `fk_conv_user2` (`user2_id`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_departamento_nombre` (`nombre`);

--
-- Indices de la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_etiqueta_nombre` (`nombre`);

--
-- Indices de la tabla `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_msg_conv` (`conversation_id`),
  ADD KEY `idx_msg_sender` (`sender_id`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user_unread` (`user_id`,`read_at`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_token_hash` (`token_hash`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_setting_key` (`setting_key`),
  ADD KEY `fk_settings_updated_by` (`updated_by`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tickets_categoria` (`categoria_id`),
  ADD KEY `idx_tickets_creado_por` (`creado_por`),
  ADD KEY `idx_tickets_asignado_a` (`asignado_a`),
  ADD KEY `idx_tickets_estado` (`estado`),
  ADD KEY `idx_tickets_prioridad` (`prioridad`),
  ADD KEY `idx_tickets_created_at` (`created_at`),
  ADD KEY `idx_tickets_resolved_at` (`resolved_at`),
  ADD KEY `idx_tickets_departamento` (`departamento_id`),
  ADD KEY `idx_tickets_deleted_at` (`deleted_at`),
  ADD KEY `idx_tickets_asignado_estado` (`asignado_a`,`estado`),
  ADD KEY `idx_tickets_creado_estado` (`creado_por`,`estado`),
  ADD KEY `idx_tickets_due_date` (`due_date`),
  ADD KEY `idx_tickets_cliente_email` (`cliente_email`),
  ADD KEY `idx_tickets_cliente_user` (`cliente_user_id`);

--
-- Indices de la tabla `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attachments_ticket` (`ticket_id`),
  ADD KEY `fk_attachments_user` (`user_id`);

--
-- Indices de la tabla `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_ticket` (`ticket_id`),
  ADD KEY `idx_comments_user` (`user_id`);

--
-- Indices de la tabla `ticket_etiquetas`
--
ALTER TABLE `ticket_etiquetas`
  ADD PRIMARY KEY (`ticket_id`,`etiqueta_id`),
  ADD KEY `fk_te_etiqueta` (`etiqueta_id`),
  ADD KEY `fk_te_added_by` (`added_by`);

--
-- Indices de la tabla `ticket_history`
--
ALTER TABLE `ticket_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_history_ticket` (`ticket_id`),
  ADD KEY `fk_history_user` (`user_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_departamento` (`departamento_id`),
  ADD KEY `idx_users_remember_token` (`remember_token`),
  ADD KEY `idx_api_token` (`api_token_hash`);

--
-- Indices de la tabla `user_departamentos`
--
ALTER TABLE `user_departamentos`
  ADD PRIMARY KEY (`user_id`,`departamento_id`),
  ADD KEY `idx_udep_depto` (`departamento_id`),
  ADD KEY `fk_udep_assigned_by` (`assigned_by`);

--
-- Indices de la tabla `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_permission` (`user_id`,`permission`),
  ADD KEY `idx_uperm_user` (`user_id`),
  ADD KEY `fk_uperm_granted_by` (`granted_by`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_comments`
--
ALTER TABLE `ticket_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `ticket_history`
--
ALTER TABLE `ticket_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_actlog_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_cat_depto` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conv_user1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conv_user2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_asignado_a` FOREIGN KEY (`asignado_a`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_cliente_user` FOREIGN KEY (`cliente_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD CONSTRAINT `fk_attachments_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_attachments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD CONSTRAINT `fk_comments_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ticket_etiquetas`
--
ALTER TABLE `ticket_etiquetas`
  ADD CONSTRAINT `fk_te_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_te_etiqueta` FOREIGN KEY (`etiqueta_id`) REFERENCES `etiquetas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_te_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ticket_history`
--
ALTER TABLE `ticket_history`
  ADD CONSTRAINT `fk_history_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `user_departamentos`
--
ALTER TABLE `user_departamentos`
  ADD CONSTRAINT `fk_udep_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_udep_depto` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_udep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `fk_uperm_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_uperm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

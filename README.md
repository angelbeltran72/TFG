# Sistema de Gestión de Tickets e Incidencias (MVC PHP)

Este proyecto es un sistema de gestión de tickets desarrollado en PHP 8.2 bajo una arquitectura Modelo-Vista-Controlador (MVC) personalizada. Permite gestionar incidencias, asignar tickets a departamentos, manejar permisos de usuarios y realizar un seguimiento completo mediante un registro de auditoría.

## Tecnologías y Stack

- **Backend:** PHP 8.2 (Arquitectura MVC propia sin frameworks externos)
- **Base de Datos:** MariaDB 10.4
- **Frontend:** HTML5, JavaScript, CSS3 nativo (por módulos)
- **Animaciones/Gráficos:** Three.js r128 (gradiente animado WebGL en vistas de autenticación)
- **Fuentes:** Google Fonts (Syne e Inter)
- **Utilidades:**
  - PHPMailer (para envío de correos, recuperación de contraseñas)
  - phpdotenv (para gestión de variables de entorno desde `.env`)

## Estructura del Proyecto

- `assets/`: Archivos estáticos (CSS, JS). Estilos modularizados por vista.
- `controllers/`: Controladores de la lógica de negocio (Auth, Dashboard, Tickets, Kanban, Perfil, etc.).
- `database/`: Scripts SQL para la creación y migración del esquema de base de datos (`Base de Datos Completa.sql`).
- `libs/`: Núcleo del framework (FrontController, AppController, View, SPDO, Config, Csrf, Mailer, setup).
- `models/`: Modelos para el acceso a datos e interacción con MariaDB mediante PDO (`TicketModel`, `UsuarioModel`, `UserPermissionModel`, etc.).
- `uploads/`: Directorio para la subida de archivos (ej. avatares de usuario `uploads/avatars/`).
- `views/`: Plantillas HTML/PHP para la interfaz de usuario.
- `vendor/`: Dependencias gestionadas mediante Composer.

## Instalación y Configuración

1.  **Clonar el repositorio:**
    Coloca el proyecto en tu servidor web (por ejemplo, en `htdocs` si usas XAMPP).

2.  **Instalar dependencias:**
    Ejecuta el siguiente comando para instalar PHPMailer, phpdotenv y otras dependencias:

    ```bash
    composer install
    ```

3.  **Configurar Variables de Entorno:**
    Copia el archivo `.env.example` y renómbralo a `.env`.

    ```bash
    cp .env.example .env
    ```

    Configura los parámetros de conexión a la base de datos y SMTP en el archivo `.env`.

4.  **Base de Datos:**
    Importa el esquema de la base de datos utilizando el archivo `database/Base de Datos Completa.sql` en tu servidor MariaDB.

## Características Principales

- **Autenticación y Seguridad:**
  - Sistema de login, registro y recuperación de contraseñas.
  - Protección CSRF en todos los formularios mediante tokens.
  - Fondo animado interactivo en vistas de login (Three.js `liquid-gradient.js`).
- **Gestión de Tickets e Incidencias:**
  - Creación, asignación (a departamentos y/o agentes), actualización de estados y prioridades.
  - Estados soportados: `sin_abrir`, `abierta`, `en_proceso`, `resuelta`, `cerrada`.
  - Vista en modo Lista detallada y modo Kanban interactivo.
- **Roles y Permisos Avanzados:**
  - Sistema granular de permisos sobreescritos por usuario (`UserPermissionModel`).
  - Los permisos definen acceso a configuración, gestión de áreas ajenas, creación en nombre de terceros, etc.
- **Estructura de Departamentos:**
  - Relación de muchos a muchos: asignación de usuarios a múltiples departamentos (`UserDepartamentoModel`).
  - Aislamiento de visión: los agentes estándar solo ven los tickets de las áreas a las que pertenecen.
- **Notificaciones y Auditoría:**
  - Campana de notificaciones con sistema "unread" y panel de preferencias por usuario.
  - Registro de actividad completo (`ActivityLogModel`) accesible para administradores, registrando accesos, cambios de permisos y ajustes globales.
- **Panel de Configuración:**
  - Ajustes en caliente del sistema sin tocar código (`SystemSettingModel`), como modo mantenimiento, abrir/cerrar registros o límites de tickets.

## Notas Adicionales

- **Enrutamiento:** El sistema utiliza un punto de entrada único (`index.php`) que enruta las peticiones basándose en parámetros de URL y en un enrutador propio en `FrontController.php`.
- **Cuentas Inactivas:** Los usuarios tienen una columna `is_active` para desactivar accesos sin borrar el historial ni corromper las relaciones de datos.
- **Forzar Contraseña:** Posibilidad desde administración de marcar `must_change_password`, lo que exige al usuario cambiar sus credenciales en su próximo login de forma interceptada.

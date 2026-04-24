<?php
// Bootstrap mínimo para la API (no carga controllers MVC ni setup.php)
$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($root);
$dotenv->safeLoad();

require_once $root . '/libs/Config.php';
require_once $root . '/libs/SPDO.php';
require_once $root . '/libs/Csrf.php';

require_once $root . '/models/UsuarioModel.php';
require_once $root . '/models/TicketModel.php';
require_once $root . '/models/DepartamentoModel.php';
require_once $root . '/models/UserDepartamentoModel.php';
require_once $root . '/models/NotificationModel.php';
require_once $root . '/models/UserPermissionModel.php';
require_once $root . '/models/SystemSettingModel.php';
require_once $root . '/models/ActivityLogModel.php';
require_once $root . '/models/TicketCommentModel.php';
require_once $root . '/models/CategoriaModel.php';
require_once $root . '/models/EtiquetaModel.php';

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Parseo de la URL
$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // ej: /MVC/api
$path     = ltrim(substr($uri, strlen($base)), '/');
$segments = array_values(array_filter(explode('/', $path)));

// /api/v1/tickets/5/comments → ['v1', 'tickets', '5', 'comments']
$version     = $segments[0] ?? '';
$resource    = $segments[1] ?? '';
$seg2        = $segments[2] ?? null;
$seg3        = $segments[3] ?? null;

// seg2 puede ser un id numérico o una acción textual (login, ping, read-all...)
$id          = ($seg2 !== null && ctype_digit($seg2)) ? (int)$seg2 : null;
$action      = ($seg2 !== null && !ctype_digit($seg2)) ? $seg2 : null;
$subresource = ($id !== null) ? $seg3 : null;

$method = strtoupper($_SERVER['REQUEST_METHOD']);

// Verificación de versión
if ($version !== 'v1') {
    http_response_code(404);
    echo json_encode(['error' => 'Versión de API no soportada'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Helpers
$ctrlDir = __DIR__ . '/controllers/';

function loadCtrl(string $name, string $dir): object {
    require_once $dir . 'ApiController.php';
    $file = $dir . $name . '.php';
    if (!file_exists($file)) {
        http_response_code(501);
        echo json_encode(['error' => "Recurso no implementado aún"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    require_once $file;
    return new $name();
}

function notFound(string $msg = 'Endpoint no encontrado'): never {
    http_response_code(404);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function methodNotAllowed(): never {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Router
switch ($resource) {

    // POST /v1/auth/login
    // POST /v1/auth/logout
    // GET  /v1/auth/me
    case 'auth':
        $ctrl = loadCtrl('ApiAuthController', $ctrlDir);
        match ([$method, $action]) {
            ['POST', 'login']  => $ctrl->login(),
            ['POST', 'logout'] => $ctrl->logout(),
            ['GET',  'me']     => $ctrl->me(),
            default            => notFound(),
        };
        break;

    // GET    /v1/tickets
    // POST   /v1/tickets
    // GET    /v1/tickets/{id}
    // PATCH  /v1/tickets/{id}
    // DELETE /v1/tickets/{id}
    // GET    /v1/tickets/{id}/comments
    // POST   /v1/tickets/{id}/comments
    // PATCH  /v1/tickets/{id}/status
    // PATCH  /v1/tickets/{id}/priority
    case 'tickets':
        $ctrl = loadCtrl('ApiTicketController', $ctrlDir);
        if ($id === null && $action === null) {
            match ($method) {
                'GET'    => $ctrl->index(),
                'POST'   => $ctrl->store(),
                default  => methodNotAllowed(),
            };
        } elseif ($id !== null && $subresource === null) {
            match ($method) {
                'GET'          => $ctrl->show($id),
                'PUT', 'PATCH' => $ctrl->update($id),
                'DELETE'       => $ctrl->destroy($id),
                default        => methodNotAllowed(),
            };
        } elseif ($id !== null) {
            match ([$method, $subresource]) {
                ['GET',   'comments'] => $ctrl->comments($id),
                ['POST',  'comments'] => $ctrl->addComment($id),
                ['PATCH', 'status']   => $ctrl->changeStatus($id),
                ['PATCH', 'priority'] => $ctrl->changePriority($id),
                default               => notFound(),
            };
        } else {
            notFound();
        }
        break;

    // GET   /v1/users
    // GET   /v1/users/me
    // GET   /v1/users/{id}
    // PATCH /v1/users/{id}
    // PATCH /v1/users/{id}/status
    case 'users':
        $ctrl = loadCtrl('ApiUserController', $ctrlDir);
        if ($id === null && $action === 'me') {
            if ($method === 'GET') $ctrl->profile();
            else methodNotAllowed();
        } elseif ($id === null && $action === null) {
            match ($method) {
                'GET'   => $ctrl->index(),
                default => methodNotAllowed(),
            };
        } elseif ($subresource === null) {
            match ($method) {
                'GET'          => $ctrl->show($id),
                'PUT', 'PATCH' => $ctrl->update($id),
                default        => methodNotAllowed(),
            };
        } elseif ($subresource === 'status') {
            if ($method === 'PATCH') $ctrl->toggleStatus($id);
            else methodNotAllowed();
        } else {
            notFound();
        }
        break;

    // POST /v1/presence/ping
    case 'presence':
        $ctrl = loadCtrl('ApiPresenceController', $ctrlDir);
        if ($method === 'POST' && $action === 'ping') $ctrl->ping();
        else notFound();
        break;

    // GET  /v1/notifications
    // GET  /v1/notifications/count
    // POST /v1/notifications/read-all
    // POST /v1/notifications/{id}/read
    case 'notifications':
        $ctrl = loadCtrl('ApiNotificationController', $ctrlDir);
        if ($id === null) {
            match ([$method, $action]) {
                ['GET',  null]       => $ctrl->index(),
                ['GET',  'count']    => $ctrl->count(),
                ['POST', 'read-all'] => $ctrl->readAll(),
                default              => notFound(),
            };
        } elseif ($method === 'POST' && $subresource === 'read') {
            $ctrl->markRead($id);
        } else {
            notFound();
        }
        break;

    // GET   /v1/kanban
    // PATCH /v1/kanban/{id}/move
    case 'kanban':
        $ctrl = loadCtrl('ApiKanbanController', $ctrlDir);
        if ($id === null) {
            if ($method === 'GET') $ctrl->index();
            else methodNotAllowed();
        } elseif ($method === 'PATCH' && $subresource === 'move') {
            $ctrl->move($id);
        } else {
            notFound();
        }
        break;

    // GET /v1/dashboard/stats
    case 'dashboard':
        $ctrl = loadCtrl('ApiDashboardController', $ctrlDir);
        if ($method === 'GET' && $action === 'stats') $ctrl->stats();
        else notFound();
        break;

    default:
        notFound('Recurso no encontrado');
}

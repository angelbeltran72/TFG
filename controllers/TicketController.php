<?php
// Controlador de tickets — gestiona creación, listado y detalle de incidencias
class TicketController extends AppController {

  private function requireLogin(): array {
    if (!isset($_SESSION["usuario"])) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Debes iniciar sesión."];
      header("Location: index.php?controller=Auth&action=iniciarSesion");
      exit;
    }
    return $_SESSION["usuario"];
  }

  private function userRole(array $user): string {
    return $user['rol'] ?? 'user';
  }

  private function isClient(array $user): bool {
    return $this->userRole($user) === 'cliente';
  }

  private function isAdmin(array $user): bool {
    return $this->userRole($user) === 'admin';
  }

  private function redirectListByRole(string $role): string {
    return $role === 'cliente'
      ? 'index.php?controller=Ticket&action=misTickets'
      : 'index.php?controller=Ticket&action=listar';
  }

  private function verificarCsrf(string $fallback): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token CSRF inválido.'];
      header('Location: ' . $fallback);
      exit;
    }
  }

  private function forbidClientManagement(array $user, bool $json = false): void {
    if (!$this->isClient($user)) {
      return;
    }
    if ($json) {
      echo json_encode(['ok' => false, 'msg' => 'Acción no permitida para cuentas cliente.']);
      exit;
    }
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Tu cuenta cliente solo tiene acceso de seguimiento.'];
    header('Location: index.php?controller=Ticket&action=misTickets');
    exit;
  }

  // Muestra el formulario de nueva incidencia con categorías y usuarios asignables
  public function nuevo() {
    $user    = $this->requireLogin();
    $this->forbidClientManagement($user);
    $role = $this->userRole($user);
    $isAdmin = $this->isAdmin($user);
    $categorias  = TicketModel::getCategorias();
    $usuarios    = TicketModel::getUsuariosAsignables();
    $departamentos = $isAdmin
      ? DepartamentoModel::getAllActive()
      : UserDepartamentoModel::getAllForUser((int)$user['id']);
    $this->view->show("ticketNewView", [
      "user"           => $user,
      "categorias"     => $categorias,
      "usuarios"       => $usuarios,
      "departamentos"  => $departamentos,
      "isAdmin"        => $isAdmin,
      "role"           => $role,
      "canAddCliente"  => !$this->isClient($user),
    ]);
  }

  // Procesa el POST del formulario; valida campos y crea el ticket con estado 'sin_abrir'
  public function crear() {
    $user = $this->requireLogin();
    $this->forbidClientManagement($user);
    $this->verificarCsrf('index.php?controller=Ticket&action=nuevo');

    $titulo           = trim($_POST['titulo'] ?? '');
    $categoria_id     = (int)($_POST['categoria_id'] ?? 0);
    $prioridad        = trim($_POST['prioridad'] ?? 'media');
    $descripcion      = trim($_POST['descripcion'] ?? '');
    $due_date_raw     = trim($_POST['due_date'] ?? '');
    $due_date         = ($due_date_raw !== '' && strtotime($due_date_raw)) ? $due_date_raw : null;
    $departamento_raw = (int)($_POST['departamento_id'] ?? 0);
    $esClienteExterno = !empty($_POST['es_cliente_externo']);
    $clienteEmailRaw  = trim($_POST['cliente_email'] ?? '');
    $clienteEmail     = null;
    $clienteUserId    = null;

    $isAdmin = $this->isAdmin($user);
    $asignado_a = null;
    if ($isAdmin) {
      $raw = $_POST['asignado_a'] ?? '';
      $asignado_a = ($raw === '' ? null : (int)$raw);
    }

    $errors = [];
    if ($titulo === '' || mb_strlen($titulo) < 3)       $errors['titulo']           = 'El título es obligatorio (mín. 3 caracteres).';
    if ($descripcion === '' || mb_strlen($descripcion) < 10) $errors['descripcion'] = 'La descripción es obligatoria (mín. 10 caracteres).';
    if ($departamento_raw <= 0)                          $errors['departamento_id']  = 'El departamento es obligatorio.';
    if ($categoria_id <= 0)                              $errors['categoria_id']     = 'Selecciona una categoría.';
    if (!in_array($prioridad, ['baja','media','alta','critica'], true)) $errors['prioridad'] = 'Prioridad no válida.';
    if ($esClienteExterno) {
      if ($clienteEmailRaw === '' || !filter_var($clienteEmailRaw, FILTER_VALIDATE_EMAIL)) {
        $errors['cliente_email'] = 'Debes indicar un email válido del cliente externo.';
      } else {
        $clienteEmail = mb_strtolower($clienteEmailRaw);
        $clienteUserId = TicketModel::findUserIdByEmail($clienteEmail);
      }
    }

    $departamento_id = null;
    if ($departamento_raw > 0 && !isset($errors['departamento_id'])) {
      if ($isAdmin) {
        $departamento_id = $departamento_raw;
      } else {
        $userDeptIds = array_column(UserDepartamentoModel::getAllForUser((int)$user['id']), 'id');
        if (in_array($departamento_raw, array_map('intval', $userDeptIds))) {
          $departamento_id = $departamento_raw;
        } else {
          $errors['departamento_id'] = 'Departamento no válido.';
        }
      }
    }

    if (!empty($errors)) {
      $_SESSION['errors'] = $errors;
      $_SESSION['old'] = [
        'titulo'          => $titulo,
        'categoria_id'    => $categoria_id,
        'departamento_id' => $departamento_raw,
        'prioridad'       => $prioridad,
        'descripcion'     => $descripcion,
        'asignado_a'      => $asignado_a,
        'es_cliente_externo' => $esClienteExterno ? '1' : '0',
        'cliente_email'      => $clienteEmailRaw,
      ];
      header('Location: index.php?controller=Ticket&action=nuevo');
      exit;
    }

    // Límite de tickets activos por usuario (no aplica a admins)
    $maxTickets = SystemSettingModel::getInt('max_tickets_por_usuario', 0);
    if ($maxTickets > 0 && !$isAdmin) {
      $activeCount = TicketModel::countActiveForUser((int)$user['id']);
      if ($activeCount >= $maxTickets) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => "Has alcanzado el límite de {$maxTickets} ticket(s) activos. Espera a que alguno se resuelva antes de crear uno nuevo."];
        header('Location: index.php?controller=Ticket&action=nuevo');
        exit;
      }
    }

    // Auto-asignación round-robin
    if ($asignado_a === null && SystemSettingModel::get('modo_asignacion') === 'roundrobin') {
      $asignado_a = self::nextRoundRobinAgent();
    }

    $id = TicketModel::create([
      'titulo'         => $titulo,
      'descripcion'    => $descripcion,
      'categoria_id'   => $categoria_id,
      'departamento_id'=> $departamento_id,
      'prioridad'      => $prioridad,
      'creado_por'     => (int)$user['id'],
      'asignado_a'     => $asignado_a,
      'cliente_email'  => $clienteEmail,
      'cliente_user_id'=> $clienteUserId,
      'due_date'       => $due_date,
    ]);

    if ($asignado_a && $asignado_a !== (int)$user['id']) {
      NotificationModel::create(
        $asignado_a, 'ticket_assigned',
        'Se te ha asignado el ticket #' . $id . ': ' . $titulo,
        'ticket', $id
      );
    }

    // Guardar archivos adjuntos
    if (!empty($_FILES['adjuntos']['name'][0])) {
      $allowedMime = [
        'image/jpeg','image/png','image/gif','image/webp',
        'application/pdf',
        'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain','text/csv',
        'application/zip','application/x-zip-compressed',
      ];
      $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/MVC/uploads/tickets/';
      $count = count($_FILES['adjuntos']['name']);
      for ($i = 0; $i < $count; $i++) {
        if ($_FILES['adjuntos']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $tmp          = $_FILES['adjuntos']['tmp_name'][$i];
        $originalName = basename($_FILES['adjuntos']['name'][$i]);
        $sizeBytes    = (int)$_FILES['adjuntos']['size'][$i];
        $finfo        = new finfo(FILEINFO_MIME_TYPE);
        $mimeType     = $finfo->file($tmp);
        if (!in_array($mimeType, $allowedMime, true)) continue;
        $ext      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = 't' . $id . '_' . uniqid() . '.' . $ext;
        $destFs   = $uploadDir . $filename;
        if (!move_uploaded_file($tmp, $destFs)) continue;
        $storagePath = BASE . '/uploads/tickets/' . $filename;
        TicketAttachmentModel::save($id, (int)$user['id'], $filename, $storagePath, $originalName, $mimeType, $sizeBytes);
      }
    }

    $_SESSION['flash'] = ['type' => 'success', 'msg' => "Incidencia creada (#$id)."];
    header('Location: ' . $this->redirectListByRole($this->userRole($user)));
    exit;
  }

  public function detalle() {
    $user    = $this->requireLogin();
    $role    = $this->userRole($user);
    $isAdmin = $this->isAdmin($user);
    $id      = (int)($_GET['id'] ?? 0);
    $listUrl = $this->redirectListByRole($role);

    if ($id <= 0) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'ID de ticket inválido.'];
      header('Location: ' . $listUrl);
      exit;
    }

    $ticket = TicketModel::findById($id, (int)$user['id'], $isAdmin, $role);
    if (!$ticket) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Incidencia no encontrada.'];
      header('Location: ' . $listUrl);
      exit;
    }

    if ($ticket['estado'] === 'sin_abrir' && $role !== 'cliente') {
      TicketModel::update($id, ['estado' => 'abierta']);
      TicketCommentModel::logEvent($id, (int)$user['id'], 'state_change', 'sin_abrir|abierta');
      $ticket['estado'] = 'abierta';
    }

    $comentarios = TicketCommentModel::getByTicket($id, $role !== 'cliente');
    $adjuntos    = TicketAttachmentModel::getByTicket($id);

    $this->view->show("ticketDetailView", [
      "user"        => $user,
      "ticket"      => $ticket,
      "comentarios" => $comentarios,
      "adjuntos"    => $adjuntos,
      "isAdmin"     => $isAdmin,
      "role"        => $role,
    ]);
  }

  public function misTickets() {
    $user    = $this->requireLogin();
    $role    = $this->userRole($user);
    $userId  = (int)$user['id'];

    if ($role === 'cliente') {
      TicketModel::linkClientTicketsByEmail($userId, (string)($user['email'] ?? ''));
    }

    $perPage = 20;
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $offset  = ($page - 1) * $perPage;

    $filters = [
      'estado'           => trim($_GET['estado']      ?? ''),
      'prioridad'        => trim($_GET['prioridad']   ?? ''),
      'categoria_id'     => $_GET['categoria_id']     ?? null,
      'q'                => trim($_GET['q']           ?? ''),
      'sort'             => in_array($_GET['sort'] ?? '', ['asc','desc'], true) ? $_GET['sort'] : 'desc',
      'solo_mis_tickets' => $role !== 'cliente',
    ];

    $tickets = TicketModel::listForUserFiltered($userId, false, $filters, $perPage, $offset, $role);
    $total   = TicketModel::countForUserFiltered($userId, false, $filters, $role);
    $pages   = max(1, (int)ceil($total / $perPage));

    $this->view->show("misTicketsView", [
      "user"       => $user,
      "tickets"    => $tickets,
      "filters"    => $filters,
      "page"       => $page,
      "pages"      => $pages,
      "total"      => $total,
      "categorias" => TicketModel::getCategorias(),
      "usuarios"   => TicketModel::getUsuariosAsignables(),
      "role"       => $role,
    ]);
  }

  // Lista todos los tickets visibles para el usuario con filtros y paginación
  public function listar() {
    $user    = $this->requireLogin();
    $role    = $this->userRole($user);
    if ($role === 'cliente') {
      header('Location: index.php?controller=Ticket&action=misTickets');
      exit;
    }
    $isAdmin = $this->isAdmin($user);

    $perPage = 12;
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $offset  = ($page - 1) * $perPage;

    $filters = [
      'estado'        => trim($_GET['estado']        ?? ''),
      'prioridad'     => trim($_GET['prioridad']     ?? ''),
      'categoria_id'  => $_GET['categoria_id']       ?? null,
      'departamento_id'=> $isAdmin ? ($_GET['departamento_id'] ?? null) : null,
      'q'             => trim($_GET['q']             ?? ''),
      'sort'          => in_array($_GET['sort'] ?? '', ['asc','desc'], true) ? $_GET['sort'] : 'desc',
    ];

    if (!$isAdmin) {
      $userDepts = UserDepartamentoModel::getAllForUser((int)$user['id']);
      $filters['agent_dept_ids'] = array_column($userDepts, 'id');
    }

    $tickets = TicketModel::listForUserFiltered((int)$user['id'], $isAdmin, $filters, $perPage, $offset, $role);
    $total   = TicketModel::countForUserFiltered((int)$user['id'], $isAdmin, $filters, $role);
    $pages   = (int)ceil($total / $perPage);

    $this->view->show("ticketListView", [
      "user"          => $user,
      "tickets"       => $tickets,
      "isAdmin"       => $isAdmin,
      "filters"       => $filters,
      "page"          => $page,
      "pages"         => $pages,
      "total"         => $total,
      "categorias"    => TicketModel::getCategorias(),
      "usuarios"      => TicketModel::getUsuariosAsignables(),
      "departamentos" => $isAdmin ? DepartamentoModel::getAllActive() : [],
    ]);
  }

  public function editar() {
    $user    = $this->requireLogin();
    $this->forbidClientManagement($user);
    $isAdmin = $this->isAdmin($user);
    if (!$isAdmin) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Sin permisos.'];
      header('Location: index.php?controller=Ticket&action=listar');
      exit;
    }
    $id = (int)($_GET['id'] ?? 0);
    $ticket = TicketModel::findById($id, (int)$user['id'], true, 'admin');
    if (!$ticket) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Ticket no encontrado.'];
      header('Location: index.php?controller=Ticket&action=listar');
      exit;
    }
    $this->view->show("ticketEditView", [
      "user"       => $user,
      "ticket"     => $ticket,
      "categorias" => TicketModel::getCategorias(),
      "usuarios"   => TicketModel::getUsuariosAsignables(),
    ]);
  }

  public function actualizar() {
    $user    = $this->requireLogin();
    $this->forbidClientManagement($user);
    $this->verificarCsrf('index.php?controller=Ticket&action=listar');
    $isAdmin = $this->isAdmin($user);
    if (!$isAdmin) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Sin permisos.'];
      header('Location: index.php?controller=Ticket&action=listar');
      exit;
    }
    $id          = (int)($_POST['ticket_id'] ?? 0);
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoriaId = (int)($_POST['categoria_id'] ?? 0);
    $prioridad   = trim($_POST['prioridad'] ?? 'media');
    $estado      = trim($_POST['estado'] ?? 'sin_abrir');
    $asignadoA   = $_POST['asignado_a'] ?? '';
    $asignadoA   = ($asignadoA === '' ? null : (int)$asignadoA);
    $dueDateRaw  = trim($_POST['due_date'] ?? '');
    $dueDate     = ($dueDateRaw !== '' && strtotime($dueDateRaw)) ? $dueDateRaw : null;
    $esClienteExterno = !empty($_POST['es_cliente_externo']);
    $clienteEmailRaw  = trim($_POST['cliente_email'] ?? '');
    $clienteEmail     = null;
    $clienteUserId    = null;

    $errors = [];
    if ($titulo === '' || mb_strlen($titulo) < 3)       $errors[] = 'El título es obligatorio.';
    if ($descripcion === '' || mb_strlen($descripcion) < 10) $errors[] = 'La descripción es obligatoria.';
    if ($categoriaId <= 0)                              $errors[] = 'Selecciona una categoría.';
    if (!in_array($prioridad, ['baja','media','alta','critica'], true)) $errors[] = 'Prioridad inválida.';
    if (!in_array($estado, ['sin_abrir','abierta','en_proceso','resuelta','cerrada'], true)) $errors[] = 'Estado inválido.';
    if ($esClienteExterno) {
      if ($clienteEmailRaw === '' || !filter_var($clienteEmailRaw, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Debes indicar un email válido del cliente externo.';
      } else {
        $clienteEmail = mb_strtolower($clienteEmailRaw);
        $clienteUserId = TicketModel::findUserIdByEmail($clienteEmail);
      }
    }

    if (!empty($errors)) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => implode(' ', $errors)];
      header('Location: index.php?controller=Ticket&action=editar&id=' . $id);
      exit;
    }

    $current = TicketModel::findById($id, (int)$user['id'], true, 'admin');

    TicketModel::update($id, [
      'titulo'       => $titulo,
      'descripcion'  => $descripcion,
      'categoria_id' => $categoriaId,
      'prioridad'    => $prioridad,
      'estado'       => $estado,
      'asignado_a'   => $asignadoA,
      'cliente_email'   => $esClienteExterno ? $clienteEmail : null,
      'cliente_user_id' => $esClienteExterno ? $clienteUserId : null,
      'due_date'     => $dueDate,
    ]);

    if ($current) {
      if ($current['estado'] !== $estado) {
        TicketCommentModel::logEvent($id, (int)$user['id'], 'state_change', $current['estado'] . '|' . $estado);
        $_statusMsg = 'El estado del ticket #' . $id . ' cambió a ' . $estado;
        foreach (array_unique(array_filter([(int)($current['creado_por'] ?? 0), (int)($current['asignado_a'] ?? 0)])) as $_uid) {
          if ($_uid !== (int)$user['id']) {
            NotificationModel::create($_uid, 'ticket_status', $_statusMsg, 'ticket', $id);
          }
        }
      }
      if ($current['prioridad'] !== $prioridad) {
        TicketCommentModel::logEvent($id, (int)$user['id'], 'priority_change', $current['prioridad'] . '|' . $prioridad);
      }
      $oldAssigned = (int)($current['asignado_a'] ?? 0);
      $newAssigned = (int)($asignadoA ?? 0);
      if ($oldAssigned !== $newAssigned) {
        $assigneeName = $newAssigned ? TicketModel::getUserNameById($newAssigned) : '';
        TicketCommentModel::logEvent($id, (int)$user['id'], 'assignment', $assigneeName);
        if ($newAssigned && $newAssigned !== (int)$user['id']) {
          NotificationModel::create(
            $newAssigned, 'ticket_assigned',
            'Se te ha asignado el ticket #' . $id . ': ' . $titulo,
            'ticket', $id
          );
        }
      }
    }

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Ticket actualizado.'];
    header('Location: index.php?controller=Ticket&action=detalle&id=' . $id);
    exit;
  }

  public function eliminar() {
    $user    = $this->requireLogin();
    $this->forbidClientManagement($user);
    $this->verificarCsrf('index.php?controller=Ticket&action=listar');
    $isAdmin = $this->isAdmin($user);
    if (!$isAdmin) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Sin permisos.'];
      header('Location: index.php?controller=Ticket&action=listar');
      exit;
    }
    $id = (int)($_POST['ticket_id'] ?? 0);
    if ($id <= 0) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'ID inválido.'];
      header('Location: index.php?controller=Ticket&action=listar');
      exit;
    }
    TicketModel::softDelete($id);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Ticket eliminado.'];
    header('Location: index.php?controller=Ticket&action=listar');
    exit;
  }

  public function comentar() {
    $user = $this->requireLogin();
    $this->forbidClientManagement($user);
    $this->verificarCsrf('index.php?controller=Ticket&action=listar');
    $role = $this->userRole($user);
    $ticketId  = (int)($_POST['ticket_id'] ?? 0);
    $contenido = trim($_POST['contenido'] ?? '');
    $isAdmin = $this->isAdmin($user);
    $isInternal = ($isAdmin || $role === 'user') && !empty($_POST['is_internal']);

    if ($ticketId <= 0 || mb_strlen($contenido) < 2) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Comentario demasiado corto.'];
      header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=Ticket&action=listar'));
      exit;
    }

    $ticket = TicketModel::findById($ticketId, (int)$user['id'], $isAdmin, $role);
    if (!$ticket || $ticket['estado'] === 'cerrada') {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'No se puede comentar en este ticket.'];
      header('Location: index.php?controller=Ticket&action=listar');
      exit;
    }

    TicketCommentModel::add($ticketId, (int)$user['id'], $contenido, $isInternal);

    if (!$isInternal) {
      $_commentMsg = ($user['nombre'] ?? 'Alguien') . ' comentó en el ticket #' . $ticketId . ': ' . mb_strimwidth($contenido, 0, 60, '…');
      foreach (array_unique(array_filter([(int)($ticket['creado_por'] ?? 0), (int)($ticket['asignado_a'] ?? 0)])) as $_uid) {
        if ($_uid !== (int)$user['id']) {
          NotificationModel::create($_uid, 'ticket_comment', $_commentMsg, 'ticket', $ticketId);
        }
      }
    }

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Comentario añadido.'];
    header('Location: index.php?controller=Ticket&action=detalle&id=' . $ticketId);
    exit;
  }

  public function adjuntar() {
    $user     = $this->requireLogin();
    $this->verificarCsrf('index.php?controller=Ticket&action=listar');
    $role     = $this->userRole($user);
    $isAdmin  = $this->isAdmin($user);
    $ticketId = (int)($_POST['ticket_id'] ?? 0);

    if ($ticketId <= 0) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Ticket no válido.'];
      header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=Ticket&action=listar'));
      exit;
    }

    $ticket = TicketModel::findById($ticketId, (int)$user['id'], $isAdmin, $role);
    if (!$ticket || $ticket['estado'] === 'cerrada') {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'No se pueden añadir adjuntos a este ticket.'];
      header('Location: index.php?controller=Ticket&action=detalle&id=' . $ticketId);
      exit;
    }

    if (empty($_FILES['adjuntos']['name'][0])) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'No has seleccionado ningún archivo.'];
      header('Location: index.php?controller=Ticket&action=detalle&id=' . $ticketId);
      exit;
    }

    $allowedMime = [
      'image/jpeg','image/png','image/gif','image/webp',
      'application/pdf',
      'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'text/plain','text/csv',
      'application/zip','application/x-zip-compressed',
    ];
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/MVC/uploads/tickets/';
    $saved     = 0;
    $count     = count($_FILES['adjuntos']['name']);

    for ($i = 0; $i < $count; $i++) {
      if ($_FILES['adjuntos']['error'][$i] !== UPLOAD_ERR_OK) continue;
      $tmp          = $_FILES['adjuntos']['tmp_name'][$i];
      $originalName = basename($_FILES['adjuntos']['name'][$i]);
      $sizeBytes    = (int)$_FILES['adjuntos']['size'][$i];
      $finfo        = new finfo(FILEINFO_MIME_TYPE);
      $mimeType     = $finfo->file($tmp);
      if (!in_array($mimeType, $allowedMime, true)) continue;
      $ext      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
      $filename = 't' . $ticketId . '_' . uniqid() . '.' . $ext;
      $destFs   = $uploadDir . $filename;
      if (!move_uploaded_file($tmp, $destFs)) continue;
      $storagePath = BASE . '/uploads/tickets/' . $filename;
      TicketAttachmentModel::save($ticketId, (int)$user['id'], $filename, $storagePath, $originalName, $mimeType, $sizeBytes);
      $saved++;
    }

    $_SESSION['flash'] = $saved > 0
      ? ['type' => 'success', 'msg' => $saved . ' archivo(s) adjuntado(s) correctamente.']
      : ['type' => 'error',   'msg' => 'No se pudo guardar ningún archivo (tipo no permitido o error al subir).'];

    header('Location: index.php?controller=Ticket&action=detalle&id=' . $ticketId);
    exit;
  }

  public function cambiarEstado() {
    $user    = $this->requireLogin();
    $this->forbidClientManagement($user);
    $this->verificarCsrf('index.php?controller=Ticket&action=listar');
    $role = $this->userRole($user);
    $isAdmin = $this->isAdmin($user);
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $nuevoEstado = trim($_POST['estado'] ?? '');
    $validos = ['sin_abrir','abierta','en_proceso','resuelta','cerrada'];

    if ($ticketId <= 0 || !in_array($nuevoEstado, $validos, true)) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Datos inválidos.'];
      header('Location: index.php?controller=Ticket&action=listar');
      exit;
    }

    $ticket = TicketModel::findById($ticketId, (int)$user['id'], $isAdmin, $role);
    if (!$ticket) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Ticket no encontrado.'];
      header('Location: index.php?controller=Ticket&action=listar');
      exit;
    }

    if (!$isAdmin && (int)($ticket['asignado_a'] ?? 0) !== (int)$user['id']) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Solo el agente asignado o un administrador puede cambiar el estado.'];
      header('Location: index.php?controller=Ticket&action=detalle&id=' . $ticketId);
      exit;
    }

    if ($nuevoEstado === 'cerrada' && !$isAdmin) {
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Sin permisos para cerrar tickets.'];
      header('Location: index.php?controller=Ticket&action=detalle&id=' . $ticketId);
      exit;
    }

    $oldEstado = $ticket['estado'];
    TicketModel::update($ticketId, ['estado' => $nuevoEstado]);
    TicketCommentModel::logEvent($ticketId, (int)$user['id'], 'state_change', $oldEstado . '|' . $nuevoEstado);

    $_statusMsg = 'El estado del ticket #' . $ticketId . ' cambió a ' . $nuevoEstado;
    foreach (array_unique(array_filter([(int)($ticket['creado_por'] ?? 0), (int)($ticket['asignado_a'] ?? 0)])) as $_uid) {
      if ($_uid !== (int)$user['id']) {
        NotificationModel::create($_uid, 'ticket_status', $_statusMsg, 'ticket', $ticketId);
      }
    }

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Estado actualizado.'];
    header('Location: index.php?controller=Ticket&action=detalle&id=' . $ticketId);
    exit;
  }

  public function ajaxCambiarEstado(): void {
    header('Content-Type: application/json');
    $user    = $this->requireLogin();
    $this->forbidClientManagement($user, true);
    $role = $this->userRole($user);
    $isAdmin = $this->isAdmin($user);

    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      echo json_encode(['ok' => false, 'msg' => 'Token CSRF inválido.']); exit;
    }
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $nuevo    = trim($_POST['estado'] ?? '');
    $validos  = ['sin_abrir','abierta','en_proceso','resuelta','cerrada'];
    if ($ticketId <= 0 || !in_array($nuevo, $validos, true)) {
      echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.']); exit;
    }
    $ticket = TicketModel::findById($ticketId, (int)$user['id'], $isAdmin, $role);
    if (!$ticket) {
      echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado.']); exit;
    }
    if (!$isAdmin && (int)($ticket['asignado_a'] ?? 0) !== (int)$user['id']) {
      echo json_encode(['ok' => false, 'msg' => 'Solo el agente asignado o un administrador puede cambiar el estado.']); exit;
    }
    if ($nuevo === 'cerrada' && !$isAdmin) {
      echo json_encode(['ok' => false, 'msg' => 'Sin permisos para cerrar tickets.']); exit;
    }
    TicketCommentModel::logEvent($ticketId, (int)$user['id'], 'state_change', $ticket['estado'] . '|' . $nuevo);
    TicketModel::update($ticketId, ['estado' => $nuevo]);

    $_statusMsg = 'El estado del ticket #' . $ticketId . ' cambió a ' . $nuevo;
    foreach (array_unique(array_filter([(int)($ticket['creado_por'] ?? 0), (int)($ticket['asignado_a'] ?? 0)])) as $_uid) {
      if ($_uid !== (int)$user['id']) {
        NotificationModel::create($_uid, 'ticket_status', $_statusMsg, 'ticket', $ticketId);
      }
    }

    echo json_encode(['ok' => true]);
    exit;
  }

  public function ajaxCambiarPrioridad(): void {
    header('Content-Type: application/json');
    $user    = $this->requireLogin();
    $this->forbidClientManagement($user, true);
    $role = $this->userRole($user);
    $isAdmin = $this->isAdmin($user);

    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      echo json_encode(['ok' => false, 'msg' => 'Token CSRF inválido.']); exit;
    }
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $nueva    = trim($_POST['prioridad'] ?? '');
    $validas  = ['baja', 'media', 'alta', 'critica'];
    if ($ticketId <= 0 || !in_array($nueva, $validas, true)) {
      echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.']); exit;
    }
    $ticket = TicketModel::findById($ticketId, (int)$user['id'], $isAdmin, $role);
    if (!$ticket) {
      echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado.']); exit;
    }
    if (!$isAdmin && (int)($ticket['asignado_a'] ?? 0) !== (int)$user['id']) {
      echo json_encode(['ok' => false, 'msg' => 'Sin permisos.']); exit;
    }
    TicketCommentModel::logEvent($ticketId, (int)$user['id'], 'priority_change', $ticket['prioridad'] . '|' . $nueva);
    TicketModel::update($ticketId, ['prioridad' => $nueva]);
    echo json_encode(['ok' => true]);
    exit;
  }

  public function ajaxEliminar(): void {
    header('Content-Type: application/json');
    $user    = $this->requireLogin();
    $this->forbidClientManagement($user, true);
    $isAdmin = $this->isAdmin($user);
    if (!$isAdmin) {
      echo json_encode(['ok' => false, 'msg' => 'Sin permisos.']); exit;
    }
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      echo json_encode(['ok' => false, 'msg' => 'Token CSRF inválido.']); exit;
    }
    $id = (int)($_POST['ticket_id'] ?? 0);
    if ($id <= 0) {
      echo json_encode(['ok' => false, 'msg' => 'ID inválido.']); exit;
    }
    TicketModel::softDelete($id);
    echo json_encode(['ok' => true]);
    exit;
  }

  public function ajaxClaimar(): void {
    header('Content-Type: application/json');
    $user = $this->requireLogin();
    $this->forbidClientManagement($user, true);
    $role = $this->userRole($user);
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      echo json_encode(['ok' => false, 'msg' => 'Token CSRF inválido.']); exit;
    }
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    if ($ticketId <= 0) {
      echo json_encode(['ok' => false, 'msg' => 'ID inválido.']); exit;
    }
    $isAdmin = $this->isAdmin($user);
    $ticket  = TicketModel::findById($ticketId, (int)$user['id'], $isAdmin, $role);
    if (!$ticket) {
      echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado.']); exit;
    }
    if ($ticket['estado'] === 'cerrada') {
      echo json_encode(['ok' => false, 'msg' => 'El ticket está cerrado.']); exit;
    }
    TicketModel::update($ticketId, ['asignado_a' => (int)$user['id']]);
    TicketCommentModel::logEvent($ticketId, (int)$user['id'], 'assignment', $user['nombre']);
    $parts = explode(' ', trim($user['nombre']));
    $inic  = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    echo json_encode(['ok' => true, 'nombre' => $user['nombre'], 'inic' => $inic]);
    exit;
  }

  public function ajaxReasignar(): void {
    header('Content-Type: application/json');
    $user    = $this->requireLogin();
    $this->forbidClientManagement($user, true);
    $isAdmin = $this->isAdmin($user);
    if (!$isAdmin) {
      echo json_encode(['ok' => false, 'msg' => 'Sin permisos.']); exit;
    }
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
      echo json_encode(['ok' => false, 'msg' => 'Token CSRF inválido.']); exit;
    }
    $ticketId  = (int)($_POST['ticket_id'] ?? 0);
    $asignadoA = $_POST['asignado_a'] ?? '';
    $asignadoA = ($asignadoA === '' ? null : (int)$asignadoA);
    if ($ticketId <= 0) {
      echo json_encode(['ok' => false, 'msg' => 'ID inválido.']); exit;
    }
    $assigneeName = $asignadoA ? TicketModel::getUserNameById($asignadoA) : '';
    TicketCommentModel::logEvent($ticketId, (int)$user['id'], 'assignment', $assigneeName);
    TicketModel::update($ticketId, ['asignado_a' => $asignadoA]);

    if ($asignadoA && $asignadoA !== (int)$user['id']) {
      $_tkt = TicketModel::findById($ticketId, (int)$user['id'], true, 'admin');
      NotificationModel::create(
        $asignadoA, 'ticket_assigned',
        'Se te ha asignado el ticket #' . $ticketId . ($_tkt ? ': ' . $_tkt['titulo'] : ''),
        'ticket', $ticketId
      );
    }

    echo json_encode(['ok' => true]);
    exit;
  }

  // Carga el detalle de un ticket; devuelve 404 si no existe o el usuario no tiene acceso
  public function ver() {
    $user = $this->requireLogin();
    $role = $this->userRole($user);
    $isAdmin = $this->isAdmin($user);
    $listUrl = $this->redirectListByRole($role);

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
      http_response_code(400);
      die('ID inválido');
    }

    $ticket = TicketModel::findById($id, (int)$user['id'], $isAdmin, $role);
    if (!$ticket) {
      http_response_code(404);
      $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Incidencia no encontrada.'];
      header('Location: ' . $listUrl);
      exit;
    }

    if ($ticket['estado'] === 'sin_abrir' && $role !== 'cliente') {
      TicketModel::update($id, ['estado' => 'abierta']);
      TicketCommentModel::logEvent($id, (int)$user['id'], 'state_change', 'sin_abrir|abierta');
      $ticket['estado'] = 'abierta';
    }

    $comentarios = TicketCommentModel::getByTicket($id, $role !== 'cliente');
    $adjuntos    = TicketAttachmentModel::getByTicket($id);
    $this->view->show("ticketDetailView", [
      "user"        => $user,
      "ticket"      => $ticket,
      "isAdmin"     => $isAdmin,
      "comentarios" => $comentarios,
      "adjuntos"    => $adjuntos,
      "role"        => $role,
    ]);
  }

  private static function nextRoundRobinAgent(): ?int {
    $inclAdmins = SystemSettingModel::getBool('incluir_admins_rotacion');
    $inSql = $inclAdmins ? "'user','admin'" : "'user'";
    $db = SPDO::singleton();
    $agents = $db->query(
      "SELECT id FROM users WHERE rol IN ($inSql) AND is_active = 1 ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_COLUMN);

    if (empty($agents)) return null;
    if (count($agents) === 1) return (int)$agents[0];

    $placeholders = implode(',', array_fill(0, count($agents), '?'));
    $st = $db->prepare(
      "SELECT asignado_a FROM tickets WHERE asignado_a IN ($placeholders) AND deleted_at IS NULL ORDER BY id DESC LIMIT 1"
    );
    $st->execute($agents);
    $lastAgent = $st->fetchColumn();

    if ($lastAgent === false) return (int)$agents[0];

    $idx = array_search((string)$lastAgent, array_map('strval', $agents));
    $nextIdx = ($idx !== false) ? (int)(($idx + 1) % count($agents)) : 0;
    return (int)$agents[$nextIdx];
  }
}

<?php
// Controlador de soporte — muestra estado del sistema y envía mensajes al administrador
class SoporteController extends AppController {

  private function verificarCsrf(string $fallback): void {
    $token = $_POST["csrf_token"] ?? "";
    if (!Csrf::validate($token)) {
      $_SESSION["flash"] = ["type" => "error", "msg" => "Token de seguridad inválido. Recarga la página."];
      header("Location: " . $fallback);
      exit;
    }
  }

  public function index(): void {
    // Versión de PHP en ejecución
    $statusItems["php"] = [
      "label" => "PHP",
      "value" => phpversion(),
      "icon"  => "code",
      "ok"    => true,
    ];

    // Comprueba la conexión a la base de datos
    try {
      SPDO::singleton();
      $statusItems["db"] = [
        "label" => "Base de datos",
        "value" => "Conectado",
        "icon"  => "database",
        "ok"    => true,
      ];
    } catch (Throwable $e) {
      $statusItems["db"] = [
        "label" => "Base de datos",
        "value" => "Sin conexión",
        "icon"  => "database",
        "ok"    => false,
      ];
    }

    // Software del servidor — solo el primer token (ej. "Apache/2.4.57")
    $serverSoftware = $_SERVER["SERVER_SOFTWARE"] ?? "Apache";
    $serverSoftware = explode(" ", $serverSoftware)[0];
    $statusItems["server"] = [
      "label" => "Servidor",
      "value" => $serverSoftware,
      "icon"  => "dns",
      "ok"    => true,
    ];

    // Uso de memoria PHP actual
    $mem = round(memory_get_usage() / 1024 / 1024, 1);
    $memLimit = ini_get("memory_limit");
    $statusItems["memory"] = [
      "label" => "Memoria PHP",
      "value" => $mem . " MB / " . $memLimit,
      "icon"  => "memory",
      "ok"    => true,
    ];

    // Hora actual del servidor
    $statusItems["time"] = [
      "label" => "Hora del servidor",
      "value" => date("d/m/Y H:i:s"),
      "icon"  => "schedule",
      "ok"    => true,
    ];

    $this->view->show("soporteView", ["statusItems" => $statusItems]);
  }

  public function enviarMensaje(): void {
    $this->verificarCsrf("index.php?controller=Soporte&action=index");

    $nombre  = trim($_POST["nombre"]  ?? "");
    $email   = trim($_POST["email"]   ?? "");
    $asunto  = trim($_POST["asunto"]  ?? "");
    $mensaje = trim($_POST["mensaje"] ?? "");

    $errors = [];
    if ($nombre === "")                               $errors[] = "El nombre es obligatorio.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = "El email no es válido.";
    if ($asunto === "")                               $errors[] = "El asunto es obligatorio.";
    if (strlen($mensaje) < 10)                        $errors[] = "El mensaje debe tener al menos 10 caracteres.";

    if (!empty($errors)) {
      $_SESSION["flash"] = ["type" => "error", "msg" => implode(" ", $errors)];
      header("Location: index.php?controller=Soporte&action=index");
      exit;
    }

    // Validar adjuntos
    $MAX_FILES    = 3;
    $MAX_PER_FILE = 5  * 1024 * 1024;   // 5 MB
    $MAX_TOTAL    = 10 * 1024 * 1024;   // 10 MB
    $ACCEPTED_EXT = '/\.(jpe?g|png|webp|gif|bmp|tiff?|svg|ico|heic|heif|avif|pdf|docx?|xlsx?|pptx?|odt|ods|odp|rtf|csv|md|txt|log|json|xml|html?|css|js|ts|php|py|java|cpp?|c|h|sh|bat|ps1|ya?ml|ini|conf|zip|rar|7z|tar|gz|bz2|xz)$/i';

    $adjuntos = [];
    $totalSize = 0;

    if (!empty($_FILES["adjuntos"]["name"][0])) {
      $count = count($_FILES["adjuntos"]["name"]);

      if ($count > $MAX_FILES) {
        $_SESSION["flash"] = ["type" => "error", "msg" => "Máximo {$MAX_FILES} archivos permitidos."];
        header("Location: index.php?controller=Soporte&action=index");
        exit;
      }

      for ($i = 0; $i < $count; $i++) {
        if ($_FILES["adjuntos"]["error"][$i] !== UPLOAD_ERR_OK) continue;

        $name = basename($_FILES["adjuntos"]["name"][$i]);
        $tmp  = $_FILES["adjuntos"]["tmp_name"][$i];
        $size = $_FILES["adjuntos"]["size"][$i];

        if (!preg_match($ACCEPTED_EXT, $name)) {
          $_SESSION["flash"] = ["type" => "error", "msg" => "\"$name\" — tipo de archivo no permitido."];
          header("Location: index.php?controller=Soporte&action=index");
          exit;
        }
        if ($size > $MAX_PER_FILE) {
          $_SESSION["flash"] = ["type" => "error", "msg" => "\"$name\" supera el límite de 5 MB."];
          header("Location: index.php?controller=Soporte&action=index");
          exit;
        }
        $totalSize += $size;
        if ($totalSize > $MAX_TOTAL) {
          $_SESSION["flash"] = ["type" => "error", "msg" => "El total de archivos supera 10 MB."];
          header("Location: index.php?controller=Soporte&action=index");
          exit;
        }

        $adjuntos[] = ["tmp_name" => $tmp, "name" => $name];
      }
    }

    try {
      Mailer::enviarSoporte($nombre, $email, $asunto, $mensaje, $adjuntos);
      $_SESSION["flash"] = ["type" => "ok", "msg" => "Mensaje enviado correctamente. Te responderemos lo antes posible."];
    } catch (Throwable $e) {
      error_log("[Mailer:Soporte] " . $e->getMessage());
      $_SESSION["flash"] = ["type" => "error", "msg" => "No se pudo enviar el mensaje. Inténtalo de nuevo más tarde."];
    }

    header("Location: index.php?controller=Soporte&action=index");
    exit;
  }
}

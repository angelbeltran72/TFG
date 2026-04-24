<?php
class FrontController {
  public function run() {
    $controller = $_GET["controller"] ?? "Auth";
    $action     = $_GET["action"] ?? "iniciarSesion";

    $controllerClass = $controller . "Controller";
    $controllerFile  = __DIR__ . "/../controllers/" . $controllerClass . ".php";

    if (!file_exists($controllerFile)) {
      http_response_code(404);
      die("Controller no encontrado: " . htmlspecialchars($controllerFile));
    }

    require_once $controllerFile;

    if (!class_exists($controllerClass)) {
      http_response_code(500);
      die("Clase no encontrada: " . htmlspecialchars($controllerClass));
    }

    $obj = new $controllerClass();

    if (!method_exists($obj, $action)) {
      http_response_code(404);
      die("Action no encontrada: " . htmlspecialchars($action));
    }

    $obj->$action();
  }
}

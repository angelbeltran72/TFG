<?php
class View {
  public function show($viewName, $data = []) {
    extract($data);
    require "views/{$viewName}.php";
  }
}

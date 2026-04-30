<?php
class AppController {
  protected $view;

  public function __construct() {
    $this->view = new View();
  }
}

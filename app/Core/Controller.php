<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller {
  protected function view(string $path, array $data=[]): void {
    extract($data);
    require __DIR__ . '/../Views/' . $path . '.php';
  }
  protected function redirect(string $to): void { header('Location: ' . $to); exit; }
  protected function json(array $data, int $code=200): void { http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); }
}

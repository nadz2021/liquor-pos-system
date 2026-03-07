<?php
declare(strict_types=1);

namespace App\Core;

final class Router {
  private array $routes = ['GET'=>[], 'POST'=>[]];

  public function get(string $path, string $handler): void { $this->routes['GET'][$path] = $handler; }
  public function post(string $path, string $handler): void { $this->routes['POST'][$path] = $handler; }

  public function dispatch(string $method, string $path): void {
    $handler = $this->routes[$method][$path] ?? null;
    if (!$handler) { http_response_code(404); echo "404 Not Found"; return; }

    [$controller, $action] = explode('@', $handler);
    $fqcn = "App\\Controllers\\{$controller}";
    $obj = new $fqcn();
    $obj->$action();
  }
}

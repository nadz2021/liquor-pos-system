<?php
declare(strict_types=1);

spl_autoload_register(function(string $class): void {
  $prefix = 'App\\';
  if (!str_starts_with($class, $prefix)) return;
  $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
  $file = __DIR__ . '/../' . $rel . '.php';
  if (file_exists($file)) require $file;
});

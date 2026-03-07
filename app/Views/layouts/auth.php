<!doctype html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'POS Login') ?></title>

  <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
  <div class="bg"></div>
  <?= $content ?? '' ?>

  <script src="/assets/js/auth.js"></script>
</body>
</html>
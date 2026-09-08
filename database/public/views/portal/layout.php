<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?> — Admin Portal</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
<div id="loading-bar" class="loading-bar"></div>
<div id="app"><!-- rendered entirely by admin.js --></div>
<div id="toast-container" class="toast-container"></div>

<script>
  window.APP_BASE = <?= json_encode($base) ?>;
  window.TENANT_SLUG = <?= json_encode($slug) ?>;
  window.TENANT_NAME = <?= json_encode($tenant['business_name']) ?>;
  window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;
</script>
<script src="<?= $base ?>/assets/js/api.js"></script>
<script src="<?= $base ?>/assets/js/admin.js"></script>
</body>
</html>

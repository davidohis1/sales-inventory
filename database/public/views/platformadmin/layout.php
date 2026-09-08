<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Platform Admin — Oripio</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
<div id="loading-bar" class="loading-bar"></div>
<div id="pa-app"><!-- rendered entirely by platformadmin.js --></div>
<div id="toast-container" class="toast-container"></div>

<script>
  window.APP_BASE = <?= json_encode($base) ?>;
</script>
<script src="<?= $base ?>/assets/js/platformadmin.js"></script>
</body>
</html>

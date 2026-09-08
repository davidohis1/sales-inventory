<?php $base = $GLOBALS['base'] ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unsubscribe — Bizflow</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="pub-body" style="min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f8f7fd;">
    <div class="card" style="max-width:420px; text-align:center; padding:40px 32px;">
        <?php if ($ok): ?>
            <div style="font-size:40px; margin-bottom:12px;">&#9989;</div>
            <h2 style="margin:0 0 10px;">You're unsubscribed</h2>
            <p class="text-muted">You won't receive marketing emails from this business anymore. Order and receipt emails aren't affected.</p>
        <?php else: ?>
            <div style="font-size:40px; margin-bottom:12px;">&#9888;&#65039;</div>
            <h2 style="margin:0 0 10px;">This link isn't valid</h2>
            <p class="text-muted">The unsubscribe link may be broken or expired. If you'd like to stop receiving emails, please contact the business directly.</p>
        <?php endif; ?>
    </div>
</body>
</html>

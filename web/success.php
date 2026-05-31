<?php
require_once __DIR__ . '/lib/portal.php';

$portal = new CaptivePortal();
$platform = $portal->detectPlatform();
$authorized = $portal->isAuthorized();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $authorized ? 'Connected' : 'Not connected' ?></title>
    <?php if ($authorized): ?>
    <meta http-equiv="refresh" content="2;url=/probe/generate_204">
    <?php endif; ?>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #ecfdf5;
            color: #065f46;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px;
        }

        .box {
            background: #fff;
            border-radius: 20px;
            padding: 32px 24px;
            max-width: 380px;
            box-shadow: 0 12px 30px rgba(6, 95, 70, 0.12);
        }

        .icon { font-size: 56px; margin-bottom: 12px; }
        h1 { margin: 0 0 8px; font-size: 24px; }
        p { margin: 0; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="box">
        <?php if ($authorized): ?>
        <div class="icon">✅</div>
        <h1>Connected Successfully</h1>
        <p>You are now connected to the network.<br>Enjoy Free Internet.</p>
        <?php else: ?>
        <div class="icon">📶</div>
        <h1>Not connected yet</h1>
        <p><a href="/">Go back and tap Close &amp; Connect</a></p>
        <?php endif; ?>
    </div>

    <?php if ($authorized): ?>
    <script>
        (function () {
            const platform = <?= json_encode($platform['platform']) ?>;
            const checks = {
                android: '/probe/generate_204',
                ios: '/probe/hotspot-detect.html',
                macos: '/probe/hotspot-detect.html',
                windows: '/probe/connecttest.txt',
                firefox: '/probe/success.txt'
            };

            const target = checks[platform] || '/probe/generate_204';

            setTimeout(function () {
                window.location.replace(target);
            }, 800);

            setTimeout(function () {
                window.close();
            }, 2500);
        })();
    </script>
    <?php endif; ?>
</body>
</html>

<?php
require_once __DIR__ . '/lib/portal.php';

$portal = new CaptivePortal();
$platform = $portal->detectPlatform();
$checkUrls = json_encode($portal->getSuccessCheckUrls($platform['platform']), JSON_UNESCAPED_SLASHES);
$alreadyAuthorized = $portal->isAuthorized();

if ($alreadyAuthorized) {
    header('Location: /success');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Sign in to network</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(160deg, #0f172a 0%, #1e3a8a 45%, #2563eb 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            text-align: center;
            padding: 36px 24px;
            max-width: 420px;
            width: 100%;
        }

        .card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 32px 24px;
            backdrop-filter: blur(8px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        }

        .logo {
            font-size: 72px;
            line-height: 1;
            margin-bottom: 16px;
        }

        h1 {
            font-size: 26px;
            margin: 0 0 8px;
            font-weight: 700;
        }

        .subtitle {
            font-size: 15px;
            opacity: 0.85;
            margin: 0 0 6px;
        }

        .by {
            font-size: 14px;
            margin: 0 0 24px;
            opacity: 0.9;
        }

        .platform {
            display: inline-block;
            font-size: 12px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 999px;
            padding: 6px 12px;
            margin-bottom: 22px;
        }

        .link {
            color: #dbeafe;
            text-decoration: none;
            font-size: 16px;
            display: inline-block;
            margin-bottom: 28px;
            word-break: break-all;
        }

        .link:hover { text-decoration: underline; }

        .close-btn {
            width: 100%;
            padding: 16px 24px;
            font-size: 18px;
            font-weight: 600;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            color: #fff;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.35);
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .close-btn:active { transform: scale(0.98); }
        .close-btn:disabled {
            opacity: 0.7;
            cursor: wait;
        }

        .status {
            margin-top: 18px;
            min-height: 22px;
            font-size: 14px;
            opacity: 0.9;
        }

        .status.error { color: #fecaca; }
        .status.ok { color: #bbf7d0; }

        .footer {
            margin-top: 18px;
            font-size: 12px;
            opacity: 0.65;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo" aria-hidden="true">📶</div>
            <h1>Free Internet</h1>
            <p class="subtitle">Sign in to this Wi-Fi network to continue</p>
            <p class="by"><strong>by MOHMMAD AL ABYAH</strong></p>
            <div class="platform"><?= htmlspecialchars($platform['label']) ?> detected</div>

            <a href="https://q5.qa" target="_blank" rel="noopener" class="link">
                🌐 Visit https://q5.qa
            </a>

            <button type="button" id="connectBtn" class="close-btn">
                Close &amp; Connect
            </button>

            <div id="status" class="status" role="status" aria-live="polite"></div>
            <p class="footer">Secure network authentication</p>
        </div>
    </div>

    <script>
        (function () {
            const platform = <?= json_encode($platform['platform']) ?>;
            const checkUrls = <?= $checkUrls ?>;
            const btn = document.getElementById('connectBtn');
            const status = document.getElementById('status');

            function setStatus(text, type) {
                status.textContent = text;
                status.className = 'status' + (type ? ' ' + type : '');
            }

            function probeAuthorized(url) {
                return fetch(url, {
                    method: 'GET',
                    cache: 'no-store',
                    credentials: 'same-origin'
                }).then(function (response) {
                    if (url.indexOf('generate_204') !== -1) {
                        return response.status === 204;
                    }
                    if (url.indexOf('connecttest.txt') !== -1) {
                        return response.status === 200;
                    }
                    if (url.indexOf('hotspot-detect.html') !== -1) {
                        return response.status === 200;
                    }
                    return response.ok;
                }).catch(function () {
                    return false;
                });
            }

            function runPlatformChecks() {
                return Promise.all(checkUrls.map(probeAuthorized)).then(function (results) {
                    return results.some(Boolean);
                });
            }

            function tryDismissWindow() {
                if (platform === 'android') {
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = '/probe/generate_204';
                    document.body.appendChild(iframe);
                }

                if (platform === 'ios' || platform === 'macos') {
                    window.location.href = '/probe/hotspot-detect.html';
                    return;
                }

                if (platform === 'windows') {
                    window.location.href = '/probe/connecttest.txt';
                    return;
                }

                window.location.replace('/success');
            }

            function closeCaptivePortal() {
                window.location.href = '/probe/generate_204';
                setTimeout(function () {
                    window.location.href = '/success';
                }, 400);
                setTimeout(function () {
                    window.close();
                }, 900);
            }

            btn.addEventListener('click', function () {
                btn.disabled = true;
                setStatus('Connecting...', '');

                fetch('/authorize', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin'
                })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Authorization failed');
                    }
                    return response.json();
                })
                .then(function () {
                    setStatus('Verifying connection...', '');

                    return runPlatformChecks().then(function (ok) {
                        if (!ok) {
                            throw new Error('Probe check failed');
                        }

                        setStatus('Connected successfully. Enjoy Free Internet', 'ok');
                        tryDismissWindow();
                        setTimeout(closeCaptivePortal, 500);
                    });
                })
                .catch(function () {
                    setStatus('Connection failed. Please try again.', 'error');
                    btn.disabled = false;
                });
            });
        })();
    </script>
</body>
</html>

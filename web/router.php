<?php

require_once __DIR__ . '/lib/portal.php';

$portal = new CaptivePortal();
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($uri === '/authorize' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $portal->authorizeClient();
    exit;
}

if (str_starts_with($uri, '/probe/')) {
    $probePath = substr($uri, strlen('/probe'));
    $_SERVER['REQUEST_URI'] = $probePath === '' ? '/generate_204' : $probePath;
    $probeResult = $portal->handleProbe();
    if ($probeResult === null) {
        $_SERVER['REQUEST_URI'] = '/generate_204';
        $probeResult = $portal->handleProbe();
    }
    if ($probeResult === false) {
        require __DIR__ . '/index.php';
    }
    exit;
}

$probeResult = $portal->handleProbe();
if ($probeResult === true) {
    exit;
}
if ($probeResult === false) {
    require __DIR__ . '/index.php';
    exit;
}

if ($uri === '/success') {
    require __DIR__ . '/success.php';
    exit;
}

require __DIR__ . '/index.php';

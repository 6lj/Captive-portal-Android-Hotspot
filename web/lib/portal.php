<?php

class CaptivePortal
{
    private string $dataDir;
    private string $authFile;

    /** @var array<string, array{body: string, code: int, type: string}> */
    private array $probes = [
        'generate_204' => [
            'code' => 204,
            'body' => '',
            'type' => 'text/plain',
        ],
        'hotspot-detect.html' => [
            'code' => 200,
            'body' => '<HTML><HEAD><TITLE>Success</TITLE></HEAD><BODY>Success</BODY></HTML>',
            'type' => 'text/html',
        ],
        'success.html' => [
            'code' => 200,
            'body' => '<HTML><HEAD><TITLE>Success</TITLE></HEAD><BODY>Success</BODY></HTML>',
            'type' => 'text/html',
        ],
        'connecttest.txt' => [
            'code' => 200,
            'body' => 'Microsoft Connect Test',
            'type' => 'text/plain',
        ],
        'ncsi.txt' => [
            'code' => 200,
            'body' => 'Microsoft NCSI.',
            'type' => 'text/plain',
        ],
        'success.txt' => [
            'code' => 200,
            'body' => 'success',
            'type' => 'text/plain',
        ],
        'check_network_status.txt' => [
            'code' => 200,
            'body' => 'NetworkManager is online',
            'type' => 'text/plain',
        ],
        'canonical.html' => [
            'code' => 200,
            'body' => '<HTML><HEAD><TITLE>success</TITLE></HEAD><BODY>success</BODY></HTML>',
            'type' => 'text/html',
        ],
        'kindle-wifi/wifiredirect.html' => [
            'code' => 200,
            'body' => '<HTML><HEAD><TITLE>Success</TITLE></HEAD><BODY>Success</BODY></HTML>',
            'type' => 'text/html',
        ],
    ];

    /** @var string[] */
    private array $probeHosts = [
        'connectivitycheck.gstatic.com',
        'clients3.google.com',
        'clients2.google.com',
        'clients4.google.com',
        'connectivitycheck.android.com',
        'android.clients.google.com',
        'www.google.com',
        'captive.apple.com',
        'www.apple.com',
        'www.appleiphonecell.com',
        'www.itools.info',
        'www.airport.us',
        'www.thinkdifferent.us',
        'www.ibook.info',
        'www.msftconnecttest.com',
        'msftconnecttest.com',
        'www.msftncsi.com',
        'msftncsi.com',
        'edge-http.microsoft.com',
        'detectportal.firefox.com',
        'nmcheck.gnome.org',
        'networkcheck.kde.org',
        'kindle.com',
        'www.gstatic.com',
    ];

    public function __construct()
    {
        $this->dataDir = dirname(__DIR__) . '/data';
        $this->authFile = $this->dataDir . '/authorized_ips.txt';

        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
        if (!file_exists($this->authFile)) {
            touch($this->authFile);
        }
    }

    public function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function isAuthorized(): bool
    {
        $ip = $this->getClientIp();
        $lines = file($this->authFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $authorized = array_map(static fn(string $line): string => trim($line), $lines);

        return in_array($ip, $authorized, true);
    }

    public function authorizeClient(): void
    {
        $ip = $this->getClientIp();
        $authorized = file($this->authFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        if (!in_array($ip, $authorized, true)) {
            file_put_contents($this->authFile, $ip . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'ip' => $ip,
            'message' => 'authorized',
        ]);
    }

    public function handleProbe(): ?bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $host = preg_replace('/:\d+$/', '', $host);

        if ($this->isGatewayHost($host) && !$this->isProbePath($uri)) {
            return null;
        }

        $isProbeHost = $this->isProbeHost($host);
        $probeKey = $this->matchProbePath($uri);

        if (!$isProbeHost && $probeKey === null) {
            return null;
        }

        if ($this->isAuthorized()) {
            $this->sendAuthorizedProbeResponse($probeKey, $uri);
            return true;
        }

        return false;
    }

    private function isGatewayHost(string $host): bool
    {
        if ($host === '') {
            return false;
        }

        $gatewayIp = $this->getGatewayIp();

        return $host === $gatewayIp || $host === 'localhost' || $host === '127.0.0.1';
    }

    private function isProbePath(string $uri): bool
    {
        return $this->matchProbePath($uri) !== null;
    }

    private function isProbeHost(string $host): bool
    {
        if ($host === '') {
            return false;
        }

        foreach ($this->probeHosts as $probeHost) {
            if ($host === $probeHost || str_ends_with($host, '.' . $probeHost)) {
                return true;
            }
        }

        return false;
    }

    private function matchProbePath(string $uri): ?string
    {
        $path = trim($uri, '/');

        foreach ($this->probes as $key => $_config) {
            if ($path === $key || str_ends_with($path, $key)) {
                return $key;
            }
        }

        return null;
    }

    private function sendAuthorizedProbeResponse(?string $probeKey, string $uri): void
    {
        if ($probeKey === null) {
            $probeKey = $this->matchProbePath($uri) ?? 'generate_204';
        }

        $config = $this->probes[$probeKey] ?? $this->probes['generate_204'];

        http_response_code($config['code']);
        header('Content-Type: ' . $config['type']);
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Connection: close');

        if ($config['code'] !== 204) {
            header('Content-Length: ' . strlen($config['body']));
            echo $config['body'];
        }
    }

    public function getGatewayIp(): string
    {
        $gatewayFile = $this->dataDir . '/gateway_ip.txt';
        if (is_readable($gatewayFile)) {
            $ip = trim((string) file_get_contents($gatewayFile));
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }

        $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
        if ($serverAddr !== '' && $serverAddr !== '0.0.0.0' && filter_var($serverAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $serverAddr;
        }

        $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
        if ($host !== '' && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $host;
        }

        return '127.0.0.1';
    }

    public function getPortalUrl(): string
    {
        return 'http://' . $this->getGatewayIp() . '/';
    }

    /** @return array{platform: string, label: string} */
    public function detectPlatform(): array
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod')) {
            return ['platform' => 'ios', 'label' => 'iOS'];
        }
        if (str_contains($ua, 'mac os') || str_contains($ua, 'macintosh')) {
            return ['platform' => 'macos', 'label' => 'macOS'];
        }
        if (str_contains($ua, 'android')) {
            return ['platform' => 'android', 'label' => 'Android'];
        }
        if (str_contains($ua, 'windows')) {
            return ['platform' => 'windows', 'label' => 'Windows'];
        }
        if (str_contains($ua, 'firefox')) {
            return ['platform' => 'firefox', 'label' => 'Firefox'];
        }
        if (str_contains($ua, 'kindle') || str_contains($ua, 'silk')) {
            return ['platform' => 'kindle', 'label' => 'Kindle'];
        }

        return ['platform' => 'generic', 'label' => 'Wi-Fi'];
    }

    /** @return string[] */
    public function getSuccessCheckUrls(string $platform): array
    {
        $common = [
            '/probe/generate_204',
            '/probe/hotspot-detect.html',
            '/probe/connecttest.txt',
            '/probe/success.txt',
        ];

        $byPlatform = [
            'android' => [
                '/probe/generate_204',
            ],
            'ios' => [
                '/probe/hotspot-detect.html',
            ],
            'macos' => [
                '/probe/hotspot-detect.html',
            ],
            'windows' => [
                '/probe/connecttest.txt',
                '/probe/ncsi.txt',
            ],
            'firefox' => [
                '/probe/success.txt',
                '/probe/canonical.html',
            ],
        ];

        return $byPlatform[$platform] ?? $common;
    }
}

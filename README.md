

Captive portal for Android Hotspot — supports Android, iOS, macOS, Windows, and Firefox.


### Requirements

* Android phone with hotspot
* Termux
* Root — required for redirecting HTTP traffic with iptables
* PHP

### Setup

Install packages:

```bash
pkg install php tsu
```

Turn on hotspot:

* Set security to **None** (open network)
* Rename hotspot as desired

Apply redirect rules as root (IP is detected automatically from the hotspot interface):

```bash
cd Captive-portal-Android-Hotspot
sudo ./redirect.sh
```

If auto-detection fails on your device, set the IP manually:

```bash
GATEWAY_IP=10.42.0.1 sudo ./redirect.sh
```

Start the web server (must use `router.php`):

```bash
cd web
php -S 0.0.0.0:8080 router.php
```

When finished, remove iptables rules:

```bash
sudo ./cleanup.sh
```

### How it works

1. Hotspot clients are redirected to the PHP server on port 8080.
2. The OS runs a connectivity check (Android `generate_204`, Apple `hotspot-detect.html`, Windows `connecttest.txt`, etc.).
3. Unauthenticated clients see the portal page (`index.php`).
4. After clicking **Close & Connect**, the client IP is authorized.
5. Connectivity checks return the expected success response (204 / Success / Microsoft Connect Test).
6. The OS closes the captive portal automatically.

### Supported platforms

| Platform | Detection URL | Expected response |
|----------|---------------|-------------------|
| Android | `/generate_204` | HTTP 204 |
| iOS / macOS | `/hotspot-detect.html` | HTML with "Success" |
| Windows 10+ | `/connecttest.txt` | `Microsoft Connect Test` |
| Windows (legacy) | `/ncsi.txt` | `Microsoft NCSI.` |
| Firefox | `/success.txt` | `success` |
| Linux (GNOME) | `/check_network_status.txt` | `NetworkManager is online` |

### Notes

* Tested on Sony Xperia XZ1 Compact LineageOS 17.1 with Magisk
* Hotspot may not show "Tap here to sign in to network" without internet; enabling mobile data (even without a data plan) can help trigger detection
* Authorized client IPs are stored in `web/data/authorized_ips.txt`
* Restart the PHP server to clear all authorized clients
* Clean up redirect.sh rules automatically on exit
* HTTPS (port 443) interception for stricter clients


### by Abyah or ENDUP

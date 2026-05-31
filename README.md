

Captive portal for Android Hotspot, supports Android, iOS, macOS, Windows, and Firefox.


### Requirements

* Android phone with hotspot
* Termux
* Root — required for redirecting HTTP traffic with iptables
* PHP

### Setup (Termux)
in Termux 
Install packages:

```bash
pkg install php tsu
```

Copy the project to Termux home — NOT `/sdcard/Download`

Turn on hotspot:

* Set security to **None** (open network)
* Rename hotspot as desired

Apply redirect rules as root:

```bash
tsu
sh redirect.sh
exit
```

If auto-detection fails, set the IP manually:

```bash
tsu
GATEWAY_IP=10.42.0.1 sh redirect.sh
exit
```

Start the web server (must use `router.php`):

```bash
cd ~/Captive-portal-Android-Hotspot/web
php -S 0.0.0.0:8080 router.php
```
![Screenshot 1](https://i.ibb.co/PG0834NF/Screenshot-20260531-063900.png)


If you want to close it:

```bash
tsu
sh cleanup.sh
exit
```

### Termux: Permission denied fix

| Error | Cause | Fix |
|-------|-------|-----|
| `bash: ./redirect.sh: Permission denied` | No execute bit or noexec mount | Use `sh redirect.sh` |
| `env: exec ./redirect.sh: Permission denied` | Cannot exec from sdcard | Move project to `~/` and use `sh redirect.sh` |
| `sudo: command not found` | Termux has no sudo | Use `tsu` then `sh redirect.sh` |

**Do not use** `./redirect.sh` or `sudo ./redirect.sh` in Termux.

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

* Hotspot may not show "Tap here to sign in to network" without internet; enabling mobile data can help trigger detection
* Authorized client IPs are stored in `web/data/authorized_ips.txt`
* Restart the PHP server to clear all authorized clients

### by Abyah or ENDUP

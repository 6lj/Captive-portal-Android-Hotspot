#!/system/bin/sh

# Captive portal redirect rules for Android hotspot (Termux + root)
# Redirects all HTTP traffic from hotspot clients to the local PHP server.

PORT="${PORT:-8080}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
GATEWAY_FILE="$SCRIPT_DIR/web/data/gateway_ip.txt"
IFACE=""

detect_iface() {
    for candidate in ap0 ap1 wlan0 swlan0 softap0 rndis0; do
        if ip link show "$candidate" >/dev/null 2>&1; then
            echo "$candidate"
            return 0
        fi
    done

    ip route 2>/dev/null | awk '/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\/[0-9]+ dev / {print $3; exit}'
}

detect_gateway_ip() {
    local iface="$1"
    local ip=""

    if [ -n "$iface" ]; then
        ip="$(ip -4 addr show "$iface" 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 | head -n1)"
        if [ -n "$ip" ]; then
            echo "$ip"
            return 0
        fi
    fi

    for candidate in ap0 ap1 wlan0 swlan0 softap0 rndis0; do
        ip="$(ip -4 addr show "$candidate" 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 | head -n1)"
        if [ -n "$ip" ]; then
            echo "$ip"
            return 0
        fi
    done

    ip="$(ip -4 addr show 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 | grep -Ev '^(127\.|169\.254\.)' | head -n1)"
    if [ -n "$ip" ]; then
        echo "$ip"
        return 0
    fi

    ip="$(hostname -I 2>/dev/null | awk '{print $1}')"
    if [ -n "$ip" ]; then
        echo "$ip"
        return 0
    fi

    return 1
}

IFACE="$(detect_iface)"

if [ -z "$GATEWAY_IP" ]; then
    GATEWAY_IP="$(detect_gateway_ip "$IFACE")"
fi

if [ -z "$GATEWAY_IP" ]; then
    echo "Error: Could not detect hotspot IP automatically." >&2
    echo "Set it manually, for example:" >&2
    echo "  GATEWAY_IP=10.42.0.1 sudo ./redirect.sh" >&2
    exit 1
fi

mkdir -p "$(dirname "$GATEWAY_FILE")"
printf '%s\n' "$GATEWAY_IP" > "$GATEWAY_FILE"

echo "Interface: ${IFACE:-auto}"
echo "Gateway IP: $GATEWAY_IP"
echo "PHP server port: $PORT"
echo "Saved to: $GATEWAY_FILE"

# Allow DNS so clients can resolve names (iptables still intercepts HTTP)
iptables -A FORWARD -p udp --dport 53 -j ACCEPT 2>/dev/null
iptables -A FORWARD -p udp --sport 53 -j ACCEPT 2>/dev/null
iptables -A FORWARD -p tcp --dport 53 -j ACCEPT 2>/dev/null
iptables -A FORWARD -p tcp --sport 53 -j ACCEPT 2>/dev/null

# Redirect HTTP from hotspot clients to this device
if [ -n "$IFACE" ]; then
    iptables -t nat -A PREROUTING -i "$IFACE" -p tcp --dport 80 -j DNAT --to-destination "$GATEWAY_IP:$PORT" 2>/dev/null
fi
iptables -t nat -A PREROUTING -p tcp --dport 80 -j REDIRECT --to-port "$PORT" 2>/dev/null

# Allow forwarded traffic back to clients
if [ -n "$IFACE" ]; then
    iptables -A FORWARD -i "$IFACE" -p tcp --dport "$PORT" -j ACCEPT 2>/dev/null
    iptables -A FORWARD -o "$IFACE" -p tcp --sport "$PORT" -j ACCEPT 2>/dev/null
fi
iptables -A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT 2>/dev/null

echo "Redirect rules applied."
echo "Run: cd web && php -S 0.0.0.0:$PORT router.php"

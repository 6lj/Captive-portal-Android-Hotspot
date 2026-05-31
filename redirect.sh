#!/system/bin/sh
#
# Termux: sh redirect.sh   (NOT ./redirect.sh)

PORT="${PORT:-8080}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
GATEWAY_FILE="$SCRIPT_DIR/web/data/gateway_ip.txt"

if [ -z "$GATEWAY_IP" ]; then
    for candidate in ap0 ap1 wlan0 swlan0 softap0 rndis0; do
        GATEWAY_IP="$(ip -4 addr show "$candidate" 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 | head -n1)"
        [ -n "$GATEWAY_IP" ] && break
    done
fi

if [ -z "$GATEWAY_IP" ]; then
    GATEWAY_IP="$(ip -4 addr show 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 | grep -Ev '^(127\.|169\.254\.)' | head -n1)"
fi

if [ -z "$GATEWAY_IP" ]; then
    echo "Error: Could not detect IP. Set manually:" >&2
    echo "  GATEWAY_IP=192.168.43.1 sh redirect.sh" >&2
    exit 1
fi

mkdir -p "$(dirname "$GATEWAY_FILE")"
printf '%s\n' "$GATEWAY_IP" > "$GATEWAY_FILE"

echo "Gateway IP: $GATEWAY_IP"
echo "Port: $PORT"

iptables -t nat -A PREROUTING -p tcp --dport 80 -j REDIRECT --to-port "$PORT"
iptables -A FORWARD -p udp --dport 53 -j ACCEPT
iptables -A FORWARD -p udp --sport 53 -j ACCEPT
iptables -t nat -A PREROUTING -p tcp --dport 80 -j DNAT --to-destination "$GATEWAY_IP"
iptables -P FORWARD DROP

echo "Redirect rules applied."
echo "Run: cd web && php -S 0.0.0.0:$PORT router.php"

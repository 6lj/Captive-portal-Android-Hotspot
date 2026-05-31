#!/system/bin/sh
#
# Termux: sh cleanup.sh

PORT="${PORT:-8080}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
GATEWAY_FILE="$SCRIPT_DIR/web/data/gateway_ip.txt"

if [ -f "$GATEWAY_FILE" ]; then
    GATEWAY_IP="$(tr -d '\n\r' < "$GATEWAY_FILE")"
fi

echo "Removing captive portal iptables rules..."

iptables -P FORWARD ACCEPT 2>/dev/null

if [ -n "$GATEWAY_IP" ]; then
    iptables -t nat -D PREROUTING -p tcp --dport 80 -j DNAT --to-destination "$GATEWAY_IP" 2>/dev/null
fi

iptables -D FORWARD -p udp --sport 53 -j ACCEPT 2>/dev/null
iptables -D FORWARD -p udp --dport 53 -j ACCEPT 2>/dev/null
iptables -t nat -D PREROUTING -p tcp --dport 80 -j REDIRECT --to-port "$PORT" 2>/dev/null

echo "Cleanup complete."

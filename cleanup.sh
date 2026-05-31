#!/system/bin/sh

PORT="${PORT:-8080}"

echo "Removing captive portal iptables rules..."

iptables -t nat -D PREROUTING -p tcp --dport 80 -j REDIRECT --to-port "$PORT" 2>/dev/null

for candidate in ap0 wlan0 swlan0 softap0; do
    if ip link show "$candidate" >/dev/null 2>&1; then
        GATEWAY_IP="$(ip -4 addr show "$candidate" 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 | head -n1)"
        if [ -n "$GATEWAY_IP" ]; then
            iptables -t nat -D PREROUTING -i "$candidate" -p tcp --dport 80 -j DNAT --to-destination "$GATEWAY_IP:$PORT" 2>/dev/null
        fi
    fi
done

echo "Cleanup complete."

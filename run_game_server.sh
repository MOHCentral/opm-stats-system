#!/bin/bash
# ==============================================================================
# OPM Stats - Local Dev Game Server Launcher
# Starts the API stack, registers the server, then launches omohaaded-dbg
# pointing at the symlinked script repo for live .scr development.
#
# Usage: ./run_game_server.sh [map]
#   map  - optional map name (default: dm/mohdm1)
# ==============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
OPENMOHAA_DIR="/run/media/elgan/evo/dev/openmohaa-central"
SERVER_BIN="$OPENMOHAA_DIR/build/Debug/omohaaded-dbg"
GAME_DATA_DIR="/run/media/elgan/evo/.Trash-1000/files"   # pak files location
SCRIPTS_HOME="$OPENMOHAA_DIR"                            # contains main/global -> symlink
API_DIR="$SCRIPT_DIR/opm-stats-api"
API_BASE="http://localhost:8084"
MAP="${1:-dm/mohdm1}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# ------------------------------------------------------------------------------
step() { echo -e "\n${CYAN}▶ $1${NC}"; }
ok()   { echo -e "${GREEN}✓ $1${NC}"; }
fail() { echo -e "${RED}✗ $1${NC}"; exit 1; }
# ------------------------------------------------------------------------------

# 1. Check binary
step "Checking game server binary"
[ -f "$SERVER_BIN" ] || fail "omohaaded-dbg not found at $SERVER_BIN. Run: cd $OPENMOHAA_DIR/build && cmake --build . -- -j\$(nproc)"
ok "Binary found: $SERVER_BIN"

# 2. Check symlink
step "Checking script symlink"
[ -L "$SCRIPTS_HOME/main/global" ] || fail "Symlink missing: $SCRIPTS_HOME/main/global. Run: ln -s $SCRIPT_DIR/opm-stats-game-scripts/global $SCRIPTS_HOME/main/global"
ok "Symlink: $SCRIPTS_HOME/main/global -> $(readlink "$SCRIPTS_HOME/main/global")"

# 3. Check pak files
step "Checking game data"
[ -f "$GAME_DATA_DIR/main/Pak0.pk3" ] || fail "Pak0.pk3 not found at $GAME_DATA_DIR/main/. Set GAME_DATA_DIR in this script."
ok "Game data found at $GAME_DATA_DIR"

# 4. Start API stack (Docker)
step "Starting API stack"
cd "$API_DIR"
if curl -sf "$API_BASE/health" > /dev/null 2>&1; then
    ok "API already running"
else
    docker compose up -d opm-stats-postgres opm-stats-clickhouse opm-stats-redis opm-stats-mosquitto
    echo -n "Waiting for API..."
    for i in $(seq 1 30); do
        sleep 1
        if curl -sf "$API_BASE/health" > /dev/null 2>&1; then break; fi
        echo -n "."
    done
    echo ""
    # Build and start API in background
    go build -o bin/api ./cmd/api/ && ./bin/api &
    API_PID=$!
    echo "API PID: $API_PID"
    sleep 2
    curl -sf "$API_BASE/health" > /dev/null || fail "API failed to start"
    ok "API started (PID $API_PID)"
fi

# 5. Register server
step "Registering game server with API"
RESPONSE=$(curl -s -X POST "$API_BASE/api/v1/servers/register" \
    -H "Content-Type: application/json" \
    -d '{"name":"Dev Server","ip_address":"127.0.0.1","port":12203}')

SERVER_ID=$(echo "$RESPONSE" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('server_id',''))" 2>/dev/null)
SERVER_TOKEN=$(echo "$RESPONSE" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('token',''))" 2>/dev/null)

if [ -z "$SERVER_ID" ] || [ -z "$SERVER_TOKEN" ]; then
    echo -e "${YELLOW}Warning: Could not register server (API may not have /servers/register yet)${NC}"
    echo "Response: $RESPONSE"
    SERVER_ID="dev-server-001"
    SERVER_TOKEN="mohaa-test-token"
fi
ok "Server ID:    $SERVER_ID"
ok "Server Token: $SERVER_TOKEN"

# 6. Launch game server
step "Launching OpenMOHAA dedicated server"
echo -e "  Map:        ${YELLOW}$MAP${NC}"
echo -e "  Basepath:   ${YELLOW}$GAME_DATA_DIR${NC}"
echo -e "  Homepath:   ${YELLOW}$SCRIPTS_HOME${NC}"
echo -e "  Scripts:    ${YELLOW}$SCRIPTS_HOME/main/global/${NC}"
echo ""

cd "$OPENMOHAA_DIR/build/Debug"

exec "$SERVER_BIN" \
    +set fs_basepath "$GAME_DATA_DIR" \
    +set fs_homepath "$SCRIPTS_HOME" \
    +set dedicated 2 \
    +set net_port 12203 \
    +set developer 1 \
    +set logfile 3 \
    +set g_logsync 1 \
    +set opm_server_id "$SERVER_ID" \
    +set opm_server_token "$SERVER_TOKEN" \
    +set opm_mqtt_host "127.0.0.1" \
    +set opm_mqtt_port 1883 \
    +set opm_mqtt_username "mohaa-server" \
    +set opm_mqtt_password "mohaa-server-secret" \
    +set g_gametype 1 \
    +set timelimit 20 \
    +set fraglimit 50 \
    +set sv_maxclients 16 \
    +set sv_hostname "OPM Dev Server" \
    +map "$MAP"

#!/usr/bin/env bash
# ==============================================================================
# OPM Stats System — Unified Dev CLI
# Usage: ./dev.sh <command> [args...]
# ==============================================================================

set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
API_DIR="$ROOT/opm-stats-api"
SMF_DIR="$ROOT/opm-stats-smf-integration"
OPENMOHAA_DIR="/run/media/elgan/evo/dev/openmohaa-central"
API_BASE="http://localhost:8084"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

ok()   { echo -e "${GREEN}✓${NC} $*"; }
warn() { echo -e "${YELLOW}!${NC} $*"; }
fail() { echo -e "${RED}✗${NC} $*"; exit 1; }
info() { echo -e "${CYAN}▶${NC} $*"; }
hdr()  { echo -e "\n${BOLD}${CYAN}$*${NC}"; }

api_healthy() { curl -sf "$API_BASE/health" > /dev/null 2>&1; }

# ------------------------------------------------------------------------------
# up — Start all infrastructure (Docker) + check SMF services
# ------------------------------------------------------------------------------
cmd_up() {
    local mode="${1:-all}"
    hdr "Starting OPM Stats System"
    cd "$API_DIR"

    case "$mode" in
        infra)
            info "Starting infrastructure only (no API container)"
            docker compose up -d opm-stats-postgres opm-stats-clickhouse opm-stats-redis opm-stats-mosquitto
            ;;
        all|"")
            info "Starting full stack (infra + API container)"
            docker compose up -d
            ;;
        *)
            fail "Unknown mode '$mode'. Use: up [all|infra]"
            ;;
    esac

    echo ""
    info "Checking SMF services..."
    _check_smf_services

    echo ""
    _wait_api 20
    _print_urls
}

# ------------------------------------------------------------------------------
# down — Stop all Docker services
# ------------------------------------------------------------------------------
cmd_down() {
    hdr "Stopping OPM Stats System"
    cd "$API_DIR"
    docker compose down
    ok "Docker services stopped"
    warn "Apache2/MariaDB not stopped (system services — run: sudo systemctl stop apache2 mariadb)"
}

# ------------------------------------------------------------------------------
# restart — Down then up
# ------------------------------------------------------------------------------
cmd_restart() {
    cmd_down
    cmd_up "${1:-all}"
}

# ------------------------------------------------------------------------------
# status — Show running containers and service health
# ------------------------------------------------------------------------------
cmd_status() {
    hdr "OPM Stats System Status"
    cd "$API_DIR"

    echo ""
    info "Docker containers:"
    docker compose ps 2>/dev/null || warn "docker compose ps failed"

    echo ""
    info "Service health:"
    _check_url "$API_BASE/health"       "Stats API (8084)"
    _check_url "http://localhost:8123"  "ClickHouse (8123)"
    _check_port 5432                    "PostgreSQL (5432)"
    _check_port 6380                    "Redis (6380)"
    _check_port 1883                    "Mosquitto MQTT (1883)"
    _check_url "http://localhost:8888"  "SMF Forum (8888)"

    echo ""
    info "Apache2:  $(systemctl is-active apache2 2>/dev/null || echo 'unknown')"
    info "MariaDB:  $(systemctl is-active mariadb 2>/dev/null || echo 'unknown')"
}

# ------------------------------------------------------------------------------
# logs — Tail logs for a service
# ------------------------------------------------------------------------------
cmd_logs() {
    local svc="${1:-opm-stats-api}"
    cd "$API_DIR"
    info "Tailing logs for $svc (Ctrl+C to stop)"
    docker compose logs -f "$svc"
}

# ------------------------------------------------------------------------------
# build — Build Go API binary
# ------------------------------------------------------------------------------
cmd_build() {
    hdr "Building API"
    cd "$API_DIR"
    go build -o bin/api ./cmd/api/
    ok "Built: $API_DIR/bin/api"
}

# ------------------------------------------------------------------------------
# run — Build + run API locally (infra must be up)
# ------------------------------------------------------------------------------
cmd_run() {
    hdr "Running API locally"
    cd "$API_DIR"

    if ! docker compose ps opm-stats-postgres | grep -q "healthy\|Up"; then
        warn "Infrastructure not running. Starting it..."
        docker compose up -d opm-stats-postgres opm-stats-clickhouse opm-stats-redis opm-stats-mosquitto
        sleep 3
    fi

    go build -o bin/api ./cmd/api/ && ./bin/api
}

# ------------------------------------------------------------------------------
# rebuild — Rebuild and restart the API Docker container
# ------------------------------------------------------------------------------
cmd_rebuild() {
    hdr "Rebuilding API container"
    cd "$API_DIR"
    docker compose build opm-stats-api
    docker compose up -d opm-stats-api
    ok "API container rebuilt and restarted"
}

# ------------------------------------------------------------------------------
# install — Run system install (DB migrations via API)
# ------------------------------------------------------------------------------
cmd_install() {
    local token="${1:-mohaa-test-token}"
    hdr "Running system install"
    _wait_api 30 || fail "API not reachable at $API_BASE"
    RESPONSE=$(curl -sf -X POST "$API_BASE/api/v1/system/install" \
        -H "X-Server-Token: $token" \
        -H "Content-Type: application/json" || true)
    echo "$RESPONSE"
    ok "Install request sent"
}

# ------------------------------------------------------------------------------
# register — Register a game server with the API
# ------------------------------------------------------------------------------
cmd_register() {
    local name="${1:-Dev Server}"
    local ip="${2:-127.0.0.1}"
    local port="${3:-12203}"
    hdr "Registering game server"
    bash "$API_DIR/register_server.sh" "$name" "$ip" "$port"
}

# ------------------------------------------------------------------------------
# seed — Seed test data
# ------------------------------------------------------------------------------
cmd_seed() {
    local mode="${1:-basic}"
    hdr "Seeding test data ($mode)"
    cd "$API_DIR"
    case "$mode" in
        basic)  bash tools/seed_test_data.sh ;;
        elgan)  bash tools/seed_elgan.sh ;;
        full)   bash tools/comprehensive_seed.sh ;;
        go)     go run ./cmd/seeder/ --count="${2:-1000}" ;;
        *)      fail "Unknown seed mode '$mode'. Use: seed [basic|elgan|full|go]" ;;
    esac
}

# ------------------------------------------------------------------------------
# reload-mvs — Drop and recreate ClickHouse materialized views
# ------------------------------------------------------------------------------
cmd_reload_mvs() {
    hdr "Reloading ClickHouse materialized views"
    bash "$API_DIR/reload_mvs.sh"
}

# ------------------------------------------------------------------------------
# test — Run tests
# ------------------------------------------------------------------------------
cmd_test() {
    local suite="${1:-unit}"
    hdr "Running tests ($suite)"
    cd "$API_DIR"
    case "$suite" in
        unit)        go test ./... ;;
        integration) go test ./tests/ -v -tags=integration ;;
        e2e)         bash tests/run_e2e.sh ;;
        bruno)       bash tools/test_bruno.sh Local ;;
        endpoints)   bash tools/test_all_endpoints.sh ;;
        docker)      bash tests/docker_health_test.sh ;;
        login)       bash tools/test_login.sh ;;
        all)
            go test ./...
            bash tools/test_bruno.sh Local || warn "Bruno tests failed"
            ;;
        *)           fail "Unknown suite '$suite'. Use: test [unit|integration|e2e|bruno|endpoints|docker|login|all]" ;;
    esac
}

# ------------------------------------------------------------------------------
# audit — Run audit scripts
# ------------------------------------------------------------------------------
cmd_audit() {
    local scope="${1:-endpoints}"
    hdr "Auditing ($scope)"
    cd "$API_DIR"
    case "$scope" in
        endpoints) bash tools/audit_all_endpoints.sh ;;
        smf)       bash tools/audit_smf_pages.sh ;;
        full)      bash tools/comprehensive_audit.sh ;;
        stats)     bash tools/diagnose_stats.sh ;;
        *)         fail "Unknown scope '$scope'. Use: audit [endpoints|smf|full|stats]" ;;
    esac
}

# ------------------------------------------------------------------------------
# smf — SMF integration management
# ------------------------------------------------------------------------------
cmd_smf() {
    local action="${1:-status}"
    hdr "SMF Integration ($action)"
    case "$action" in
        install) bash "$SMF_DIR/install_to_container.sh" ;;
        verify)  bash "$SMF_DIR/verify_smf_setup.sh" ;;
        sql)     shift; bash "$SMF_DIR/run_sql.sh" "$@" ;;
        status)  _check_url "http://localhost:8888" "SMF Forum" ;;
        *)       fail "Unknown action '$action'. Use: smf [install|verify|sql|status]" ;;
    esac
}

# ------------------------------------------------------------------------------
# game — Launch OpenMOHAA game server (starts infra first)
# ------------------------------------------------------------------------------
cmd_game() {
    hdr "Launching game server"
    bash "$ROOT/run_game_server.sh" "${1:-dm/mohdm1}"
}

# ------------------------------------------------------------------------------
# mqtt — MQTT utilities
# ------------------------------------------------------------------------------
cmd_mqtt() {
    local action="${1:-listen}"
    case "$action" in
        listen)
            local topic="${2:-openmohaa/#}"
            info "Subscribing to $topic (Ctrl+C to stop)"
            mosquitto_sub -h localhost -p 1883 -t "$topic" -v
            ;;
        pub)
            local topic="${2:-openmohaa/events/test}"
            local msg="${3:-{\"type\":\"test\"}}"
            mosquitto_pub -h localhost -p 1883 -t "$topic" -m "$msg"
            ok "Published to $topic"
            ;;
        *)
            fail "Unknown action '$action'. Use: mqtt [listen [topic]|pub <topic> <msg>]"
            ;;
    esac
}

# ------------------------------------------------------------------------------
# simulate — Send synthetic game events to the API
# ------------------------------------------------------------------------------
cmd_simulate() {
    hdr "Simulating game events"
    cd "$API_DIR"
    bash tools/simulate_events.sh "$@"
}

# ------------------------------------------------------------------------------
# generate — Code generation
# ------------------------------------------------------------------------------
cmd_generate() {
    local what="${1:-types}"
    hdr "Generating ($what)"
    case "$what" in
        types)  cd "$API_DIR" && make generate-types ;;
        docs)   cd "$API_DIR" && make docs ;;
        bruno)  cd "$API_DIR" && make bruno ;;
        all)    cd "$API_DIR" && make generate-types docs bruno ;;
        *)      fail "Unknown target '$what'. Use: generate [types|docs|bruno|all]" ;;
    esac
}

# ------------------------------------------------------------------------------
# reset — Nuclear option: destroy all volumes and restart
# ------------------------------------------------------------------------------
cmd_reset() {
    warn "This will DESTROY all database volumes (postgres, clickhouse, redis, mosquitto)."
    read -r -p "Type 'yes' to confirm: " confirm
    [[ "$confirm" == "yes" ]] || { info "Aborted."; exit 0; }
    cd "$API_DIR"
    docker compose down -v
    docker compose up -d
    ok "All services restarted with clean state"
}

# ==============================================================================
# Internal helpers
# ==============================================================================

_check_smf_services() {
    for svc in apache2 mariadb; do
        if systemctl is-active --quiet "$svc" 2>/dev/null; then
            ok "$svc is running"
        else
            warn "$svc not running — start with: sudo systemctl start $svc"
        fi
    done
}

_wait_api() {
    local max="${1:-20}"
    echo -n "  Waiting for API"
    for _ in $(seq 1 "$max"); do
        if api_healthy; then echo ""; ok "API is up"; return 0; fi
        echo -n "."; sleep 1
    done
    echo ""
    return 1
}

_check_url() {
    local url="$1" label="$2"
    local code
    code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 2 "$url" 2>/dev/null || echo "000")
    if [[ "$code" =~ ^(200|301|302)$ ]]; then
        ok "$label — HTTP $code"
    else
        warn "$label — HTTP $code (not reachable)"
    fi
}

_check_port() {
    local port="$1" label="$2"
    if nc -z localhost "$port" 2>/dev/null; then
        ok "$label"
    else
        warn "$label — not listening"
    fi
}

_print_urls() {
    echo ""
    echo -e "  ${BOLD}Stats API:${NC}    $API_BASE"
    echo -e "  ${BOLD}API Health:${NC}   $API_BASE/health"
    echo -e "  ${BOLD}Swagger UI:${NC}   $API_BASE/swagger/index.html"
    echo -e "  ${BOLD}SMF Forum:${NC}    http://localhost:8888"
    echo -e "  ${BOLD}ClickHouse:${NC}   http://localhost:8123"
    echo ""
}

# ==============================================================================
# Help
# ==============================================================================

cmd_help() {
    echo -e "${BOLD}OPM Stats Dev CLI${NC}"
    echo ""
    echo -e "${CYAN}Infrastructure:${NC}"
    echo "  up [all|infra]          Start all services (default: all)"
    echo "  down                    Stop Docker services"
    echo "  restart [all|infra]     Restart services"
    echo "  status                  Show service health"
    echo "  logs [service]          Tail service logs (default: opm-stats-api)"
    echo "  reset                   DESTROY volumes and restart (destructive)"
    echo ""
    echo -e "${CYAN}API Development:${NC}"
    echo "  build                   Build Go API binary"
    echo "  run                     Build + run API locally"
    echo "  rebuild                 Rebuild + restart API Docker container"
    echo "  install [token]         Run system install (DB migrations)"
    echo ""
    echo -e "${CYAN}Data & Setup:${NC}"
    echo "  register [name] [ip] [port]   Register a game server"
    echo "  seed [basic|elgan|full|go]    Seed test data"
    echo "  reload-mvs              Reload ClickHouse materialized views"
    echo ""
    echo -e "${CYAN}Testing & Audit:${NC}"
    echo "  test [unit|integration|e2e|bruno|endpoints|docker|login|all]"
    echo "  simulate                    Send synthetic game events to the API"
    echo "  audit [endpoints|smf|full|stats]"
    echo ""
    echo -e "${CYAN}SMF Integration:${NC}"
    echo "  smf [install|verify|sql|status]"
    echo ""
    echo -e "${CYAN}Game Server:${NC}"
    echo "  game [map]              Launch OpenMOHAA server (default: dm/mohdm1)"
    echo ""
    echo -e "${CYAN}MQTT:${NC}"
    echo "  mqtt listen [topic]     Subscribe to MQTT topic"
    echo "  mqtt pub <topic> <msg>  Publish MQTT message"
    echo ""
    echo -e "${CYAN}Code Generation:${NC}"
    echo "  generate [types|docs|bruno|all]"
    echo ""
    echo -e "${CYAN}Examples:${NC}"
    echo "  ./dev.sh up"
    echo "  ./dev.sh up infra && ./dev.sh run"
    echo "  ./dev.sh test bruno"
    echo "  ./dev.sh seed elgan"
    echo "  ./dev.sh game dm/mohdm6"
    echo "  ./dev.sh mqtt listen openmohaa/events/#"
}

# ==============================================================================
# Dispatch
# ==============================================================================

CMD="${1:-help}"
shift || true

case "$CMD" in
    up)          cmd_up "$@" ;;
    down)        cmd_down "$@" ;;
    restart)     cmd_restart "$@" ;;
    status)      cmd_status "$@" ;;
    logs)        cmd_logs "$@" ;;
    build)       cmd_build "$@" ;;
    run)         cmd_run "$@" ;;
    rebuild)     cmd_rebuild "$@" ;;
    install)     cmd_install "$@" ;;
    register)    cmd_register "$@" ;;
    seed)        cmd_seed "$@" ;;
    reload-mvs)  cmd_reload_mvs "$@" ;;
    test)        cmd_test "$@" ;;
    audit)       cmd_audit "$@" ;;
    smf)         cmd_smf "$@" ;;
    game)        cmd_game "$@" ;;
    mqtt)        cmd_mqtt "$@" ;;
    generate)    cmd_generate "$@" ;;
    simulate)    cmd_simulate "$@" ;;
    reset)       cmd_reset "$@" ;;
    help|--help|-h) cmd_help ;;
    *)
        echo -e "${RED}Unknown command: $CMD${NC}"
        echo "Run './dev.sh help' for usage."
        exit 1
        ;;
esac

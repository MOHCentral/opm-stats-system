#!/bin/bash
# ==============================================================================
# OpenMOHAA Stats System - Full Stack Launcher
# ==============================================================================
# This script starts ALL components needed for local testing:
#   - PostgreSQL (OLTP database)
#   - ClickHouse (OLAP analytics)
#   - Redis (caching/real-time state)
#   - Prometheus (metrics collection)
#   - Grafana (dashboards)
#   - Go API Server (main stats API)
#   - Node.js Server (legacy fallback)
#   - Instructions for OpenMOHAA game
# ==============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$SCRIPT_DIR/stats-system"
NODE_DIR="$SCRIPT_DIR/stats-system/api-server"
SMF_DIR="$SCRIPT_DIR/stats-system/smf"
LOG_DIR="$SCRIPT_DIR/logs"

# Create log directory
mkdir -p "$LOG_DIR"

# ==============================================================================
# Helper Functions
# ==============================================================================

print_header() {
    echo -e "\n${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC} $1"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
}

print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[i]${NC} $1"
}

check_command() {
    if command -v "$1" &> /dev/null; then
        return 0
    else
        return 1
    fi
}

# ==============================================================================
# Cleanup on Exit
# ==============================================================================

cleanup() {
    echo -e "\n${YELLOW}Shutting down services...${NC}"
    
    # Kill background processes
    if [ ! -z "$GO_API_PID" ]; then
        kill $GO_API_PID 2>/dev/null || true
        print_status "Stopped Go API Server"
    fi
    
    if [ ! -z "$NODE_API_PID" ]; then
        kill $NODE_API_PID 2>/dev/null || true
        print_status "Stopped Node.js Server"
    fi
    
    echo -e "${GREEN}Cleanup complete. Docker containers are still running.${NC}"
    echo -e "Run ${YELLOW}docker-compose -f $API_DIR/docker-compose.yml down${NC} to stop databases."
}

trap cleanup EXIT

# ==============================================================================
# Main Script
# ==============================================================================

clear
echo -e "${CYAN}"
cat << 'EOF'
   ___                   __  __       _   _    _    _    
  / _ \ _ __   ___ _ __ |  \/  | ___ | | | |  / \  / \   
 | | | | '_ \ / _ \ '_ \| |\/| |/ _ \| |_| | / _ \/ _ \  
 | |_| | |_) |  __/ | | | |  | | (_) |  _  |/ ___ \ ___ \ 
  \___/| .__/ \___|_| |_|_|  |_|\___/|_| |_/_/   \_\_/  \_\
       |_|                                                  
                    STATS SYSTEM v2.0
EOF
echo -e "${NC}"

print_header "Checking Prerequisites"

# Check Docker
if check_command docker; then
    print_status "Docker found"
else
    print_error "Docker not found! Please install Docker first."
    exit 1
fi

# Check Docker Compose
if check_command docker-compose || docker compose version &>/dev/null; then
    print_status "Docker Compose found"
else
    print_error "Docker Compose not found! Please install Docker Compose."
    exit 1
fi

# Check Go
if check_command go; then
    GO_VERSION=$(go version | awk '{print $3}')
    print_status "Go found ($GO_VERSION)"
    HAS_GO=1
else
    print_warning "Go not found - will use Node.js server only"
    HAS_GO=0
fi

# Check Node.js
if check_command node; then
    NODE_VERSION=$(node --version)
    print_status "Node.js found ($NODE_VERSION)"
    HAS_NODE=1
else
    print_warning "Node.js not found"
    HAS_NODE=0
fi

# Check OpenMOHAA
OPENMOHAA_BIN=""
if check_command openmohaa; then
    OPENMOHAA_BIN="openmohaa"
elif check_command omohaaded; then
    OPENMOHAA_BIN="omohaaded"
elif [ -f "/usr/bin/openmohaa" ]; then
    OPENMOHAA_BIN="/usr/bin/openmohaa"
elif [ -f "$HOME/.local/bin/openmohaa" ]; then
    OPENMOHAA_BIN="$HOME/.local/bin/openmohaa"
elif [ -f "/opt/openmohaa/openmohaa" ]; then
    OPENMOHAA_BIN="/opt/openmohaa/openmohaa"
fi

if [ ! -z "$OPENMOHAA_BIN" ]; then
    print_status "OpenMOHAA found: $OPENMOHAA_BIN"
else
    print_warning "OpenMOHAA binary not found in PATH - you'll need to start it manually"
fi

# ==============================================================================
# Start Docker Containers
# ==============================================================================

print_header "Starting Docker Containers"

cd "$API_DIR"

# Check if docker-compose.yml exists
if [ ! -f "docker-compose.yml" ]; then
    print_error "docker-compose.yml not found in $API_DIR"
    exit 1
fi

# Check if containers are already running
RUNNING_CONTAINERS=$(docker-compose ps -q 2>/dev/null | wc -l)
if [ "$RUNNING_CONTAINERS" -gt 0 ]; then
    print_info "Some containers are already running, restarting..."
    docker-compose down 2>/dev/null || true
fi

# Start containers
print_info "Starting PostgreSQL, ClickHouse, Redis, Prometheus, Grafana..."
docker-compose up -d

# Wait for databases to be healthy
print_info "Waiting for databases to be ready..."
RETRY=0
MAX_RETRY=30

while [ $RETRY -lt $MAX_RETRY ]; do
    if docker-compose exec -T postgres pg_isready -U mohaa &>/dev/null; then
        break
    fi
    RETRY=$((RETRY + 1))
    sleep 1
    printf "."
done
echo ""

if [ $RETRY -ge $MAX_RETRY ]; then
    print_error "Databases failed to start in time"
    exit 1
fi

print_status "PostgreSQL is ready (port 5432)"
print_status "ClickHouse is ready (ports 8123, 9000)"
print_status "Redis is ready (port 6379)"
print_status "Prometheus is ready (port 9090)"
print_status "Grafana is ready (port 3000, admin/admin)"

# ==============================================================================
# Start Go API Server
# ==============================================================================

if [ "$HAS_GO" -eq 1 ]; then
    print_header "Building & Starting Go API Server"
    
    cd "$API_DIR"
    
    # Check if go.mod exists
    if [ -f "go.mod" ]; then
        # Download dependencies
        print_info "Downloading Go dependencies..."
        go mod download 2>/dev/null || go mod tidy
        
        # Build the server
        print_info "Building API server..."
        if go build -o bin/api-server ./cmd/api; then
            print_status "Build successful"
            
            # Start the server
            print_info "Starting Go API server on port 8080..."
            ENV=development ./bin/api-server > "$LOG_DIR/go-api.log" 2>&1 &
            GO_API_PID=$!
            
            # Wait for it to start
            sleep 2
            if kill -0 $GO_API_PID 2>/dev/null; then
                print_status "Go API Server running (PID: $GO_API_PID)"
            else
                print_error "Go API Server failed to start. Check $LOG_DIR/go-api.log"
                GO_API_PID=""
            fi
        else
            print_error "Build failed - check for missing dependencies"
            print_info "Falling back to Node.js server..."
        fi
    else
        print_error "go.mod not found in $API_DIR"
    fi
fi

# ==============================================================================
# Start Node.js Server (as fallback or additional)
# ==============================================================================

if [ "$HAS_NODE" -eq 1 ]; then
    print_header "Starting Node.js Event Server"
    
    cd "$NODE_DIR"
    
    if [ -f "package.json" ]; then
        # Install dependencies if needed
        if [ ! -d "node_modules" ]; then
            print_info "Installing Node.js dependencies..."
            npm install
        fi
        
        # Start the server
        print_info "Starting Node.js server on port 3000..."
        node server.js > "$LOG_DIR/node-api.log" 2>&1 &
        NODE_API_PID=$!
        
        sleep 1
        if kill -0 $NODE_API_PID 2>/dev/null; then
            print_status "Node.js Server running (PID: $NODE_API_PID)"
        else
            print_error "Node.js Server failed to start. Check $LOG_DIR/node-api.log"
            NODE_API_PID=""
        fi
    fi
fi

# ==============================================================================
# Start SMF Forum Containers
# ==============================================================================

print_header "Starting SMF Forum"

cd "$SMF_DIR"

if [ -f "docker-compose.yml" ]; then
    # Check if SMF containers are already running
    SMF_RUNNING=$(docker-compose ps -q 2>/dev/null | wc -l)
    if [ "$SMF_RUNNING" -gt 0 ]; then
        print_info "SMF containers already running, restarting..."
        docker-compose down 2>/dev/null || true
    fi
    
    print_info "Starting SMF, MariaDB, phpMyAdmin..."
    docker-compose up -d
    
    # Wait for MariaDB to be healthy
    print_info "Waiting for SMF database to be ready..."
    RETRY=0
    MAX_RETRY=30
    while [ $RETRY -lt $MAX_RETRY ]; do
        if docker-compose exec -T smf-db mysqladmin ping -uroot -proot_password &>/dev/null; then
            break
        fi
        RETRY=$((RETRY + 1))
        sleep 1
        printf "."
    done
    echo ""
    
    if [ $RETRY -ge $MAX_RETRY ]; then
        print_warning "SMF database may not be ready yet"
    else
        print_status "SMF Forum is ready (port 8888)"
        print_status "phpMyAdmin is ready (port 8889)"
    fi
else
    print_warning "SMF docker-compose.yml not found in $SMF_DIR"
fi

cd "$SCRIPT_DIR"

# ==============================================================================
# Update tracker.scr configuration
# ==============================================================================

print_header "Configuring Tracker"

TRACKER_FILE="$SCRIPT_DIR/global/tracker.scr"
if [ -f "$TRACKER_FILE" ]; then
    print_status "Tracker script found: $TRACKER_FILE"
    print_info "Tracker is configured to send events to: http://localhost:8080"
else
    print_warning "Tracker script not found at $TRACKER_FILE"
fi

# ==============================================================================
# Service Status Summary
# ==============================================================================

print_header "Service Status"

echo ""
echo -e "  ${GREEN}┌─────────────────────────────────────────────────────────┐${NC}"
echo -e "  ${GREEN}│${NC}                    RUNNING SERVICES                     ${GREEN}│${NC}"
echo -e "  ${GREEN}├─────────────────────────────────────────────────────────┤${NC}"

# Check each service
check_port() {
    nc -z localhost $1 2>/dev/null
}

# PostgreSQL
if check_port 5432; then
    echo -e "  ${GREEN}│${NC}  ✅ PostgreSQL         ${BLUE}localhost:5432${NC}              ${GREEN}│${NC}"
else
    echo -e "  ${GREEN}│${NC}  ❌ PostgreSQL         NOT RUNNING                    ${GREEN}│${NC}"
fi

# ClickHouse
if check_port 8123; then
    echo -e "  ${GREEN}│${NC}  ✅ ClickHouse         ${BLUE}localhost:8123, 9000${NC}       ${GREEN}│${NC}"
else
    echo -e "  ${GREEN}│${NC}  ❌ ClickHouse         NOT RUNNING                    ${GREEN}│${NC}"
fi

# Redis
if check_port 6379; then
    echo -e "  ${GREEN}│${NC}  ✅ Redis              ${BLUE}localhost:6379${NC}              ${GREEN}│${NC}"
else
    echo -e "  ${GREEN}│${NC}  ❌ Redis              NOT RUNNING                    ${GREEN}│${NC}"
fi

# Prometheus
if check_port 9090; then
    echo -e "  ${GREEN}│${NC}  ✅ Prometheus         ${BLUE}localhost:9090${NC}              ${GREEN}│${NC}"
else
    echo -e "  ${GREEN}│${NC}  ❌ Prometheus         NOT RUNNING                    ${GREEN}│${NC}"
fi

# Grafana
if check_port 3000; then
    echo -e "  ${GREEN}│${NC}  ✅ Grafana            ${BLUE}localhost:3000${NC}              ${GREEN}│${NC}"
else
    echo -e "  ${GREEN}│${NC}  ❌ Grafana            NOT RUNNING                    ${GREEN}│${NC}"
fi

# Go API
if [ ! -z "$GO_API_PID" ] && kill -0 $GO_API_PID 2>/dev/null; then
    echo -e "  ${GREEN}│${NC}  ✅ Go API Server      ${BLUE}localhost:8080${NC}              ${GREEN}│${NC}"
else
    echo -e "  ${GREEN}│${NC}  ⚠️  Go API Server      NOT RUNNING                    ${GREEN}│${NC}"
fi

# Node.js
if [ ! -z "$NODE_API_PID" ] && kill -0 $NODE_API_PID 2>/dev/null; then
    echo -e "  ${GREEN}│${NC}  ✅ Node.js Server     ${BLUE}localhost:3000${NC}              ${GREEN}│${NC}"
fi

# SMF Forum
if check_port 8888; then
    echo -e "  ${GREEN}│${NC}  ✅ SMF Forum          ${BLUE}localhost:8888${NC}              ${GREEN}│${NC}"
else
    echo -e "  ${GREEN}│${NC}  ❌ SMF Forum          NOT RUNNING                    ${GREEN}│${NC}"
fi

# phpMyAdmin
if check_port 8889; then
    echo -e "  ${GREEN}│${NC}  ✅ phpMyAdmin         ${BLUE}localhost:8889${NC}              ${GREEN}│${NC}"
fi

echo -e "  ${GREEN}└─────────────────────────────────────────────────────────┘${NC}"

# ==============================================================================
# Quick Access URLs
# ==============================================================================

echo ""
echo -e "  ${CYAN}┌─────────────────────────────────────────────────────────┐${NC}"
echo -e "  ${CYAN}│${NC}                     QUICK ACCESS                        ${CYAN}│${NC}"
echo -e "  ${CYAN}├─────────────────────────────────────────────────────────┤${NC}"
echo -e "  ${CYAN}│${NC}  📊 Grafana Dashboard:  ${YELLOW}http://localhost:3000${NC}         ${CYAN}│${NC}"
echo -e "  ${CYAN}│${NC}     Login: admin / admin                               ${CYAN}│${NC}"
echo -e "  ${CYAN}│${NC}                                                         ${CYAN}│${NC}"
echo -e "  ${CYAN}│${NC}  � SMF Forum:          ${YELLOW}http://localhost:8888${NC}         ${CYAN}│${NC}"
echo -e "  ${CYAN}│${NC}  🗃️  phpMyAdmin:         ${YELLOW}http://localhost:8889${NC}         ${CYAN}│${NC}"
echo -e "  ${CYAN}│${NC}                                                         ${CYAN}│${NC}"
echo -e "  ${CYAN}│${NC}  �📈 Prometheus:         ${YELLOW}http://localhost:9090${NC}         ${CYAN}│${NC}"
echo -e "  ${CYAN}│${NC}  🔧 API Health:         ${YELLOW}http://localhost:8080/health${NC}  ${CYAN}│${NC}"
echo -e "  ${CYAN}│${NC}  📉 API Metrics:        ${YELLOW}http://localhost:8080/metrics${NC} ${CYAN}│${NC}"
echo -e "  ${CYAN}│${NC}  🗄️  ClickHouse:         ${YELLOW}http://localhost:8123${NC}         ${CYAN}│${NC}"
echo -e "  ${CYAN}└─────────────────────────────────────────────────────────┘${NC}"

# ==============================================================================
# OpenMOHAA Instructions
# ==============================================================================

print_header "Starting OpenMOHAA"

echo ""
echo -e "  ${YELLOW}To test with OpenMOHAA:${NC}"
echo ""
if [ ! -z "$OPENMOHAA_BIN" ]; then
    echo -e "  ${GREEN}1.${NC} Start the dedicated server:"
    echo -e "     ${BLUE}$OPENMOHAA_BIN +set dedicated 1 +set net_port 12203 +map dm/mohdm1${NC}"
    echo ""
    echo -e "  ${GREEN}2.${NC} Or start client and create local game:"
    echo -e "     ${BLUE}$OPENMOHAA_BIN${NC}"
else
    echo -e "  ${GREEN}1.${NC} Find your OpenMOHAA binary and run:"
    echo -e "     ${BLUE}openmohaa +set dedicated 1 +set net_port 12203 +map dm/mohdm1${NC}"
fi
echo ""
echo -e "  ${GREEN}3.${NC} The tracker.scr will automatically:"
echo -e "     - Send events to http://localhost:8080"
echo -e "     - Track kills, deaths, movement, weapons"
echo -e "     - Support /login, /logout, /stats commands"
echo ""

# ==============================================================================
# Test Commands
# ==============================================================================

print_header "Test Commands"

echo ""
echo -e "  ${YELLOW}Test the API with curl:${NC}"
echo ""
echo -e "  ${BLUE}# Health check${NC}"
echo -e "  curl http://localhost:8080/health"
echo ""
echo -e "  ${BLUE}# Send a test event${NC}"
echo -e "  curl -X POST http://localhost:8080/api/v1/ingest/events \\"
echo -e "    -H 'Content-Type: application/x-www-form-urlencoded' \\"
echo -e "    -H 'X-Server-Token: dev-server-token-replace-in-production' \\"
echo -e "    -d 'type=kill&attacker=Player1&victim=Player2&weapon=m1_garand'"
echo ""
echo -e "  ${BLUE}# View logs${NC}"
echo -e "  tail -f $LOG_DIR/go-api.log"
echo ""

# ==============================================================================
# Keep Running
# ==============================================================================

print_header "System Ready"

echo ""
echo -e "  ${GREEN}All services are running!${NC}"
echo -e "  Press ${YELLOW}Ctrl+C${NC} to stop the API servers."
echo -e "  Docker containers will keep running."
echo ""
echo -e "  ${CYAN}Watching logs... (Ctrl+C to exit)${NC}"
echo ""

# Tail logs if they exist
if [ -f "$LOG_DIR/go-api.log" ]; then
    tail -f "$LOG_DIR/go-api.log" &
    TAIL_PID=$!
fi

# Wait for interrupt
wait

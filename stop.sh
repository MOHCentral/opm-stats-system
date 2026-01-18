#!/bin/bash
# ==============================================================================
# OpenMOHAA Stats System - Stop All Services
# ==============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$SCRIPT_DIR/stats-system"
SMF_DIR="$SCRIPT_DIR/stats-system/smf"

echo -e "${YELLOW}Stopping OpenMOHAA Stats System...${NC}"

# Kill any running API servers
echo -e "${YELLOW}Stopping API servers...${NC}"
pkill -f "bin/api-server" 2>/dev/null && echo -e "${GREEN}[✓] Stopped Go API Server${NC}" || echo -e "${YELLOW}[!] Go API Server not running${NC}"
pkill -f "node server.js" 2>/dev/null && echo -e "${GREEN}[✓] Stopped Node.js Server${NC}" || echo -e "${YELLOW}[!] Node.js Server not running${NC}"

# Stop Docker containers
echo -e "${YELLOW}Stopping Docker containers...${NC}"
cd "$API_DIR"
if [ -f "docker-compose.yml" ]; then
    docker-compose down
    echo -e "${GREEN}[✓] Stats system containers stopped${NC}"
else
    echo -e "${RED}[!] docker-compose.yml not found in $API_DIR${NC}"
fi

# Stop SMF containers
echo -e "${YELLOW}Stopping SMF containers...${NC}"
cd "$SMF_DIR"
if [ -f "docker-compose.yml" ]; then
    docker-compose down
    echo -e "${GREEN}[✓] SMF containers stopped${NC}"
else
    echo -e "${YELLOW}[!] SMF docker-compose.yml not found${NC}"
fi

echo ""
echo -e "${GREEN}All services stopped.${NC}"

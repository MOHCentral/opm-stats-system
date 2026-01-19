#!/bin/bash
# ==============================================================================
# OpenMOHAA Stats System - Stop All Services
# ==============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SMF_DIR="$SCRIPT_DIR/smf"

echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}Stopping OpenMOHAA Stats System...${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Stop Stats System containers
echo ""
echo -e "${YELLOW}Stopping Stats System...${NC}"
cd "$SCRIPT_DIR"
if [ -f "docker-compose.yml" ]; then
    docker compose down
    echo -e "${GREEN}[✓]${NC} Stats system stopped"
fi

# Stop SMF containers
echo ""
echo -e "${YELLOW}Stopping SMF Forum...${NC}"
cd "$SMF_DIR"
if [ -f "docker-compose.yml" ]; then
    docker compose down
    echo -e "${GREEN}[✓]${NC} SMF Forum stopped"
fi

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}All services stopped.${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}Run ./start.sh to restart all services${NC}"

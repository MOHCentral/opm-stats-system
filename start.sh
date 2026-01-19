#!/bin/bash
# ==============================================================================
# OpenMOHAA Stats System - Start All Services
# ==============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SMF_DIR="$SCRIPT_DIR/smf"

clear
echo -e "${CYAN}"
cat << 'LOGO'
   ___                   __  __       _   _    _    _    
  / _ \ _ __   ___ _ __ |  \/  | ___ | | | |  / \  / \   
 | | | | '_ \ / _ \ '_ \| |\/| |/ _ \| |_| | / _ \/ _ \  
 | |_| | |_) |  __/ | | | |  | | (_) |  _  |/ ___ \ ___ \ 
  \___/| .__/ \___|_| |_|_|  |_|\___/|_| |_/_/   \_\_/  \_\
       |_|                                                  
                    STATS SYSTEM v2.0
LOGO
echo -e "${NC}"

# Stats System
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}Starting Stats System...${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

cd "$SCRIPT_DIR"
docker compose down 2>/dev/null || true
docker compose up -d --build

echo -e "${GREEN}[✓]${NC} Stats API (port 8080)"
echo -e "${GREEN}[✓]${NC} PostgreSQL (port 5432)"
echo -e "${GREEN}[✓]${NC} ClickHouse (port 8123)"
echo -e "${GREEN}[✓]${NC} Redis (port 6379)"

# SMF Forum
echo ""
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}Starting SMF Forum...${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

cd "$SMF_DIR"
docker compose down 2>/dev/null || true
docker compose up -d

echo -e "${GREEN}[✓]${NC} SMF Forum (port 8888)"
echo -e "${GREEN}[✓]${NC} MariaDB"
echo -e "${GREEN}[✓]${NC} phpMyAdmin (port 8889)"

# Wait
echo ""
echo -e "${YELLOW}Waiting for services...${NC}"
sleep 5

# Status
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}                    ALL SERVICES STARTED${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  ${BLUE}Stats API:${NC}      http://localhost:8080"
echo -e "  ${BLUE}API Health:${NC}     http://localhost:8080/health"
echo -e "  ${BLUE}SMF Forum:${NC}      http://localhost:8888"
echo -e "  ${BLUE}phpMyAdmin:${NC}     http://localhost:8889"
echo -e "  ${BLUE}ClickHouse:${NC}     http://localhost:8123"
echo ""
echo -e "${YELLOW}Run ./stop.sh to stop all services${NC}"
echo ""
echo -e "${CYAN}Container Status:${NC}"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "opm-stats|smf" || true

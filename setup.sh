#!/bin/bash
# =============================================================================
# MOHAA Stats System - Quick Setup Script
# =============================================================================
# Usage: ./setup.sh [smf_path]
# Example: ./setup.sh /var/www/smf
# =============================================================================

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SMF_PATH="${1:-/var/www/smf}"
PLUGIN_PATH="$SCRIPT_DIR/opm-stats-smf-integration/smf-mohaa"

echo -e "${GREEN}=== MOHAA Stats System Setup ===${NC}"
echo "Script Dir: $SCRIPT_DIR"
echo "SMF Path: $SMF_PATH"
echo "Plugin Path: $PLUGIN_PATH"
echo ""

# -----------------------------------------------------------------------------
# Step 1: Start Docker Services
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[1/4] Starting Docker services...${NC}"
cd "$SCRIPT_DIR/opm-stats-api"

if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker not found. Please install Docker first.${NC}"
    exit 1
fi

docker compose up -d
sleep 5

# Wait for health checks
echo "Waiting for services to be healthy..."
timeout=60
while [ $timeout -gt 0 ]; do
    if curl -s http://localhost:8080/health | grep -q "ok"; then
        echo -e "${GREEN}API is healthy!${NC}"
        break
    fi
    sleep 2
    timeout=$((timeout-2))
done

if [ $timeout -le 0 ]; then
    echo -e "${YELLOW}Warning: API may not be fully ready yet${NC}"
fi

# -----------------------------------------------------------------------------
# Step 2: Install SMF Plugin Files (Symlinks)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[2/4] Installing SMF plugin files...${NC}"

if [ ! -d "$SMF_PATH/Sources" ]; then
    echo -e "${RED}SMF Sources directory not found at $SMF_PATH${NC}"
    echo "Please specify correct SMF path: ./setup.sh /path/to/smf"
    exit 1
fi

# Create symlinks for Source files
cd "$SMF_PATH/Sources"
for file in MohaaPlayers.php MohaaAchievements.php MohaaServers.php MohaaTeams.php MohaaTournaments.php MohaaComparison.php MohaaPredictions.php; do
    if [ -f "$PLUGIN_PATH/Sources/$file" ]; then
        sudo rm -f "$file" 2>/dev/null || true
        sudo ln -sf "$PLUGIN_PATH/Sources/$file" .
        echo "  ✓ Linked $file"
    fi
done

# Link MohaaStats directory
sudo rm -f MohaaStats 2>/dev/null || true
sudo ln -sf "$PLUGIN_PATH/Sources/MohaaStats" .
echo "  ✓ Linked MohaaStats/"

# Create symlinks for Template files
cd "$SMF_PATH/Themes/default"
for file in MohaaPlayers.template.php MohaaServers.template.php MohaaAchievements.template.php MohaaTeams.template.php MohaaTournaments.template.php MohaaStats.template.php MohaaWarRoom.template.php MohaaLeaderboards.template.php; do
    if [ -f "$PLUGIN_PATH/Themes/default/$file" ]; then
        sudo rm -f "$file" 2>/dev/null || true
        sudo ln -sf "$PLUGIN_PATH/Themes/default/$file" .
        echo "  ✓ Linked $file"
    fi
done

echo -e "${GREEN}Plugin files installed!${NC}"

# -----------------------------------------------------------------------------
# Step 3: Copy installer to SMF
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[3/4] Copying installer...${NC}"
sudo cp "$PLUGIN_PATH/install/mohaa_master_install.php" "$SMF_PATH/Sources/"
echo "  ✓ Copied mohaa_master_install.php"

# -----------------------------------------------------------------------------
# Step 4: Instructions
# -----------------------------------------------------------------------------
echo ""
echo -e "${GREEN}=== Setup Complete! ===${NC}"
echo ""
echo "Next steps:"
echo "1. Run the database installer in your browser:"
echo "   http://your-forum.com/Sources/mohaa_master_install.php"
echo ""
echo "2. Or run from CLI:"
echo "   cd $SMF_PATH && php Sources/mohaa_master_install.php"
echo ""
echo "3. Test the API:"
echo "   curl http://localhost:8080/health"
echo ""
echo "4. Visit your stats pages:"
echo "   http://your-forum.com/index.php?action=mohaastats"
echo ""
echo -e "${YELLOW}Docker Services:${NC}"
docker compose ps

echo ""
echo -e "${GREEN}Done!${NC}"

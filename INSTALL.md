# 🚀 OpenMOHAA Stats System - Installation Guide

## Prerequisites
- **Docker** & **Docker Compose** (v2+)
- **Git**
- **PHP 8.x** with curl, mysqli extensions
- **Apache/Nginx** with SMF 2.1 installed
- **MySQL/MariaDB** for SMF

---

## Step 1: Clone the Repository

```bash
# Clone the main repo with submodules
git clone --recursive git@github.com:MOHCentral/opm-stats-system.git
cd opm-stats-system

# If you already cloned without --recursive:
git submodule update --init --recursive
```

---

## Step 2: Start API and Dependencies (Docker)

```bash
cd opm-stats-api

# Start all services (API, PostgreSQL, ClickHouse, Redis)
docker compose up -d

# Verify all services are healthy
docker compose ps
```

Expected output:
```
NAME                         STATUS
opm-stats-api-api-1          Up (healthy)
opm-stats-api-clickhouse-1   Up (healthy)
opm-stats-api-postgres-1     Up (healthy)
opm-stats-api-redis-1        Up (healthy)
```

**Test API:**
```bash
curl http://localhost:8080/health
# Should return: {"status":"ok",...}
```

---

## Step 3: Install SMF Plugin Files

### Option A: Symlink (Development)
```bash
# Replace /var/www/smf with your SMF installation path
SMF_PATH="/var/www/smf"
PLUGIN_PATH="/path/to/opm-stats-system/opm-stats-smf-integration/smf-mohaa"

# Symlink Source files
cd $SMF_PATH/Sources
sudo ln -sf $PLUGIN_PATH/Sources/MohaaPlayers.php .
sudo ln -sf $PLUGIN_PATH/Sources/MohaaAchievements.php .
sudo ln -sf $PLUGIN_PATH/Sources/MohaaServers.php .
sudo ln -sf $PLUGIN_PATH/Sources/MohaaTeams.php .
sudo ln -sf $PLUGIN_PATH/Sources/MohaaTournaments.php .
sudo ln -sf $PLUGIN_PATH/Sources/MohaaComparison.php .
sudo ln -sf $PLUGIN_PATH/Sources/MohaaPredictions.php .
sudo ln -sf $PLUGIN_PATH/Sources/MohaaStats .

# Symlink Template files
cd $SMF_PATH/Themes/default
sudo ln -sf $PLUGIN_PATH/Themes/default/MohaaPlayers.template.php .
sudo ln -sf $PLUGIN_PATH/Themes/default/MohaaServers.template.php .
sudo ln -sf $PLUGIN_PATH/Themes/default/MohaaAchievements.template.php .
sudo ln -sf $PLUGIN_PATH/Themes/default/MohaaTeams.template.php .
sudo ln -sf $PLUGIN_PATH/Themes/default/MohaaTournaments.template.php .
sudo ln -sf $PLUGIN_PATH/Themes/default/MohaaStats.template.php .
sudo ln -sf $PLUGIN_PATH/Themes/default/MohaaWarRoom.template.php .
sudo ln -sf $PLUGIN_PATH/Themes/default/MohaaLeaderboards.template.php .
```

### Option B: Copy (Production)
```bash
# Copy files instead of symlink for production
cp -r $PLUGIN_PATH/Sources/* $SMF_PATH/Sources/
cp -r $PLUGIN_PATH/Themes/* $SMF_PATH/Themes/
```

---

## Step 4: Install SMF Database Tables & Hooks

Run the master installer from your browser or CLI:

### Browser Method
Navigate to: `http://your-forum.com/Sources/mohaa_install.php`

### CLI Method
```bash
cd /path/to/smf
php -r "
require_once 'SSI.php';
require_once 'Sources/MohaaStats/install_database.php';
require_once 'opm-stats-smf-integration/smf-mohaa/install/install_hooks.php';
echo 'Installation complete!';
"
```

### Manual SQL (if needed)
```bash
# Connect to your SMF MySQL database
mysql -u smf_user -p smf_database

# Run these SQL files:
SOURCE /path/to/opm-stats-smf-integration/smf-mohaa/install_achievements.sql;
SOURCE /path/to/opm-stats-smf-integration/smf-mohaa/install_tournaments.sql;
SOURCE /path/to/opm-stats-smf-integration/smf-mohaa/link_identity.sql;
```

---

## Step 5: Configure SMF Settings

Add these to your SMF database (or use Admin panel):

```sql
-- Run in your SMF MySQL database
INSERT INTO smf_settings (variable, value) VALUES
('mohaa_stats_enabled', '1'),
('mohaa_api_url', 'http://localhost:8080/api/v1'),
('mohaa_api_timeout', '10'),
('mohaa_cache_duration', '60')
ON DUPLICATE KEY UPDATE value = VALUES(value);
```

---

## Step 6: Seed Test Data (Optional)

```bash
cd opm-stats-api

# Run the seeder to populate test data
docker exec opm-stats-api-api-1 /app/api seed

# Or manually insert test events
./tools/seed_test_data.sh
```

---

## Step 7: Verify Installation

### Check API endpoints:
```bash
# Health check
curl http://localhost:8080/health

# Leaderboard
curl "http://localhost:8080/api/v1/stats/leaderboard/global?stat=kills&limit=5"

# Global stats
curl http://localhost:8080/api/v1/stats/global
```

### Check SMF pages:
- Stats Dashboard: `http://your-forum.com/index.php?action=mohaastats`
- Leaderboards: `http://your-forum.com/index.php?action=mohaastats;sa=leaderboards`
- Servers: `http://your-forum.com/index.php?action=mohaaservers`

---

## Troubleshooting

### API not responding
```bash
# Check container status
docker compose ps

# Check logs
docker logs opm-stats-api-api-1 --tail 100

# Restart
docker compose restart api
```

### Port conflicts
```bash
# Kill process on port 6379 (Redis)
sudo fuser -k 6379/tcp

# Kill process on port 8080 (API)
sudo fuser -k 8080/tcp

# Restart docker
docker compose up -d
```

### Database migrations
```bash
# Re-run ClickHouse migrations
docker exec -i opm-stats-api-clickhouse-1 clickhouse-client < migrations/clickhouse/001_initial_schema.sql

# Re-run PostgreSQL migrations
docker exec -i opm-stats-api-postgres-1 psql -U mohaa -d mohaa_stats < migrations/postgres/001_initial_schema.sql
```

---

## Service Endpoints

| Service | Port | URL |
|---------|------|-----|
| API | 8080 | http://localhost:8080 |
| PostgreSQL | 5432 | postgres://mohaa:admin123@localhost:5432/mohaa_stats |
| ClickHouse HTTP | 8123 | http://localhost:8123 |
| ClickHouse Native | 9000 | localhost:9000 |
| Redis | 6379 | redis://localhost:6379 |

---

## Quick Commands Reference

```bash
# Start everything
cd opm-stats-api && docker compose up -d

# Stop everything
docker compose down

# View logs
docker compose logs -f api

# Rebuild API after code changes
docker compose build api && docker compose up -d api

# Access ClickHouse CLI
docker exec -it opm-stats-api-clickhouse-1 clickhouse-client

# Access PostgreSQL CLI
docker exec -it opm-stats-api-postgres-1 psql -U mohaa -d mohaa_stats

# Access Redis CLI
docker exec -it opm-stats-api-redis-1 redis-cli
```

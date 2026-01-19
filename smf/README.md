# SMF Forum for MOHAA Stats

This directory contains the Docker setup for Simple Machines Forum (SMF) integrated with the MOHAA Stats system.

## Quick Start

```bash
# Build and start SMF
docker compose up -d --build

# Watch the logs
docker compose logs -f smf
```

## Access Points

| Service | URL | Description |
|---------|-----|-------------|
| **SMF Forum** | http://localhost:8888 | Main forum |
| **phpMyAdmin** | http://localhost:8889 | Database admin |

## First-Time Setup

1. Navigate to http://localhost:8888
2. You'll see the SMF installer
3. Configure the database:
   - **Database Server**: `smf-db`
   - **Database Name**: `smf`
   - **Database User**: `smf`
   - **Database Password**: `smf_password`
4. Create admin account
5. Complete installation
6. Delete `install.php` when prompted

## MOHAA Plugin Development

All MOHAA plugin files are in `smf-mohaa/` directory:

```
smf-mohaa/
├── Sources/                    # PHP source files
│   ├── MohaaAchievements.php
│   ├── MohaaPlayers.php
│   ├── MohaaServers.php
│   ├── MohaaTeams.php
│   ├── MohaaTournaments.php
│   └── MohaaStats/             # Core stats module
│       ├── MohaaStats.php
│       ├── MohaaStatsAPI.php
│       └── MohaaStatsAdmin.php
└── Themes/default/             # Template files
    ├── *.template.php          # All templates
    └── languages/              # Language files
```

### How It Works

1. `smf-mohaa/` is mounted at `/mohaa` in the container
2. Entrypoint script creates symlinks to SMF directories
3. **Edit files locally → Changes are instant** (no docker cp!)

## Directory Structure

```
smf/
├── docker-compose.yml    # Docker services (mounts ../smf-mohaa)
├── entrypoint.sh         # Creates symlinks on startup
├── custom/               # Custom PHP files
└── README.md             # This file
```

## Integration with MOHAA Stats API

The SMF container connects to the MOHAA Stats API at `http://172.17.0.1:8080`.

Environment variable:
- `MOHAA_API_URL`: Override the API URL

## Volumes

- `smf-data`: Forum files and uploads
- `smf-db-data`: MariaDB database

## Troubleshooting

### Can't connect to database

```bash
# Check if MariaDB is ready
docker compose logs smf-db

# Verify connection
docker compose exec smf-db mysql -u smf -psmf_password smf -e "SELECT 1"
```

### Permission issues

```bash
# Fix permissions inside container
docker compose exec smf chown -R www-data:www-data /var/www/html
docker compose exec smf chmod -R 755 /var/www/html
```

### Clear cache

```bash
docker compose exec smf rm -rf /var/www/html/cache/*
```

## Ports Summary

| Port | Service |
|------|---------|
| 8888 | SMF Forum |
| 8889 | phpMyAdmin |
| 8080 | MOHAA Stats API |
| 3000 | Grafana |
| 9090 | Prometheus |

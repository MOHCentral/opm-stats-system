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

## Installing MOHAA Plugins

The plugins are automatically mounted at `/var/www/html/Packages/mohaa/`.

To install them:

1. Go to **Admin** → **Packages** → **Browse Packages**
2. Upload each plugin in this order:
   1. `mohaa_stats_core` (required first!)
   2. `mohaa_players`
   3. `mohaa_servers`
   4. `mohaa_achievements`
   5. `mohaa_teams`
   6. `mohaa_tournaments`
   7. `mohaa_login_tokens`

Or use the Package Manager to install from `Packages/mohaa/` directory.

## Directory Structure

```
smf/
├── docker-compose.yml    # Docker services
├── Dockerfile            # SMF image build
├── custom/               # Custom PHP files
│   └── mohaa_api.php     # API integration layer
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

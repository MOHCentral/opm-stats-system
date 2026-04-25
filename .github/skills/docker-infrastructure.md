# Skill: Docker Infrastructure

Manage the full OPM Stats docker infrastructure.

## Source Location
```
/run/media/elgan/evo/dev/opm-stats-system/opm-stats-api/
```

## Services

| Service | Image | Port(s) | Purpose |
|---------|-------|---------|---------|
| opm-stats-api | ghcr.io/mohcentral/opm-stats-api | 8084:8080 | Go API server |
| opm-stats-postgres | postgres:16-alpine | 5432:5432 | OLTP (users, auth, tournaments) |
| opm-stats-clickhouse | clickhouse/clickhouse-server:24-alpine | 8123, 9000 | OLAP (events, analytics) |
| opm-stats-redis | redis:7-alpine | 6380:6379 | Cache, rate limit, real-time |
| opm-stats-mosquitto | eclipse-mosquitto:2 | 1883, 9001 | MQTT broker |

## Commands

### Start All
```bash
cd /run/media/elgan/evo/dev/opm-stats-system/opm-stats-api
docker compose up -d
```

### Start Infrastructure Only (for local API dev)
```bash
docker compose up -d opm-stats-postgres opm-stats-clickhouse opm-stats-redis opm-stats-mosquitto
```

### View Logs
```bash
docker compose logs -f opm-stats-api
docker compose logs -f opm-stats-mosquitto
```

### Reset All Data
```bash
docker compose down -v  # WARNING: Destroys all volumes
docker compose up -d
```

### Rebuild API Image
```bash
docker compose build opm-stats-api
docker compose up -d opm-stats-api
```

## Health Checks
All services have built-in health checks:
```bash
docker compose ps  # Check health status
```

## Network
All services are on `opm-stats-network` (bridge driver).

## Volumes
| Volume | Service | Path |
|--------|---------|------|
| opm-stats-postgres-data | postgres | /var/lib/postgresql/data |
| opm-stats-clickhouse-data | clickhouse | /var/lib/clickhouse |
| opm-stats-redis-data | redis | /data |
| opm-stats-mosquitto-data | mosquitto | /mosquitto/data |
| opm-stats-mosquitto-log | mosquitto | /mosquitto/log |

## MQTT Broker Access
```bash
# Test MQTT connection
mosquitto_sub -h localhost -p 1883 -t "openmohaa/#" -v

# Publish test event
mosquitto_pub -h localhost -p 1883 -t "openmohaa/events/test" -m '{"type":"test"}'
```

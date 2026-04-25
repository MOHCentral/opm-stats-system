# Skill: Build & Run OPM Stats API

Build and run the Go API server with all dependencies.

## Prerequisites
- Go 1.22+
- Docker & Docker Compose (for databases)

## Source Location
```
/run/media/elgan/evo/dev/opm-stats-system/opm-stats-api/
```

## Quick Start (Docker - Full Stack)

### 1. Start Infrastructure
```bash
cd /run/media/elgan/evo/dev/opm-stats-system/opm-stats-api
docker compose up -d opm-stats-postgres opm-stats-clickhouse opm-stats-redis opm-stats-mosquitto
```

### 2. Build & Run API Locally
```bash
cd /run/media/elgan/evo/dev/opm-stats-system/opm-stats-api
go build -o bin/api ./cmd/api/
./bin/api
```

### 3. Or Run Everything in Docker
```bash
docker compose up -d
```

## Environment Variables
```bash
export PORT=8080
export POSTGRES_URL="postgres://postgres:postgres@localhost:5432/mohaa_stats?sslmode=disable"
export CLICKHOUSE_URL="clickhouse://localhost:9000/mohaa_stats"
export REDIS_URL="redis://localhost:6380/0"
export MQTT_BROKER_URL="tcp://localhost:1883"
export MQTT_CLIENT_ID="opm-stats-api"
export MQTT_TOPIC_PREFIX="openmohaa"
export WORKER_COUNT=8
export QUEUE_SIZE=10000
export BATCH_SIZE=500
export FLUSH_INTERVAL=1s
export ENV=development
```

## Makefile Targets
```bash
make build        # Build API binary
make run          # Build and run
make test         # Run tests
make generate-types  # Regenerate event types from OpenAPI spec
make docker-build # Build Docker image
```

## Database Initialization
The API auto-runs migrations on first request to `POST /api/v1/system/install`:
```bash
curl -X POST http://localhost:8084/api/v1/system/install \
  -H "X-Server-Token: your-server-token"
```

## Health Checks
```bash
curl http://localhost:8084/health
curl http://localhost:8084/ready
curl http://localhost:8084/metrics  # Prometheus metrics
```

## Testing
```bash
# Unit tests
cd /run/media/elgan/evo/dev/opm-stats-system/opm-stats-api
go test ./...

# Integration tests (requires running infrastructure)
go test ./tests/ -v -tags=integration

# E2E tests
./tests/run_e2e.sh
```

## Seeding Test Data
```bash
go run ./cmd/seeder/ --count=1000
# Or from game scripts:
# In-game console: /seed 100
```

## MQTT Integration
When MQTT broker is available, the API subscribes to:
- `openmohaa/events/#` — Game telemetry events
- `openmohaa/servers/#` — Server registration/heartbeat

Events received via MQTT are processed through the same worker pool as HTTP events.

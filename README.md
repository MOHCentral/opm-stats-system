# MOHAA Stats API

High-throughput telemetry and competitive infrastructure for Medal of Honor: Allied Assault (OpenMOHAA).

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          Game Servers (OpenMOHAA)                        │
│                      tracker.scr sends events via HTTP                   │
└──────────────────────────────────┬──────────────────────────────────────┘
                                   │ URL-encoded events
                                   ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                           Go API Server                                  │
│  ┌─────────────┐   ┌──────────────────┐   ┌─────────────────────────┐   │
│  │   Ingest    │──▶│   Worker Pool    │──▶│    Batch Insert         │   │
│  │  Handlers   │   │ (Buffered Chan)  │   │   (ClickHouse)          │   │
│  └─────────────┘   └──────────────────┘   └─────────────────────────┘   │
│         │                   │                                           │
│         │                   ▼                                           │
│         │          ┌──────────────────┐                                 │
│         │          │  Side Effects    │ ◀──── Real-time state updates   │
│         │          │    (Redis)       │       Achievement checks        │
│         │          └──────────────────┘                                 │
│         ▼                                                               │
│  ┌─────────────┐   ┌──────────────────┐                                 │
│  │   Stats     │──▶│    ClickHouse    │ ◀──── Materialized Views       │
│  │  Handlers   │   │   (OLAP Queries) │       for aggregates            │
│  └─────────────┘   └──────────────────┘                                 │
│         ▼                                                               │
│  ┌─────────────┐   ┌──────────────────┐                                 │
│  │ Tournament  │──▶│   PostgreSQL     │ ◀──── Users, Tournaments       │
│  │  Handlers   │   │  (OLTP State)    │       Brackets, Teams           │
│  └─────────────┘   └──────────────────┘                                 │
└─────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         HTMX Frontend                                    │
│           Leaderboards • Player Profiles • Heatmaps                      │
│           Live Matches • Tournament Brackets                             │
└─────────────────────────────────────────────────────────────────────────┘
```

## Quick Start

### Prerequisites
- Docker & Docker Compose
- Go 1.22+ (for development)

### Running with Docker

```bash
# Start all services
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f api
```

### Local Development

```bash
# Start dependencies only
docker-compose up -d postgres clickhouse redis

# Run API locally
go run ./cmd/api

# Run with live reload (install: go install github.com/air-verse/air@latest)
air
```

## API Endpoints

### Ingestion

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/ingest/events` | Ingest game events |
| POST | `/api/v1/ingest/match-result` | Report match results |

### Stats

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/stats/leaderboard` | Global leaderboard |
| GET | `/api/v1/stats/leaderboard/weekly` | Weekly leaderboard |
| GET | `/api/v1/stats/leaderboard/weapon/:weapon` | Weapon-specific leaderboard |
| GET | `/api/v1/stats/leaderboard/map/:map` | Map-specific leaderboard |
| GET | `/api/v1/stats/player/:guid` | Player stats |
| GET | `/api/v1/stats/player/:guid/matches` | Player match history |
| GET | `/api/v1/stats/player/:guid/weapons` | Player weapon stats |
| GET | `/api/v1/stats/player/:guid/heatmap/:map` | Player kill heatmap |
| GET | `/api/v1/stats/match/:matchId` | Match details |
| GET | `/api/v1/stats/match/:matchId/timeline` | Match timeline |
| GET | `/api/v1/stats/server/:serverId` | Server stats |
| GET | `/api/v1/stats/live` | Active matches |

### Tournaments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/tournaments` | List tournaments |
| POST | `/api/v1/tournaments` | Create tournament |
| GET | `/api/v1/tournaments/:id` | Tournament details |
| PUT | `/api/v1/tournaments/:id` | Update tournament |
| GET | `/api/v1/tournaments/:id/bracket` | Tournament bracket |
| GET | `/api/v1/tournaments/:id/standings` | Standings (Swiss/RR) |
| POST | `/api/v1/tournaments/:id/register` | Register for tournament |
| POST | `/api/v1/tournaments/:id/checkin` | Check in for tournament |

### Auth

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/device/init` | Start device auth flow |
| POST | `/api/v1/auth/device/poll` | Poll for token |
| POST | `/api/v1/auth/verify` | Verify token |
| GET | `/api/v1/auth/discord` | Discord OAuth |
| GET | `/api/v1/auth/discord/callback` | Discord callback |
| GET | `/api/v1/auth/steam` | Steam OpenID |
| GET | `/api/v1/auth/steam/callback` | Steam callback |
| POST | `/api/v1/auth/claim/init` | Init identity claim |
| POST | `/api/v1/auth/claim/verify` | Verify claim code |

## Configuration

Environment variables:

```bash
# Server
PORT=8080

# Databases
POSTGRES_URL=postgres://user:pass@localhost:5432/mohaa_stats
CLICKHOUSE_URL=clickhouse://localhost:9000/mohaa_stats
REDIS_URL=redis://localhost:6379/0

# Worker Pool
WORKER_COUNT=8
WORKER_QUEUE_SIZE=50000
WORKER_BATCH_SIZE=1000
WORKER_FLUSH_INTERVAL=1s

# Auth
JWT_SECRET=your-secret-key
DISCORD_CLIENT_ID=xxx
DISCORD_CLIENT_SECRET=xxx
STEAM_API_KEY=xxx
```

## Event Types

The tracker.scr script sends these event types:

### Match Lifecycle
- `match_start` - Match begins
- `match_end` - Match ends
- `round_start` - Round begins
- `round_end` - Round ends
- `heartbeat` - Periodic server state

### Combat
- `kill` - Player killed another
- `death` - Player died
- `damage` - Player damaged another
- `weapon_fire` - Weapon discharged
- `weapon_hit` - Projectile hit target
- `headshot` - Headshot kill
- `reload` - Weapon reloaded
- `weapon_change` - Switched weapons
- `grenade_throw` - Grenade thrown
- `grenade_explode` - Grenade detonated

### Movement
- `jump` - Player jumped
- `land` - Player landed
- `crouch` - Crouch state change
- `prone` - Prone state change
- `distance` - Distance traveled summary

### Session
- `connect` - Player connected
- `disconnect` - Player disconnected
- `spawn` - Player spawned
- `team_change` - Team changed
- `chat` - Chat message

## Worker Pool Pattern

Events are processed asynchronously using a buffered worker pool:

1. **Ingest Handler** receives event, enqueues to buffered channel
2. **Returns 202 Accepted** immediately (fast response)
3. **Workers** (N goroutines) pull from channel, accumulate batches
4. **Batch Insert** when batch is full OR flush interval expires
5. **Load Shedding** - returns 429 if queue is full

## Database Schemas

### ClickHouse (OLAP)
- `raw_events` - All game events with TTL
- `player_kills_hourly_mv` - Hourly kill aggregates
- `player_stats_daily_mv` - Daily player stats
- `weapon_stats_mv` - Weapon usage stats
- `kill_heatmap_mv` - Spatial kill data

### PostgreSQL (OLTP)
- `users` - User accounts (Discord/Steam OAuth)
- `user_identities` - Links users to game GUIDs
- `servers` - Registered game servers
- `tournaments` - Tournament definitions
- `tournament_matches` - Bracket matches
- `achievements` - Achievement definitions
- `player_achievements` - Unlocked achievements

## Monitoring

- **Prometheus** metrics at `/metrics`
- **Grafana** dashboards at `:3000`

Key metrics:
- `mohaa_events_ingested_total` - Events received
- `mohaa_events_processed_total` - Events written to ClickHouse
- `mohaa_worker_queue_depth` - Current queue size
- `mohaa_batch_insert_duration_seconds` - Insert latency
- `mohaa_events_load_shed_total` - Events dropped

## Game Server Setup

1. Copy `tracker.scr` to your server's `global/` folder
2. Configure API URL and server token at top of file
3. Add `exec global/tracker.scr` to your server.cfg

Example in tracker.scr:
```c
local.api_url = "https://your-api.com/api/v1/ingest/events"
local.server_token = "your-server-token-here"
```

## License

MIT

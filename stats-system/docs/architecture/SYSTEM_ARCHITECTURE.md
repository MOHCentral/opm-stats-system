# 🏗️ System Architecture - OpenMOHAA Stats

> **High-Performance Competitive Statistics Infrastructure**

---

## 📊 Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                                GAME LAYER                                        │
│                                                                                  │
│   ┌─────────────────────────────────────────────────────────────────────────┐   │
│   │                     OpenMOHAA Game Servers                               │   │
│   │   ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │   │
│   │   │  Server 1   │  │  Server 2   │  │  Server 3   │  │  Server N   │    │   │
│   │   │ (US East)   │  │ (EU West)   │  │ (Asia)      │  │  (...)      │    │   │
│   │   └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘    │   │
│   │          │                │                │                │           │   │
│   │          └────────────────┴────────────────┴────────────────┘           │   │
│   │                                    │                                     │   │
│   │                          tracker.scr                                     │   │
│   │                    (Morpheus Event Hooks)                                │   │
│   └────────────────────────────────────┬────────────────────────────────────┘   │
│                                        │ HTTP POST                               │
│                                        │ (URL-encoded events)                    │
└────────────────────────────────────────┼────────────────────────────────────────┘
                                         │
                                         ▼
┌────────────────────────────────────────────────────────────────────────────────┐
│                                 API LAYER                                        │
│                                                                                  │
│   ┌─────────────────────────────────────────────────────────────────────────┐   │
│   │                        Go Stats API (:8080)                              │   │
│   │                                                                          │   │
│   │   ┌───────────────────────────────────────────────────────────────────┐ │   │
│   │   │                      INGEST PIPELINE                               │ │   │
│   │   │                                                                    │ │   │
│   │   │   ┌──────────┐    ┌────────────────┐    ┌──────────────────────┐  │ │   │
│   │   │   │ Ingest   │───▶│  Worker Pool   │───▶│   Batch Insert       │  │ │   │
│   │   │   │ Handler  │    │  (Buffered)    │    │   (ClickHouse)       │  │ │   │
│   │   │   └──────────┘    │  50K capacity  │    │   1000 rows/batch    │  │ │   │
│   │   │        │          └────────────────┘    └──────────────────────┘  │ │   │
│   │   │        │                 │                                        │ │   │
│   │   │   202 Accepted          ▼                                        │ │   │
│   │   │   (fast return)   Side Effects                                   │ │   │
│   │   │                   ┌──────────────┐                               │ │   │
│   │   │                   │ Redis State  │ ◀─── Live match state         │ │   │
│   │   │                   │ Achievement  │      Achievement triggers     │ │   │
│   │   │                   │ Checks       │      Session management       │ │   │
│   │   │                   └──────────────┘                               │ │   │
│   │   └───────────────────────────────────────────────────────────────────┘ │   │
│   │                                                                          │   │
│   │   ┌───────────────────────────────────────────────────────────────────┐ │   │
│   │   │                      QUERY ENDPOINTS                               │ │   │
│   │   │                                                                    │ │   │
│   │   │   /api/v1/stats/leaderboard     → ClickHouse aggregations         │ │   │
│   │   │   /api/v1/stats/player/:guid    → ClickHouse + PostgreSQL         │ │   │
│   │   │   /api/v1/stats/match/:id       → ClickHouse                      │ │   │
│   │   │   /api/v1/tournaments           → PostgreSQL                      │ │   │
│   │   │   /api/v1/auth/*                → PostgreSQL + Redis              │ │   │
│   │   │                                                                    │ │   │
│   │   └───────────────────────────────────────────────────────────────────┘ │   │
│   └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
└─────────────────────────┬────────────────────────────────┬──────────────────────┘
                          │                                │
          ┌───────────────┼───────────────┐   ┌────────────┼────────────┐
          │               │               │   │            │            │
          ▼               ▼               ▼   ▼            ▼            ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                               DATA LAYER                                         │
│                                                                                  │
│   ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐     │
│   │    ClickHouse       │  │     PostgreSQL      │  │       Redis         │     │
│   │    (OLAP)           │  │     (OLTP)          │  │      (Cache)        │     │
│   │                     │  │                     │  │                     │     │
│   │  • raw_events       │  │  • users            │  │  • Live match state │     │
│   │  • player_kills_mv  │  │  • user_identities  │  │  • Session tokens   │     │
│   │  • player_stats_mv  │  │  • tournaments      │  │  • Rate limiting    │     │
│   │  • weapon_stats_mv  │  │  • tournament_matches│ │  • Leaderboard cache│     │
│   │  • kill_heatmap_mv  │  │  • achievements     │  │  • Hot player stats │     │
│   │  • map_stats_mv     │  │  • player_achieves  │  │                     │     │
│   │                     │  │  • teams            │  │                     │     │
│   │  90-day TTL         │  │  • team_members     │  │  1-60 second TTL    │     │
│   │  Columnar storage   │  │  • servers          │  │  In-memory speed    │     │
│   │  Real-time inserts  │  │  • brackets         │  │                     │     │
│   └─────────────────────┘  └─────────────────────┘  └─────────────────────┘     │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              PRESENTATION LAYER                                  │
│                                                                                  │
│   ┌─────────────────────────────────────────────────────────────────────────┐   │
│   │                      SMF Forum (:8888)                                   │   │
│   │                                                                          │   │
│   │   ┌─────────────────────────────────────────────────────────────────┐   │   │
│   │   │                  MohaaPlayers.php (Sources/)                     │   │   │
│   │   │                                                                  │   │   │
│   │   │   Actions:                                                       │   │   │
│   │   │   • mohaadashboard  → War Room overview                         │   │   │
│   │   │   • mohaaleaderboard → Player rankings                          │   │   │
│   │   │   • mohaaplayer     → Individual player stats                   │   │   │
│   │   │   • mohaamatches    → Match history                             │   │   │
│   │   │   • mohaamaps       → Map statistics                            │   │   │
│   │   │   • mohaaservers    → Server browser                            │   │   │
│   │   │   • mohaaachievements → Medal cabinet                           │   │   │
│   │   │   • mohaatournaments → Tournament system                        │   │   │
│   │   │   • mohaaclaims     → Identity linking                          │   │   │
│   │   └─────────────────────────────────────────────────────────────────┘   │   │
│   │                                                                          │   │
│   │   ┌─────────────────────────────────────────────────────────────────┐   │   │
│   │   │                 Templates (Themes/default/)                      │   │   │
│   │   │                                                                  │   │   │
│   │   │   • MohaaDashboard.template.php                                  │   │   │
│   │   │   • MohaaLeaderboard.template.php                               │   │   │
│   │   │   • MohaaPlayer.template.php                                    │   │   │
│   │   │   • MohaaMatches.template.php                                   │   │   │
│   │   │   • MohaaMaps.template.php                                      │   │   │
│   │   │   • MohaaServers.template.php                                   │   │   │
│   │   │   • MohaaAchievements.template.php                              │   │   │
│   │   │   • MohaaTournaments.template.php                               │   │   │
│   │   │                                                                  │   │   │
│   │   │   Visualization Libraries:                                       │   │   │
│   │   │   • ApexCharts (gauges, bars, lines, heatmaps)                  │   │   │
│   │   │   • HTMX (dynamic partial updates)                              │   │   │
│   │   │   • Custom CSS (Command & Control theme)                        │   │   │
│   │   └─────────────────────────────────────────────────────────────────┘   │   │
│   └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Data Flow: Event Lifecycle

### 1. Event Generation (Game Server)
```
Player kills enemy
    ↓
Engine calls G_ScriptEvent("player_kill", attacker, victim, weapon, hitloc, mod)
    ↓
tracker.scr handler receives event
    ↓
Handler formats URL-encoded data
    ↓
curl_post sends HTTP POST to API
```

### 2. Event Ingestion (API)
```
POST /api/v1/ingest/events
    ↓
Ingest handler validates & enriches
    ↓
Event pushed to buffered channel (50K capacity)
    ↓
HTTP 202 Accepted returned immediately (< 10ms)
    ↓
Worker pulls from channel
    ↓
Batch accumulates (max 1000 events or 1s timeout)
    ↓
Batch INSERT to ClickHouse
```

### 3. Real-Time Processing
```
Parallel to batch insert:
    ↓
Redis state updates
    • Current match scoreboard
    • Player session state
    • Live kill feed
    ↓
Achievement triggers checked
    • "100 kills" milestone
    • "Headshot streak" in progress
    • "Map mastery" completion
    ↓
If triggered → PostgreSQL INSERT
```

### 4. Query Path
```
User visits ?action=mohaaleaderboard
    ↓
MohaaPlayers.php handles action
    ↓
Calls Go API /api/v1/stats/leaderboard
    ↓
API checks Redis cache (5-60s TTL)
    ↓
Cache miss → ClickHouse query
    SELECT player_guid, SUM(kills), SUM(deaths)
    FROM player_stats_daily_mv
    GROUP BY player_guid
    ORDER BY sum_kills DESC
    ↓
Result cached in Redis
    ↓
JSON returned to PHP
    ↓
Template renders with ApexCharts
```

---

## 💾 Database Schemas

### ClickHouse: raw_events
```sql
CREATE TABLE raw_events (
    event_id UUID DEFAULT generateUUIDv4(),
    event_type LowCardinality(String),
    event_time DateTime64(3),
    server_id LowCardinality(String),
    match_id String,
    round_number UInt8,
    
    -- Player 1 (actor)
    player_guid String,
    player_name String,
    player_team LowCardinality(String),
    player_pos_x Float32,
    player_pos_y Float32,
    player_pos_z Float32,
    player_stance LowCardinality(String),
    player_health UInt8,
    
    -- Player 2 (target, optional)
    target_guid Nullable(String),
    target_name Nullable(String),
    target_pos_x Nullable(Float32),
    target_pos_y Nullable(Float32),
    target_pos_z Nullable(Float32),
    
    -- Event-specific data
    weapon LowCardinality(Nullable(String)),
    damage Nullable(UInt16),
    hitloc LowCardinality(Nullable(String)),
    distance Nullable(Float32),
    
    -- Metadata
    map_name LowCardinality(String),
    game_mode LowCardinality(String)
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (event_type, event_time, player_guid)
TTL event_time + INTERVAL 90 DAY;
```

### ClickHouse: Materialized Views
```sql
-- Player daily aggregates
CREATE MATERIALIZED VIEW player_stats_daily_mv
ENGINE = SummingMergeTree()
ORDER BY (player_guid, date)
AS SELECT
    player_guid,
    toDate(event_time) as date,
    countIf(event_type = 'kill') as kills,
    countIf(event_type = 'death') as deaths,
    countIf(event_type = 'headshot') as headshots,
    sumIf(damage, event_type = 'damage') as damage_dealt,
    countIf(event_type = 'weapon_fire') as shots_fired,
    countIf(event_type = 'weapon_hit') as shots_hit
FROM raw_events
GROUP BY player_guid, date;

-- Weapon stats
CREATE MATERIALIZED VIEW weapon_stats_mv
ENGINE = SummingMergeTree()
ORDER BY (player_guid, weapon, date)
AS SELECT
    player_guid,
    weapon,
    toDate(event_time) as date,
    countIf(event_type = 'kill') as kills,
    countIf(event_type = 'headshot') as headshots,
    countIf(event_type = 'weapon_fire') as shots_fired,
    countIf(event_type = 'weapon_hit') as shots_hit
FROM raw_events
WHERE weapon IS NOT NULL
GROUP BY player_guid, weapon, date;

-- Kill heatmap
CREATE MATERIALIZED VIEW kill_heatmap_mv
ENGINE = SummingMergeTree()
ORDER BY (map_name, grid_x, grid_y)
AS SELECT
    map_name,
    floor(player_pos_x / 100) as grid_x,
    floor(player_pos_y / 100) as grid_y,
    count() as kill_count
FROM raw_events
WHERE event_type = 'kill'
GROUP BY map_name, grid_x, grid_y;
```

### PostgreSQL: Core Tables
```sql
-- Users (linked to SMF via OAuth or direct)
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    smf_member_id INTEGER UNIQUE,
    discord_id VARCHAR(64) UNIQUE,
    steam_id VARCHAR(64) UNIQUE,
    username VARCHAR(64) NOT NULL,
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW(),
    last_login TIMESTAMP
);

-- Game identity links
CREATE TABLE user_identities (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    player_guid VARCHAR(64) NOT NULL UNIQUE,
    player_name VARCHAR(64),
    linked_at TIMESTAMP DEFAULT NOW(),
    verified BOOLEAN DEFAULT FALSE
);

-- Tournaments
CREATE TABLE tournaments (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    format VARCHAR(32), -- single_elim, double_elim, swiss, round_robin
    game_mode VARCHAR(32),
    max_teams INTEGER,
    team_size INTEGER,
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    status VARCHAR(32) DEFAULT 'draft', -- draft, open, in_progress, completed
    prize_pool DECIMAL(10,2),
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Tournament matches (brackets)
CREATE TABLE tournament_matches (
    id SERIAL PRIMARY KEY,
    tournament_id INTEGER REFERENCES tournaments(id),
    round_number INTEGER,
    match_number INTEGER,
    team1_id INTEGER REFERENCES teams(id),
    team2_id INTEGER REFERENCES teams(id),
    winner_id INTEGER REFERENCES teams(id),
    score_team1 INTEGER,
    score_team2 INTEGER,
    scheduled_time TIMESTAMP,
    played_at TIMESTAMP,
    vod_url VARCHAR(512)
);

-- Teams
CREATE TABLE teams (
    id SERIAL PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    tag VARCHAR(8),
    logo_url VARCHAR(512),
    captain_id INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Achievements
CREATE TABLE achievements (
    id SERIAL PRIMARY KEY,
    code VARCHAR(64) UNIQUE NOT NULL,
    name VARCHAR(128) NOT NULL,
    description TEXT,
    category VARCHAR(32),
    tier INTEGER, -- 1=Bronze, 2=Silver, ... 10=Immortal
    icon_url VARCHAR(512),
    points INTEGER DEFAULT 10,
    hidden BOOLEAN DEFAULT FALSE
);

-- Player achievements
CREATE TABLE player_achievements (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    achievement_id INTEGER REFERENCES achievements(id),
    unlocked_at TIMESTAMP DEFAULT NOW(),
    match_id VARCHAR(64), -- Reference to when it was earned
    UNIQUE(user_id, achievement_id)
);
```

---

## 🔐 Authentication Flow

### Identity Linking (Game → Forum)
```
1. User logs into SMF forum
2. Goes to Profile → Link Game Identity
3. Clicks "Generate Token" 
   → Creates 32-char hex token in smf_mohaa_claims
   → Token expires in 10 minutes
4. User copies token
5. In game, types: /login TOKEN
6. tracker.scr sends POST to ?action=mohaaclaims
   → Token validated
   → Player GUID linked to SMF member in smf_mohaa_identities
7. All future events linked to forum account
```

### Device Auth Flow (No Browser)
```
1. Player types /auth in game
2. Game displays: "Go to mohaa.example.com/link and enter: ABC123"
3. Player opens browser, enters code
4. Logs in with Discord/Steam/Forum credentials
5. API polls until authorized
6. JWT token returned to game
7. Token stored locally, used for future requests
```

---

## ⚡ Performance Considerations

### Ingestion Throughput
- Target: 10,000 events/second sustained
- Worker pool: 8 workers, 50K buffer
- Batch inserts: 1000 rows or 1s timeout
- Load shedding: 429 when queue > 90% full

### Query Performance
- ClickHouse MergeTree: Billions of rows, subsecond queries
- Materialized Views: Pre-aggregated common queries
- Redis caching: 5-60 second TTL for hot data
- PostgreSQL indexes: B-tree on foreign keys, GiST for spatial

### Caching Strategy
| Data | TTL | Cache Key Pattern |
|------|-----|-------------------|
| Leaderboard (global) | 60s | `lb:global:kills` |
| Leaderboard (weekly) | 30s | `lb:weekly:kills:2026-03` |
| Player stats | 10s | `player:{guid}:stats` |
| Live matches | 5s | `live:matches` |
| Achievement list | 300s | `achievements:all` |
| Tournament bracket | 30s | `tournament:{id}:bracket` |

---

## 🐳 Docker Deployment

### docker-compose.yml Structure
```yaml
services:
  api:
    build: ./mohaa-stats-api
    ports:
      - "8080:8080"
    depends_on:
      - clickhouse
      - postgres
      - redis
    environment:
      - CLICKHOUSE_URL=clickhouse://clickhouse:9000/mohaa
      - POSTGRES_URL=postgres://user:pass@postgres:5432/mohaa
      - REDIS_URL=redis://redis:6379/0

  clickhouse:
    image: clickhouse/clickhouse-server:latest
    volumes:
      - clickhouse-data:/var/lib/clickhouse
    ports:
      - "8123:8123"
      - "9000:9000"

  postgres:
    image: postgres:15
    volumes:
      - postgres-data:/var/lib/postgresql/data
    environment:
      POSTGRES_USER: mohaa
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: mohaa

  redis:
    image: redis:7-alpine
    volumes:
      - redis-data:/data

  smf:
    build: ./mohaa-stats-api/smf
    ports:
      - "8888:80"
    depends_on:
      - smf-db
    volumes:
      - smf-data:/var/www/html

  smf-db:
    image: mariadb:10.11
    volumes:
      - smf-db-data:/var/lib/mysql
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: smf
      MYSQL_USER: smf
      MYSQL_PASSWORD: smf_password

  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"

  prometheus:
    image: prom/prometheus:latest
    ports:
      - "9090:9090"
```

---

## 📊 Monitoring & Observability

### Key Metrics (Prometheus)
```
# Ingestion
mohaa_events_ingested_total{event_type}
mohaa_events_processed_total{event_type}
mohaa_events_load_shed_total
mohaa_worker_queue_depth
mohaa_batch_insert_duration_seconds

# Query
mohaa_api_requests_total{endpoint, status}
mohaa_api_request_duration_seconds{endpoint}
mohaa_cache_hits_total{key_pattern}
mohaa_cache_misses_total{key_pattern}

# Database
mohaa_clickhouse_query_duration_seconds
mohaa_postgres_query_duration_seconds
mohaa_redis_operations_total{operation}
```

### Grafana Dashboards
1. **Ingestion Pipeline**: Events/sec, queue depth, batch latency
2. **API Performance**: Request rate, latency percentiles, error rate
3. **Database Health**: Query times, connection pool, storage usage
4. **Player Activity**: Active players, matches in progress, popular maps

---

## 📚 Related Documentation

| Document | Description |
|----------|-------------|
| [STATS_MASTER.md](../stats/STATS_MASTER.md) | 100,000+ metric taxonomy |
| [ADVANCED_ANALYTICS.md](../stats/ADVANCED_ANALYTICS.md) | Micro-telemetry & deep analysis |
| [VISUALIZATIONS.md](../stats/VISUALIZATIONS.md) | UI/UX specifications for charts |
| [CLICKHOUSE_QUERIES.md](./CLICKHOUSE_QUERIES.md) | SQL queries for all analytics |
| [EVENTS.md](../stats/EVENTS.md) | 30 engine event reference |
| [ACHIEVEMENTS.md](../stats/ACHIEVEMENTS.md) | 540+ achievement system |

---

*This document describes the complete system architecture for OpenMOHAA Stats.*
*Last Updated: 2026-01-18*

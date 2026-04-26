# OPM Stats System — Full Audit Report

**Date:** 2026-04-14  
**Scope:** Complete system audit of all components

---

## Executive Summary

The OPM Stats System is a high-throughput competitive statistics platform for Medal of Honor: Allied Assault via OpenMOHAA. It captures real-time game telemetry from dedicated servers, processes 1M+ events/day through a dual-database architecture, and surfaces analytics through both an SMF forum integration and a standalone web dashboard.

**Current architecture: Game Scripts → HTTP POST → Go API → ClickHouse/Postgres/Redis → SMF/Web**

**Target architecture: Game Scripts → MQTT → Go API (MQTT subscriber) → ClickHouse/Postgres/Redis → SMF/Web**

---

## System Components

### 1. opm-stats-api (Go API Server)

**Language:** Go 1.22 | **Router:** Chi v5 | **Port:** 8080 (mapped to 8084)

#### Databases
| Database | Role | Key Tables |
|----------|------|------------|
| PostgreSQL 16 | OLTP | users, servers, login_tokens, tournaments, achievements, user_identities |
| ClickHouse 24 | OLAP | raw_events (ReplacingMergeTree), player_stats_daily, weapon_stats_mv, kill_heatmap_mv |
| Redis 7 | Cache | Rate limiting, real-time state, achievement tracking |

#### Worker Pool Architecture
```
HTTP POST → IngestEvents handler → normalizeRawEventAliases() → pool.Enqueue()
                                                                      ↓
Worker Pool: 8 workers, 10k queue, 500 batch size, 1s flush
                                                                      ↓
Batch Insert → ClickHouse raw_events → Materialized Views auto-aggregate
                                                                      ↓
Achievement Engine → Redis stat tracking → Postgres unlock records
```

#### API Surface (85+ endpoints)
- **Ingestion:** `POST /api/v1/ingest/events`, `/match-result`
- **Auth:** Device code flow, SMF verify, trusted IPs, identity claiming
- **Stats:** Global, player (deep/combat/movement/stance), weapons, maps, gametypes
- **Leaderboards:** 38+ sortable metrics, weapon/map/gametype-specific
- **Advanced:** Predictions, peak performance, playstyle, drilldown, heatmaps
- **Servers:** Live status, player history, peak hours, map rotation, favorites
- **Tournaments:** Brackets, standings, match schedules
- **Achievements:** 100+ definitions, player progress, match/tournament context
- **Frontend SSR:** HTMX partials, page routes for player/leaderboard/match/maps

#### Config (Environment Variables)
```
PORT, POSTGRES_URL, CLICKHOUSE_URL, REDIS_URL
WORKER_COUNT(8), QUEUE_SIZE(10k), BATCH_SIZE(500), FLUSH_INTERVAL(1s)
DEVICE_CODE_TTL(10m), ACCESS_TOKEN_TTL(24h)
RATE_LIMIT_PER_SECOND(100), RATE_LIMIT_BURST(200)
```

### 2. opm-stats-game-scripts (Morpheus Script .scr)

**12 script files** capturing 101+ event types from OpenMOHAA game servers.

#### Script Modules
| File | Events | Description |
|------|--------|-------------|
| tracker.scr | - | Main entry, session init, console commands (login/logout/claim/stats) |
| tracker_common.scr | - | HTTP comms, JSON builders, batching, payload helpers |
| tracker_combat.scr | kill, death, damage, weapon_fire, headshot | Combat tracking |
| tracker_movement.scr | jump, land, crouch, prone, sprint, ladder | Movement tracking |
| tracker_client.scr | connect, disconnect, team_join, vote | Client lifecycle |
| tracker_gameflow.scr | game_init/start/end, round, warmup, intermission | Game state |
| tracker_items.scr | pickup, drop, respawn | Item tracking |
| tracker_interaction.scr | chat, use_object, spectate, freeze | Player interactions |
| tracker_vehicle.scr | vehicle_enter/exit, crash | Vehicle tracking |
| tracker_world.scr | door, explosion | World events |
| tracker_bot.scr | bot_spawn, bot_kill, bot_roam | AI tracking |
| register.scr | - | Server auto-registration |

#### Current Data Flow
```
event_subscribe → handler → init_event() → add_player() → queue_event()
                                                                ↓
Batch queue (max 20 events) → flush_queue every 2s
                                                                ↓
curl_post → POST /api/v1/ingest/events [X-Server-Token]
```

### 3. opm-stats-smf-integration (PHP/SMF 2.1)

**6 plugins** providing forum-integrated stats display:

| Plugin | Purpose |
|--------|---------|
| mohaa_stats_core | Base API client, caching, admin UI |
| mohaa_players | Player profiles, identity linking |
| mohaa_achievements | Achievement/medal system |
| mohaa_servers | Server browser, live status |
| mohaa_teams | Team stats, comparison |
| mohaa_tournaments | Brackets, standings |

**API Client:** `MohaaStatsAPI.php` with parallel curl_multi, caching, error handling.

### 4. opm-stats-web (Standalone Dashboard)
Currently **empty placeholder**. Sync'd from SMF plugins via `tools/sync_web_code.py`.

### 5. opm-stats-docs
`events_reference.md` — 101 event types with signatures and parameters.

### 6. Tools
- `generate_types.py` — Single source of truth: OpenAPI → Go constants, PHP classes, .scr docs, normalization maps
- `sync_web_code.py` — Keeps SMF plugin code synchronized across repos
- `web_sync_manifest.json` — Sync pair definitions

---

## PR #80: MQTT Script Commands (openmohaa C++ Engine)

PR #80 adds native MQTT support to the OpenMOHAA game engine:

### New Script Commands
| Command | Args | Description |
|---------|------|-------------|
| `mqtt_connect` | host port client_id callback [user] [pass] [keepalive] | Async connect to broker |
| `mqtt_disconnect` | [callback] | Disconnect from broker |
| `mqtt_publish` | topic payload [qos] [callback] | Publish message (QoS 0/1) |
| `mqtt_subscribe` | topic message_callback [qos] | Subscribe with handler |
| `mqtt_unsubscribe` | topic | Unsubscribe |
| `mqtt_is_connected` | (none) | Returns 1/0 |

### C++ Architecture
- **MqttClient** (`mqttclient.cpp/h`): Self-contained MQTT 3.1.1 over BSD sockets, no external deps
- **MqttWorker** (`mqttworker.cpp/h`): Background thread with task/result queues
- **ScriptMaster**: Polls MqttWorker results in `ExecuteRunning()`, invokes .scr callbacks
- **ScriptThread**: Registers event handlers for each mqtt_* command
- **CMake**: `USE_MQTT` option (ON by default), `GAME_DEFINITIONS` propagated to targets

### Callback Pattern
```
mqtt_connect → MqttWorker → background connect → callback(success, error)
mqtt_subscribe → MqttWorker → messages polled → callback(topic, payload)
```

---

## Gap Analysis & Recommendations

### What's Missing for MQTT
1. **API side:** No MQTT subscriber — API only accepts HTTP POST
2. **Game scripts:** Still use `curl_post` for all event transmission
3. **Docker:** No MQTT broker (Mosquitto/EMQX) in docker-compose
4. **Config:** No MQTT connection settings in API config
5. **Tests:** No MQTT integration tests

### MQTT Migration Plan
1. Add Mosquitto to docker-compose
2. Add MQTT subscriber to Go API (using paho.mqtt.golang)
3. Convert game scripts from `curl_post` to `mqtt_publish`
4. Keep HTTP ingestion as fallback
5. Add MQTT health checks and metrics

---

## File Inventory

### opm-stats-api
- **cmd/api/main.go** — Server bootstrap, 85+ route definitions
- **cmd/seeder/main.go** — Data seeding utilities
- **internal/config/config.go** — Environment-based configuration
- **internal/db/** — Postgres, ClickHouse, Redis connection setup
- **internal/handlers/** — 15+ handler files by feature domain
- **internal/logic/** — 10+ service files (player stats, weapons, maps, predictions)
- **internal/models/** — 15+ model files (35+ structs)
- **internal/worker/** — Pool (8 workers, batch processing), achievement engine
- **migrations/postgres/** — 5 SQL files (users, servers, tokens, identities, achievements)
- **migrations/clickhouse/** — 4 SQL files (raw_events, materialized views, identity tables)

### opm-stats-game-scripts
- **global/** — 14 .scr files (tracker, combat, movement, client, gameflow, items, interaction, vehicle, world, bot, register, seeder, spawns)
- **EVENT_TYPES.md** — 101 canonical event types
- **JSON_MIGRATION_GUIDE.md** — URL-encoded → JSON conversion guide

### opm-stats-smf-integration
- **smf-plugins/** — 6 PHP plugins with templates, language files, install scripts
- **smf-mohaa/** — Core SMF integration files
- **tests/** — API integration tests

---

*End of audit report.*

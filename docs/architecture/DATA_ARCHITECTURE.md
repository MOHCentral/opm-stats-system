# Data Architecture - OpenMOHAA Stats System

## Overview

This document defines the authoritative data stores for each category of data in the system.

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         GAME SERVERS (tracker.scr)                              │
│                              ↓ HTTP POST                                        │
└─────────────────────────────────────────────────────────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                          GO API SERVER (:8080)                                  │
│                                                                                 │
│  ┌──────────────────────────────────┐    ┌────────────────────────────────┐   │
│  │        EVENT INGESTION           │    │         STATS QUERIES          │   │
│  │  /api/v1/ingest/events           │    │  /api/v1/stats/*               │   │
│  │                ↓                 │    │         ↑                      │   │
│  └──────────────────────────────────┘    └────────────────────────────────┘   │
│                  │                                      │                      │
│                  ▼                                      ▼                      │
│  ┌─────────────────────────────────────────────────────────────────────────┐  │
│  │                         CLICKHOUSE (OLAP)                               │  │
│  │  • raw_events table - 67 game events                                    │  │
│  │  • player_stats_mv - materialized aggregations                          │  │
│  │  • All telemetry data: kills, deaths, movements, interactions           │  │
│  └─────────────────────────────────────────────────────────────────────────┘  │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐  │
│  │                         REDIS (Cache/Real-time)                         │  │
│  │  • live_matches - Current active matches                                │  │
│  │  • login_token:* - Short-lived login tokens                             │  │
│  │  • Session caching                                                      │  │
│  └─────────────────────────────────────────────────────────────────────────┘  │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐  │
│  │                         POSTGRES (OLTP - API)                           │  │
│  │  • login_tokens - Token persistence                                     │  │
│  │  • trusted_ips - IP approval history                                    │  │
│  │  • pending_ip_approvals - Pending requests                              │  │
│  │  • login_token_history - Audit log                                      │  │
│  │  • smf_user_mappings - GUID ↔ SMF member_id mapping                     │  │
│  └─────────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────────┘
                                      
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         SMF FORUM SERVER (:8888)                                │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐  │
│  │                     SMF MARIADB (Player-centric data)                   │  │
│  │                                                                          │  │
│  │  IDENTITY:                                                               │  │
│  │  • smf_members - Forum users (id_member)                                │  │
│  │  • smf_mohaa_player_links - GUID ↔ member_id linking                    │  │
│  │  • smf_mohaa_login_tokens - Game login tokens                           │  │
│  │                                                                          │  │
│  │  TOURNAMENTS:                                                            │  │
│  │  • smf_mohaa_tournaments - Tournament metadata                          │  │
│  │  • smf_mohaa_tournament_registrations - Player registrations            │  │
│  │  • smf_mohaa_tournament_matches - Bracket matches                       │  │
│  │  • smf_mohaa_tournament_admins - Tournament staff                       │  │
│  │                                                                          │  │
│  │  TEAMS:                                                                  │  │
│  │  • smf_mohaa_teams - Team definitions                                   │  │
│  │  • smf_mohaa_team_members - Team rosters                                │  │
│  │  • smf_mohaa_team_invites - Pending invites                             │  │
│  │                                                                          │  │
│  │  ACHIEVEMENTS:                                                           │  │
│  │  • smf_mohaa_achievements - Unlocked achievements                       │  │
│  │  • smf_mohaa_achievement_progress - Progress tracking                   │  │
│  └─────────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

## Data Categories

### 1. Game Events → ClickHouse
**All 67 ScriptDelegate events go to ClickHouse via Go API**

| Event Category | Events |
|----------------|--------|
| Combat | player_kill, player_death, player_damage, weapon_fire, weapon_hit, player_headshot, grenade_throw, grenade_explode |
| Movement | player_jump, player_land, player_crouch, player_prone, player_distance |
| Interaction | ladder_mount, ladder_dismount, item_pickup, item_drop, player_use |
| Session | client_connect, client_disconnect, client_begin, team_join, player_say |
| Vehicle | vehicle_enter, vehicle_exit, vehicle_damage, turret_enter, turret_exit |
| Bot | bot_spawn, bot_death, bot_target_changed |
| Gameflow | match_start, match_end, round_start, round_end, intermission |
| World | door_state_change, explosion, player_spawn |

### 2. Player Data → SMF MariaDB
**All player-related data uses SMF member IDs**

- **Tournaments**: Creation, registration, brackets, results
- **Teams**: Team creation, rosters, invites, match history
- **Identity Linking**: GUID ↔ Forum account mapping
- **Achievements**: Unlocks (criteria checked against ClickHouse, unlocks stored in MariaDB)

### 3. Stats Aggregations → ClickHouse + Redis
**Computed from raw events, cached in Redis**

- Player statistics (kills, deaths, accuracy, etc.)
- Leaderboards (daily, weekly, all-time)
- Weapon statistics
- Map statistics
- Heatmaps

## API Responsibilities

### Go API (`/api/v1/*`)
1. **Event Ingestion**: Receive from game servers → ClickHouse
2. **Stats Queries**: Read from ClickHouse → Return to clients
3. **Authentication**: Token verification for game logins

### SMF Plugins
1. **Tournaments**: Full CRUD via SMF actions
2. **Teams**: Full CRUD via SMF actions
3. **Servers**: Game server management (if needed)
4. **Identity**: Link/unlink GUIDs to forum accounts
5. **Profile Integration**: Show stats from Go API in profile

## Cross-System Integration

### Linking Stats to SMF Users
```
SMF member_id ← smf_mohaa_player_links → player_guid
                                              ↓
                                        ClickHouse raw_events
                                        (actor_id = player_guid)
```

### Tournament Match Results
1. Tournament managed in SMF MariaDB
2. Match played on game server → Events to ClickHouse
3. Optional: Link match_id to tournament match for stats integration

## Notes

- **SMF is the source of truth** for all player-centric data
- **ClickHouse is the source of truth** for all gameplay events
- **Never duplicate** player data between Postgres and MariaDB
- Go API Postgres is only used for auth tokens and user mappings

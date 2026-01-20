# 🤖 AGENTS.md - The Universal Manual

> **Project**: OpenMOHAA Stats System
> **Goal**: Build the most comprehensive FPS stats tracking system ever created (100,000+ metrics).

## 🚨 CRITICAL DIRECTIVES (READ FIRST)

1.  **NO DOCKER FOR SMF DEV**: The SMF Forum (PHP) runs on a **Local Server**, NOT Docker.
    -   **Context**: We edited `smf-mohaa/Sources/` locally. Changes are reflected instantly.
    -   **Forbidden Command**: `docker exec smf-smf-1 ...` (unless explicitly testing container build).
    -   **Correct Command**: `php -l smf-mohaa/Sources/MohaaPlayers.php` (Local syntax check).
2.  **DRILL-DOWN EVERYWHERE**: Every number on the UI must be clickable.
    -   Click "50 Kills" -> Show Breakdown by Weapon/Map.
    -   Click "Weapon" -> Show Weapon Stats.
3.  **MASSIVE SCALE**: Optimize for high cardinality. We track *everything* (angles, velocity, hit-locations).
4.  **HYBRID DESIGN**: Use SMF native containers (`windowbg`, `cat_bar`) mixed with rich visualizations (ApexCharts).

---

## 🏗️ System Architecture

### 1. Game Layer (OpenMOHAA)
-   **Script**: `global/tracker.scr` (Morpheus Script).
-   **Function**: Subscribes to 30 engine events.
-   **Transport**: Sends standard HTTP POST to API.

### 2. API Layer (Go)
-   **Service**: `cmd/api/` (Port 8080).
-   **Pattern**: Ingest Handler -> Buffered Channel -> Worker Pool -> Batch Insert.
-   **Databases**:
    -   **ClickHouse (OLAP)**: `raw_events`, Materialized Views for aggregates.
    -   **PostgreSQL (OLTP)**: Users, Tournaments, Achievements.
    -   **Redis**: Real-time state, caching.

### 3. Frontend Layer (SMF Forum)
-   **Tech**: PHP 8.x, Simple Machines Forum 2.1.
-   **Plugin Source**: `smf-mohaa/Sources/` (Single Source of Truth).
-   **Templates**: `smf-mohaa/Themes/default/`.
-   **Visualization**: ApexCharts.js + HTMX.

---

## 🛠️ Development Workflows

### SMF / PHP Development (Local)
-   **Editing**: Edit files in `smf-mohaa/`.
-   **Testing**: Refresh browser (`localhost:8888` or local vhost).
-   **Debugging**:
    -   Syntax Check: `php -l <file>`
    -   Logs: Check local Apache/Nginx error logs.
    -   **Do not use Docker for this loop.**

### Go API Development (Docker/Local)
-   **Run**: `go run ./cmd/api` (Local) or `docker-compose up -d api` (Docker).
-   **Deps**: `docker-compose up -d postgres clickhouse redis` (Required).

---

## 📂 Key File Locations

| Component | Path | Notes |
| :--- | :--- | :--- |
| **SMF Sources** | `smf-mohaa/Sources/` | Edit PHP logic here. |
| **SMF Templates** | `smf-mohaa/Themes/default/` | Edit HTML/JS here. |
| **Game Tracker** | `global/tracker.scr` | Event capture script. |
| **Go API** | `cmd/api/` | Main server entry point. |
| **Documentation** | `docs/` | Detailed Reference (See below). |

---

## 📚 Documentation Reference (In `docs/`)

-   **`docs/stats/STATS_MASTER.md`**: Definition of all 100,000+ metrics.
-   **`docs/EVENT_DOCUMENTATION.md`**: Parameters for all 30 engine events.
-   **`docs/stats/ACHIEVEMENTS.md`**: Achievement tier system design.
-   **`docs/architecture/CLICKHOUSE_QUERIES.md`**: Common OLAP queries.

---

## 🧪 Coding Standards

### PHP (SMF)
-   Use `$context` to pass data to templates.
-   Use `loadTemplate('TemplateName')`.
-   Follow Hook Pattern: `integrate_actions` mapped in `smf_settings`.
-   **Secure Hashing**: `password_hash(strtolower($username) . $password, PASSWORD_BCRYPT)`.

### Go (API)
-   Idiomatic Go (Effective Go).
-   Strong typing for all event payloads.
-   Use `sqlx` for Postgres, `clickhouse-go` for ClickHouse.

### Morpheus Script (`.scr`)
-   **Event Subs**: `event_subscribe "event_name" "callback_name"`.
-   **Command Reg**: `registercmd "name" "callback"`.
-   **Variables**: Use `local.` scope for temporary vars.

#### Scripting Example
```morpheus
// Subscribe to kill event
event_subscribe "player_kill" "tracker.scr::handle_kill"

handle_kill local.attacker local.victim local.inflictor local.hitloc local.mod:
    println ("Kill: " + local.attacker.netname + " -> " + local.victim.netname)
end
```

---

## ✅ Project Status (Snapshot)

-   **Architecture**: Complete.
-   **API**: Healthy (Ingest, Workers, DBs connected). response < 10ms.
-   **SMF Plugin**: Core working. Player/Dashboard pages optimized (Parallel Curl).
-   **Next Steps**:
    1.  Restart Docker containers (fix stale mounts).
    2.  Data Seeder.
    3.  Achievement Logic Implementation.
    3.  Achievement Logic Implementation (See `Feature Concepts`).
    4.  Tournament Brackets.

---

## 🧩 Component Details

### SMF Plugins (`smf-plugins/`)
-   **Structure**:
    -   `mohaa_stats_core/`: API client, base definitions.
    -   `mohaa_stats_profile/`: Profile tabs.
    -   `mohaa_stats_leaderboards/`: Ranking pages.
    -   `mohaa_stats_heatmaps/`: Canvas/JS visualizations.
-   **Config**: Settings mapped in Admin → Configuration → MOHAA Stats.

### API Server (`api-server/`)
-   **Endpoints**:
    -   `POST /events`: Ingest 30 atomic events.
    -   `GET /health`: Status check.
-   **Payload**: `{ client_id, event_type, timestamp, ...args }`

---

## 🔮 Future Feature Concepts (The "War Room")

### 1. Team System (Single Allegiance)
-   **Rule**: Players can join **only one** team.
-   **Impact**: Team stats aggregated from members. Tournaments are team-based.

### 2. Match-Specific Achievements
-   **Concept**: Non-cumulative unlocks (e.g., "5 Headshots _this_ match").
-   **Usage**: Provide short-loop feedback during tournaments.

### 3. Peak Performance Analytics
-   **Temporal**: "You are 20% more lethal between 20:00-23:00".
-   **Contextual**: "Best Map: V2 Rocket (+12% Win Rate)".
-   **Drill-Down**: Click any stat (K/D) -> Break down by Map/Weapon/Time.

### 4. Combo Metrics
-   **Run & Gun Index**: % of kills while moving velocity > 100.
-   **Clutch Factor**: Win rate when HP < 25%.
-   **Medkit Efficiency**: Survival time after pickup.

*This file acts as the primary instruction set for Copilot, Claude, Gemini, and Antigravity.*

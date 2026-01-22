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
-   **Role**: **Event Dump/Ingestion ONLY**.
-   **Restriction**: Does NOT store player profiles/metadata.
-   **Pattern**: Ingest Handler -> Buffered Channel -> Worker Pool -> Batch Insert.
-   **Databases**:
    -   **ClickHouse (OLAP)**: `raw_events`, Materialized Views for aggregates.
    -   **PostgreSQL (OLTP)**: Users, Tournaments, Achievements.
    -   **Redis**: Real-time state, caching.

### 3. Frontend Layer (SMF Forum)
-   **Tech**: PHP 8.x, Simple Machines Forum 2.1.
-   **Role**: **Player Data Authority** (Profiles, stats tables, identity linking).
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
-   **`docs/Game_Module_Classes.htm`**: Complete Game Module Classes Reference.

## 📚 Official OpenMOHAA Documentation (External)

-   **Main Site**: [docs.openmohaa.org](https://docs.openmohaa.org/)
-   **Scripting Events**: [Script Events Reference](https://docs.openmohaa.org/md_docs_2markdown_204-coding_202-scripting_201-script-events.html) - *Critical for `tracker.scr`*
-   **Code Documentation**: [Coding Reference](https://docs.openmohaa.org/md_docs_2markdown_204-coding_202-coding.html)
-   **Server Config**: [Server Configuration](https://docs.openmohaa.org/md_docs_2markdown_203-configuration_202-configuration-server.html)

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

## 🔐 Player Identity Resolution System

### The Problem
When Player A (logged in) kills Player B (not logged in), we capture A's SMF ID but not B's.
Later, when B logs in, how do we link their historical stats?

### The Solution: GUID as Stable Key
The game GUID is permanent and unique per player. We use it as the linking key.

### Identity Flow
```
1. Player types /login <token> in-game
2. tracker.scr → POST /auth/smf-verify {token, guid, name}
3. API verifies token, stores GUID → SMF ID in player_guid_registry
4. Player entity gets: is_authenticated=1, smf_member_id=X
5. All events now include smf_id for this player
6. Other players (not logged in) have smf_id=0 but GUID is captured
7. When they log in later, we can resolve past events via GUID
```

### Key Files
| File | Purpose |
|------|---------|
| `global/tracker_common.scr` | `build_player_payload` sends guid + smf_id |
| `internal/logic/identity.go` | Go identity resolver with caching |
| `internal/models/events.go` | RawEvent has PlayerSMFID, AttackerSMFID, VictimSMFID |
| `smf-mohaa/Sources/MohaaStats/MohaaIdentityResolver.php` | PHP helper for profile links |

### Database Tables
| Database | Table | Purpose |
|----------|-------|---------|
| **Postgres** | `player_guid_registry` | Authoritative GUID → SMF ID (source of truth) |
| **Postgres** | `player_name_aliases` | All names used by each GUID |
| **Postgres** | `unverified_players` | GUIDs seen but not yet linked |
| **ClickHouse** | `raw_events.actor_smf_id` | SMF ID at event time (0 if unknown) |
| **ClickHouse** | `player_guid_registry` | Fast lookup copy for analytics |
| **SMF MySQL** | `mohaa_identities` | Forum's view of linked accounts |

### PHP Usage Example
```php
require_once($sourcedir . '/MohaaStats/MohaaIdentityResolver.php');

// Resolve multiple GUIDs in one query
$guids = ['abc123', 'xyz789'];
$mapping = MohaaIdentityResolver::resolveGuids($guids);
// Returns: ['abc123' => 42, 'xyz789' => 0]

// Build profile links
$events = $api->getRecentKills();
$events = MohaaIdentityResolver::enrichEventsWithProfiles($events);
// Each event now has actor_link, target_link with HTML
```

### Morpheus Script Usage
```morpheus
// build_player_payload automatically includes smf_id if authenticated
local.payload = tracker_common.scr::build_player_payload local.player "attacker"
// Result: &attacker_name=Elgan&attacker_guid=abc123&attacker_smf_id=42&attacker_team=allies
```

---

## 🔮 Future Feature Concepts (The "War Room")

### 1. Team System (Single Allegiance)
-   **Rule**: Players can join **only one** team.
-   **Impact**: Team stats aggregated from members. Tournaments are team-based.
-   **Team Leaderboard Widgets**: (Click any widget -> Detailed Team Leaderboard)
    -   **Dominance**: Win/Loss Ratio, Avg Placement, Flawless Victories (Zero Deaths), Comeback Kings, Fastest Win.
    -   **Aggression**: K/D Spread (Team Kills - Deaths), Damage Per Minute (DPM), Wolf Pack (Assist % on Kills).
    -   **Economy**: Gold Hoarded, Resource Deniers, Support Points (Heals/Shields).
    -   **Shade**: Friendly Fire Incidents ("Most Reckless"), Damage Taken Survived ("Bullet Magnets"), Bot Hunters.
    -   **Consistency**: Longest Win Streak, Rivalry Record (vs Top 5), Veteran Status (Total Hours).

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

### 5. Tournament Ecosystem
-   **Format**: Supports both **Team** and **FFA** (Free-For-All) brackets.
-   **General Stats**:
    -   **Giant Killer**: Lowest seed defeating highest seed.
    -   **Survival Clock**: Total time spent alive in-match.
    -   **Upset Frequency**: % of lower seed victories.
    -   **Prize Pool Efficiency**: Winnings per minute played.
-   **Team Stats**:
    -   **Avg Margin of Victory**: Point difference (Dominance).
    -   **Synergy**: Assists per Kill (Wolf Pack rating).
    -   **First Strike**: % of matches getting First Blood/Objective.
    -   **Iron Wall**: Fewest deaths conceded in bracket.
    -   **Comeback (Gyakuten)**: Wins after trailing at midpoint.
-   **FFA Stats**:
    -   **King of the Hill**: Duration holding #1 spot.
    -   **Vengeance Ratio**: Kills on players who last killed you.
    -   **Third-Party King**: Final blows on engaged targets.
    -   **Ghost Award**: Deepest run with fewest engagements.
-   **Meta & Scheduling**:
    -   **Meta Breaker**: Highest rank with non-meta loadout.
    -   **Prime Time**: Heatmap of match activity hours.
    -   **Forfeit Rate**: Tracking no-shows.

### 6. Stats & Analytics (The "When" Engine)
**Analysis Philosophy**: "When are you best?" vs "When are you worst?"

-   **Drill-Down Everything**: Clicking any stat (e.g., "Accuracy") explodes into time-based and map-based graphs.
-   **Granular "When" Questions**:
    -   *When* most accurate? (Hour of day, specific weapon, specific map).
    -   *When* most wins? (Team composition, Server).
    -   *When* most loses? (After 2 hours play session - fatigue).
    -   *When* most team wins? (With specific clan mates).
    -   *When* most rounds played? (Weekend vs Weekday).
    -   *When* most objective completed? (Attacking side vs Defending).

### 7. Event Dictionary (Atomic Data)
These are the raw events ingested by the API.

#### Combat Events
| Event | Description | Parameters |
|-------|-------------|------------|
| `player_kill` | Elimination | attacker, victim, inflictor, loc, mod |
| `player_death` | Death | player, inflictor |
| `player_damage` | Taken damage | player, attacker, damage, mod |
| `player_pain` | Pain reaction | player, attacker, damage, loc |
| `player_headshot` | Headshot | attacker, victim, weapon |
| `player_suicide` | Self-kill | player |
| `player_bash` | Melee kill | attacker, victim |
| `weapon_fire` | Fired shot | owner, weapon, ammo |
| `weapon_hit` | Bullet impact | owner, target, loc |
| `grenade_throw` | Nade toss | owner, projectile |

#### Movement & Interaction
| Event | Description | Parameters |
|-------|-------------|------------|
| `player_jump` | Jumped | player |
| `player_crouch` | Crouched | player |
| `player_prone` | Proned | player |
| `player_distance` | Moved dist | player, walked, sprinted, swam |
| `item_pickup` | Looted item | player, item, amount |
| `ladder_mount` | Ladder use | player, ladder |

#### Vehicle & World
| Event | Description | Parameters |
|-------|-------------|------------|
| `vehicle_enter` | Driving | player, vehicle |
| `vehicle_death` | Vehicle kill | vehicle, attacker |
| `turret_enter` | Gunner | player, turret |
| `door_open` | Interaction | door, activator |
| `objective_update`| Game state | index, status |

#### Game Flow
| Event | Description | Parameters |
|-------|-------------|------------|
| `round_start` | Round loop | (none) |
| `team_win` | Team victory | teamnum |
| `client_connect` | Player join | clientNum |
| `bot_killed` | PvE kill | bot, attacker |

### 8. Advanced Analytics & Server Health
**Goal**: Move beyond simple counters to "Player DNA" and "Behavioural Patterns".

#### A. Main Server "Global Pulse"
Macro-stats showing the "chaos level" of the server.
-   **Server Lethality**: Total `player_kill` / `game_start`. (Bloodiness rating).
-   **Lead Exchange Rate**: How often the #1 spot changes hands.
-   **Meat Grinder Zone**: Heatmap of `player_death` events.
-   **Total Lead Poured**: Sum of all `weapon_hit` events (Engagement volume).
-   **Revolving Door**: Peak hours for `client_connect` vs `disconnect`.

#### B. Deep-Dive Player DNA
Combinations comprising a player's profile.
-   **Surgical (Precision)**:
    -   *Headhsot Efficiency*: `headshot` / `weapon_hit` %.
    -   *One-Tap King*: Kills with exactly 1 `weapon_fire`.
    -   *Trigger Discipline*: High Hit/Fire ratio.
-   **Unstoppable (Aggression)**:
    -   *Road Warrior*: `vehicle_kill` + `vehicle_enter` time.
    -   *The Juggernaut*: Damage taken without dying.
    -   *Aggression Factor*: Distance moved towards enemies / Time alive.
-   **Survivalist (Tactics)**:
    -   *The Ghost*: Distance moved with 0 damage taken.
    -   *Ledge Camper*: Kills while `ladder_mount` or high Z-axis.
    -   *The Scavenger*: `item_pickup` frequency when HP < 10%.

#### C. Creative "Stat Cards" (Badges)
| Card Name | Logic | Description |
| :--- | :--- | :--- |
| **Deadly Mechanic** | `vehicle_exit` + `kill` < 3s | Bail & Kill. |
| **The Janitor** | `kill` on low HP victim | Cleaning up. |
| **Olympic Sprinter** | `jump` + `sprint` dist | Constant movement. |
| **The Spiteful** | `say` + `kill` < 2s | "Chat & Smack". |
| **Iron Sights** | `weapon_ready` + `headshot` | Quick-scope style. |
| **Denied** | `kill` vs victim on `objective` | Stopping the win. |

#### D. Analytical Drill-Downs
-   **"Most Deaths" Click**:
    -   *By MOD*: Pie chart (Crushed vs Shot vs Bash).
    -   *Nemesis*: Who killed you most?
    -   *Last Words*: Last chat message before death.
-   **"Total Distance" Click**:
    -   *Travel Mode*: Walk vs Sprint vs Swim vs Drive.
    -   *Verticality*: Ladder usage vs Jumping.

    -   **Micro-interactions**: Subtle feedback when clicking drill-downs.

### 10. The "Widget Library" (Creative Stats)
**Concept**: A collection of fun, high-engagement stats derived from cross-referencing events.

#### A. Combat & Arsenal
-   **The Artilleryman**: `grenade_kill` / `grenade_throw` (Efficiency).
-   **Shin-Buster**: High frequency of `player_pain` in `lower_legs`.
-   **Peek-a-Boo**: `crouch` + `weapon_fire` (Tactical firing).
-   **Turret Terror**: Kills while `turret_enter` is active.
-   **Iron Fists**: Wins in `bash` vs `bash` duels.
-   **Leap Frog**: Kills while airborne (`jump` -> `land`).

#### B. Survival & Movement
-   **Door Camper**: Kills within 2s of `door_open` (Ambush).
-   **Pacifist Run**: Top placement with 0 `player_kill` (Scavenging).
-   **Loot Goblin**: Total `item_pickup` count (Hoarding).
-   **Escapist**: High `distance` traveled while HP < 20%.

#### C. Social & World
-   **Chatty Cathy**: High `player_say` frequency (Social Score).
-   **Window Shopper**: Time spent in `spectate` (Observing).
-   **Bot Bully**: High Ratio of `bot_killed` vs `player_killed`.
-   **Medic?**: `health_pickup` (Damage Healed) > Damage Taken (Net Positive).

#### D. Interactive UX Concepts
-   **Kill Feed Timeline**: A draggable slider below the match summary.
    -   *Interaction*: Drag to replay kills/deaths on the mini-map.
-   **Heatmap Shift**: A slider to see how hotzones move from "Early Game" to "Late Game".
-   **Nemesis Web**: A spider chart showing who killed you (Nodes) and how (Thickness = frequency).

*This file acts as the primary instruction set for Copilot, Claude, Gemini, and Antigravity.*

### 9. Visualization & UX Strategy
**Core Philosophy**: "Data is Alive". Static tables are forbidden for detailed stats.

-   **Mandatory Interactive Elements**:
    -   **Spider/Radar Charts**: Required for "Player DNA" (compare Agility vs Accuracy vs Tactics).
    -   **Heatmaps**: Required for "Meat Grinder" (Map coords) and "Prime Time" (Time of day).
    -   **Interactive Line Graphs**: For "consistency" metrics (K/D over last 30 days).
-   **UX Principles**:
    -   **Hover-to-Reveal**: Clean UI by default, data-heavy on interaction.
    -   **Animation**: Bars fill up, numbers tick up.
    -   **Micro-interactions**: Subtle feedback when clicking drill-downs.

IN SMF - 
you could modify the avatar to show the rank 

Always add player related data to the smf database
Always automatically run install and migration scripts for me

Weapons (MohaaStats_Weapons, MohaaStats_WeaponDetail)
Maps (MohaaStats_Maps, MohaaStats_MapDetail, MohaaStats_MapLeaderboard)
GameTypes (MohaaStats_GameTypes, MohaaStats_GameTypeDetail)
Players (MohaaStats_Player)
Servers (MohaaStats_ServerDashboard)
Achievements (MohaaAchievements.php)
Teams (MohaaTeams.php)
Tournaments (MohaaTournaments.php)

** FRONT END OR API **
NEVER REUTRN OR USE FAKE OR MOCK DATA. Absolately no fake data
no use of mock or fake data
All data must be seeded

*** DEV ***
lets not use docker for local dev. only docker for production!!!

*** SUDO ***
For sudo use TEMPerary password `Gramjchq1`
# 🤖 CLAUDE.md - AI Assistant Instructions

> **OpenMOHAA Competitive Stats & Tournament Infrastructure**

## 📋 Project Summary

You are assisting with building a **massive competitive statistics and tournament infrastructure** for Medal of Honor: Allied Assault (OpenMOHAA). This is a passion project to create the most comprehensive FPS stats tracking system ever built.

### Core Philosophy
- **HUGE emphasis on statistics** - We want 100,000+ trackable metrics
- **Drill-down everything** - Every stat is clickable, explorable, filterable
- **Visualize beautifully** - Graphs, heatmaps, spider charts, Sankey diagrams
- **Hybrid Design** - SMF Native integration + Rich Visuals
- **Community integration** - Stats live inside SMF forum

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     OpenMOHAA Game Servers                              │
│                                                                         │
│   tracker.scr (Morpheus Script)                                         │
│   ├── Subscribes to 30 engine events                                    │
│   ├── Formats event data with player GUID, positions, weapons          │
│   └── HTTP POST to stats API                                            │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          Go Stats API                                   │
│                        (Port 8080)                                      │
│                                                                         │
│   ┌─────────────┐   ┌──────────────────┐   ┌───────────────────────┐   │
│   │   Ingest    │──▶│   Worker Pool    │──▶│   Batch Insert        │   │
│   │  Handlers   │   │ (Buffered Chan)  │   │   (ClickHouse)        │   │
│   └─────────────┘   └──────────────────┘   └───────────────────────┘   │
│                                                                         │
│   Databases:                                                            │
│   ├── ClickHouse: raw_events, materialized views (OLAP)                │
│   ├── PostgreSQL: users, tournaments, teams, brackets (OLTP)           │
│   └── Redis: caching, real-time state, session management              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                     SMF Forum (PHP)                                     │
│                   (localhost:8888)                                      │
│                                                                         │
│   MohaaPlayers.php                                                      │
│   ├── Action handlers: mohaadashboard, mohaaleaderboard, etc.          │
│   ├── Menu integration: MOHAA Stats dropdown                           │
│   └── Profile areas: Player stats, identity linking                    │
│                                                                         │
│   Templates (Themes/default/):                                          │
│   ├── MohaaDashboard.template.php    - War Room overview               │
│   ├── MohaaLeaderboard.template.php  - Player rankings                 │
│   ├── MohaaPlayer.template.php       - Individual player drill-down    │
│   ├── MohaaMatches.template.php      - Match history                   │
│   ├── MohaaServers.template.php      - Server browser                  │
│   ├── MohaaAchievements.template.php - Medal cabinet                   │
│   └── MohaaTournaments.template.php  - Tournament system               │
│                                                                         │
│   Visualization: ApexCharts + HTMX for dynamic updates                 │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 📊 The 30 Engine Events

These are the atomic data points we capture from the game:

### Combat (10)
| Event | Parameters | Description |
|-------|------------|-------------|
| `player_kill` | attacker, victim, inflictor, hitloc, mod | Kill registered |
| `player_death` | inflictor | Player died |
| `player_damage` | attacker, damage, mod | Damage dealt |
| `weapon_fire` | weapon, ammo | Weapon discharged |
| `weapon_hit` | target, hitloc | Projectile connected |
| `player_headshot` | target, weapon | Headshot kill |
| `weapon_reload` | weapon | Reload initiated |
| `weapon_change` | old_weapon, new_weapon | Weapon swap |
| `grenade_throw` | projectile | Grenade thrown |
| `grenade_explode` | projectile | Grenade detonated |

### Movement (5)
| Event | Parameters | Description |
|-------|------------|-------------|
| `player_jump` | - | Player jumped |
| `player_land` | fall_height | Player landed |
| `player_crouch` | - | Crouch state |
| `player_prone` | - | Prone state |
| `player_distance` | walked, sprinted, swam, driven | Distance traveled |

### Interaction (5)
| Event | Parameters | Description |
|-------|------------|-------------|
| `ladder_mount` | ladder | Climbed ladder |
| `ladder_dismount` | ladder | Left ladder |
| `item_pickup` | item, amount | Picked up item |
| `item_drop` | item | Dropped item |
| `player_use` | target | Used entity |

### Session (5)
| Event | Parameters | Description |
|-------|------------|-------------|
| `client_connect` | client_num | Player connected |
| `client_disconnect` | - | Player left |
| `client_begin` | - | Player spawned |
| `team_join` | old_team, new_team | Team change |
| `player_say` | message | Chat message |

---

## 📈 Statistics Taxonomy (100,000+ Metrics)

### Category A: Combat Core (60+ stats)
- Total kills, deaths, KDR, KPM
- Headshot %, torso %, limb %
- First bloods, final kills, aces
- Trade kills, assists, cleanups
- Multi-kills (double, triple, quad, ace)
- Killstreaks (max, avg, frequency)

### Category B: Weapon Mastery (500+ stats)
For EACH of 20+ weapons:
- Kills, deaths, accuracy
- Headshot ratio, damage dealt
- Time equipped, reloads
- Pick rate, drop rate
- Snap speed (time to first hit)

### Category C: Movement (50+ stats)
- Distance walked, sprinted, swam
- Time crouched, prone, standing
- Jumps, air time, air kills
- Ladder climbs, fall damage
- Average velocity, stance transitions

### Category D: Accuracy & Aiming (40+ stats)
- Overall accuracy %
- Bullets per kill
- Hit region distribution
- Snap aim speed
- Tracking accuracy (full-auto)
- First shot accuracy

### Category E: Session & Time (30+ stats)
- Total playtime
- Matches played, rounds played
- Win rate, round win rate
- Time per session
- Daily/weekly/monthly activity

### Category F: Clutch & Situational (50+ stats)
- 1v1, 1v2, 1v3+ clutch wins
- Low HP kills (< 15 HP)
- Trades (revenge within 3s)
- Comeback rounds
- Flawless rounds
- First blood rate

### Category G: Objective (40+ stats)
- Bomb plants, defuses
- Flag captures, returns
- Objective time
- Carrier kills
- Defensive vs offensive kills

### Category H: Map-Specific (100+ per map)
- Kill heatmap positions
- Death positions
- Lane control %
- Spawn kill rate
- Time per zone

### Category I: Cross-Dimensional (50,000+ combinations)
- Weapon + Map combinations
- Weapon + Time of day
- Stance + Accuracy
- Player vs Player (head-to-head)
- Team compositions

---

## 🏆 Achievement System (1,000+ Achievements)

### Tier 1: Bronze (Basic Training)
- First Blood, 100 Kills, Open 50 Doors

### Tier 2: Silver (Specialist)
- 500 Kills, 100 Headshots, Weapon-specific badges

### Tier 3: Gold (Veteran)
- 1,000 Kills, 50% Accuracy, Map mastery

### Tier 4: Platinum (Elite)
- 10 Ace rounds, 100 Clutch wins

### Tier 5: Diamond (Master)
- Master all weapons, Perfect accuracy badge

### Tier 6-10: Legendary
- Tournament wins, dynasty streaks, community recognition

### Hall of Shame (Anti-achievements)
- 100 Suicides, Reload deaths, Team kills

---

## 🖥️ UI/UX Guidelines

### Design Theme: "Hybrid Design"
- **Philosophy**: Clean Grid Layouts + SMF Native Integration + Rich Visuals
- **Structure**: Use CSS Grid (`.mohaa-grid`) and SMF classes (`windowbg`, `roundframe`).
- **Colors**: Inherit forum variables. Use functional colors (Red/Green/Orange) for status.
- **Typography**: Inherit forum fonts. Clean and readable.
- **Components**: Stat Cards (`.stat-card`) with minimal boxing/shadows.

### Visualization Types
1. **Gauges**: KDR, accuracy, win rate
2. **Bar Charts**: Weapon comparisons, rankings
3. **Heatmaps**: Kill/death positions on maps
4. **Spider Charts**: 5-axis skill profile (Aim, Movement, Tactics, Survival, Clutch)
5. **Sankey Diagrams**: Kill flow (who kills whom)
6. **Line Charts**: Performance over time, Elo history
7. **Tables**: Leaderboards with sorting/filtering

### Key Interaction Pattern
**DRILL-DOWN EVERYTHING**: Click any stat → Opens filtered table/graph showing breakdown
- Click "Headshots" → Leaderboard of headshot leaders
- Click player's "Accuracy" → Per-weapon accuracy breakdown
- Click map name → Full map statistics page

---

## 🗄️ Key Files & Locations

### Game Scripts
- `global/tracker.scr` - Event tracking script (Morpheus)

### API Server
- `cmd/api/main.go` - Go API entry point
- `internal/` - API handlers, logic, models

### SMF Plugin Development (SINGLE SOURCE OF TRUTH)
- **All sources**: `smf-mohaa/Sources/`
- **All templates**: `smf-mohaa/Themes/default/`
- **Container mount**: `/mohaa` → symlinked to SMF directories
- **No docker cp needed** - edit locally, changes instant via symlinks

```
smf-mohaa/
├── Sources/
│   ├── MohaaAchievements.php
│   ├── MohaaPlayers.php
│   ├── MohaaServers.php
│   ├── MohaaTeams.php
│   ├── MohaaTournaments.php
│   └── MohaaStats/
│       ├── MohaaStats.php
│       ├── MohaaStatsAPI.php
│       └── MohaaStatsAdmin.php
└── Themes/default/
    ├── Mohaa*.template.php
    └── languages/
```

### Docker
- Container: `smf-smf-1` (localhost:8888)
- Database: `smf-smf-db-1` (MariaDB, root/root_password)
- Docker config: `smf/docker-compose.yml`

### Documentation
- `docs/` - Organized documentation
- `docs/design/MASSIVE_STATS.md` - Full stats taxonomy
- `docs/EVENT_DOCUMENTATION.md` - 30 engine events

---

## 🔧 Common Tasks

### Adding a New Stat
1. Ensure event is being captured in `tracker.scr`
2. Define aggregation in ClickHouse materialized view
3. Add API endpoint in Go server
4. Add visualization in SMF template
5. Document in STATS_MASTER.md

### Adding an Achievement
1. Define trigger conditions (SQL or event-based)
2. Add to PostgreSQL `achievements` table
3. Create badge graphic
4. Add check logic in achievement service
5. Update template to display

### Fixing SMF Templates
```bash
# Check PHP syntax
docker exec smf-smf-1 php -l /var/www/html/Themes/default/[Template].template.php

# View Apache errors
docker exec smf-smf-1 tail -n 100 /var/log/apache2/error.log

# Verify function names match
docker exec smf-smf-1 grep "function template_" /var/www/html/Themes/default/[Template].template.php
```

### Database Access
```bash
# SMF Database
docker exec smf-smf-db-1 mysql -uroot -proot_password smf

# Check hooks
SELECT * FROM smf_settings WHERE variable LIKE 'integrate_%';
```

---

## ⚠️ Important Notes

1. **SMF Password Hash Format**: `password_hash(strtolower($username) . $password, PASSWORD_BCRYPT)`

2. **Template Naming**: `sub_template = "mohaa_foo"` requires function `template_mohaa_foo()`

3. **Event Argument Order**: Always check EVENT_DOCUMENTATION.md for correct parameter order

4. **ClickHouse is OLAP**: Optimized for aggregations, not single-row updates

5. **Player Identity**: Forum accounts link to game GUIDs via `smf_mohaa_identities` table

---

## 🎯 Current Priorities

1. **Fix Profile Integration** - Profile areas not appearing
2. **Wire Real Data** - Replace mock data with API calls
3. **Complete Templates** - MohaaPlayer, MohaaMaps need updates
4. **Add More Stats** - Expand from 50K to 100K+ metrics
5. **Visualization** - Add heatmaps, spider charts

---

## 💡 When Responding

1. **Use military/tactical language** in UI text where appropriate
2. **Always suggest drill-down** - Every stat should be explorable
3. **Think about combinations** - Weapon × Map × Player combinations
4. **Consider visualizations** - What's the best way to show this data?
5. **Keep SMF conventions** - Use `$context`, `loadTemplate()`, hook patterns
6. **Document everything** - Add to appropriate .md file

---

*This file is the primary instruction set for Claude when working on the OpenMOHAA Stats project.*

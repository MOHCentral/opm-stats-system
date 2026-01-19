# 🤖 AGENTS.md - Multi-Agent Workspace Rules

> **OpenMOHAA Stats System - Instructions for All AI Assistants**

This file contains instructions for any AI assistant (Claude, Copilot, Gemini, etc.) working on this project.

---

## 📋 Project Identity

**Project**: OpenMOHAA Competitive Statistics & Tournament Infrastructure  
**Goal**: Build the most comprehensive FPS stats tracking system ever created  
**Scale**: 100,000+ metrics, 1,000+ achievements, 30 engine events  
**Philosophy**: Drill-down everything, visualize beautifully, massive data density

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                 OpenMOHAA Game Servers                          │
│          tracker.scr → 30 events → HTTP POST                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Go Stats API (:8080)                        │
│    Worker Pool → ClickHouse (OLAP) + PostgreSQL (OLTP)          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   SMF Forum (:8888)                             │
│      MohaaPlayers.php → Templates → ApexCharts + HTMX           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📁 Key File Locations

| Component | Location | Notes |
|-----------|----------|-------|
| Game Scripts | `global/tracker.scr` | Morpheus scripting |
| Go API | `mohaa-stats-api/` | Main API project |
| SMF Container | `smf-smf-1` | localhost:8888 |
| SMF Database | `smf-smf-db-1` | MariaDB, root/root_password |
| SMF Sources | `/var/www/html/Sources/` | In container |
| SMF Templates | `/var/www/html/Themes/default/` | In container |
| Documentation | `docs/` | Organized docs |
| Stats Spec | `MASSIVE_STATS.md` | Full taxonomy |
| Events Spec | `EVENT_DOCUMENTATION.md` | 30 events |

---

## 🎮 The 30 Engine Events

**Combat (10)**: player_kill, player_death, player_damage, weapon_fire, weapon_hit, player_headshot, weapon_reload, weapon_change, grenade_throw, grenade_explode

**Movement (5)**: player_jump, player_land, player_crouch, player_prone, player_distance

**Interaction (5)**: ladder_mount, ladder_dismount, item_pickup, item_drop, player_use

**Session (5)**: client_connect, client_disconnect, client_begin, team_join, player_say

---

## 📊 Statistics Categories

| Category | Count | Examples |
|----------|-------|----------|
| Combat Core | 60+ | Kills, KDR, accuracy, headshots |
| Weapon Stats | 500+ | 25 metrics × 20+ weapons |
| Movement | 50+ | Distance, velocity, stance time |
| Accuracy | 40+ | Hit %, bullets per kill, snap speed |
| Session | 30+ | Playtime, matches, win rate |
| Clutch | 50+ | 1vX wins, trades, comebacks |
| Objective | 40+ | Plants, captures, hold time |
| Map-Specific | 100+ per map | Heatmaps, lane control |
| Combinations | 50,000+ | Cross-dimensional analysis |

---

## 🎨 UI Theme: "Hybrid Design"

### Philosophy
We typically use a **Hybrid Design** approach:
1. **Clean Grid Layouts**: CSS Grid for structure.
2. **SMF Native Containers**: `windowbg`, `roundframe`, `cat_bar` for integration.
3. **Rich Visualizations**: Custom SVG gauges, heatmaps, and charts inside the native containers.
4. **Minimal Custom CSS**: Use structural classes instead of heavy custom styles.

### Colors
- **Integrate**: Use Forum Theme colors where possible.
- **Accents**:
  - Success: `#4caf50` (Green)
  - Danger: `#f44336` (Red)
  - Warning: `#ff9800` (Orange)
  - Info: `#2196f3` (Blue)

### Typography
- Inherit from forum theme (e.g., `Segoe UI`, `Verdana`).
- Use **Bold** for emphasis, but avoid forced monospace headers.

### Key Pattern
**DRILL-DOWN EVERYTHING**: Every stat is a link. Click any number to see its breakdown, leaderboard, or time series.

---

## 🛠️ Technology Patterns

### SMF Template Convention
```php
// sub_template value MUST match function name
$context["sub_template"] = "mohaa_foo";
// Requires: function template_mohaa_foo() { ... }
```

### SMF Password Hash
```php
password_hash(strtolower($username) . $password, PASSWORD_BCRYPT)
```

### Morpheus Script Pattern
```c
main:
    event_subscribe "player_kill" "on_kill"
end

on_kill local.attacker local.victim local.inflictor local.hitloc local.mod:
    // Handle kill event
end
```

### Docker Commands
```bash
# PHP syntax check
docker exec smf-smf-1 php -l /var/www/html/Sources/MohaaPlayers.php

# Apache errors
docker exec smf-smf-1 tail -n 100 /var/log/apache2/error.log

# MySQL access
docker exec smf-smf-db-1 mysql -uroot -proot_password smf
```

---

## ✅ Coding Guidelines

1. **Massive Stats Focus**
   - Always think about metric combinations (weapon × map × player × time)
   - Every aggregation should be explorable
   - Default to MORE data, not less

2. **Drill-Down Pattern**
   - Every displayed stat should link to a detailed view
   - Click "500 kills" → Show kill breakdown by weapon, map, time
   - Click weapon → Show per-weapon stats page

3. **Visualization First**
   - Consider the best chart type for each metric
   - Use ApexCharts for interactive charts
   - Heatmaps for spatial data, spider charts for profiles

4. **SMF Conventions**
   - Use `$context` for passing data to templates
   - Use `loadTemplate()` to load template files
   - Hook functions must follow naming patterns exactly
   - Check PHP syntax before considering code complete

5. **Documentation**
   - Update relevant .md files when adding features
   - Document new stats in STATS_MASTER.md
   - Add new events to EVENT_DOCUMENTATION.md

---

## 🚫 Anti-Patterns to Avoid

1. **Don't pre-aggregate too early** - ClickHouse can compute on the fly
2. **Don't hardcode stats** - Everything should come from data
3. **Don't skip drill-down** - Every stat needs exploration
4. **Don't ignore SMF conventions** - Use $context, not direct echo
5. **Don't forget to test PHP syntax** - Always verify before declaring done

---

## 📚 Reference Documents

- `CLAUDE.md` - Detailed AI instructions
- `.github/copilot-instructions.md` - Copilot-specific instructions
- `MASSIVE_STATS.md` - Complete 50,000+ stats taxonomy
- `STATS_SYSTEM.md` - UI/UX specifications
- `EVENT_DOCUMENTATION.md` - 30 engine events with parameters
- `MORE_ACHIEVEMENTS.md` - Sabermetrics approach
- `mohaa-stats-api/DEVELOPER_GUIDE.md` - SMF integration guide

---

## 🎯 Current Priorities

1. Fix SMF profile area integration
2. Replace mock data with real API calls
3. Complete remaining templates (MohaaPlayer, MohaaMaps)
4. Add heatmap visualizations
5. Expand stats taxonomy to 100,000+ metrics
6. Implement tournament bracket system

---

*This file is read by all AI assistants working on this project. Keep it updated.*

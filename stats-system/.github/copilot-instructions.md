# GitHub Copilot Instructions - OpenMOHAA Stats System

## Project Overview

This is a **massive competitive statistics and tournament infrastructure** for Medal of Honor: Allied Assault (OpenMOHAA). The goal is to build the most comprehensive FPS stats tracking system with 100,000+ metrics, 1,000+ achievements, and rich visualizations.

## Architecture

```
Game Servers (tracker.scr) → Go API (ClickHouse/PostgreSQL) → SMF Forum (PHP Templates)
```

## Key Technologies

- **Game Scripts**: Morpheus scripting language (.scr files)
- **API Server**: Go with worker pool pattern
- **OLAP Database**: ClickHouse for raw events and aggregations
- **OLTP Database**: PostgreSQL for users, tournaments, teams
- **Cache**: Redis for real-time state
- **Frontend**: SMF 2.1 (PHP), ApexCharts, HTMX

## File Patterns

### Morpheus Scripts (.scr)
Located in `global/` and `maps/`. Use this pattern:
```c
main:
    event_subscribe "event_name" "handler_label"
end

handler_label local.arg1 local.arg2:
    println "Handling event"
end
```

### SMF Templates (.template.php)
Located in container at `/var/www/html/Themes/default/`. Function naming is CRITICAL:
```php
<?php
// If sub_template = "mohaa_dashboard", function MUST be:
function template_mohaa_dashboard() {
    global $context, $scripturl;
    echo '<div class="mohaa-container">...</div>';
}
```

### SMF Source Files (.php)
`MohaaPlayers.php` handles actions:
```php
function MohaaPlayers_Actions(&$actionArray) {
    $actionArray["mohaadashboard"] = array("MohaaPlayers.php", "MohaaPlayers_Dashboard");
}

function MohaaPlayers_Dashboard() {
    global $context;
    loadTemplate("MohaaDashboard");
    $context["page_title"] = "MOHAA War Room";
    $context["sub_template"] = "mohaa_dashboard";
}
```

## 30 Engine Events

Combat: player_kill, player_death, player_damage, weapon_fire, weapon_hit, player_headshot, weapon_reload, weapon_change, grenade_throw, grenade_explode

Movement: player_jump, player_land, player_crouch, player_prone, player_distance

Interaction: ladder_mount, ladder_dismount, item_pickup, item_drop, player_use

Session: client_connect, client_disconnect, client_begin, team_join, player_say

## Stats Categories

- **Combat Core**: Kills, deaths, KDR, accuracy, headshots
- **Weapon Stats**: Per-weapon kills, accuracy, time equipped (×20 weapons)
- **Movement**: Distance, stance time, jumps, velocity
- **Clutch**: 1vX wins, trades, low HP kills, comebacks
- **Objective**: Plants, defuses, captures, objective time
- **Map-Specific**: Heatmaps, lane control, spawn stats

## UI Theme: "Command & Control"

- **Colors**: Dark blue-grey (#1a1a2e), military green (#8bc34a), gold (#ffd700)
- **Pattern**: Drill-down everything - every stat is clickable
- **Charts**: Gauges, bar charts, heatmaps, spider charts, line graphs

## Database Access

```bash
# SMF Database
docker exec smf-smf-db-1 mysql -uroot -proot_password smf

# Check registered hooks
SELECT * FROM smf_settings WHERE variable LIKE 'integrate_%';
```

## SMF Password Hash

SMF uses: `password_hash(strtolower($username) . $password, PASSWORD_BCRYPT)`

## Debugging

```bash
# PHP syntax check
docker exec smf-smf-1 php -l /var/www/html/Sources/MohaaPlayers.php

# Apache errors
docker exec smf-smf-1 tail -n 100 /var/log/apache2/error.log

# Template functions
docker exec smf-smf-1 grep "function template_" /var/www/html/Themes/default/*.template.php
```

## Important Tables

- `smf_mohaa_claims`: Token claims for identity linking
- `smf_mohaa_identities`: Forum ↔ game GUID links
- ClickHouse `raw_events`: All game events
- PostgreSQL `users`, `tournaments`, `achievements`

## Key Principles

1. **Massive stats** - Always think about combinations (weapon × map × player)
2. **Drill-down** - Every stat should link to a breakdown
3. **Visualize** - Use appropriate chart types
4. **Document** - Update .md files when adding features
5. **SMF conventions** - Use $context, loadTemplate(), hooks

## Reference Files

- `CLAUDE.md` - Full AI instructions
- `MASSIVE_STATS.md` - Complete stats taxonomy
- `EVENT_DOCUMENTATION.md` - Event parameters
- `mohaa-stats-api/DEVELOPER_GUIDE.md` - SMF integration details

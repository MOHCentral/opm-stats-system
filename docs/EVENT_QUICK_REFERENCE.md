# 🔍 Event System Quick Reference

> Fast lookup guide for the 92-event tracking system

---

## 📂 File Locations

| Component | Path | Purpose |
|-----------|------|---------|
| **Event Constants** | `internal/models/events.go` | Go EventType enum (92 constants) |
| **Event Subscriptions** | `global/tracker_init_player.scr` | Player event subscriptions (62) |
| **Event Subscriptions** | `global/tracker_init_server.scr` | Server event subscriptions (30) |
| **Combat Handlers** | `global/tracker_combat_ext.scr` | Kill, damage, weapon events |
| **Movement Handlers** | `global/tracker_movement_ext.scr` | Jump, crouch, spawn events |
| **Item Handlers** | `global/tracker_items_ext.scr` | Pickup, health, ammo events |
| **Vehicle Handlers** | `global/tracker_vehicle_ext.scr` | Vehicle, turret events |
| **Interaction Handlers** | `global/tracker_interaction_ext.scr` | Use, spectate, chat events |
| **GameFlow Handlers** | `global/tracker_gameflow_ext.scr` | Match, round, server, map events |
| **World Handlers** | `global/tracker_world_ext.scr` | Door, explosion events |
| **Bot Handlers** | `global/tracker_bot_ext.scr` | Bot, actor events |
| **Client Handlers** | `global/tracker_client_ext.scr` | Connect, vote events |
| **Score Handlers** | `global/tracker_score_ext.scr` | Score, kick events |
| **Common Utils** | `global/tracker_common.scr` | Shared functions (send_to_api) |
| **API Handlers** | `internal/handlers/handlers.go` | HTTP endpoints |
| **ClickHouse Schema** | `migrations/clickhouse/001_initial_schema.sql` | Database schema |

---

## 🎯 Event Categories (92 Total)

### Combat (23)
```
kill, death, damage, player_pain, headshot
player_suicide, player_crushed, player_telefragged, player_roadkill
player_bash, player_teamkill
weapon_fire, weapon_hit, weapon_change, weapon_reload, weapon_reload_done
weapon_ready, weapon_no_ammo, weapon_holster, weapon_raise, weapon_drop
grenade_throw, grenade_explode
```

### Movement (10)
```
jump, land, crouch, prone, player_stand
player_spawn, player_respawn, distance
ladder_mount, ladder_dismount
```

### Interaction (6)
```
player_use, player_use_object_start, player_use_object_finish
player_spectate, player_freeze, player_say
```

### Item (5)
```
item_pickup, item_drop, item_respawn
health_pickup, ammo_pickup
```

### Vehicle/Turret (6)
```
vehicle_enter, vehicle_exit, vehicle_death, vehicle_collision
turret_enter, turret_exit
```

### Server (5)
```
server_init, server_start, server_shutdown, server_spawned
server_console_command
```

### Map (4)
```
map_load_start, map_load_end, map_change_start, map_restart
```

### Game Flow (11)
```
game_init, game_start, game_end
match_start, match_end
round_start, round_end
warmup_start, warmup_end
intermission_start
objective_update
```

### Team/Vote (5)
```
team_join, team_change
vote_start, vote_passed, vote_failed
```

### Client (5)
```
client_connect, client_disconnect, client_begin
client_userinfo_changed, player_inactivity_drop
```

### World (3)
```
door_open, door_close, explosion
```

### AI/Actor (7)
```
actor_spawn, actor_killed
bot_spawn, bot_killed, bot_roam, bot_curious, bot_attack
```

### Objectives (2)
```
objective_update, objective_capture
```

### Score/Admin (2)
```
score_change, teamkill_kick
```

---

## 🔗 Event Flow

```
Game Engine
    ↓ (event fires)
tracker.scr / tracker_init_*.scr
    ↓ (event_subscribe)
tracker_*_ext.scr (handler function)
    ↓ (build payload)
tracker_common.scr::send_to_api
    ↓ (HTTP POST)
Go API (internal/handlers/handlers.go::IngestEvents)
    ↓ (parse & enqueue)
Worker Pool (internal/worker/worker.go)
    ↓ (batch insert)
ClickHouse (raw_events table)
    ↓ (materialized views)
Aggregated Stats
    ↓ (API query)
SMF PHP (smf-mohaa/Sources/Mohaa*.php)
    ↓ (render)
SMF Template (smf-mohaa/Themes/default/Mohaa*.template.php)
    ↓ (display with ApexCharts)
User Browser
```

---

## 📊 Common Queries

### Get event count by type
```sql
SELECT
    event_type,
    count() AS count
FROM raw_events
WHERE match_id = 'xyz123'
GROUP BY event_type
ORDER BY count DESC
```

### Player kill/death ratio
```sql
SELECT
    actor_id,
    actor_name,
    countIf(event_type = 'kill') AS kills,
    countIf(event_type = 'death') AS deaths,
    kills / deaths AS kd_ratio
FROM raw_events
WHERE actor_id != ''
GROUP BY actor_id, actor_name
ORDER BY kd_ratio DESC
LIMIT 10
```

### Weapon accuracy
```sql
SELECT
    actor_weapon,
    countIf(event_type = 'weapon_fire') AS shots_fired,
    countIf(event_type = 'weapon_hit') AS shots_hit,
    (shots_hit / shots_fired) * 100 AS accuracy
FROM raw_events
WHERE event_type IN ('weapon_fire', 'weapon_hit')
  AND actor_weapon != ''
GROUP BY actor_weapon
ORDER BY accuracy DESC
```

### Hit location heatmap
```sql
SELECT
    hitloc,
    count() AS hits
FROM raw_events
WHERE event_type = 'player_pain'
  AND hitloc != ''
GROUP BY hitloc
ORDER BY hits DESC
```

### Reload efficiency
```sql
SELECT
    actor_id,
    actor_name,
    countIf(event_type = 'weapon_reload') AS reloads_started,
    countIf(event_type = 'weapon_reload_done') AS reloads_completed,
    (reloads_completed / reloads_started) * 100 AS reload_efficiency
FROM raw_events
WHERE event_type IN ('weapon_reload', 'weapon_reload_done')
GROUP BY actor_id, actor_name
ORDER BY reload_efficiency DESC
```

---

## 🛠️ Adding a New Event

### 1. Define Event Constant (Go)
```go
// internal/models/events.go
const (
    EventMyNewEvent EventType = "my_new_event"
)
```

### 2. Subscribe to Event (Morpheus Script)
```morpheus
// global/tracker_init_player.scr or tracker_init_server.scr
event_subscribe "my_new_event" "tracker_custom_ext.scr::on_my_new_event"
```

### 3. Create Handler (Morpheus Script)
```morpheus
// global/tracker_custom_ext.scr
on_my_new_event local.player local.data:
    local.payload = "type=my_new_event"
    local.payload = local.payload + "&match_id=" + level.match_id
    local.payload = local.payload + "&timestamp=" + level.time
    local.payload = local.payload + tracker_common.scr::build_player_payload local.player "player"
    local.payload = local.payload + "&data=" + local.data

    thread tracker_common.scr::send_to_api local.payload
end
```

### 4. Test
```bash
# Generate test event
curl -X POST http://localhost:8080/events \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "type=my_new_event&match_id=test123&player_name=TestPlayer&data=test"

# Verify in ClickHouse
docker exec clickhouse-clickhouse-1 clickhouse-client --query \
  "SELECT * FROM default.raw_events WHERE event_type = 'my_new_event' LIMIT 1"
```

---

## 🎨 Visualization Examples

### ApexCharts Line Chart (K/D Trend)
```javascript
const options = {
  chart: { type: 'line', height: 350 },
  series: [
    { name: 'Kills', data: [10, 15, 12, 18, 20] },
    { name: 'Deaths', data: [8, 10, 9, 12, 11] }
  ],
  xaxis: { categories: ['Match 1', 'Match 2', 'Match 3', 'Match 4', 'Match 5'] },
  colors: ['#00E396', '#FF4560']
};
const chart = new ApexCharts(document.querySelector("#chart"), options);
chart.render();
```

### SMF Integration (Clickable Stat)
```php
<tr>
    <td><strong>Reload Efficiency:</strong></td>
    <td class="clickable-stat" data-chart="reload-efficiency">
        <span class="stat-value">87.5%</span>
        <i class="fas fa-chart-bar"></i>
    </td>
</tr>

<div id="reload-efficiency-chart" class="chart-container" style="display:none;">
    <div id="reload-apex-chart"></div>
</div>

<script>
document.querySelector('[data-chart="reload-efficiency"]').addEventListener('click', function() {
    const container = document.getElementById('reload-efficiency-chart');
    container.style.display = container.style.display === 'none' ? 'block' : 'none';
    
    if (container.style.display === 'block') {
        // Lazy load chart data via AJAX
        fetch('/api/reload-efficiency?player_id=123')
            .then(res => res.json())
            .then(data => renderReloadChart(data));
    }
});
</script>
```

---

## 🧮 Derived Metric Formulas

| Metric | Formula | Events |
|--------|---------|--------|
| **Accuracy** | (weapon_hit / weapon_fire) × 100 | weapon_fire, weapon_hit |
| **K/D Ratio** | kill / death | kill, death |
| **Headshot %** | (headshot / kill) × 100 | headshot, kill |
| **Reload Efficiency** | (weapon_reload_done / weapon_reload) × 100 | weapon_reload, weapon_reload_done |
| **Grenade Efficiency** | (grenade kills / grenade_throw) × 100 | grenade_explode, grenade_throw |
| **Vehicle K/D** | vehicle_kills / vehicle_deaths | vehicle_enter, vehicle_exit, kill, death |
| **Door Camper** | Kills within 5s of door_open | door_open, kill |
| **Bot Farming %** | (bot_killed / total_kills) × 100 | bot_killed, kill |
| **Pain Threshold** | Avg damage per death | player_pain, death |
| **Stance Distribution** | Time in prone/crouch/stand | prone, crouch, stand |

---

## 🚨 Debugging Tips

### Check if events are being subscribed
```bash
# In-game console
/developer 1
/sv_cheats 1
# Look for "Initializing Player Event Subscriptions..." in console
```

### Monitor API logs
```bash
# API container
docker logs -f api-api-1 | grep "event_type"
```

### Check ClickHouse ingestion
```bash
docker exec clickhouse-clickhouse-1 clickhouse-client --query \
  "SELECT event_type, count() FROM default.raw_events GROUP BY event_type ORDER BY count() DESC"
```

### Test event handler directly
```morpheus
// In game console or script
exec global/tracker_combat_ext.scr
thread tracker_combat_ext.scr::on_player_bash $player $player
```

---

## 📚 Related Documentation

- **Full Analysis**: [`docs/EVENT_COVERAGE_ANALYSIS.md`](./EVENT_COVERAGE_ANALYSIS.md)
- **Derived Metrics**: [`docs/DERIVED_METRICS.md`](./DERIVED_METRICS.md)
- **Visualizations**: [`docs/EVENT_VISUALIZATIONS.md`](./EVENT_VISUALIZATIONS.md)
- **Completion Report**: [`docs/MASSIVE_EVENT_ANALYSIS_REPORT.md`](./MASSIVE_EVENT_ANALYSIS_REPORT.md)
- **Event Docs**: [`docs/EVENT_DOCUMENTATION.md`](./EVENT_DOCUMENTATION.md)
- **OpenMOHAA Docs**: [https://docs.openmohaa.org/](https://docs.openmohaa.org/)

---

**Last Updated**: 2026-01-20  
**Coverage**: 92/92 events (100%)

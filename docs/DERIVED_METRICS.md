# 📊 Derived Metrics System
## The "Second Layer" of Stats - Combining Events for Insight

> **Philosophy**: Raw events are the atoms. Derived metrics are the molecules.

---

## 🧬 Metric Categories

### 1. Combat Efficiency Metrics

#### Accuracy & Precision
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Overall Accuracy** | `weapon_hit` / `weapon_fire` × 100 | weapon_fire, weapon_hit | 45.2% |
| **Headshot %** | `headshot` / `kill` × 100 | headshot, kill | 18.5% |
| **First-Shot Kill %** | Kills with 1 `weapon_fire` / Total kills × 100 | weapon_fire, kill (time-correlated) | 12.3% |
| **Burst Control** | Avg `weapon_fire` per kill | weapon_fire, kill | 8.2 shots/kill |
| **Killshot Precision** | Distance distribution (avg, max) on final `weapon_hit` | weapon_hit, kill (position) | Avg: 45m |

#### Lethality Metrics
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **K/D Ratio** | `kill` / `death` | kill, death | 1.85 |
| **K/D Spread** | `kill` - `death` | kill, death | +120 |
| **Kills Per Minute (KPM)** | `kill` / Total time played | kill, match_start→match_end | 0.85 KPM |
| **Damage Per Minute (DPM)** | Sum of `damage` / Total time | damage, match_start→match_end | 650 DPM |
| **Damage Per Kill** | Total `damage` / `kill` | damage, kill | 120 dmg/kill |
| **Overkill Damage** | Excess damage after `death` | damage, death | Avg +15 dmg |

#### Special Kill Metrics
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Bash %** | `player_bash` / `kill` × 100 | player_bash, kill | 5.2% |
| **Grenade Efficiency** | Kills from `grenade_explode` / `grenade_throw` × 100 | grenade_explode (with kills), grenade_throw | 22% |
| **Roadkill %** | `player_roadkill` / `kill` × 100 | player_roadkill, kill | 3.1% |
| **Telefrag Assassin** | `player_telefragged` count (rare!) | player_telefragged | 2 frags |
| **Environmental Killer** | `player_crushed` (as attacker) | player_crushed | 8 crushed |
| **Suicide Rate** | `player_suicide` / (`kill` + `player_suicide`) × 100 | player_suicide, kill | 2.5% |
| **Friendly Fire %** | `player_teamkill` / `kill` × 100 | player_teamkill, kill | 1.8% ⚠️ |

---

### 2. Weapon Mastery Metrics

#### Reload Behavior
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Reload Frequency** | `weapon_reload` per minute | weapon_reload, match time | 3.2 reloads/min |
| **Reload Completion %** | `weapon_reload_done` / `weapon_reload` × 100 | weapon_reload, weapon_reload_done | 87% |
| **Tactical Reload Score** | Reloads with >50% ammo remaining | weapon_reload, ammo_remaining | 45% |
| **Empty Mag Frequency** | `weapon_no_ammo` count | weapon_no_ammo | 12 times |
| **Reload Speed** | Avg time `weapon_reload` → `weapon_reload_done` | weapon_reload, weapon_reload_done (timestamps) | 2.3s avg |

#### Weapon Switching
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Weapon Swap Frequency** | `weapon_change` per minute | weapon_change, match time | 1.5 swaps/min |
| **Primary Reliance** | Time with primary weapon / Total time | weapon_change, weapon_ready | 78% |
| **Sidearm Rescues** | Kills within 3s of `weapon_change` | weapon_change, kill (time-correlated) | 23 rescues |
| **Weapon Diversity** | Unique weapons used per match | weapon_change, weapon_fire | Avg 4.2 weapons |

#### Weapon Performance
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Per-Weapon K/D** | Kills / Deaths (by weapon) | kill, death (weapon field) | Kar98k: 2.1 |
| **Per-Weapon Accuracy** | `weapon_hit` / `weapon_fire` (by weapon) | weapon_fire, weapon_hit (weapon field) | M1 Garand: 52% |
| **Per-Weapon Headshot %** | `headshot` / kills (by weapon) | headshot, kill (weapon field) | Springfield: 68% |
| **Weapon Preference** | % of kills per weapon | kill (weapon field) | BAR: 35%, Kar98k: 28% |

---

### 3. Movement & Positioning Metrics

#### Mobility Metrics
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Total Distance** | Sum of `distance` (walked + sprinted + swam + driven) | distance | 15.2 km |
| **Sprint %** | Sprinted / Total distance × 100 | distance (sprinted field) | 42% |
| **Swim Distance** | Sum of `distance.swam` | distance (swam field) | 0.8 km |
| **Driven Distance** | Sum of `distance.driven` | distance (driven field) | 3.2 km |
| **Jump Frequency** | `jump` per minute | jump, match time | 2.1 jumps/min |
| **Vertical Mobility** | Time on ladders (`ladder_mount` → `ladder_dismount`) | ladder_mount, ladder_dismount | 45s total |
| **Fall Damage Taken** | Sum of `player_pain` from fall damage | player_pain (mod field) | 120 dmg |

#### Stance Behavior
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Stance Distribution** | Pie chart: % time prone/crouch/stand | player_prone, player_crouch, player_stand (durations) | Prone: 25%, Crouch: 40%, Stand: 35% |
| **Tactical Croucher** | Kills while `crouch` active | crouch, kill (state-based) | 45 kills |
| **Sniper Prone %** | % of kills while `prone` | prone, kill (state-based) | 15% |
| **Aggressive Stander** | % of kills while `stand` | stand, kill (state-based) | 60% |

#### Engagement Range
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Avg Kill Distance** | Avg distance (attacker → victim) on kills | kill (position fields) | 42.5m |
| **Max Kill Distance** | Max distance on kills | kill (position fields) | 287m |
| **CQB %** | Kills <10m / Total kills × 100 | kill (position fields) | 35% |
| **Long Range %** | Kills >100m / Total kills × 100 | kill (position fields) | 12% |
| **Sniper Preference** | % of kills >75m | kill (position fields) | 18% |

---

### 4. Survival & Durability Metrics

#### Pain Tolerance
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Pain Threshold** | Avg `player_pain` count before `death` | player_pain, death (sequences) | Avg 4.2 hits |
| **Damage Absorbed** | Total `player_pain` damage before death | player_pain (damage field), death | Avg 180 dmg |
| **Tank Score** | Max damage taken without dying | player_pain (damage field), death | 320 dmg |
| **Hit Location Distribution** | Heatmap of `player_pain` hitloc | player_pain (location field) | Chart: Body heatmap |

#### Survivability
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Avg Life Duration** | Avg time `player_spawn` → `death` | player_spawn, death (timestamps) | 2m 45s |
| **Longest Killstreak** | Max consecutive kills before death | kill, death (sequences) | 18 kills |
| **Death Avoidance** | % of `player_pain` not ending in death | player_pain, death | 35% |
| **Respawn Frequency** | `player_respawn` per minute | player_respawn, match time | 0.4 respawns/min |
| **One-Shot Deaths** | Deaths with only 1 `player_pain` | player_pain, death (sequences) | 28% |

---

### 5. Objective & Team Metrics

#### Objective Performance
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Objective Capture Rate** | `objective_capture` count | objective_capture | 12 captures |
| **Objective Time** | Time between `player_use_object_start` → `player_use_object_finish` | player_use_object_start, player_use_object_finish | Avg 8.5s |
| **Objective Kill Denial** | Kills on players near objectives | kill, objective_update (position-based) | 23 denials |
| **Objective Efficiency** | Captures / Deaths on objective × 100 | objective_capture, death (position-based) | 1.8 ratio |

#### Team Contribution
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Team Loyalty** | # of `team_change` events (lower = better) | team_change | 2 switches |
| **Friendly Fire Incidents** | `player_teamkill` count | player_teamkill | 5 incidents ⚠️ |
| **Team Damage %** | Damage to teammates / Total damage × 100 | damage (team field), player_teamkill | 3.2% |
| **Vote Participation** | (`vote_start` + responded votes) / Total votes | vote_start, vote_passed, vote_failed | 75% |

---

### 6. World Interaction Metrics

#### Environmental Interaction
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Door Camper Score** | Kills within 5s of `door_open` | door_open, kill (time-correlated) | 12 ambushes |
| **Explosive Artist** | Kills from `explosion` | explosion, kill (radius-based) | 8 kills |
| **Health Pack Efficiency** | HP healed / `health_pickup` count | health_pickup (amount field) | Avg +25 HP |
| **Ammo Scavenger** | `ammo_pickup` count | ammo_pickup | 45 pickups |
| **Item Hoarding** | `item_pickup` count | item_pickup | 78 items |
| **World Interactor** | `player_use` + `door_open` + `item_pickup` | player_use, door_open, item_pickup | 145 interactions |

---

### 7. Vehicle & Turret Metrics

#### Vehicle Usage
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Vehicle Time** | Total time in vehicle (`vehicle_enter` → `vehicle_exit`) | vehicle_enter, vehicle_exit | 8m 30s |
| **Vehicle Kills** | Kills between `vehicle_enter` → `vehicle_exit` | vehicle_enter, vehicle_exit, kill | 23 kills |
| **Vehicle Deaths** | Deaths between `vehicle_enter` → `vehicle_exit` | vehicle_enter, vehicle_exit, death | 5 deaths |
| **Vehicle K/D** | Vehicle kills / Vehicle deaths | vehicle_enter, vehicle_exit, kill, death | 4.6 |
| **Vehicle Destruction** | `vehicle_death` (as attacker) | vehicle_death | 7 destroyed |
| **Roadkill Specialist** | `player_roadkill` count | player_roadkill | 12 roadkills |
| **Crash Rate** | `vehicle_collision` count | vehicle_collision | 18 crashes |

#### Turret Mastery
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Turret Time** | Total time on turret (`turret_enter` → `turret_exit`) | turret_enter, turret_exit | 4m 15s |
| **Turret Kills** | Kills between `turret_enter` → `turret_exit` | turret_enter, turret_exit, kill | 18 kills |
| **Turret K/D** | Turret kills / Turret deaths | turret_enter, turret_exit, kill, death | 6.0 |

---

### 8. Bot & AI Metrics

#### PvE Performance
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Bot Farming %** | `bot_killed` / (`bot_killed` + `kill`) × 100 | bot_killed, kill | 15% |
| **Bot Hunter Score** | `bot_killed` count | bot_killed | 42 bots |
| **Actor Kills** | `actor_killed` count | actor_killed | 28 actors |
| **PvE vs PvP Ratio** | (`bot_killed` + `actor_killed`) / `kill` | bot_killed, actor_killed, kill | 0.18 |

---

### 9. Social & Behavioral Metrics

#### Communication
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Chat Frequency** | `player_say` per minute | player_say, match time | 1.2 msgs/min |
| **Spectator Time** | Total time in `player_spectate` | player_spectate (state duration) | 3m 20s |
| **AFK Incidents** | `player_inactivity_drop` count | player_inactivity_drop | 2 drops |
| **Name Changes** | `client_userinfo_changed` count | client_userinfo_changed | 1 change |

#### Admin Interactions
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Freeze Events** | `player_freeze` count | player_freeze | 0 freezes |
| **Toxicity Score** | `teamkill_kick` count | teamkill_kick | 0 kicks ✅ |
| **Vote Initiator** | `vote_start` count | vote_start | 5 votes |
| **Vote Success Rate** | `vote_passed` / (`vote_passed` + `vote_failed`) × 100 | vote_passed, vote_failed | 80% |

---

### 10. Time-Based Analytics

#### Performance by Time
| Metric | Formula | Events Used | Display Format |
|--------|---------|-------------|----------------|
| **Best Hour** | Hour with highest K/D | kill, death (timestamp hour) | 21:00-22:00 (K/D: 2.3) |
| **Fatigue Indicator** | K/D decline after 2+ hour sessions | kill, death (session duration) | -15% after 2hrs |
| **Warmup Performance** | Stats during `warmup_start` → `warmup_end` | warmup_start, warmup_end, kill, death | K/D: 1.2 |
| **Clutch Performance** | K/D in final 60s of rounds | round_end, kill, death (time-correlated) | K/D: 1.9 |

---

## 🎨 Visualization Mapping

### Recommended Charts per Metric Type

| Metric Category | Visualization Type | Library/Tool |
|-----------------|-------------------|--------------|
| **Accuracy Trends** | Line chart (time-series) | ApexCharts (area chart) |
| **Stance Distribution** | Pie chart / Donut | ApexCharts (donut chart) |
| **Hit Location Heatmap** | Body diagram heatmap | Custom Canvas + ApexCharts |
| **Kill Distance Histogram** | Bar chart (range buckets) | ApexCharts (column chart) |
| **Weapon Preference** | Horizontal bar chart | ApexCharts (bar chart) |
| **K/D Over Time** | Line chart with trend | ApexCharts (line + moving avg) |
| **Performance by Hour** | Radial bar chart (24hr clock) | ApexCharts (radial bar) |
| **Weapon Swap Flow** | Sankey diagram | D3.js Sankey |
| **Map Hotspots** | 2D heatmap overlay | Canvas API + gradient |
| **Comparative Stats** | Radar/Spider chart | ApexCharts (radar chart) |

---

## 🧮 Calculation Examples

### Example 1: Reload Efficiency
```sql
-- ClickHouse query
SELECT
    actor_id,
    actor_name,
    countIf(event_type = 'weapon_reload') AS total_reloads,
    countIf(event_type = 'weapon_reload_done') AS completed_reloads,
    (completed_reloads / total_reloads) * 100 AS reload_completion_pct,
    avg(time_diff) AS avg_reload_time
FROM (
    SELECT
        actor_id,
        actor_name,
        event_type,
        dateDiff('millisecond', 
            lagInFrame(timestamp, 1) OVER (PARTITION BY actor_id ORDER BY timestamp),
            timestamp
        ) / 1000.0 AS time_diff
    FROM raw_events
    WHERE event_type IN ('weapon_reload', 'weapon_reload_done')
    AND match_id = 'xyz123'
)
GROUP BY actor_id, actor_name
ORDER BY reload_completion_pct DESC
```

### Example 2: Stance Kill Distribution
```sql
-- Requires state tracking (complex)
-- Approach: Track stance changes and correlate with kills
WITH stance_intervals AS (
    SELECT
        actor_id,
        event_type AS stance,
        timestamp AS start_time,
        lead(timestamp) OVER (PARTITION BY actor_id ORDER BY timestamp) AS end_time
    FROM raw_events
    WHERE event_type IN ('player_prone', 'player_crouch', 'player_stand')
),
kills_with_stance AS (
    SELECT
        k.actor_id,
        k.timestamp AS kill_time,
        s.stance
    FROM raw_events k
    JOIN stance_intervals s
        ON k.actor_id = s.actor_id
        AND k.timestamp BETWEEN s.start_time AND s.end_time
    WHERE k.event_type = 'kill'
)
SELECT
    actor_id,
    stance,
    count(*) AS kills
FROM kills_with_stance
GROUP BY actor_id, stance
```

### Example 3: Door Camper Score
```sql
-- Kills within 5 seconds of door_open
SELECT
    k.actor_id,
    k.actor_name,
    count(*) AS door_camper_kills
FROM raw_events k
WHERE k.event_type = 'kill'
  AND EXISTS (
      SELECT 1
      FROM raw_events d
      WHERE d.event_type = 'door_open'
        AND d.map_name = k.map_name
        AND dateDiff('second', d.timestamp, k.timestamp) BETWEEN 0 AND 5
        AND distance(k.actor_pos_x, k.actor_pos_y, d.pos_x, d.pos_y) < 10
  )
GROUP BY k.actor_id, k.actor_name
ORDER BY door_camper_kills DESC
```

---

## 🚀 Implementation Roadmap

### Phase 1: Core Metrics (Current Sprint)
- [x] K/D, Accuracy, Headshot %
- [x] Weapon-specific stats
- [ ] Stance distribution
- [ ] Distance-based metrics

### Phase 2: Advanced Metrics (Sprint 2)
- [ ] Reload behavior metrics
- [ ] Weapon swap analysis
- [ ] Pain threshold metrics
- [ ] Objective performance

### Phase 3: Composite Metrics (Sprint 3)
- [ ] Time-based analytics (best hour, fatigue)
- [ ] Door camper, explosion artist
- [ ] Vehicle/turret mastery
- [ ] Bot farming metrics

### Phase 4: Creative Metrics (Sprint 4)
- [ ] "DNA Profile" (Tactical vs Aggressive scoring)
- [ ] Clutch factor (performance under pressure)
- [ ] Consistency index
- [ ] "Unlucky" metric (most pain without kill)

---

## 📝 Notes for Developers

1. **State Tracking**: Metrics like stance kills require tracking player state over time. Use ClickHouse window functions or maintain state in Redis.
2. **Performance**: Pre-calculate expensive metrics in materialized views. Avoid on-the-fly calculations for dashboards.
3. **Time Correlation**: Many metrics require correlating events within time windows. Use `dateDiff` and `BETWEEN` efficiently.
4. **Position-Based Metrics**: Distance calculations need `sqrt((x1-x2)^2 + (y1-y2)^2)`. Index position fields for performance.
5. **Null Handling**: Not all events have all fields. Use `coalesce` and `ifNull` liberally.

**Confidence**: 98% (Need to validate some event correlations with actual gameplay)

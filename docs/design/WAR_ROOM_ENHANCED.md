# 🎯 War Room Enhanced - Data Science Specification

> Deep tactical insight for competitive FPS players

## Philosophy

Move beyond basic counters to **actionable intelligence**:
- "You are 20% more lethal with the Sniper Rifle on Map A between 20:00-22:00"
- "Your accuracy drops 15% after 90 minutes of play"
- "You win 30% more rounds when you're the bomb carrier"

---

## 1. Peak Performance System

### 1.1 Time-Based Analysis

**When are you at your best?**

| Dimension | Metrics | Insight Example |
|-----------|---------|-----------------|
| **Hour of Day** | K/D, Accuracy, Win Rate | "Peak hours: 20:00-23:00 (+18% K/D)" |
| **Day of Week** | K/D, Win Rate, Playtime | "Best day: Saturday (+12% Win Rate)" |
| **Session Duration** | K/D degradation curve | "Performance drops after 90 min" |
| **Match Progression** | Early/Mid/Late game K/D | "Strong opener, weak closer" |

**API Endpoint**: `GET /api/v1/stats/player/{guid}/peak-performance`

```json
{
  "time_of_day": {
    "best_hour": 21,
    "best_kd": 1.85,
    "worst_hour": 9,
    "worst_kd": 0.92,
    "hourly_kd": [0.8, 0.9, 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.5, ...]
  },
  "day_of_week": {
    "best_day": "Saturday",
    "best_win_rate": 58.3,
    "worst_day": "Monday", 
    "daily_stats": {...}
  },
  "session_fatigue": {
    "optimal_duration_minutes": 75,
    "kd_at_30_min": 1.52,
    "kd_at_60_min": 1.41,
    "kd_at_90_min": 1.18,
    "kd_at_120_min": 0.95
  },
  "match_momentum": {
    "first_5_kills_kd": 1.8,
    "mid_game_kd": 1.4,
    "last_5_kills_kd": 1.1
  }
}
```

### 1.2 Context-Based Analysis

**Where are you at your best?**

| Dimension | Metrics | Insight Example |
|-----------|---------|-----------------|
| **Map** | K/D, Accuracy, Win Rate per map | "Best map: mohdm6 (+25% K/D)" |
| **Game Mode** | K/D, Obj contribution | "Best in Objective modes (+15%)" |
| **Server** | K/D by server | "Dominant on [AMG] server" |
| **Team Size** | 1v1 to 32v32 | "Better in small games" |

**API Endpoint**: `GET /api/v1/stats/player/{guid}/context-performance`

```json
{
  "maps": [
    {"map": "mohdm6", "kd": 1.85, "accuracy": 34.2, "matches": 45, "percentile": 92},
    {"map": "mohdm4", "kd": 1.22, "accuracy": 28.1, "matches": 32, "percentile": 75}
  ],
  "game_modes": [
    {"mode": "obj", "kd": 1.65, "obj_score": 850, "win_rate": 58},
    {"mode": "tdm", "kd": 1.42, "win_rate": 52}
  ],
  "servers": [
    {"server": "AMG TDM", "kd": 2.1, "matches": 28},
    {"server": "N00b Zone", "kd": 0.8, "matches": 5}
  ]
}
```

---

## 2. Drill-Down Architecture

Every stat becomes a gateway to deeper analysis.

### 2.1 Stat Hierarchy Tree

```
K/D Ratio (1.45)
├── By Weapon Class
│   ├── Rifles (1.82)
│   │   ├── M1 Garand (2.1)
│   │   ├── Kar98k (1.6)
│   │   └── By Range
│   │       ├── Close (<10m): 0.8
│   │       ├── Medium (10-30m): 1.9
│   │       └── Long (>30m): 2.4
│   ├── SMGs (1.35)
│   │   └── By Stance
│   │       ├── Standing: 1.2
│   │       ├── Crouching: 1.5
│   │       └── Prone: 0.9
│   └── Sidearms (0.65)
├── By Map
│   ├── mohdm6: 1.85
│   └── mohdm4: 1.22
├── By Time of Day
│   ├── Morning: 0.92
│   ├── Afternoon: 1.35
│   └── Evening: 1.72
└── By Enemy Type
    ├── vs Verified Players: 1.1
    └── vs Unknown Players: 1.8
```

### 2.2 Drill-Down API

**API Endpoint**: `GET /api/v1/stats/player/{guid}/drilldown`

**Parameters**:
- `stat`: The base stat (kd, accuracy, winrate, etc.)
- `dimensions[]`: Array of dimensions to break down by
- `filters`: Optional filters to apply

**Example**: `/api/v1/stats/player/abc123/drilldown?stat=kd&dimensions[]=weapon&dimensions[]=map`

```json
{
  "base_stat": "kd",
  "base_value": 1.45,
  "breakdown": {
    "weapon": [
      {
        "label": "M1 Garand",
        "value": 2.1,
        "sample_size": 450,
        "children": {
          "map": [
            {"label": "mohdm6", "value": 2.4, "sample_size": 120},
            {"label": "mohdm4", "value": 1.8, "sample_size": 85}
          ]
        }
      }
    ]
  }
}
```

### 2.3 Frontend Drill-Down Pattern

```html
<!-- Every stat card is clickable -->
<div class="stat-card drillable" 
     data-stat="kd" 
     data-value="1.45"
     onclick="drillDown('kd')">
  <div class="stat-value">1.45</div>
  <div class="stat-label">K/D Ratio</div>
  <div class="drill-indicator">▼ Click to explore</div>
</div>

<!-- Modal expands with breakdown -->
<div id="drilldown-modal" class="modal">
  <div class="drill-breadcrumb">
    K/D → <span>Weapon Class</span> → <span>Range</span>
  </div>
  <div class="drill-content">
    <!-- Dynamic content loaded via HTMX -->
  </div>
</div>
```

---

## 3. Creative "Combo" Metrics

Cross-referencing event tables to find novel correlations.

### 3.1 Movement + Combat Combos

| Metric | Formula | Insight |
|--------|---------|---------|
| **Run & Gun Index** | Kills while moving / Total kills | "You're a mobile predator" |
| **Bunny Hop Efficiency** | Kills within 2s of jump / Jumps | "Jump shots: 12% hit rate" |
| **Sneak Attack Rate** | Kills while crouched + no recent fire / Total kills | "Ambush style: 18%" |
| **Momentum Kills** | Kills within 3s of sprint end | "Rush tactics work for you" |

### 3.2 Health + Objectives Combos

| Metric | Formula | Insight |
|--------|---------|---------|
| **Clutch Factor** | Wins when <25% health | "Clutch god: 34% survival rate" |
| **Objective Aggression** | Damage dealt during objective capture | "Point defender: 850 damage/cap" |
| **Medkit Efficiency** | Time survived after health pickup | "Good resource usage" |

### 3.3 Economy + Survival Combos

| Metric | Formula | Insight |
|--------|---------|---------|
| **Scavenger Score** | Items picked up per death | "Efficient looting" |
| **Kit Synergy** | Win rate with specific weapon combos | "Rifle+Pistol = +15% win rate" |

### 3.4 Combo Metrics API

**API Endpoint**: `GET /api/v1/stats/player/{guid}/combos`

```json
{
  "movement_combat": {
    "run_gun_index": 0.42,
    "run_gun_rank": "Top 15%",
    "bunny_hop_kills": 23,
    "bunny_hop_efficiency": 0.12,
    "crouch_ambush_rate": 0.18,
    "momentum_kills": 156
  },
  "health_objectives": {
    "clutch_wins": 34,
    "clutch_rate": 0.28,
    "objective_damage": 12500,
    "damage_per_capture": 850
  },
  "economy_survival": {
    "scavenger_score": 3.2,
    "avg_pickups_per_life": 4.1,
    "best_loadout": "Garand + Colt",
    "loadout_win_rate": 0.62
  }
}
```

---

## 4. Enhanced War Room UI Sections

### 4.1 "When You're Dangerous" Card

```
┌─────────────────────────────────────────────────────────┐
│  ⚡ PEAK PERFORMANCE                                    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  🕐 Best Time: 20:00 - 23:00  (+18% K/D)               │
│  📅 Best Day: Saturday        (+12% Win Rate)          │
│  🗺️  Best Map: mohdm6          (+25% K/D)              │
│  ⏱️  Sweet Spot: 45-75 min     (Optimal session)       │
│                                                         │
│  [View Full Time Analysis →]                            │
└─────────────────────────────────────────────────────────┘
```

### 4.2 "Signature Moves" Card (Combo Metrics)

```
┌─────────────────────────────────────────────────────────┐
│  🎯 YOUR SIGNATURE MOVES                                │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Run & Gun Master      ████████░░  42%                  │
│  You get 42% of kills while moving                      │
│                                                         │
│  Clutch Artist         ████████░░  34%                  │
│  Win 34% of fights under 25% health                     │
│                                                         │
│  Momentum Hunter       ██████░░░░  28%                  │
│  156 kills within 3s of sprinting                       │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### 4.3 Interactive Drill-Down Table

```
┌─────────────────────────────────────────────────────────┐
│  📊 DEEP DIVE: K/D RATIO (1.45)                         │
├──────────────────────┬────────────┬─────────────────────┤
│  Dimension           │ Best       │ Worst               │
├──────────────────────┼────────────┼─────────────────────┤
│  ▶ Weapon            │ Garand 2.1 │ Colt 0.6            │
│  ▶ Map               │ mohdm6 1.9 │ mohdm2 0.9          │
│  ▶ Time of Day       │ 21:00 1.8  │ 09:00 0.8           │
│  ▶ Range             │ Long 2.4   │ Close 0.8           │
│  ▶ Stance            │ Crouch 1.6 │ Prone 0.9           │
└──────────────────────┴────────────┴─────────────────────┘
Click any row to expand further breakdown
```

---

## 5. Leaderboard Enhancements

### 5.1 New Leaderboard Categories

| Category | Description |
|----------|-------------|
| **Clutch Kings** | Highest wins when <25% health |
| **Run & Gun** | Highest kills-while-moving percentage |
| **Night Owls** | Best evening performers |
| **Consistency** | Lowest K/D variance across sessions |
| **Momentum Masters** | Most kills after sprinting |
| **Scavengers** | Best item pickup efficiency |

### 5.2 Contextual Leaderboards

```
Top Players on mohdm6 (Last 7 Days)
─────────────────────────────────────────
#1  [Player1]  K/D: 2.85  Acc: 42%  
#2  [Player2]  K/D: 2.41  Acc: 38%
#3  [Player3]  K/D: 2.22  Acc: 35%
```

---

## 6. Implementation Phases

### Phase 1: API Foundation
1. Add `PeakPerformanceService` to Go API
2. Add `DrilldownService` with query builder
3. Add `ComboMetricsService` for cross-table queries
4. Create new endpoints in handlers.go

### Phase 2: SMF Integration
1. Add API methods to MohaaStatsAPI.php
2. Create HTMX partials for drill-down modals
3. Update War Room template with new sections

### Phase 3: Visualization
1. ApexCharts for time-based heatmaps
2. Interactive drill-down tree component
3. Combo metric progress bars

### Phase 4: Leaderboards
1. Add new leaderboard categories
2. Contextual leaderboard filters
3. "Your Rank In..." widget

---

## 7. ClickHouse Queries

### Peak Performance by Hour

```sql
SELECT 
    toHour(timestamp) as hour,
    countIf(event_type = 'player_kill' AND actor_id = ?) as kills,
    countIf(event_type = 'player_death' AND actor_id = ?) as deaths,
    if(deaths > 0, kills/deaths, kills) as kd
FROM raw_events
WHERE actor_id = ? OR target_id = ?
GROUP BY hour
ORDER BY hour
```

### Drill-Down: K/D by Weapon by Map

```sql
SELECT 
    extract(extra, 'weapon') as weapon,
    map_name,
    countIf(event_type = 'player_kill') as kills,
    countIf(event_type = 'player_death') as deaths,
    if(deaths > 0, kills/deaths, kills) as kd
FROM raw_events
WHERE actor_id = ?
GROUP BY weapon, map_name
ORDER BY kills DESC
```

### Combo: Kills While Moving

```sql
-- Requires join with recent movement events
WITH recent_movement AS (
    SELECT actor_id, timestamp, 'moving' as state
    FROM raw_events
    WHERE event_type = 'player_distance' 
      AND toFloat64OrZero(extract(extra, 'velocity')) > 100
)
SELECT 
    countIf(k.event_type = 'player_kill' AND m.state = 'moving') as moving_kills,
    count() as total_kills
FROM raw_events k
LEFT ASOF JOIN recent_movement m 
    ON k.actor_id = m.actor_id 
    AND k.timestamp >= m.timestamp
WHERE k.event_type = 'player_kill' AND k.actor_id = ?
```

---

## 8. Data Requirements

### Events Needed

| Event | Required Fields | Used For |
|-------|-----------------|----------|
| `player_kill` | timestamp, actor_id, weapon, map, hitloc, pos | All combat stats |
| `player_death` | timestamp, actor_id, pos | Death analysis |
| `player_distance` | distance, velocity, walked, sprinted | Movement combos |
| `player_jump` | timestamp, pos | Jump shot analysis |
| `player_crouch` | timestamp, duration | Stance analysis |
| `health_pickup` | timestamp, amount | Health efficiency |
| `objective_update` | status, team, pos | Objective combos |
| `round_end` | winner, duration | Clutch analysis |

---

*This specification defines the enhanced War Room dashboard with Peak Performance, Drill-Down, and Combo metrics.*

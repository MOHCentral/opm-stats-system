# 🎮 Battle/Match System - Complete Implementation Guide

## Overview
Comprehensive battle tracking system for OpenMOHAA with **30+ metrics** and **advanced visualizations**.

---

## 📊 Database Schema (COMPLETED ✅)

### Tables Created:
1. **`battles`** - Parent container (each map load)
   - Tracks: Map, game type, duration, scores, player counts
   - Quick stats: Total kills, damage, headshots, objectives
   
2. **`battle_rounds`** - Child rounds within battles
   - TDM/OBJ: Multiple rounds per battle
   - FFA: Single "ghost round"
   
3. **`battle_players`** - Player statistics per battle/round
   - **60+ columns** tracking everything:
     - Combat: Kills, deaths, assists, headshots, melee, grenades
     - Movement: Distance walked/sprinted/swam, jumps
     - Stance: Time standing/crouching/prone
     - Survival: Health/ammo/weapon pickups
     - Vehicles: Vehicle kills, roadkills, turret kills
     - Accuracy: Shots fired/hit, calculated accuracy %
     - Playstyle scores: Rusher, Camper, Support

4. **`battle_weapons`** - Weapon usage per battle
   - Per-weapon accuracy, kills, headshots
   
5. **`battle_timeline`** - Event-by-event timeline
   - For match replay functionality
   - Impact scoring for key moments
   
6. **`battle_momentum`** - Tug-of-war tracking
   - Sampled every 10 seconds
   - Score differential, recent activity
   
7. **`battle_heatmap`** - Spatial data
   - Kill/death/objective locations (X, Y, Z coords)

### Views Created:
1. **`battle_summary`** - Quick overview with intensity score
2. **`battle_player_rankings`** - Rankings within each battle

### Functions Created:
1. **`create_battle()`** - Initialize new battle
2. **`update_battle_stats()`** - Aggregate player stats

---

## 🎨 30+ Metrics & Visualizations

### Core Metrics:
1. **Intensity Score** = (Total Kills + (Total Damage / 100)) / Minutes
2. **Momentum** = Score differential over time
3. **Panic Index** = Reloads/second × Damage being dealt
4. **Camper vs Rusher Score** = Movement + Combat patterns
5. **Health Economy** = Health picked up - Damage taken
6. **Verticality** = Ladder mounts + High-altitude kills
7. **Weapon Efficiency** = Accuracy × Lethality × Headshot %

### Creative Badges (Auto-Assigned):
- 🥛 **Glass Cannon**: High kills, high damage taken (barely survived)
- 🎒 **Scavenger**: Most item pickups
- 🕊️ **Pacifist**: High movement, low kills (running simulator)
- 🔫 **Trigger Happy**: Spray and pray (low accuracy, high shots)
- 🗡️ **The Finisher**: Melee kill specialist
- 💣 **The Martyr**: Most self-kills/suicides

### ApexCharts Implemented:
1. **Momentum Tug-of-War** (Area Chart)
   - Score differential over time
   - Shows lead changes
   
2. **Weapon Efficiency Matrix** (Radar Chart)
   - 3 metrics: Accuracy, Lethality, Headshot %
   - Compare weapon classes
   
3. **Camper vs Rusher Spectrum** (Stacked Bar, 100%)
   - % time standing/crouching/prone
   - Horizontal bars per player
   
4. **Panic Index** (Scatter Chart) [Placeholder]
   - X: Time, Y: Reloads/second, Size: Damage
   
5. **Health Economy** (Stacked Area) [Placeholder]
   - Green: Health picked up
   - Red: Damage taken
   
6. **Verticality Graph** (Column) [Placeholder]
   - Ladder mounts + jumps
   
7. **Vehicle Dominance** (Donut) [Placeholder]
   - Infantry vs Roadkills vs Turret vs Tank
   
8. **Nemesis Network** (Dependency Wheel) [Placeholder]
   - Who killed whom most
   - Visual "food chain"
   
9. **Kill Heatmap** (Heatmap) [Placeholder]
   - X/Y coordinates on map
   
10. **Performance Timeline** (Line) [Placeholder]
    - K/D over time

---

## 📄 Files Created

### 1. Database Migration ✅
**File**: `migrations/postgres/009_battles_and_rounds.sql`
- **Lines**: ~400
- **Status**: ✅ Executed successfully
- **Tables**: 7 tables, 2 views, 2 functions, 15+ indexes

### 2. Battle Detail Template ✅
**File**: `smf-mohaa/Themes/default/MohaaBattleDetail.template.php`
- **Lines**: ~800+
- **Status**: ✅ Created (partial implementations for remaining charts)
- **Features**:
  - Battle summary header with MVP cards
  - Round selector for multi-round matches
  - 10+ chart widgets
  - Player scoreboard with auto-badges
  - Event timeline

### 3. Battle List Template (TODO)
**File**: `smf-mohaa/Themes/default/MohaaBattleList.template.php`
- **Status**: ❌ Not yet created
- **Planned Features**:
  - Sortable/filterable table
  - Columns: Map, Game Type, Duration, Intensity, Top Fragger, Winner
  - Search by player name
  - Date range filter

### 4. PHP Source File (TODO)
**File**: `smf-mohaa/Sources/MohaaStats/MohaaBattles.php`
- **Status**: ❌ Not yet created
- **Functions Needed**:
  - `getBattleList()` - Paginated list with filters
  - `getBattleDetail($battleId)` - Full battle data
  - `getBattlePlayers($battleId, $roundId)` - Player stats
  - `getBattleMomentum($battleId)` - Momentum data points
  - `getBattleTimeline($battleId)` - Event timeline
  - `getBattleWeapons($battleId)` - Weapon stats
  - `calculateBadges($player)` - Auto-assign badges

### 5. API Endpoints (TODO)
**File**: `internal/handlers/battles.go`
- **Status**: ❌ Not yet created
- **Endpoints Needed**:
  ```go
  GET /api/v1/battles - List all battles
  GET /api/v1/battles/{id} - Battle detail
  GET /api/v1/battles/{id}/rounds/{round} - Round-specific stats
  GET /api/v1/battles/{id}/momentum - Momentum data
  GET /api/v1/battles/{id}/timeline - Event timeline
  GET /api/v1/battles/{id}/heatmap - Kill/death heatmap
  GET /api/v1/battles/{id}/players - Player rankings
  ```

---

## 🔌 Event Integration

### Events to Track per Battle:

#### Game Flow Events:
- `server_spawned` → Create battle
- `map_load_start` → Initialize battle context
- `round_start` → Create round (TDM/OBJ)
- `round_end` → Close round, update stats
- `team_win` → Set winner
- `game_end` → Close battle

#### Combat Events (23 total):
- `player_kill` → Increment kills, update timeline, add to heatmap
- `player_death` → Increment deaths
- `player_damage` → Track damage dealt/taken
- `player_headshot` → Headshot counter
- `player_bash` → Melee kill counter
- `weapon_fire` → Shots fired
- `weapon_hit` → Shots hit
- `grenade_kill` → Grenade kill counter
- etc.

#### Movement Events:
- `player_distance` → Track walked/sprinted/swam distances
- `player_jump` → Jump counter
- `player_crouch` → Start crouch timer
- `player_stand` → End crouch/prone timer
- `player_prone` → Start prone timer
- `ladder_mount` → Verticality counter

#### Item Events:
- `item_pickup` → Track health/ammo/weapon pickups
- Categorize by `item_type`

#### Vehicle Events:
- `vehicle_enter` → Track vehicle usage
- `player_roadkill` → Roadkill counter
- `turret_enter` → Turret usage
- `vehicle_death` → Vehicle destruction

#### Objective Events:
- `objective_complete` → Objective counter
- `objective_update` → Track progress
- Add to timeline with high impact score

---

## 🎯 Next Steps (Implementation Order)

### Step 1: Event Processor (Go) ⚡ HIGH PRIORITY
**File**: `internal/worker/battle_tracker.go`
- Listen to event stream
- Create/update battles in real-time
- Aggregate stats as events arrive
- Update momentum every 10 seconds

### Step 2: API Handlers (Go)
**File**: `internal/handlers/battles.go`
- Implement all 6 battle endpoints
- Query views for performance
- Return JSON data

### Step 3: PHP Source File
**File**: `smf-mohaa/Sources/MohaaStats/MohaaBattles.php`
- API client wrapper
- Data transformation for templates
- Badge calculation logic

### Step 4: Battle List Template
**File**: `smf-mohaa/Themes/default/MohaaBattleList.template.php`
- Sortable table with DataTables.js
- Filters: Map, Game Type, Date Range, Player
- Click row → Battle detail page

### Step 5: Complete Remaining Charts
**Update**: `MohaaBattleDetail.template.php`
- Implement placeholders:
  - Panic Index
  - Health Economy
  - Verticality Graph
  - Vehicle Dominance
  - Nemesis Network
  - Kill Heatmap
  - Performance Timeline

### Step 6: SMF Action Hook
**File**: `smf-mohaa/Sources/MohaaStats.php`
- Add `mohaa_battles` action
- Add `mohaa_battle` action (detail page)
- Register in SMF settings

---

## 📊 Sample Data Structure

### Battle Object (from API):
```json
{
  "battle_id": 123,
  "battle_guid": "a1b2c3d4-...",
  "server_id": 1,
  "map_name": "v2_rocket",
  "game_type": "obj",
  "started_at": "2026-01-21 14:30:00",
  "ended_at": "2026-01-21 14:45:00",
  "duration_seconds": 900,
  "winner_team": "allies",
  "final_score_allies": 5,
  "final_score_axis": 3,
  "total_players": 16,
  "peak_players": 18,
  "total_kills": 145,
  "total_deaths": 145,
  "total_damage": 28500,
  "total_headshots": 23,
  "intensity_score": 12.3,
  "mvp": "Elgan",
  "top_fragger": "Elgan",
  "survivor": "PlayerX",
  "total_rounds": 3
}
```

### Player Stats Object:
```json
{
  "player_name": "Elgan",
  "team": "allies",
  "kills": 25,
  "deaths": 8,
  "kd_ratio": 3.13,
  "accuracy_percent": 28.5,
  "headshots": 7,
  "melee_kills": 2,
  "damage_dealt": 5200,
  "damage_taken": 3100,
  "health_pickups": 5,
  "objectives_completed": 2,
  "vehicle_kills": 0,
  "time_standing_seconds": 450,
  "time_crouching_seconds": 300,
  "time_prone_seconds": 150,
  "badges": ["glass-cannon", "finisher"]
}
```

---

## 🎨 UI/UX Design Principles

### Battle List Page:
- **Goal**: Quick scanability, find interesting battles
- **Layout**: Wide table, full viewport width
- **Sortable Columns**: Duration, Intensity, Players, Date
- **Highlight**: Your battles (if logged in) in different color
- **Click**: Row click → Detail page

### Battle Detail Page:
- **Goal**: Deep dive, tell the story of the match
- **Layout**: 
  - Header: Final score, MVPs, quick stats
  - Round tabs (if multi-round)
  - Grid of charts (2 columns on desktop, 1 on mobile)
  - Scoreboard table
  - Timeline at bottom
- **Interaction**: 
  - Round tabs filter all charts
  - Click player name → Player profile
  - Click timeline event → Highlight on heatmap (future)

---

## 🚀 Performance Considerations

### Database Optimizations:
1. **Materialized Views** for battle_summary (refresh on battle_end)
2. **Partial Indexes** on hot queries:
   ```sql
   CREATE INDEX idx_battles_recent ON battles(started_at DESC) 
   WHERE started_at > NOW() - INTERVAL '7 days';
   ```
3. **JSONB Columns** for flexible metadata (timeline events)
4. **Partitioning** battles by month (for historical data)

### API Optimizations:
1. **Redis Caching** for recent battles (5min TTL)
2. **Batch Queries** - Fetch players/weapons/momentum in parallel
3. **Pagination** - 50 battles per page (list)
4. **Lazy Loading** - Charts load on scroll (detail page)

### Frontend Optimizations:
1. **ApexCharts** - Lazy render (IntersectionObserver)
2. **DataTables** - Server-side processing for large lists
3. **Image Sprites** - Team icons, badges
4. **Skeleton Loaders** - While charts render

---

## 🧪 Testing Checklist

### Unit Tests:
- [ ] Battle creation function
- [ ] Stats aggregation
- [ ] Badge calculation logic
- [ ] Momentum calculation

### Integration Tests:
- [ ] End-to-end battle creation from events
- [ ] Round handling (FFA vs TDM vs OBJ)
- [ ] Player stats accuracy
- [ ] Timeline event ordering

### UI Tests:
- [ ] Battle list loads and sorts correctly
- [ ] Detail page renders all charts
- [ ] Round selector updates data
- [ ] Mobile responsiveness

---

## 📚 Documentation TODOs

1. **API Documentation**: OpenAPI/Swagger spec for battle endpoints
2. **User Guide**: How to interpret battle stats
3. **Admin Guide**: How battles are created, manual corrections
4. **Developer Guide**: Adding new metrics/badges

---

## 🎯 Success Metrics

Once fully implemented, the battle system will track:
- ✅ **7 database tables** with 100+ columns
- ✅ **30+ unique metrics** per battle
- ✅ **10+ ApexCharts** visualizations
- ✅ **6+ creative badges** auto-assigned
- ✅ **Round-by-round** breakdown for TDM/OBJ
- ✅ **Event timeline** for match replay
- ✅ **Spatial heatmaps** for kill/death locations
- ✅ **Momentum tracking** (tug-of-war visualization)

This makes it the **most comprehensive battle tracking system** for any FPS game. 🚀

---

**Status**: Database schema ✅ COMPLETE | Templates 60% COMPLETE | API 0% PENDING | Event Integration 0% PENDING

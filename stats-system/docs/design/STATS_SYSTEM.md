# MOHAA Mass Stats System - Complete Specification

## 🎯 Vision

Build the most comprehensive telemetry, statistics, and competitive gaming infrastructure ever created for a classic FPS. Every single action generates data. Every stat can be explored infinitely deep. Every click leads to more insights.

---

## 📊 Core Design Principle: DRILL-DOWN EVERYTHING

**Every statistic is clickable and explodes into a detailed breakdown table.**

### Example Drill-Down Paths:

```
SERVER PAGE
  └─> "Most Kills" statistic
       └─> Table: All players ranked by kills on this server
            └─> Click player row
                 └─> Player profile on this specific server
                      └─> Their weapon breakdown
                           └─> Each weapon's kill/death on this server

PLAYER PROFILE
  └─> "3,421 Headshots" statistic
       └─> Table: Headshots broken down by:
            ├─> By Weapon (Kar98: 1892, Thompson: 423, ...)
            ├─> By Map (Stalingrad: 892, V2 Rocket: 567, ...)
            ├─> By Match (Match #1234: 15, Match #1233: 8, ...)
            └─> By Time Period (Today: 12, This Week: 89, ...)
                 └─> Click any row for even deeper breakdown

LEADERBOARD
  └─> "Best K/D Ratio" 
       └─> Table: All players sorted by K/D
            └─> Click player
                 └─> Their K/D broken down by:
                      ├─> By Weapon
                      ├─> By Map
                      ├─> By Team (Allies vs Axis)
                      └─> By Time of Day (Peak hours vs Off-peak)
```

---

## 🧠 Master Statistics Taxonomy

### Category A: Player Combat Statistics

Each stat links to a granular breakdown table.

| Statistic | Description | Drill-Down Dimensions |
|-----------|-------------|----------------------|
| **Total Kills** | Lifetime kill count | By weapon, map, server, match, team, time period, victim |
| **Total Deaths** | Lifetime death count | By weapon, map, server, match, team, time period, killer |
| **K/D Ratio** | Kills / Deaths | By weapon, map, match, against specific opponents |
| **Headshots** | Head hit kills | By weapon, distance, stance (standing/crouch/prone), map |
| **Headshot %** | Headshots / Total Kills | By weapon, map, over time (trend) |
| **Torso Kills** | Center mass kills | By weapon, distance |
| **Limb Kills** | Arms/legs kills | By weapon (indicates spray patterns) |
| **Accuracy** | Shots Hit / Shots Fired | By weapon, range, stance, movement state |
| **Damage Dealt** | Total damage inflicted | By weapon, map, match |
| **Damage Taken** | Total damage received | By weapon (what hurts you most), attacker |
| **ADR** | Avg Damage per Round | By map, by match, trend over time |
| **Trade Kills** | Kills within 3s of teammate death | By map (which maps are you trading well on) |
| **Trade Deaths** | Deaths within 3s of your kill | By map, by weapon |
| **First Blood** | First kill of round/match | By map, by weapon, % rate |
| **Final Kill** | Last kill of round/match | By map, by weapon, % rate |
| **Clutch Wins** | Won when last alive | 1v1, 1v2, 1v3, 1v4, 1v5+ breakdowns |
| **Killstreaks** | Max consecutive kills | By weapon, by map, per match |
| **Multi-kills** | 2+, 3+, 4+, 5+ kills | Double, Triple, Quad, Ace counts |
| **Spawn Kills** | Kills <5s after victim spawn | By map (spawn trap detection) |
| **Backstabs** | Kills where victim faced away | By weapon, by map |
| **Turn-On Kills** | Kills after being shot first | By weapon (reaction time indicator) |
| **Long Range Kills** | Kills >100m distance | By weapon, by map |
| **Close Range Kills** | Kills <5m | By weapon |
| **Air Kills** | Kills while jumping | By weapon |
| **Prone Kills** | Kills while prone | By weapon, by map |
| **Low HP Kills** | Kills when <15 HP | By weapon |
| **Revenge Kills** | Kill your previous killer | Count, time between |
| **Grenade Kills** | Explosive kills | Frag, smoke proximity |
| **Melee Kills** | Bash/knife kills | By victim's weapon (disarm success) |
| **Wallbang Kills** | Through-wall kills | By weapon, by wall type |
| **Collateral Kills** | Multi-kills with one shot | By weapon (sniper mostly) |

### Category B: Player Weapon Statistics

| Statistic | Description | Drill-Down Dimensions |
|-----------|-------------|----------------------|
| **Weapon Usage Time** | Time equipped | By weapon, by map, trend |
| **Weapon Kills** | Kills per weapon | By hitloc, by distance, by map |
| **Weapon Deaths** | Deaths TO weapon | Who kills you with what |
| **Weapon Accuracy** | Per-weapon accuracy | By distance, by target stance |
| **Shots Fired** | Ammo expended | By weapon, by match |
| **Shots Hit** | Registered hits | By weapon, by hitloc |
| **Reload Count** | Times reloaded | By weapon, by map |
| **Reload Deaths** | Died while reloading | By weapon (reload cancel needed?) |
| **Empty Mag Deaths** | Died with 0 ammo | By weapon |
| **Weapon Switches** | How often changed | Pattern analysis |
| **Weapon Pickups** | Scavenged weapons | Ground pickups |
| **Grenade Efficiency** | Damage per grenade | By type (frag/smoke/stun) |
| **Grenade Directs** | Direct hit grenades | By distance thrown |
| **Rocket Directs** | Non-splash rocket kills | By distance |
| **Splash Kills** | Explosion radius kills | Avg enemies per explosion |

### Category C: Player Movement Statistics

| Statistic | Description | Drill-Down Dimensions |
|-----------|-------------|----------------------|
| **Distance Traveled** | Total km moved | By match, by map |
| **Sprint Distance** | Distance sprinting | By map (aggressive vs passive) |
| **Crouch Time** | Time crouching | By map |
| **Prone Time** | Time prone | By map (camper detection) |
| **Stationary Time** | Time not moving | By position (camping spots) |
| **Jump Count** | Total jumps | By map |
| **Air Time** | Cumulative jump duration | By map |
| **Ladder Climbs** | Ladder uses | By map |
| **Fall Deaths** | Environmental deaths | By map |
| **Drown Deaths** | Water deaths | By map |
| **Average Velocity** | Movement speed | By match, by situation |
| **Sprint Ratio** | Sprint / Walk time | Aggression indicator |
| **Stance Transitions** | Crouch/prone frequency | Evasion tactics |
| **Lean Usage** | Left lean vs Right lean | Preference detection |

### Category D: Player Session Statistics

| Statistic | Description | Drill-Down Dimensions |
|-----------|-------------|----------------------|
| **Playtime** | Total hours | By server, by map, by day of week |
| **Matches Played** | Total matches | By gametype, by map |
| **Win Rate** | Wins / Total | By map, by team, by gametype |
| **Quit Rate** | Ragequits (leave before end) | By map, by losing status |
| **Avg Match Duration** | Time in matches | By gametype |
| **Peak Hours** | When they play | Day/time heatmap |
| **First Seen** | Account creation | Date |
| **Last Seen** | Last activity | Date |
| **Consecutive Days** | Login streak | Max streak |
| **Chat Messages** | Total messages sent | By type (team/all) |
| **Commands Used** | Console commands | By command type |

### Category E: Server Statistics

Each server has its own complete stat page.

| Statistic | Description | Drill-Down |
|-----------|-------------|------------|
| **Total Players** | Unique players seen | List all with personal stats |
| **Total Matches** | Matches hosted | List all matches |
| **Total Kills** | All kills on server | By player, by weapon, by map |
| **Total Playtime** | Cumulative player hours | By player |
| **Most Popular Map** | Most played map | By playtime, by votes |
| **Peak Players** | Max concurrent | Historical trend |
| **Average Players** | Mean concurrency | By time of day |
| **Top Player** | Highest kill player | Full profile |
| **Kill Leader** | Most kills on this server | leaderboard |
| **Death Leader** | Most deaths on this server | leaderboard |
| **Longest Match** | Longest match duration | Match details |
| **Shortest Match** | Quickest match | Match details |

### Category F: Map Statistics

Each map has comprehensive analytics.

| Statistic | Description | Drill-Down |
|-----------|-------------|------------|
| **Times Played** | Total match count | By server, by date |
| **Total Kills** | All kills on map | By weapon, by location |
| **Kill Heatmap** | Spatial kill distribution | Interactive 2D overlay |
| **Death Heatmap** | Spatial death distribution | Interactive 2D overlay |
| **Hotspots** | Highest kill density areas | Named zones |
| **Camping Spots** | Low-movement kill areas | Positions list |
| **Spawn Trap Areas** | Spawn proximity kills | Warning zones |
| **Average Duration** | Typical match length | By gametype |
| **Win Rate by Team** | Allies vs Axis bias | Balance indicator |
| **Most Used Weapon** | Dominant weapon | By position on map |
| **Longest Kill Distance** | Map sniper record | Player + position |
| **Most Grenade Kills** | Nade spam zones | Position heatmap |

### Category G: Match Statistics

Every match has complete replay data.

| Statistic | Description | Details |
|-----------|-------------|---------|
| **Match ID** | Unique identifier | Link to full match page |
| **Duration** | Total length | Minutes:Seconds |
| **Score** | Allies vs Axis | Round by round |
| **MVP** | Highest performing player | Multiple categories |
| **Kill Feed** | Complete kill timeline | Scrollable log |
| **Scoreboard** | All players stats | Sortable table |
| **Heatmap** | Match-specific kills | 2D overlay |
| **Timeline** | Event waterfall | Visualized graph |
| **Momentum Graph** | Team advantage over time | Dual-color wave |
| **Weapon Distribution** | Weapons used | Pie chart |
| **Round Breakdown** | Per-round stats | Expandable sections |

### Category H: Achievement Statistics

| Statistic | Description | Drill-Down |
|-----------|-------------|------------|
| **Total Achievements** | Unlocked count | List all |
| **Achievement Points** | Cumulative points | By category |
| **Rarest Achievement** | Lowest % unlock | Details |
| **Most Recent** | Latest unlocked | Date/match |
| **Category Progress** | Per-category completion | By tier |
| **Time to Unlock** | Average unlock speed | Comparison to others |

---

## 🎖️ Extended Achievement Ideas

### Tier 1: First Steps (Easy - 89%+ players have these)
1. **Boot Camp Graduate** - Complete first match
2. **First Blood** - Get your first kill
3. **Took One for the Team** - Die for the first time
4. **Sharpshooter** - Get your first headshot
5. **Social Butterfly** - Send first chat message
6. **Team Player** - Join a team
7. **Door Opener** - Use first door
8. **Tourist** - Play on 3 different maps
9. **Regular** - Play 10 matches
10. **Returning Soldier** - Log in on 2 different days

### Tier 2: Getting Serious (Medium - 30-60% players)
11. **Centurion** - 100 kills
12. **Headhunter** - 100 headshots
13. **Marathon Runner** - Travel 10km total
14. **Trigger Happy** - Fire 10,000 shots
15. **Lead Magnet** - Take 10,000 damage
16. **Survivor** - Win 50 matches
17. **World Traveler** - Play on 10 different maps
18. **Weapon Collector** - Get kills with 10 different weapons
19. **Dedicated** - 24 hours total playtime
20. **Ladder Climber** - Climb 100 ladders
21. **Bunny** - Jump 1,000 times
22. **Snake** - Prone for 1 hour total
23. **Grenadier** - 50 grenade kills
24. **Avenger** - 25 trade kills
25. **Clutch Player** - Win 10 1v1 clutches

### Tier 3: Expert (Hard - 10-30% players)
26. **Thousand Kills Club** - 1,000 kills
27. **The Surgeon** - 10 consecutive headshots
28. **Ironman** - 100 hours playtime
29. **Sniper Elite** - 50 kills >100m distance
30. **Close Quarters Specialist** - 100 kills <5m
31. **Air Jordan** - Kill while mid-air
32. **Ninja** - 50 backstab kills
33. **Terminator** - 10 killstreak
34. **Wrecking Ball** - Triple kill
35. **Untouchable** - Win a round with 0 deaths and 5+ kills
36. **Turn-On King** - 25 turn-on kills
37. **One Clip Wonder** - Kill 3 enemies without reloading
38. **Master of Arms** - 100 kills with 5 different weapons
39. **Map Master** - Win 50 matches on same map
40. **Dominator** - Kill same player 10 times in one match

### Tier 4: Elite (Very Hard - 5-10% players)
41. **Legend** - 10,000 kills
42. **Immortal** - 500 hours playtime
43. **Precision Machine** - 50% overall accuracy
44. **Flawless Victory** - Win match with 0 deaths
45. **Ace** - Kill entire enemy team solo (5+)
46. **Rampage** - 20 killstreak
47. **Quad Feed** - Quad kill
48. **Clean Sweep** - Win 5 1v3+ clutches
49. **Tournament Victor** - Win a tournament
50. **Long Shot Legend** - Kill at 200m+ distance

### Tier 5: Legendary (Rare - 1-5% players)
51. **Living Legend** - 50,000 kills
52. **Eternal Warrior** - 1,000 hours playtime
53. **The One** - #1 on leaderboard for 7 days
54. **Unkillable** - 5+ K/D ratio lifetime
55. **Pentakill** - 5 rapid kills
56. **Dynasty** - Win 3 tournaments
57. **Server King** - Most kills on any server

### Tier 6: Hall of Shame (Funny/Embarrassing)
58. **Kenny** - Die first in 5 consecutive rounds
59. **Oops** - Kill yourself with your own grenade
60. **Friendly Fire Incident** - Kill a teammate
61. **Gravity's Victim** - 10 fall deaths
62. **Fish Out of Water** - Drown
63. **Suppressing Fire** - 0 kills with 100+ shots in a match
64. **Reload Junkie** - Reload 1,000 times
65. **Rage Quit** - Leave 10 matches early
66. **Target Practice** - Die 100 times without getting a kill
67. **Pacifist** - Complete match with 0 kills

### Tier 7: Hidden/Secret
68. **Found the Easter Egg** - Discover hidden spot on map
69. **Midnight Warrior** - Play at 3 AM local time
70. **Marathon Man** - 42km traveled
71. **Door Master** - Open 500 doors
72. **Chat Warrior** - Send 1,000 messages
73. **Ghost** - Win a round with 0 shots fired
74. **Sticky Fingers** - Pick up 500 items

---

## 🖥️ UI/UX Specifications

### Design Philosophy
- **Use SMF native CSS classes** - No custom styling
- **Tables for everything** - `table_grid`, `windowbg`, `title_bar`
- **Clickable stats** - Every number is a link
- **Breadcrumb navigation** - Always know where you are
- **Pagination** - Handle large datasets

### SMF CSS Classes to Use
```html
<!-- Headers -->
<div class="cat_bar"><h3 class="catbg">Title</h3></div>

<!-- Content areas -->
<div class="windowbg">Content here</div>

<!-- Tables -->
<table class="table_grid">
    <thead><tr class="title_bar"><th>Column</th></tr></thead>
    <tbody><tr class="windowbg"><td>Data</td></tr></tbody>
</table>

<!-- Buttons -->
<a href="#" class="button">Action</a>

<!-- Pagination -->
<div class="pagesection"><nav>Page links</nav></div>
```

### Interactive Elements
1. **Stat Cards** - Click any stat to drill down
2. **Sortable Tables** - Click headers to sort
3. **Filters** - Dropdown for time period, weapon, map
4. **Search** - Find players, matches, servers
5. **Export** - CSV download for any table

---

## 📡 API Endpoints for Drill-Down

```
# Player stat drill-down
GET /api/v1/stats/player/{guid}/kills
    ?group_by=weapon|map|server|match|victim|day
    &start_date=2026-01-01
    &end_date=2026-01-18
    &limit=100
    &offset=0

# Server stat drill-down
GET /api/v1/stats/server/{id}/top-players
    ?stat=kills|deaths|kdr|headshots|playtime
    &limit=100

# Map stat drill-down
GET /api/v1/stats/map/{name}/leaderboard
    ?stat=kills|wins|playtime
    &limit=100

# General leaderboards
GET /api/v1/stats/leaderboard
    ?stat=kills|kdr|headshots|wins|playtime
    &scope=global|server|map
    &period=all|month|week|day
    &limit=100

# [NEW] Dynamic Drill-Down API (Flexible Query)
GET /api/v1/stats/query
    ?dimension=weapon|map|server|timestamp|hitloc  <-- What to group by
    &metric=kills|deaths|kdr|headshots|accuracy     <-- What to measure
    &filter_player_guid={guid}                      <-- Optional filters
    &filter_map={map_name}
    &filter_weapon={weapon}
    &filter_server={server_id}
    &start_date={iso8601}
    &end_date={iso8601}
    &limit=100

    Example: "My headshot % with Kar98 on Stalingrad"
    GET /api/v1/stats/query?dimension=hitloc&metric=kills&filter_player_guid=...&filter_weapon=kar98&filter_map=obj/obj_team1
```

---

## 🗄️ ClickHouse Tables for Analytics

```sql
-- Raw events (partitioned by day)
CREATE TABLE raw_events (
    event_id UUID,
    event_type LowCardinality(String),
    timestamp DateTime64(3),
    match_id String,
    server_id String,
    map_name LowCardinality(String),
    
    -- Player info
    player_guid String,
    player_name String,
    player_member_id Nullable(UInt32),
    player_team LowCardinality(String),
    
    -- Target info (for kill/damage events)
    target_guid Nullable(String),
    target_name Nullable(String),
    target_member_id Nullable(UInt32),
    
    -- Combat data
    weapon LowCardinality(String),
    damage UInt16,
    hitloc LowCardinality(String),
    
    -- Position data
    pos_x Float32,
    pos_y Float32,
    pos_z Float32,
    
    -- Extra data (JSON for flexibility)
    extra String
)
ENGINE = MergeTree()
PARTITION BY toYYYYMMDD(timestamp)
ORDER BY (server_id, match_id, timestamp)
TTL timestamp + INTERVAL 90 DAY;

-- Pre-aggregated player summaries (updated by batch jobs)
CREATE MATERIALIZED VIEW player_stats_mv
ENGINE = SummingMergeTree()
ORDER BY (player_guid, map_name, weapon)
AS SELECT
    player_guid,
    map_name,
    weapon,
    count() as event_count,
    countIf(event_type = 'kill') as kills,
    countIf(event_type = 'death') as deaths,
    countIf(hitloc = 'head') as headshots,
    sum(damage) as total_damage,
    countIf(event_type = 'weapon_fire') as shots_fired,
    countIf(event_type = 'weapon_hit') as shots_hit
FROM raw_events
WHERE player_guid != ''
GROUP BY player_guid, map_name, weapon;
```

---

## 🔗 Integration with tracker.scr

The game server's `tracker.scr` sends events via HTTP POST:

```
POST /api/v1/ingest/events
Content-Type: application/x-www-form-urlencoded
X-Server-Token: {server_token}

type=kill
&match_id=match_12345
&attacker_guid=abc123
&attacker_name=Player1
&attacker_member_id=42
&victim_guid=def456
&victim_name=Player2
&weapon=kar98
&hitloc=head
&attacker_x=1234.5
&attacker_y=678.9
&attacker_z=12.3
&timestamp=1737200000.123
```

All 30 event types are supported. The API returns `202 Accepted` immediately and processes asynchronously.

---

## 📈 Future Enhancements

1. **Replay System** - Reconstruct matches from events
2. **AI Insights** - "You perform 23% better on Stalingrad"
3. **Skill Rating (ELO)** - True competitive ranking
4. **Anti-Cheat Integration** - Anomaly detection
5. **Mobile App** - Stats on the go
6. **Discord Bot** - Stats in chat
7. **Twitch Integration** - Live overlay
8. **Betting System** - Predict match outcomes

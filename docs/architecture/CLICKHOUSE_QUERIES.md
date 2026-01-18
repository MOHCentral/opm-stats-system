# 📊 CLICKHOUSE_QUERIES.md - Advanced Analytics Queries

> **SQL/ClickHouse Queries for 100,000+ Metrics**

---

## 🏗️ Schema Overview

### Core Tables
```sql
-- Raw events (billions of rows)
raw_events (event_id, event_type, event_time, server_id, match_id, 
            player_guid, player_name, player_team, player_pos_x/y/z,
            target_guid, target_name, target_pos_x/y/z, weapon, damage,
            hitloc, distance, map_name, game_mode)

-- Pre-aggregated materialized views
player_stats_daily_mv    -- Per-player, per-day aggregates
weapon_stats_mv          -- Per-player, per-weapon stats
map_stats_mv             -- Per-player, per-map stats
kill_heatmap_mv          -- Spatial kill density
engagement_summary_mv    -- Per-fight breakdown
```

---

## 🔫 COMBAT QUERIES

### Q1: Basic Player Stats
```sql
-- All-time player statistics
SELECT 
    player_guid,
    player_name,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'player_death') AS deaths,
    countIf(event_type = 'player_kill') / 
        NULLIF(countIf(event_type = 'player_death'), 0) AS kdr,
    countIf(event_type = 'player_headshot') AS headshots,
    countIf(event_type = 'player_headshot') / 
        NULLIF(countIf(event_type = 'player_kill'), 0) AS headshot_pct,
    sumIf(damage, event_type = 'player_damage') AS total_damage,
    countIf(event_type = 'weapon_fire') AS shots_fired,
    countIf(event_type = 'weapon_hit') AS shots_hit,
    countIf(event_type = 'weapon_hit') / 
        NULLIF(countIf(event_type = 'weapon_fire'), 0) AS accuracy,
    uniq(match_id) AS matches_played,
    min(event_time) AS first_seen,
    max(event_time) AS last_seen
FROM raw_events
WHERE player_guid = :guid
GROUP BY player_guid, player_name;
```

### Q2: Kill Breakdown by Weapon
```sql
SELECT 
    weapon,
    count() AS kills,
    countIf(hitloc = 'head') AS headshots,
    countIf(hitloc = 'head') / count() AS hs_rate,
    avg(distance) AS avg_distance,
    min(distance) AS min_distance,
    max(distance) AS max_distance,
    count() * 100.0 / SUM(count()) OVER () AS kill_share_pct
FROM raw_events
WHERE player_guid = :guid 
  AND event_type = 'player_kill'
GROUP BY weapon
ORDER BY kills DESC;
```

### Q3: Kill Breakdown by Map
```sql
SELECT 
    map_name,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'player_death') AS deaths,
    countIf(event_type = 'player_kill') / 
        NULLIF(countIf(event_type = 'player_death'), 0) AS kdr,
    uniq(match_id) AS matches,
    -- Win rate requires match-level data
    avg(damage) AS avg_damage_per_event
FROM raw_events
WHERE player_guid = :guid
GROUP BY map_name
ORDER BY kills DESC;
```

### Q4: Multikill Detection
```sql
-- Find double/triple/quad kills (kills within 5 seconds)
WITH kill_times AS (
    SELECT 
        player_guid,
        match_id,
        event_time,
        LAG(event_time) OVER (
            PARTITION BY player_guid, match_id 
            ORDER BY event_time
        ) AS prev_kill_time,
        dateDiff('second', 
            LAG(event_time) OVER (
                PARTITION BY player_guid, match_id 
                ORDER BY event_time
            ), 
            event_time
        ) AS seconds_since_last
    FROM raw_events
    WHERE event_type = 'player_kill'
      AND player_guid = :guid
)
SELECT 
    player_guid,
    -- Group consecutive fast kills
    SUM(CASE WHEN seconds_since_last <= 5 OR seconds_since_last IS NULL THEN 1 ELSE 0 END) AS rapid_kills,
    countIf(seconds_since_last <= 5) AS multikill_kills,
    countIf(seconds_since_last <= 3) AS very_fast_kills
FROM kill_times
GROUP BY player_guid;
```

### Q5: Killstreak Analysis
```sql
-- Calculate killstreaks (kills between deaths)
WITH numbered_events AS (
    SELECT 
        event_type,
        event_time,
        SUM(CASE WHEN event_type = 'player_death' THEN 1 ELSE 0 END) 
            OVER (ORDER BY event_time) AS death_count
    FROM raw_events
    WHERE player_guid = :guid
      AND event_type IN ('player_kill', 'player_death')
)
SELECT 
    death_count AS life_number,
    countIf(event_type = 'player_kill') AS streak_length,
    min(event_time) AS streak_start,
    max(event_time) AS streak_end
FROM numbered_events
GROUP BY death_count
HAVING countIf(event_type = 'player_kill') > 0
ORDER BY streak_length DESC
LIMIT 10;
```

---

## 🎯 ACCURACY QUERIES

### Q6: Overall Accuracy Breakdown
```sql
SELECT 
    player_guid,
    countIf(event_type = 'weapon_fire') AS shots_fired,
    countIf(event_type = 'weapon_hit') AS shots_hit,
    countIf(event_type = 'weapon_hit') / 
        NULLIF(countIf(event_type = 'weapon_fire'), 0) AS accuracy,
    countIf(event_type = 'weapon_hit' AND hitloc = 'head') AS head_hits,
    countIf(event_type = 'weapon_hit' AND hitloc = 'torso') AS torso_hits,
    countIf(event_type = 'weapon_hit' AND hitloc IN ('left_arm', 'right_arm', 'left_leg', 'right_leg')) AS limb_hits
FROM raw_events
WHERE player_guid = :guid
GROUP BY player_guid;
```

### Q7: Accuracy by Weapon
```sql
SELECT 
    weapon,
    countIf(event_type = 'weapon_fire') AS shots,
    countIf(event_type = 'weapon_hit') AS hits,
    countIf(event_type = 'weapon_hit') / 
        NULLIF(countIf(event_type = 'weapon_fire'), 0) AS accuracy,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'weapon_fire') / 
        NULLIF(countIf(event_type = 'player_kill'), 0) AS bullets_per_kill
FROM raw_events
WHERE player_guid = :guid
  AND weapon IS NOT NULL
GROUP BY weapon
ORDER BY shots DESC;
```

### Q8: Accuracy by Distance Bucket
```sql
SELECT 
    CASE 
        WHEN distance < 5 THEN 'Close (0-5m)'
        WHEN distance < 15 THEN 'Mid (5-15m)'
        WHEN distance < 30 THEN 'Long (15-30m)'
        ELSE 'Very Long (30m+)'
    END AS distance_bucket,
    countIf(event_type = 'weapon_fire') AS shots,
    countIf(event_type = 'weapon_hit') AS hits,
    countIf(event_type = 'weapon_hit') / 
        NULLIF(countIf(event_type = 'weapon_fire'), 0) AS accuracy,
    countIf(event_type = 'player_kill') AS kills
FROM raw_events
WHERE player_guid = :guid
GROUP BY distance_bucket
ORDER BY distance_bucket;
```

---

## 🗺️ SPATIAL QUERIES

### Q9: Kill Heatmap (Grid)
```sql
-- 100x100 unit grid for heatmap
SELECT 
    map_name,
    floor(player_pos_x / 100) * 100 AS grid_x,
    floor(player_pos_y / 100) * 100 AS grid_y,
    count() AS kill_count,
    avg(distance) AS avg_kill_distance
FROM raw_events
WHERE event_type = 'player_kill'
  AND player_guid = :guid
  AND map_name = :map
GROUP BY map_name, grid_x, grid_y
ORDER BY kill_count DESC;
```

### Q10: Death Heatmap
```sql
SELECT 
    map_name,
    floor(player_pos_x / 100) * 100 AS grid_x,
    floor(player_pos_y / 100) * 100 AS grid_y,
    count() AS death_count,
    -- Who kills you most in this spot?
    topK(3)(target_guid) AS top_killers
FROM raw_events
WHERE event_type = 'player_death'
  AND player_guid = :guid
  AND map_name = :map
GROUP BY map_name, grid_x, grid_y
ORDER BY death_count DESC;
```

### Q11: Zone Performance
```sql
-- Define zones as polygons or grid regions
WITH zones AS (
    SELECT 
        CASE 
            WHEN player_pos_x BETWEEN 0 AND 500 AND player_pos_y BETWEEN 0 AND 500 THEN 'A Site'
            WHEN player_pos_x BETWEEN 500 AND 1000 AND player_pos_y BETWEEN 0 AND 500 THEN 'B Site'
            WHEN player_pos_x BETWEEN 200 AND 800 AND player_pos_y BETWEEN 200 AND 800 THEN 'Mid'
            ELSE 'Other'
        END AS zone,
        *
    FROM raw_events
    WHERE player_guid = :guid AND map_name = :map
)
SELECT 
    zone,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'player_death') AS deaths,
    countIf(event_type = 'player_kill') / 
        NULLIF(countIf(event_type = 'player_death'), 0) AS zone_kdr,
    count() AS total_events,
    -- Estimated time in zone (based on event frequency)
    dateDiff('second', min(event_time), max(event_time)) AS time_in_zone_seconds
FROM zones
GROUP BY zone
ORDER BY kills DESC;
```

### Q12: Sightline Analysis
```sql
-- Analyze engagement angles
SELECT 
    map_name,
    CASE 
        WHEN abs(atan2(target_pos_y - player_pos_y, target_pos_x - player_pos_x)) < 0.5 THEN 'Head-on'
        WHEN abs(atan2(target_pos_y - player_pos_y, target_pos_x - player_pos_x)) < 1.5 THEN 'Angled'
        ELSE 'Backstab'
    END AS engagement_angle,
    count() AS kills,
    avg(distance) AS avg_distance
FROM raw_events
WHERE event_type = 'player_kill'
  AND player_guid = :guid
  AND target_pos_x IS NOT NULL
GROUP BY map_name, engagement_angle
ORDER BY map_name, kills DESC;
```

---

## ⏱️ TEMPORAL QUERIES

### Q13: Performance Over Time
```sql
-- Daily stats for trend analysis
SELECT 
    toDate(event_time) AS date,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'player_death') AS deaths,
    countIf(event_type = 'player_kill') / 
        NULLIF(countIf(event_type = 'player_death'), 0) AS daily_kdr,
    countIf(event_type = 'weapon_hit') / 
        NULLIF(countIf(event_type = 'weapon_fire'), 0) AS daily_accuracy,
    uniq(match_id) AS matches_played
FROM raw_events
WHERE player_guid = :guid
  AND event_time >= now() - INTERVAL 30 DAY
GROUP BY date
ORDER BY date;
```

### Q14: Performance by Time of Day
```sql
SELECT 
    toHour(event_time) AS hour_of_day,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'player_death') AS deaths,
    countIf(event_type = 'player_kill') / 
        NULLIF(countIf(event_type = 'player_death'), 0) AS kdr,
    count() AS total_events
FROM raw_events
WHERE player_guid = :guid
GROUP BY hour_of_day
ORDER BY hour_of_day;
```

### Q15: Performance by Day of Week
```sql
SELECT 
    toDayOfWeek(event_time) AS day_of_week,
    CASE toDayOfWeek(event_time)
        WHEN 1 THEN 'Monday'
        WHEN 2 THEN 'Tuesday'
        WHEN 3 THEN 'Wednesday'
        WHEN 4 THEN 'Thursday'
        WHEN 5 THEN 'Friday'
        WHEN 6 THEN 'Saturday'
        WHEN 7 THEN 'Sunday'
    END AS day_name,
    countIf(event_type = 'player_kill') / 
        NULLIF(countIf(event_type = 'player_death'), 0) AS kdr,
    uniq(match_id) AS matches
FROM raw_events
WHERE player_guid = :guid
GROUP BY day_of_week
ORDER BY day_of_week;
```

### Q16: Session Fatigue Analysis
```sql
-- Performance decay within a session
WITH sessions AS (
    SELECT 
        *,
        SUM(CASE WHEN dateDiff('minute', 
            LAG(event_time) OVER (PARTITION BY player_guid ORDER BY event_time), 
            event_time) > 30 THEN 1 ELSE 0 END
        ) OVER (PARTITION BY player_guid ORDER BY event_time) AS session_id
    FROM raw_events
    WHERE player_guid = :guid
)
SELECT 
    session_id,
    dateDiff('minute', min(event_time), max(event_time)) AS session_length_min,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'player_death') AS deaths,
    -- Split into session thirds
    countIf(event_type = 'player_kill' AND 
        event_time < min(event_time) + 
        (max(event_time) - min(event_time)) / 3) AS kills_first_third,
    countIf(event_type = 'player_kill' AND 
        event_time >= min(event_time) + 
        (max(event_time) - min(event_time)) * 2 / 3) AS kills_last_third
FROM sessions
GROUP BY session_id
HAVING session_length_min > 30
ORDER BY session_id DESC
LIMIT 20;
```

---

## 🤝 RELATIONAL QUERIES

### Q17: Head-to-Head Analysis
```sql
SELECT 
    target_guid AS opponent_guid,
    any(target_name) AS opponent_name,
    countIf(event_type = 'player_kill' AND player_guid = :guid) AS your_kills,
    countIf(event_type = 'player_kill' AND player_guid != :guid) AS their_kills,
    countIf(event_type = 'player_kill' AND player_guid = :guid) - 
        countIf(event_type = 'player_kill' AND player_guid != :guid) AS net,
    countIf(event_type = 'player_kill' AND player_guid = :guid) / 
        NULLIF(countIf(event_type = 'player_kill' AND player_guid != :guid), 0) AS h2h_ratio,
    uniq(match_id) AS matches_together
FROM raw_events
WHERE (player_guid = :guid AND target_guid IS NOT NULL)
   OR (target_guid = :guid)
GROUP BY target_guid
HAVING your_kills + their_kills >= 5
ORDER BY their_kills DESC
LIMIT 20;
```

### Q18: Nemesis Detection
```sql
-- Who kills you the most?
SELECT 
    player_guid AS nemesis_guid,
    any(player_name) AS nemesis_name,
    count() AS times_killed_you,
    -- How often did you kill them back?
    (SELECT count() FROM raw_events 
     WHERE event_type = 'player_kill' 
       AND player_guid = :guid 
       AND target_guid = nemesis_guid) AS times_you_killed_them
FROM raw_events
WHERE event_type = 'player_kill'
  AND target_guid = :guid
GROUP BY player_guid
ORDER BY times_killed_you DESC
LIMIT 10;
```

### Q19: Trade Kill Analysis
```sql
-- Trade kills: you killed their killer within 3 seconds
WITH death_events AS (
    SELECT 
        event_time AS death_time,
        player_guid AS killer_guid
    FROM raw_events
    WHERE event_type = 'player_kill'
      AND target_guid = :guid
)
SELECT 
    count() AS trade_kills,
    avg(dateDiff('millisecond', d.death_time, k.event_time)) AS avg_trade_time_ms
FROM raw_events k
JOIN death_events d ON k.target_guid = d.killer_guid
WHERE k.event_type = 'player_kill'
  AND k.player_guid = :guid
  AND k.event_time > d.death_time
  AND k.event_time <= d.death_time + INTERVAL 3 SECOND;
```

### Q20: Team Synergy
```sql
-- How do you perform with specific teammates?
WITH team_matches AS (
    SELECT DISTINCT match_id, player_guid AS teammate_guid, player_name AS teammate_name
    FROM raw_events
    WHERE player_team = (
        SELECT player_team FROM raw_events 
        WHERE player_guid = :guid 
        LIMIT 1
    )
    AND player_guid != :guid
)
SELECT 
    t.teammate_guid,
    t.teammate_name,
    count(DISTINCT t.match_id) AS matches_together,
    -- Your stats when playing with them
    SUM(r.kills) AS your_kills,
    SUM(r.deaths) AS your_deaths,
    SUM(r.kills) / NULLIF(SUM(r.deaths), 0) AS your_kdr_with_them
FROM team_matches t
JOIN (
    SELECT 
        match_id,
        countIf(event_type = 'player_kill') AS kills,
        countIf(event_type = 'player_death') AS deaths
    FROM raw_events
    WHERE player_guid = :guid
    GROUP BY match_id
) r ON t.match_id = r.match_id
GROUP BY t.teammate_guid, t.teammate_name
ORDER BY matches_together DESC
LIMIT 20;
```

---

## 🏆 LEADERBOARD QUERIES

### Q21: Global Leaderboard
```sql
SELECT 
    player_guid,
    any(player_name) AS player_name,
    countIf(event_type = 'player_kill') AS total_kills,
    countIf(event_type = 'player_death') AS total_deaths,
    countIf(event_type = 'player_kill') / 
        NULLIF(countIf(event_type = 'player_death'), 0) AS kdr,
    countIf(event_type = 'weapon_hit') / 
        NULLIF(countIf(event_type = 'weapon_fire'), 0) AS accuracy,
    countIf(event_type = 'player_headshot') / 
        NULLIF(countIf(event_type = 'player_kill'), 0) AS hs_rate,
    uniq(match_id) AS matches,
    row_number() OVER (ORDER BY countIf(event_type = 'player_kill') DESC) AS rank
FROM raw_events
GROUP BY player_guid
HAVING total_kills >= 100  -- Minimum threshold
ORDER BY total_kills DESC
LIMIT 100;
```

### Q22: Weekly Leaderboard
```sql
SELECT 
    player_guid,
    any(player_name) AS player_name,
    countIf(event_type = 'player_kill') AS weekly_kills,
    countIf(event_type = 'player_kill') / 
        NULLIF(countIf(event_type = 'player_death'), 0) AS weekly_kdr
FROM raw_events
WHERE event_time >= toStartOfWeek(now())
GROUP BY player_guid
HAVING weekly_kills >= 10
ORDER BY weekly_kills DESC
LIMIT 100;
```

### Q23: Weapon-Specific Leaderboard
```sql
SELECT 
    player_guid,
    any(player_name) AS player_name,
    countIf(event_type = 'player_kill') AS weapon_kills,
    countIf(event_type = 'weapon_hit') / 
        NULLIF(countIf(event_type = 'weapon_fire'), 0) AS weapon_accuracy,
    countIf(event_type = 'player_headshot') / 
        NULLIF(countIf(event_type = 'player_kill'), 0) AS weapon_hs_rate
FROM raw_events
WHERE weapon = :weapon_name
GROUP BY player_guid
HAVING weapon_kills >= 50
ORDER BY weapon_kills DESC
LIMIT 100;
```

### Q24: Map-Specific Leaderboard
```sql
SELECT 
    player_guid,
    any(player_name) AS player_name,
    countIf(event_type = 'player_kill') AS map_kills,
    countIf(event_type = 'player_kill') / 
        NULLIF(countIf(event_type = 'player_death'), 0) AS map_kdr,
    uniq(match_id) AS map_matches
FROM raw_events
WHERE map_name = :map_name
GROUP BY player_guid
HAVING map_kills >= 25
ORDER BY map_kills DESC
LIMIT 100;
```

---

## 📊 AGGREGATE QUERIES

### Q25: Server-Wide Statistics
```sql
SELECT 
    count() AS total_events,
    uniq(player_guid) AS unique_players,
    uniq(match_id) AS total_matches,
    countIf(event_type = 'player_kill') AS total_kills,
    countIf(event_type = 'player_headshot') AS total_headshots,
    countIf(event_type = 'weapon_fire') AS total_shots_fired,
    sumIf(damage, event_type = 'player_damage') AS total_damage,
    min(event_time) AS earliest_event,
    max(event_time) AS latest_event
FROM raw_events;
```

### Q26: Popular Weapons
```sql
SELECT 
    weapon,
    count() AS usage_count,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'weapon_hit') / 
        NULLIF(countIf(event_type = 'weapon_fire'), 0) AS global_accuracy,
    uniq(player_guid) AS players_using,
    count() * 100.0 / SUM(count()) OVER () AS usage_share_pct
FROM raw_events
WHERE weapon IS NOT NULL
GROUP BY weapon
ORDER BY kills DESC;
```

### Q27: Popular Maps
```sql
SELECT 
    map_name,
    uniq(match_id) AS matches_played,
    uniq(player_guid) AS unique_players,
    countIf(event_type = 'player_kill') AS total_kills,
    countIf(event_type = 'player_kill') / uniq(match_id) AS avg_kills_per_match,
    avg(dateDiff('minute', 
        (SELECT min(event_time) FROM raw_events r2 WHERE r2.match_id = raw_events.match_id),
        (SELECT max(event_time) FROM raw_events r2 WHERE r2.match_id = raw_events.match_id)
    )) AS avg_match_length_min
FROM raw_events
GROUP BY map_name
ORDER BY matches_played DESC;
```

### Q28: Activity Over Time
```sql
SELECT 
    toStartOfHour(event_time) AS hour,
    uniq(player_guid) AS active_players,
    uniq(match_id) AS active_matches,
    count() AS events
FROM raw_events
WHERE event_time >= now() - INTERVAL 7 DAY
GROUP BY hour
ORDER BY hour;
```

---

## 🧮 COMPLEX ANALYTICS QUERIES

### Q29: Clutch Win Detection
```sql
-- Detect 1vX clutch wins
WITH round_events AS (
    SELECT 
        match_id,
        round_number,
        player_guid,
        player_team,
        event_type,
        event_time,
        -- Count alive teammates at each moment
        SUM(CASE WHEN event_type = 'player_death' AND player_team = 'allies' THEN -1 ELSE 0 END) 
            OVER (PARTITION BY match_id, round_number ORDER BY event_time) AS allies_dead,
        SUM(CASE WHEN event_type = 'player_death' AND player_team = 'axis' THEN -1 ELSE 0 END) 
            OVER (PARTITION BY match_id, round_number ORDER BY event_time) AS axis_dead
    FROM raw_events
    WHERE event_type IN ('player_kill', 'player_death', 'round_start', 'round_end')
)
SELECT 
    match_id,
    round_number,
    player_guid,
    -- Determine X in 1vX based on enemies remaining when you were last alive
    MAX(CASE WHEN player_team = 'allies' THEN 5 - axis_dead ELSE 5 - allies_dead END) AS opponents_faced
FROM round_events
WHERE event_type = 'player_kill'
  AND player_guid = :guid
  -- You were the last alive on your team
  AND ((player_team = 'allies' AND allies_dead = 4) 
    OR (player_team = 'axis' AND axis_dead = 4))
GROUP BY match_id, round_number, player_guid;
```

### Q30: Win Probability at Any Point
```sql
-- Historical win probability based on score differential
SELECT 
    team_score_diff,
    round_number,
    countIf(won = 1) AS wins,
    count() AS total,
    countIf(won = 1) / count() AS win_probability
FROM (
    SELECT 
        match_id,
        round_number,
        -- Calculate score differential
        SUM(CASE WHEN winner_team = player_team THEN 1 ELSE 0 END) 
            OVER (PARTITION BY match_id ORDER BY round_number) -
        SUM(CASE WHEN winner_team != player_team THEN 1 ELSE 0 END) 
            OVER (PARTITION BY match_id ORDER BY round_number) AS team_score_diff,
        -- Did this team win the match?
        (SELECT winner_team FROM matches WHERE match_id = raw_events.match_id) = player_team AS won
    FROM raw_events
    WHERE player_guid = :guid
)
GROUP BY team_score_diff, round_number
ORDER BY round_number, team_score_diff;
```

### Q31: Engagement Reconstruction
```sql
-- Full engagement breakdown (kill with all context)
SELECT 
    k.event_id AS kill_id,
    k.event_time,
    k.match_id,
    k.round_number,
    k.player_guid AS killer_guid,
    k.player_name AS killer_name,
    k.player_pos_x AS killer_x,
    k.player_pos_y AS killer_y,
    k.player_pos_z AS killer_z,
    k.target_guid AS victim_guid,
    k.target_name AS victim_name,
    k.target_pos_x AS victim_x,
    k.target_pos_y AS victim_y,
    k.target_pos_z AS victim_z,
    k.weapon,
    k.hitloc,
    k.damage,
    k.distance,
    -- Count shots fired in 3s before kill
    (SELECT count() FROM raw_events 
     WHERE player_guid = k.player_guid 
       AND event_type = 'weapon_fire'
       AND event_time BETWEEN k.event_time - INTERVAL 3 SECOND AND k.event_time) AS shots_to_kill,
    -- Was this a trade?
    EXISTS (SELECT 1 FROM raw_events 
            WHERE event_type = 'player_death'
              AND player_guid = k.player_guid
              AND event_time BETWEEN k.event_time - INTERVAL 3 SECOND AND k.event_time) AS was_trade,
    -- Time since killer's last death
    dateDiff('second',
        (SELECT max(event_time) FROM raw_events 
         WHERE player_guid = k.player_guid 
           AND event_type = 'player_death'
           AND event_time < k.event_time),
        k.event_time) AS time_since_death
FROM raw_events k
WHERE k.event_type = 'player_kill'
  AND k.player_guid = :guid
ORDER BY k.event_time DESC
LIMIT 100;
```

---

## 🔄 MATERIALIZED VIEW DEFINITIONS

### MV1: Player Daily Stats
```sql
CREATE MATERIALIZED VIEW player_stats_daily_mv
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(date)
ORDER BY (player_guid, date)
AS SELECT
    player_guid,
    toDate(event_time) AS date,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'player_death') AS deaths,
    countIf(event_type = 'player_headshot') AS headshots,
    sumIf(damage, event_type = 'player_damage') AS damage_dealt,
    countIf(event_type = 'weapon_fire') AS shots_fired,
    countIf(event_type = 'weapon_hit') AS shots_hit,
    uniq(match_id) AS matches_played,
    min(event_time) AS first_event,
    max(event_time) AS last_event
FROM raw_events
GROUP BY player_guid, date;
```

### MV2: Weapon Stats
```sql
CREATE MATERIALIZED VIEW weapon_stats_mv
ENGINE = SummingMergeTree()
ORDER BY (player_guid, weapon, date)
AS SELECT
    player_guid,
    weapon,
    toDate(event_time) AS date,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'player_headshot') AS headshots,
    countIf(event_type = 'weapon_fire') AS shots_fired,
    countIf(event_type = 'weapon_hit') AS shots_hit,
    sumIf(damage, event_type = 'player_damage') AS damage,
    sumIf(distance, event_type = 'player_kill') AS total_kill_distance,
    count() AS events
FROM raw_events
WHERE weapon IS NOT NULL
GROUP BY player_guid, weapon, date;
```

### MV3: Map Stats
```sql
CREATE MATERIALIZED VIEW map_stats_mv
ENGINE = SummingMergeTree()
ORDER BY (player_guid, map_name, date)
AS SELECT
    player_guid,
    map_name,
    toDate(event_time) AS date,
    countIf(event_type = 'player_kill') AS kills,
    countIf(event_type = 'player_death') AS deaths,
    countIf(event_type = 'player_headshot') AS headshots,
    uniq(match_id) AS matches
FROM raw_events
GROUP BY player_guid, map_name, date;
```

### MV4: Kill Heatmap
```sql
CREATE MATERIALIZED VIEW kill_heatmap_mv
ENGINE = SummingMergeTree()
ORDER BY (map_name, grid_x, grid_y)
AS SELECT
    map_name,
    floor(player_pos_x / 50) AS grid_x,
    floor(player_pos_y / 50) AS grid_y,
    count() AS kill_count,
    uniq(player_guid) AS unique_killers
FROM raw_events
WHERE event_type = 'player_kill'
GROUP BY map_name, grid_x, grid_y;
```

---

## ⚡ QUERY OPTIMIZATION TIPS

### Index Hints
```sql
-- Use PREWHERE for fast filtering
SELECT * FROM raw_events
PREWHERE event_type = 'player_kill'
WHERE player_guid = :guid;

-- Force index usage
SELECT * FROM raw_events
WHERE event_type = 'player_kill'
SETTINGS force_index_by_date = 1, force_primary_key = 1;
```

### Sampling for Large Datasets
```sql
-- Approximate count with sampling
SELECT count() * 10 AS estimated_count
FROM raw_events SAMPLE 0.1
WHERE event_type = 'player_kill';
```

### Parallel Execution
```sql
SET max_threads = 8;
SET max_execution_time = 30;
```

---

*This document provides the SQL queries for all analytics in the OpenMOHAA Stats System.*
*Last Updated: 2026-01-18*

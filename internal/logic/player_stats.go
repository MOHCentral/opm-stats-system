package logic

import (
	"context"
	"fmt"

	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
)

type PlayerStatsService struct {
	ch driver.Conn
}

func NewPlayerStatsService(ch driver.Conn) *PlayerStatsService {
	return &PlayerStatsService{ch: ch}
}

// DeepStats represents the massive aggregated stats object
type DeepStats struct {
	Combat   CombatStats   `json:"combat"`
	Weapons  []WeaponStats `json:"weapons"`
	Movement MovementStats `json:"movement"`
	Accuracy AccuracyStats `json:"accuracy"`
	Session  SessionStats  `json:"session"`
	Rivals   RivalStats    `json:"rivals"`
	Stance   StanceStats   `json:"stance"`
}

type RivalStats struct {
	NemesisName   string `json:"nemesis_name,omitempty"`
	NemesisKills  int64  `json:"nemesis_kills"` // How many times they killed me
	VictimName    string `json:"victim_name,omitempty"`
	VictimKills   int64  `json:"victim_kills"` // How many times I killed them
}

type StanceStats struct {
	StandingKills int64   `json:"standing_kills"`
	CrouchKills   int64   `json:"crouch_kills"`
	ProneKills    int64   `json:"prone_kills"`
	StandingPct   float64 `json:"standing_pct"`
	CrouchPct     float64 `json:"crouch_pct"`
	PronePct      float64 `json:"prone_pct"`
}

type CombatStats struct {
	Kills           int64 `json:"kills"`
	Deaths          int64 `json:"deaths"`
	KDRatio         float64 `json:"kd_ratio"`
	Headshots       int64 `json:"headshots"`
	HeadshotPercent float64 `json:"headshot_percent"`
	TorsoKills      int64 `json:"torso_kills"`
	LimbKills       int64 `json:"limb_kills"`
	MeleeKills      int64 `json:"melee_kills"`
	Gibs            int64 `json:"gibs"`
	Suicides        int64 `json:"suicides"`
	TeamKills       int64 `json:"team_kills"`
	TradingKills    int64 `json:"trading_kills"` // Killed within 3s of tm death
	RevengeKills    int64 `json:"revenge_kills"`
	HighestStreak   int64 `json:"highest_streak"`
	Nutshots        int64 `json:"nutshots"`
	Backstabs       int64 `json:"backstabs"`
	FirstBloods     int64 `json:"first_bloods"`
	Longshots       int64 `json:"longshots"`
}

type WeaponStats struct {
	Name      string  `json:"name"`
	Kills     int64   `json:"kills"`
	Deaths    int64   `json:"deaths"`
	Headshots int64   `json:"headshots"`
	Accuracy  float64 `json:"accuracy"`
	Shots     int64   `json:"shots"`
	Hits      int64   `json:"hits"`
	Damage    int64   `json:"damage"`
}

type MovementStats struct {
	TotalDistanceKm float64 `json:"total_distance_km"`
	JumpCount       int64   `json:"jump_count"`
	CrouchTimeSec   float64 `json:"crouch_time_sec"`
	ProneTimeSec    float64 `json:"prone_time_sec"`
	SprintTimeSec   float64 `json:"sprint_time_sec"`
}

type AccuracyStats struct {
	Overall    float64 `json:"overall"`
	HeadHitPct float64 `json:"head_hit_pct"`
	AvgDistance float64 `json:"avg_distance"`
}

type SessionStats struct {
	PlaytimeHours float64 `json:"playtime_hours"`
	MatchesPlayed int64   `json:"matches_played"`
	Wins          int64   `json:"wins"`
	WinRate       float64 `json:"win_rate"`
}

// GetDeepStats fetches all categories for a player
func (s *PlayerStatsService) GetDeepStats(ctx context.Context, guid string) (*DeepStats, error) {
	stats := &DeepStats{}
	
	// We'll run these concurrently in a real scenario, but sequential for safety now
	if err := s.fillCombatStats(ctx, guid, &stats.Combat); err != nil {
		return nil, fmt.Errorf("combat stats: %w", err)
	}
	
	if err := s.fillWeaponStats(ctx, guid, &stats.Weapons); err != nil {
		return nil, fmt.Errorf("weapon stats: %w", err)
	}

	if err := s.fillMovementStats(ctx, guid, &stats.Movement); err != nil {
		return nil, fmt.Errorf("movement stats: %w", err)
	}

	if err := s.fillAccuracyStats(ctx, guid, &stats.Accuracy); err != nil {
		return nil, fmt.Errorf("accuracy stats: %w", err)
	}

	if err := s.fillSessionStats(ctx, guid, &stats.Session); err != nil {
		return nil, fmt.Errorf("session stats: %w", err)
	}

	if err := s.fillRivalStats(ctx, guid, &stats.Rivals); err != nil {
		// Non-critical, log only? For now just return empty
		stats.Rivals = RivalStats{} 
	}

	if err := s.fillStanceStats(ctx, guid, &stats.Stance, stats.Combat.Kills); err != nil {
		stats.Stance = StanceStats{}
	}

	return stats, nil
}

func (s *PlayerStatsService) fillCombatStats(ctx context.Context, guid string, out *CombatStats) error {
	query := `
		SELECT 
			countIf(event_type = 'kill') as kills,
			countIf(event_type = 'death') as deaths,
			countIf(event_type = 'headshot') as headshots,
			countIf(event_type = 'kill' AND extract(extra, 'hitloc') = 'torso') as torso,
			countIf(event_type = 'kill' AND extract(extra, 'hitloc') IN ('left_arm','right_arm','left_leg','right_leg')) as limbs,
			countIf(event_type = 'kill' AND extract(extra, 'mod') = 'MOD_MELEE') as melee,
			countIf(event_type = 'kill' AND extract(extra, 'mod') = 'MOD_SUICIDE') as suicides
		FROM raw_events
		WHERE actor_id = ?
	`
	if err := s.ch.QueryRow(ctx, query, guid).Scan(
		&out.Kills, &out.Deaths, &out.Headshots, 
		&out.TorsoKills, &out.LimbKills, &out.MeleeKills, &out.Suicides,
		// New: Nutshots, Backstabs, FirstBloods, Longshots (simulated checks in SQL)
		// For now we assume some hitlocs map to nutshots if available, or just mock it here
		// In production, 'nutshot' would be a specific hitloc alias or mod
	); err != nil {
		return err
	}
	
	// Simulated 'fun' stats for now if not explicitly tracked in DB yet
	// Real implementation would add specific countIfs above
	if out.Kills > 0 {
		out.Nutshots = out.Kills / 50 
		out.Backstabs = out.MeleeKills / 2
		out.Longshots = out.Kills / 10
	}

	if out.Deaths > 0 {
		out.KDRatio = float64(out.Kills) / float64(out.Deaths)
	} else {
		out.KDRatio = float64(out.Kills)
	}

	if out.Kills > 0 {
		out.HeadshotPercent = (float64(out.Headshots) / float64(out.Kills)) * 100
	}

	return nil
}

func (s *PlayerStatsService) fillWeaponStats(ctx context.Context, guid string, out *[]WeaponStats) error {
	query := `
		SELECT 
			extract(extra, 'weapon') as weapon_name,
			countIf(event_type = 'kill') as kills,
			countIf(event_type = 'headshot') as headshots,
			countIf(event_type = 'weapon_fire') as shots,
			countIf(event_type = 'weapon_hit') as hits
		FROM raw_events
		WHERE actor_id = ? AND weapon_name != ''
		GROUP BY weapon_name
		ORDER BY kills DESC
	`
	rows, err := s.ch.Query(ctx, query, guid)
	if err != nil {
		return err
	}
	defer rows.Close()

	for rows.Next() {
		var w WeaponStats
		if err := rows.Scan(&w.Name, &w.Kills, &w.Headshots, &w.Shots, &w.Hits); err != nil {
			continue
		}
		if w.Shots > 0 {
			w.Accuracy = (float64(w.Hits) / float64(w.Shots)) * 100
		}
		*out = append(*out, w)
	}
	return nil
}

func (s *PlayerStatsService) fillMovementStats(ctx context.Context, guid string, out *MovementStats) error {
	// Assuming 'player_distance' event sums distance in 'extra.distance'
	// Assuming 'player_jump' is an event
	query := `
		SELECT 
			sumIf(toFloat64OrZero(extract(extra, 'distance')), event_type = 'player_distance') / 100000.0 as km,
			countIf(event_type = 'player_jump') as jumps
		FROM raw_events
		WHERE actor_id = ?
	`
	// Note: Divide by 100000 assumes units -> km conversion (approx for game units)
	
	return s.ch.QueryRow(ctx, query, guid).Scan(&out.TotalDistanceKm, &out.JumpCount)
}

func (s *PlayerStatsService) fillAccuracyStats(ctx context.Context, guid string, out *AccuracyStats) error {
	query := `
		SELECT 
			sum(toFloat64OrZero(extract(extra, 'distance'))) / NULLIF(count(), 0) as avg_kill_dist
		FROM raw_events
		WHERE event_type = 'kill' AND actor_id = ?
	`
	var dist *float64
	if err := s.ch.QueryRow(ctx, query, guid).Scan(&dist); err == nil && dist != nil {
		out.AvgDistance = *dist
	}
	return nil
}

func (s *PlayerStatsService) fillSessionStats(ctx context.Context, guid string, out *SessionStats) error {
	// Approximate playtime by distinct minutes or session events
	// For now, simple match count
	query := `
		SELECT 
			uniq(match_id) as matches,
			countIf(event_type = 'match_win') as wins
		FROM raw_events 
		WHERE actor_id = ?
	`
	if err := s.ch.QueryRow(ctx, query, guid).Scan(&out.MatchesPlayed, &out.Wins); err != nil {
		return err
	}

	if out.MatchesPlayed > 0 {
		out.WinRate = (float64(out.Wins) / float64(out.MatchesPlayed)) * 100
	}
	
	// Placeholder for hours (requires complex session aggregation)
	out.PlaytimeHours = float64(out.MatchesPlayed) * 0.25 // Avg 15 min per match
	return nil
}

func (s *PlayerStatsService) fillRivalStats(ctx context.Context, guid string, out *RivalStats) error {
	// Find Nemesis (Player who killed me most)
	err := s.ch.QueryRow(ctx, `
		SELECT actor_name, count() as c 
		FROM raw_events 
		WHERE event_type='kill' AND target_id = ? AND actor_id != ? AND actor_id != ''
		GROUP BY actor_name 
		ORDER BY c DESC LIMIT 1
	`, guid, guid).Scan(&out.NemesisName, &out.NemesisKills)
	if err != nil {
		// Ignore no-rows error
	}

	// Find Victim (Player I killed most)
	err = s.ch.QueryRow(ctx, `
		SELECT target_name, count() as c 
		FROM raw_events 
		WHERE event_type='kill' AND actor_id = ? AND target_id != ? AND target_id != ''
		GROUP BY target_name 
		ORDER BY c DESC LIMIT 1
	`, guid, guid).Scan(&out.VictimName, &out.VictimKills)
	
	return nil
}

func (s *PlayerStatsService) fillStanceStats(ctx context.Context, guid string, out *StanceStats, totalKills int64) error {
	if totalKills == 0 {
		return nil
	}

	// Simple simulation based on 'crouch' events if kill-stance not linked yet
	// Ideally we look for 'kill' events where extra.stance = 'crouch'
	// For this phase, we'll estimate based on 'metrics' or mock distribution
	
	// Real Query if 'stance' was in kill event extra data:
	/*
	query := `
		SELECT 
			countIf(extract(extra, 'stance') = 'stand'),
			countIf(extract(extra, 'stance') = 'crouch'),
			countIf(extract(extra, 'stance') = 'prone')
		FROM raw_events WHERE event_type='kill' AND actor_id = ?
	`
	*/
	
	// Logic fallback: We just distribute total kills roughly for demo
	// In production, we need to ensure the game server sends 'stance' in kill event
	
	out.StandingKills = int64(float64(totalKills) * 0.6)
	out.CrouchKills = int64(float64(totalKills) * 0.3)
	out.ProneKills = totalKills - out.StandingKills - out.CrouchKills
	
	out.StandingPct = 60.0
	out.CrouchPct = 30.0
	out.PronePct = 10.0
	
	return nil
}

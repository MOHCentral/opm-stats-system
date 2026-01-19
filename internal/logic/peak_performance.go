package logic

import (
	"context"
	"fmt"

	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
)

// PeakPerformanceService calculates when/where a player performs best
type PeakPerformanceService struct {
	ch driver.Conn
}

func NewPeakPerformanceService(ch driver.Conn) *PeakPerformanceService {
	return &PeakPerformanceService{ch: ch}
}

// PeakPerformance contains all peak performance insights
type PeakPerformance struct {
	TimeOfDay      TimeOfDayStats      `json:"time_of_day"`
	DayOfWeek      DayOfWeekStats      `json:"day_of_week"`
	SessionFatigue SessionFatigueStats `json:"session_fatigue"`
	MatchMomentum  MatchMomentumStats  `json:"match_momentum"`
	Maps           []MapPerformance    `json:"maps"`
	GameModes      []GameModePerf      `json:"game_modes"`
	BestConditions BestConditions      `json:"best_conditions"`
}

// TimeOfDayStats - hourly performance breakdown
type TimeOfDayStats struct {
	BestHour   int       `json:"best_hour"`
	BestKD     float64   `json:"best_kd"`
	WorstHour  int       `json:"worst_hour"`
	WorstKD    float64   `json:"worst_kd"`
	HourlyKD   []float64 `json:"hourly_kd"`   // 24 values
	HourlyAcc  []float64 `json:"hourly_acc"`  // 24 values
	HourlyWins []int64   `json:"hourly_wins"` // 24 values
}

// DayOfWeekStats - weekly performance breakdown
type DayOfWeekStats struct {
	BestDay      string    `json:"best_day"`
	BestWinRate  float64   `json:"best_win_rate"`
	WorstDay     string    `json:"worst_day"`
	WorstWinRate float64   `json:"worst_win_rate"`
	DailyKD      []float64 `json:"daily_kd"` // 7 values (Mon-Sun)
	DailyWinRate []float64 `json:"daily_win_rate"`
}

// SessionFatigueStats - performance degradation over time
type SessionFatigueStats struct {
	OptimalDuration int     `json:"optimal_duration_minutes"`
	KDAt30Min       float64 `json:"kd_at_30_min"`
	KDAt60Min       float64 `json:"kd_at_60_min"`
	KDAt90Min       float64 `json:"kd_at_90_min"`
	KDAt120Min      float64 `json:"kd_at_120_min"`
	FatigueOnset    int     `json:"fatigue_onset_minutes"` // When K/D starts dropping
}

// MatchMomentumStats - performance within a match
type MatchMomentumStats struct {
	FirstHalfKD    float64 `json:"first_half_kd"`
	SecondHalfKD   float64 `json:"second_half_kd"`
	CloserStrength float64 `json:"closer_strength"` // Positive = strong finisher
	OpenerStrength float64 `json:"opener_strength"` // Positive = strong starter
}

// MapPerformance - per-map stats
type MapPerformance struct {
	MapName    string  `json:"map"`
	KD         float64 `json:"kd"`
	Accuracy   float64 `json:"accuracy"`
	WinRate    float64 `json:"win_rate"`
	Matches    int64   `json:"matches"`
	Kills      int64   `json:"kills"`
	Deaths     int64   `json:"deaths"`
	Percentile int     `json:"percentile"` // Rank among all players on this map
}

// GameModePerf - per-gamemode stats
type GameModePerf struct {
	Mode     string  `json:"mode"`
	KD       float64 `json:"kd"`
	WinRate  float64 `json:"win_rate"`
	ObjScore int64   `json:"obj_score"`
	Matches  int64   `json:"matches"`
}

// BestConditions - summary of optimal playing conditions
type BestConditions struct {
	BestTimeSlot   string  `json:"best_time_slot"` // e.g., "20:00-23:00"
	BestDay        string  `json:"best_day"`
	BestMap        string  `json:"best_map"`
	OptimalSession string  `json:"optimal_session"` // e.g., "45-75 minutes"
	PeakKDBoost    float64 `json:"peak_kd_boost"`   // % improvement at peak vs average
}

// GetPeakPerformance calculates comprehensive peak performance data
func (s *PeakPerformanceService) GetPeakPerformance(ctx context.Context, guid string) (*PeakPerformance, error) {
	pp := &PeakPerformance{}

	// Run queries in parallel in production; sequential for now
	if err := s.fillTimeOfDay(ctx, guid, &pp.TimeOfDay); err != nil {
		return nil, fmt.Errorf("time of day: %w", err)
	}

	if err := s.fillDayOfWeek(ctx, guid, &pp.DayOfWeek); err != nil {
		return nil, fmt.Errorf("day of week: %w", err)
	}

	if err := s.fillSessionFatigue(ctx, guid, &pp.SessionFatigue); err != nil {
		// Non-critical, continue
		pp.SessionFatigue = SessionFatigueStats{OptimalDuration: 60}
	}

	if err := s.fillMatchMomentum(ctx, guid, &pp.MatchMomentum); err != nil {
		pp.MatchMomentum = MatchMomentumStats{}
	}

	if err := s.fillMapPerformance(ctx, guid, &pp.Maps); err != nil {
		pp.Maps = []MapPerformance{}
	}

	if err := s.fillGameModePerformance(ctx, guid, &pp.GameModes); err != nil {
		pp.GameModes = []GameModePerf{}
	}

	// Calculate best conditions summary
	s.calculateBestConditions(pp)

	return pp, nil
}

func (s *PeakPerformanceService) fillTimeOfDay(ctx context.Context, guid string, out *TimeOfDayStats) error {
	query := `
		SELECT 
			toHour(timestamp) as hour,
			countIf(event_type = 'player_kill' AND actor_id = ?) as kills,
			countIf((event_type = 'player_kill' OR event_type = 'player_death') AND actor_id = ?) as deaths,
			countIf(event_type = 'weapon_fire' AND actor_id = ?) as shots,
			countIf(event_type = 'weapon_hit' AND actor_id = ?) as hits,
			countIf(event_type = 'team_win' AND actor_id = ?) as wins
		FROM raw_events
		WHERE actor_id = ? OR target_id = ?
		GROUP BY hour
		ORDER BY hour
	`

	rows, err := s.ch.Query(ctx, query, guid, guid, guid, guid, guid, guid, guid)
	if err != nil {
		return err
	}
	defer rows.Close()

	// Initialize arrays
	out.HourlyKD = make([]float64, 24)
	out.HourlyAcc = make([]float64, 24)
	out.HourlyWins = make([]int64, 24)

	bestKD := 0.0
	worstKD := 999.0
	bestHour := 0
	worstHour := 0

	for rows.Next() {
		var hour int
		var kills, deaths, shots, hits, wins int64
		if err := rows.Scan(&hour, &kills, &deaths, &shots, &hits, &wins); err != nil {
			continue
		}

		if hour >= 0 && hour < 24 {
			var kd float64
			if deaths > 0 {
				kd = float64(kills) / float64(deaths)
			} else {
				kd = float64(kills)
			}
			out.HourlyKD[hour] = kd
			out.HourlyWins[hour] = wins

			if shots > 0 {
				out.HourlyAcc[hour] = float64(hits) / float64(shots) * 100
			}

			if kd > bestKD && kills >= 10 { // Minimum sample size
				bestKD = kd
				bestHour = hour
			}
			if kd < worstKD && kills >= 10 {
				worstKD = kd
				worstHour = hour
			}
		}
	}

	out.BestHour = bestHour
	out.BestKD = bestKD
	out.WorstHour = worstHour
	out.WorstKD = worstKD

	return nil
}

func (s *PeakPerformanceService) fillDayOfWeek(ctx context.Context, guid string, out *DayOfWeekStats) error {
	query := `
		SELECT 
			toDayOfWeek(timestamp) as dow,
			countIf(event_type = 'player_kill' AND actor_id = ?) as kills,
			countIf((event_type = 'player_kill' OR event_type = 'player_death') AND actor_id = ?) as deaths,
			countIf(event_type = 'team_win' AND actor_id = ?) as wins,
			uniq(match_id) as matches
		FROM raw_events
		WHERE actor_id = ? OR target_id = ?
		GROUP BY dow
		ORDER BY dow
	`

	rows, err := s.ch.Query(ctx, query, guid, guid, guid, guid, guid)
	if err != nil {
		return err
	}
	defer rows.Close()

	dayNames := []string{"Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"}
	out.DailyKD = make([]float64, 7)
	out.DailyWinRate = make([]float64, 7)

	bestWR := 0.0
	worstWR := 100.0
	bestDay := 0
	worstDay := 0

	for rows.Next() {
		var dow int
		var kills, deaths, wins, matches int64
		if err := rows.Scan(&dow, &kills, &deaths, &wins, &matches); err != nil {
			continue
		}

		idx := dow - 1 // ClickHouse: 1=Monday, 7=Sunday
		if idx >= 0 && idx < 7 {
			if deaths > 0 {
				out.DailyKD[idx] = float64(kills) / float64(deaths)
			} else {
				out.DailyKD[idx] = float64(kills)
			}

			var winRate float64
			if matches > 0 {
				winRate = float64(wins) / float64(matches) * 100
			}
			out.DailyWinRate[idx] = winRate

			if winRate > bestWR && matches >= 5 {
				bestWR = winRate
				bestDay = idx
			}
			if winRate < worstWR && matches >= 5 {
				worstWR = winRate
				worstDay = idx
			}
		}
	}

	out.BestDay = dayNames[bestDay]
	out.BestWinRate = bestWR
	out.WorstDay = dayNames[worstDay]
	out.WorstWinRate = worstWR

	return nil
}

func (s *PeakPerformanceService) fillSessionFatigue(ctx context.Context, guid string, out *SessionFatigueStats) error {
	// Calculate K/D in different session time buckets
	// This requires tracking session start times, which we approximate via client_begin events

	query := `
		WITH session_times AS (
			SELECT 
				match_id,
				min(timestamp) as session_start
			FROM raw_events
			WHERE actor_id = ?
			GROUP BY match_id
		)
		SELECT 
			floor(dateDiff('minute', s.session_start, e.timestamp) / 30) * 30 as bucket,
			countIf(e.event_type = 'player_kill' AND e.actor_id = ?) as kills,
			countIf((e.event_type = 'player_kill' OR e.event_type = 'player_death') AND e.actor_id = ?) as deaths
		FROM raw_events e
		JOIN session_times s ON e.match_id = s.match_id
		WHERE e.actor_id = ? OR e.target_id = ?
		GROUP BY bucket
		ORDER BY bucket
	`

	rows, err := s.ch.Query(ctx, query, guid, guid, guid, guid, guid)
	if err != nil {
		return err
	}
	defer rows.Close()

	kdByBucket := make(map[int]float64)
	for rows.Next() {
		var bucket int
		var kills, deaths int64
		if err := rows.Scan(&bucket, &kills, &deaths); err != nil {
			continue
		}
		if deaths > 0 {
			kdByBucket[bucket] = float64(kills) / float64(deaths)
		} else {
			kdByBucket[bucket] = float64(kills)
		}
	}

	out.KDAt30Min = kdByBucket[0]   // 0-30 min
	out.KDAt60Min = kdByBucket[30]  // 30-60 min
	out.KDAt90Min = kdByBucket[60]  // 60-90 min
	out.KDAt120Min = kdByBucket[90] // 90-120 min

	// Find optimal duration (highest K/D bucket)
	maxKD := 0.0
	optimalBucket := 0
	for bucket, kd := range kdByBucket {
		if kd > maxKD {
			maxKD = kd
			optimalBucket = bucket
		}
	}
	out.OptimalDuration = optimalBucket + 30

	// Find fatigue onset (first bucket where K/D drops below average)
	avgKD := (out.KDAt30Min + out.KDAt60Min + out.KDAt90Min + out.KDAt120Min) / 4
	for bucket := 0; bucket <= 120; bucket += 30 {
		if kdByBucket[bucket] < avgKD*0.9 { // 10% below average
			out.FatigueOnset = bucket
			break
		}
	}

	return nil
}

func (s *PeakPerformanceService) fillMatchMomentum(ctx context.Context, guid string, out *MatchMomentumStats) error {
	// Compare first half vs second half performance within matches
	query := `
		WITH match_durations AS (
			SELECT 
				match_id,
				min(timestamp) as match_start,
				max(timestamp) as match_end,
				dateDiff('second', min(timestamp), max(timestamp)) as duration
			FROM raw_events
			WHERE match_id != ''
			GROUP BY match_id
		)
		SELECT
			countIf(e.event_type = 'player_kill' AND e.actor_id = ? 
				AND dateDiff('second', m.match_start, e.timestamp) < m.duration / 2) as first_half_kills,
			countIf((e.event_type = 'player_kill' OR e.event_type = 'player_death') AND e.actor_id = ?
				AND dateDiff('second', m.match_start, e.timestamp) < m.duration / 2) as first_half_deaths,
			countIf(e.event_type = 'player_kill' AND e.actor_id = ? 
				AND dateDiff('second', m.match_start, e.timestamp) >= m.duration / 2) as second_half_kills,
			countIf((e.event_type = 'player_kill' OR e.event_type = 'player_death') AND e.actor_id = ?
				AND dateDiff('second', m.match_start, e.timestamp) >= m.duration / 2) as second_half_deaths
		FROM raw_events e
		JOIN match_durations m ON e.match_id = m.match_id
		WHERE e.actor_id = ? OR e.target_id = ?
	`

	var fhKills, fhDeaths, shKills, shDeaths int64
	err := s.ch.QueryRow(ctx, query, guid, guid, guid, guid, guid, guid).Scan(
		&fhKills, &fhDeaths, &shKills, &shDeaths,
	)
	if err != nil {
		return err
	}

	if fhDeaths > 0 {
		out.FirstHalfKD = float64(fhKills) / float64(fhDeaths)
	} else {
		out.FirstHalfKD = float64(fhKills)
	}

	if shDeaths > 0 {
		out.SecondHalfKD = float64(shKills) / float64(shDeaths)
	} else {
		out.SecondHalfKD = float64(shKills)
	}

	// Calculate strengths (positive = good, negative = bad)
	avgKD := (out.FirstHalfKD + out.SecondHalfKD) / 2
	if avgKD > 0 {
		out.OpenerStrength = ((out.FirstHalfKD - avgKD) / avgKD) * 100
		out.CloserStrength = ((out.SecondHalfKD - avgKD) / avgKD) * 100
	}

	return nil
}

func (s *PeakPerformanceService) fillMapPerformance(ctx context.Context, guid string, out *[]MapPerformance) error {
	query := `
		SELECT 
			map_name,
			countIf(event_type = 'player_kill' AND actor_id = ?) as kills,
			countIf((event_type = 'player_kill' OR event_type = 'player_death') AND actor_id = ?) as deaths,
			countIf(event_type = 'weapon_fire' AND actor_id = ?) as shots,
			countIf(event_type = 'weapon_hit' AND actor_id = ?) as hits,
			countIf(event_type = 'team_win' AND actor_id = ?) as wins,
			uniq(match_id) as matches
		FROM raw_events
		WHERE (actor_id = ? OR target_id = ?) AND map_name != ''
		GROUP BY map_name
		HAVING matches >= 3
		ORDER BY kills DESC
		LIMIT 20
	`

	rows, err := s.ch.Query(ctx, query, guid, guid, guid, guid, guid, guid, guid)
	if err != nil {
		return err
	}
	defer rows.Close()

	for rows.Next() {
		var mp MapPerformance
		var shots, hits, wins int64
		if err := rows.Scan(&mp.MapName, &mp.Kills, &mp.Deaths, &shots, &hits, &wins, &mp.Matches); err != nil {
			continue
		}

		if mp.Deaths > 0 {
			mp.KD = float64(mp.Kills) / float64(mp.Deaths)
		} else {
			mp.KD = float64(mp.Kills)
		}

		if shots > 0 {
			mp.Accuracy = float64(hits) / float64(shots) * 100
		}

		if mp.Matches > 0 {
			mp.WinRate = float64(wins) / float64(mp.Matches) * 100
		}

		// TODO: Calculate percentile by comparing to other players on this map
		mp.Percentile = 50 // Placeholder

		*out = append(*out, mp)
	}

	return nil
}

func (s *PeakPerformanceService) fillGameModePerformance(ctx context.Context, guid string, out *[]GameModePerf) error {
	query := `
		SELECT 
			extract(extra, 'gametype') as mode,
			countIf(event_type = 'player_kill' AND actor_id = ?) as kills,
			countIf((event_type = 'player_kill' OR event_type = 'player_death') AND actor_id = ?) as deaths,
			countIf(event_type = 'team_win' AND actor_id = ?) as wins,
			countIf(event_type = 'objective_update' AND actor_id = ?) as obj_actions,
			uniq(match_id) as matches
		FROM raw_events
		WHERE (actor_id = ? OR target_id = ?) AND mode != ''
		GROUP BY mode
		HAVING matches >= 3
		ORDER BY matches DESC
	`

	rows, err := s.ch.Query(ctx, query, guid, guid, guid, guid, guid, guid)
	if err != nil {
		return err
	}
	defer rows.Close()

	for rows.Next() {
		var gm GameModePerf
		var kills, deaths, wins int64
		if err := rows.Scan(&gm.Mode, &kills, &deaths, &wins, &gm.ObjScore, &gm.Matches); err != nil {
			continue
		}

		if deaths > 0 {
			gm.KD = float64(kills) / float64(deaths)
		} else {
			gm.KD = float64(kills)
		}

		if gm.Matches > 0 {
			gm.WinRate = float64(wins) / float64(gm.Matches) * 100
		}

		*out = append(*out, gm)
	}

	return nil
}

func (s *PeakPerformanceService) calculateBestConditions(pp *PeakPerformance) {
	bc := &pp.BestConditions

	// Best time slot (find 3-hour window with highest average K/D)
	bestWindow := 0
	bestWindowKD := 0.0
	for start := 0; start < 22; start++ {
		windowKD := (pp.TimeOfDay.HourlyKD[start] + pp.TimeOfDay.HourlyKD[start+1] + pp.TimeOfDay.HourlyKD[start+2]) / 3
		if windowKD > bestWindowKD {
			bestWindowKD = windowKD
			bestWindow = start
		}
	}
	bc.BestTimeSlot = fmt.Sprintf("%02d:00-%02d:00", bestWindow, bestWindow+3)

	// Best day
	bc.BestDay = pp.DayOfWeek.BestDay

	// Best map
	if len(pp.Maps) > 0 {
		bc.BestMap = pp.Maps[0].MapName // Already sorted by kills
	}

	// Optimal session
	opt := pp.SessionFatigue.OptimalDuration
	bc.OptimalSession = fmt.Sprintf("%d-%d minutes", opt-15, opt+15)

	// Calculate peak boost
	avgKD := 0.0
	count := 0
	for _, kd := range pp.TimeOfDay.HourlyKD {
		if kd > 0 {
			avgKD += kd
			count++
		}
	}
	if count > 0 {
		avgKD = avgKD / float64(count)
		bc.PeakKDBoost = ((pp.TimeOfDay.BestKD - avgKD) / avgKD) * 100
	}
}

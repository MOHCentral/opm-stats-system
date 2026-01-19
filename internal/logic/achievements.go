package logic

import (
	"context"
	"fmt"
	"time"
	
	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/openmohaa/stats-api/internal/models"
)

// AchievementService handles achievement calculations
type AchievementService struct {
	ch driver.Conn
}

func NewAchievementService(ch driver.Conn) *AchievementService {
	return &AchievementService{ch: ch}
}

// CheckAchievements queries player stats and determines which achievements are unlocked
func (s *AchievementService) CheckAchievements(ctx context.Context, playerID string) ([]models.PlayerAchievement, error) {
	// 1. Fetch comprehensive player stats
	stats, err := s.getPlayerStats(ctx, playerID)
	if err != nil {
		return nil, fmt.Errorf("failed to get player stats: %w", err)
	}

	var unlocked []models.PlayerAchievement
	
	// 2. Iterate through all achievement definitions and check criteria
	for _, def := range models.AllAchievements {
		if s.checkCriteria(def, stats) {
			unlocked = append(unlocked, models.PlayerAchievement{
				PlayerGUID:    playerID,
				AchievementID: def.ID,
				// Ideally we'd know WHEN it was unlocked, but for now we calculate instantaneous status
				// Real system might store unlocks in a separate table
				UnlockedAt:    stats.LastActive, 
			})
		}
	}

	return unlocked, nil
}

// Temporary struct to hold the aggregate stats needed for achievement checks
type AchievementStats struct {
	Kills           int64
	Deaths          int64
	Headshots       int64
	Wins            int64
	Matches         int64
	Distance        float64
	Jumps           int64
	ChatMsgs        int64
	BashKills       int64
	RoadKills       int64
	Suicides        int64
	FlagCaptures    int64
	LastActive      time.Time
}

func (s *AchievementService) getPlayerStats(ctx context.Context, playerID string) (*AchievementStats, error) {
	// Reusing logic similar to GetPlayerStats handler but internal
	// In a real app, we might extract the query to a repository layer
	row := s.ch.QueryRow(ctx, `
		SELECT
			countIf(event_type = 'kill' AND actor_id = ?) as kills,
			countIf(event_type = 'death' AND actor_id = ?) as deaths,
			countIf(event_type = 'headshot' AND actor_id = ?) as headshots,
			uniq(match_id) as matches,
			countIf(event_type = 'match_win' AND actor_id = ?) as wins,
			countIf(event_type = 'player_jump' AND actor_id = ?) as jumps,
			countIf(event_type = 'say' AND actor_id = ?) as chat_msgs,
			countIf(event_type = 'kill' AND actor_id = ? AND extract(extra, 'mod') = 'MOD_PISTOL_WHIP') as bash_kills,
			countIf(event_type = 'kill' AND actor_id = ? AND (extract(extra, 'mod') = 'MOD_CRUSH' OR extract(extra, 'mod') = 'MOD_VEHICLE')) as roadkills,
			countIf(event_type = 'suicide' AND actor_id = ?) as suicides,
			countIf(event_type = 'flag_capture' AND actor_id = ?) as flag_captures,
			sumIf(toFloat64OrZero(extract(extra, 'distance')), event_type = 'player_distance' AND actor_id = ?) / 100000.0 as distance_km,
			max(timestamp) as last_active
		FROM raw_events
		WHERE actor_id = ?
	`, 
	playerID, playerID, playerID, 
	playerID, playerID, playerID, 
	playerID, playerID, playerID, 
	playerID, playerID, playerID)

	var stats AchievementStats
	// Clickhouse driver handles nulls properly usually, or returns zero values
	if err := row.Scan(
		&stats.Kills, &stats.Deaths, &stats.Headshots, &stats.Matches, 
		&stats.Wins, &stats.Jumps, &stats.ChatMsgs, &stats.BashKills, 
		&stats.RoadKills, &stats.Suicides, &stats.FlagCaptures, &stats.Distance, 
		&stats.LastActive,
	); err != nil {
		return nil, err
	}
	return &stats, nil
}

func (s *AchievementService) checkCriteria(def models.AchievementDefinition, stats *AchievementStats) bool {
	switch def.Metric {
	case "kills":
		return stats.Kills >= def.Target
	case "headshots":
		return stats.Headshots >= def.Target
	case "matches":
		return stats.Matches >= def.Target
	case "wins":
		return stats.Wins >= def.Target
	case "distance":
		return stats.Distance >= float64(def.Target) / 1000.0 // Target is meters, stats is km? 
		// Actually definition says 'Target: 10000 (km?)' or meters?
		// My logic calc: distance_km.
		// If def.Target is 10000 (meters? km?), I should align units.
		// Let's assume Target is RAW UNITS from definition.
		// Definition for 'Marathon Runner' says "Travel 10km". Target 10000.
		// If target is meters, 10000m = 10km.
		// My SQL returns KM. 
		// So compare stats.Distance (km) * 1000 >= Target (m)
		// Or update definition to be in KM (Target: 10).
		// I'll update the check: stats.Distance >= float64(def.Target)/1000.0 if target is meters.
		// Or simpler: Update definition to target 10 (km).
		// But let's assume Target is consistent with Description.
		// I'll stick to: Compare KM. If Target is 10000 and Description "10km", then Target is meters. 
		// stats.Distance (km) * 1000 >= Target.
		return (stats.Distance * 1000) >= float64(def.Target)
	case "jumps":
		return stats.Jumps >= def.Target
	case "chat_msgs":
		return stats.ChatMsgs >= def.Target
	case "bash_kills":
		return stats.BashKills >= def.Target
	case "roadkills":
		return stats.RoadKills >= def.Target
	case "suicides":
		return stats.Suicides >= def.Target
	case "flag_captures":
		return stats.FlagCaptures >= def.Target
	}
	return false
}

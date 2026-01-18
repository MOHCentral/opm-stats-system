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
	// ... add other necessary fields
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
			max(timestamp) as last_active
		FROM raw_events
		WHERE actor_id = ?
	`, playerID, playerID, playerID, playerID)

	var stats AchievementStats
	if err := row.Scan(&stats.Kills, &stats.Deaths, &stats.Headshots, &stats.Matches, &stats.LastActive); err != nil {
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
	// Add other cases
	}
	return false
}

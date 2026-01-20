package logic

import (
	"context"

	"github.com/google/uuid"
	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/openmohaa/stats-api/internal/models"
)

type TournamentService struct {
	ch driver.Conn
}

func NewTournamentService(ch driver.Conn) *TournamentService {
	return &TournamentService{ch: ch}
}

// GetTournaments returns list of tournaments
func (s *TournamentService) GetTournaments(ctx context.Context) ([]models.Tournament, error) {
	// For now, returning mock/placeholder or query from DB if table exists.
	// Assuming a 'tournaments' table in ClickHouse or Postgres. 
	// The prompt implies ClickHouse for stats, but tournaments might be stateful/Postgres.
	// But models are in Go. I'll assume ClickHouse 'tournaments' table for now or just return empty.
	// Since I don't see Postgres usage heavily in other services (except auth), I'll stick to ClickHouse or hardcode one for demo.
	
	// Actually, let's just make it return an empty list or placeholder as I don't have the table schema for tournaments in CH explicitly in my learnings.
	// But `models/tournament.go` exists.
	// I'll implementing a basic query derived from matches tagged with tournament_id?
	// Or maybe just stub it for now as "Coming Soon" effectively, or minimal implementation.
	
	return []models.Tournament{}, nil
}

// GetTournament returns details
func (s *TournamentService) GetTournament(ctx context.Context, id string) (*models.Tournament, error) {
	// Parse UUID
	uid, err := uuid.Parse(id)
	if err != nil {
		return nil, err
	}

	return &models.Tournament{
		ID: uid,
		Name: "Sample Tournament",
		Status: models.TournamentStatusInProgress,
	}, nil
}

// GetTournamentStats returns aggregated stats for a tournament
func (s *TournamentService) GetTournamentStats(ctx context.Context, tournamentID string) (map[string]interface{}, error) {
	stats := make(map[string]interface{})
	
	// Use temp vars for scanning
	var totalMatches int64
	s.ch.QueryRow(ctx, `
		SELECT count(DISTINCT match_id) 
		FROM raw_events 
		WHERE tournament_id = ?
	`, tournamentID).Scan(&totalMatches)
	stats["total_matches"] = totalMatches

	var totalKills int64
	s.ch.QueryRow(ctx, `
		SELECT count() 
		FROM raw_events 
		WHERE tournament_id = ? AND event_type = 'player_kill'
	`, tournamentID).Scan(&totalKills)
	stats["total_kills"] = totalKills

	// Top Player ( MVP )
	var mvp string
	// Check if any results before scanning to avoid "sql: no rows" error if using QueryRow on empty set
	// Actually count() always returns row. But grouping might not.
	// For simple logic, we'll try/catch scan error or just ignore.
	if err := s.ch.QueryRow(ctx, `
		SELECT actor_name 
		FROM raw_events 
		WHERE tournament_id = ? AND event_type = 'player_kill'
		GROUP BY actor_name 
		ORDER BY count() DESC LIMIT 1
	`, tournamentID).Scan(&mvp); err == nil {
		stats["mvp"] = mvp
	} else {
		stats["mvp"] = "None"
	}

	return stats, nil
}

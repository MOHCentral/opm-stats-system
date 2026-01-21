package worker

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"sync"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/openmohaa/stats-api/internal/models"
)

// AchievementWorker processes events and unlocks achievements
type AchievementWorker struct {
	db              *pgxpool.Pool        // Postgres for achievement defs and unlocks
	ch              driver.Conn          // ClickHouse for stats queries
	achievementDefs map[string]*AchievementDefinition
	mu              sync.RWMutex
	ctx             context.Context
	cancel          context.CancelFunc
}

// AchievementDefinition holds criteria for unlocking
type AchievementDefinition struct {
	Slug        string
	Category    string
	Tier        string
	Points      int
	Criteria    string // JSON criteria
	Description string
}

// NewAchievementWorker creates a new achievement processing worker
func NewAchievementWorker(db *pgxpool.Pool, ch driver.Conn) *AchievementWorker {
	ctx, cancel := context.WithCancel(context.Background())

	worker := &AchievementWorker{
		db:              db,
		ch:              ch,
		achievementDefs: make(map[string]*AchievementDefinition),
		ctx:             ctx,
		cancel:          cancel,
	}

	// Load achievement definitions from database
	if err := worker.loadAchievementDefinitions(); err != nil {
		log.Printf("Failed to load achievement definitions: %v", err)
	}

	return worker
}

// Start begins the achievement worker
func (w *AchievementWorker) Start() {
	log.Println("Achievement Worker started")
}

// Stop gracefully stops the worker
func (w *AchievementWorker) Stop() {
	w.cancel()
	log.Println("Achievement Worker stopped")
}

// loadAchievementDefinitions loads all achievements from database
func (w *AchievementWorker) loadAchievementDefinitions() error {
	query := `
		SELECT achievement_code, category, tier, points, requirement_value::text, achievement_name
		FROM mohaa_achievements
	`

	rows, err := w.db.Query(w.ctx, query)
	if err != nil {
		return fmt.Errorf("failed to query achievements: %w", err)
	}
	defer rows.Close()

	w.mu.Lock()
	defer w.mu.Unlock()

	count := 0
	for rows.Next() {
		def := &AchievementDefinition{}
		err := rows.Scan(
			&def.Slug,
			&def.Category,
			&def.Tier,
			&def.Points,
			&def.Criteria,
			&def.Description,
		)
		if err != nil {
			log.Printf("Failed to scan achievement: %v", err)
			continue
		}

		w.achievementDefs[def.Slug] = def
		count++
	}

	log.Printf("Loaded %d achievement definitions", count)
	return nil
}

// ProcessEvent checks if an event triggers any achievements
func (w *AchievementWorker) ProcessEvent(event *models.RawEvent) {
	// Determine Actor ID based on event type
	actorSMFID := w.getActorSMFID(event)
	log.Printf("Processing event type=%s, actorSMFID=%d", event.Type, actorSMFID)
	
	if actorSMFID == 0 {
		return // Only process for authenticated players
	}

	// Check different event types
	switch event.Type {
	case models.EventKill:
		log.Printf("Checking combat achievements for SMF ID %d", actorSMFID)
		w.checkCombatAchievements(actorSMFID, event)
	case models.EventHeadshot:
		w.checkHeadshotAchievements(actorSMFID, event)
	case models.EventDistance:
		w.checkMovementAchievements(actorSMFID, event)
	case models.EventVehicleEnter:
		w.checkVehicleAchievements(actorSMFID, event)
	case models.EventItemPickup, models.EventHealthPickup:
		w.checkSurvivalAchievements(actorSMFID, event)
	case models.EventObjectiveUpdate: // Assuming objective_complete maps to this or similar
		w.checkObjectiveAchievements(actorSMFID, event)
	case models.EventTeamWin: // Assuming round_win maps to this
		w.checkTeamplayAchievements(actorSMFID, event)
	}
}

// getActorSMFID resolves the primary actor's SMF ID for the event
func (w *AchievementWorker) getActorSMFID(event *models.RawEvent) int64 {
	// For combat events where the killer is the actor
	if event.Type == models.EventKill || event.Type == models.EventHeadshot || event.Type == models.EventDamage {
		return event.AttackerSMFID
	}
	// For most other events, it's the player
	return event.PlayerSMFID
}

// checkCombatAchievements checks for combat-related achievements
func (w *AchievementWorker) checkCombatAchievements(smfID int64, event *models.RawEvent) {
	// Get player's total kills
	totalKills := w.getPlayerStat(int(smfID), "total_kills")
	log.Printf("Player SMF ID %d has %d total kills", smfID, totalKills)

	serverID := 0
	// Try parsing ServerID if needed, or default to 0

	ts := time.Unix(int64(event.Timestamp), 0)

	// Check milestone achievements
	milestones := map[string]int{
		"first-blood":     1,
		"killer-bronze":   10,
		"killer-silver":   50,
		"killer-gold":     100,
		"killer-platinum": 500,
		"killer-diamond":  1000,
		"killing-spree":   5,  // In single match
		"unstoppable":     10, // In single match
		"legendary":       20, // In single match
	}

	for slug, threshold := range milestones {
		if totalKills == threshold {
			log.Printf("Achievement unlocked! %s (threshold: %d)", slug, threshold)
			w.unlockAchievement(int(smfID), slug, serverID, ts)
		}
	}

	// Check weapon-specific achievements
	if event.Weapon != "" {
		w.checkWeaponMasteryAchievement(int(smfID), event.Weapon, serverID, ts)
	}

	// Check multikill achievements
	w.checkMultikillAchievement(int(smfID), event)
}

// checkHeadshotAchievements checks headshot-based achievements
func (w *AchievementWorker) checkHeadshotAchievements(smfID int64, event *models.RawEvent) {
	totalHeadshots := w.getPlayerStat(int(smfID), "total_headshots")

	serverID := 0
	ts := time.Unix(int64(event.Timestamp), 0)

	milestones := map[string]int{
		"sharpshooter-bronze":   10,
		"sharpshooter-silver":   50,
		"sharpshooter-gold":     100,
		"sharpshooter-platinum": 250,
		"sharpshooter-diamond":  500,
	}

	for slug, threshold := range milestones {
		if totalHeadshots == threshold {
			w.unlockAchievement(int(smfID), slug, serverID, ts)
		}
	}

	// Check headshot streak
	w.checkHeadshotStreakAchievement(int(smfID), event)
}

// checkMovementAchievements checks distance and movement achievements
func (w *AchievementWorker) checkMovementAchievements(smfID int64, event *models.RawEvent) {
	totalDistance := w.getPlayerStat(int(smfID), "total_distance")

	// Convert to kilometers
	distanceKM := float64(totalDistance) / 1000.0

	serverID := 0
	ts := time.Unix(int64(event.Timestamp), 0)

	milestones := map[string]float64{
		"marathoner-bronze":   10,
		"marathoner-silver":   50,
		"marathoner-gold":     100,
		"marathoner-platinum": 250,
		"marathoner-diamond":  500,
	}

	for slug, threshold := range milestones {
		if distanceKM >= threshold && distanceKM < threshold+0.1 {
			w.unlockAchievement(int(smfID), slug, serverID, ts)
		}
	}
}

// checkVehicleAchievements checks vehicle-related achievements
func (w *AchievementWorker) checkVehicleAchievements(smfID int64, event *models.RawEvent) {
	vehicleKills := w.getPlayerStat(int(smfID), "vehicle_kills")

	serverID := 0
	ts := time.Unix(int64(event.Timestamp), 0)

	milestones := map[string]int{
		"tanker-bronze":   5,
		"tanker-silver":   25,
		"tanker-gold":     50,
		"tanker-platinum": 100,
		"tanker-diamond":  250,
	}

	for slug, threshold := range milestones {
		if vehicleKills == threshold {
			w.unlockAchievement(int(smfID), slug, serverID, ts)
		}
	}
}

// checkSurvivalAchievements checks survival and healing achievements
func (w *AchievementWorker) checkSurvivalAchievements(smfID int64, event *models.RawEvent) {
	serverID := 0
	ts := time.Unix(int64(event.Timestamp), 0)

	if event.Type == models.EventHealthPickup {
		healthPickups := w.getPlayerStat(int(smfID), "health_pickups")

		milestones := map[string]int{
			"medic-bronze":   10,
			"medic-silver":   50,
			"medic-gold":     100,
			"medic-platinum": 250,
			"medic-diamond":  500,
		}

		for slug, threshold := range milestones {
			if healthPickups == threshold {
				w.unlockAchievement(int(smfID), slug, serverID, ts)
			}
		}
	}
}

// checkObjectiveAchievements checks objective-based achievements
func (w *AchievementWorker) checkObjectiveAchievements(smfID int64, event *models.RawEvent) {
	totalObjectives := w.getPlayerStat(int(smfID), "objectives_completed")

	serverID := 0
	ts := time.Unix(int64(event.Timestamp), 0)

	milestones := map[string]int{
		"objective-bronze":   5,
		"objective-silver":   25,
		"objective-gold":     50,
		"objective-platinum": 100,
		"objective-diamond":  250,
	}

	for slug, threshold := range milestones {
		if totalObjectives == threshold {
			w.unlockAchievement(int(smfID), slug, serverID, ts)
		}
	}
}

// checkTeamplayAchievements checks team-based achievements
func (w *AchievementWorker) checkTeamplayAchievements(smfID int64, event *models.RawEvent) {
	totalWins := w.getPlayerStat(int(smfID), "total_wins")

	serverID := 0
	ts := time.Unix(int64(event.Timestamp), 0)

	milestones := map[string]int{
		"winner-bronze":   10,
		"winner-silver":   25,
		"winner-gold":     50,
		"winner-platinum": 100,
		"winner-diamond":  250,
	}

	for slug, threshold := range milestones {
		if totalWins == threshold {
			w.unlockAchievement(int(smfID), slug, serverID, ts)
		}
	}
}

// Helper functions

func (w *AchievementWorker) checkWeaponMasteryAchievement(smfID int, weapon string, serverID int, ts time.Time) {
	weaponKills := w.getWeaponKills(smfID, weapon)

	// Example: 100 kills with Kar98k unlocks "Sniper Master"
	if weapon == "kar98k" && weaponKills == 100 {
		w.unlockAchievement(smfID, "sniper-master", serverID, ts)
	}
}

func (w *AchievementWorker) checkMultikillAchievement(smfID int, event *models.RawEvent) {
	// Would check recent kills within time window
	// For now, simplified
}

func (w *AchievementWorker) checkHeadshotStreakAchievement(smfID int, event *models.RawEvent) {
	// Would check consecutive headshots
	// For now, simplified
}

// getPlayerStat retrieves a player stat from ClickHouse
func (w *AchievementWorker) getPlayerStat(smfID int, statName string) int {
	// Map stat names to ClickHouse queries
	var query string
	switch statName {
	case "total_kills":
		query = `SELECT count() FROM mohaa_stats.raw_events WHERE actor_smf_id = ? AND event_type = 'kill'`
	case "total_headshots":
		query = `SELECT count() FROM mohaa_stats.raw_events WHERE actor_smf_id = ? AND event_type = 'kill' AND hitloc = 'head'`
	case "total_distance":
		query = `SELECT SUM(walked + sprinted + swam + driven) FROM mohaa_stats.raw_events WHERE player_smf_id = ? AND event_type = 'distance'`
	case "vehicle_kills":
		query = `SELECT count() FROM mohaa_stats.raw_events WHERE actor_smf_id = ? AND event_type = 'kill' AND inflictor LIKE '%vehicle%'`
	case "health_pickups":
		query = `SELECT count() FROM mohaa_stats.raw_events WHERE player_smf_id = ? AND event_type = 'item_pickup' AND item LIKE '%health%'`
	case "objectives_completed":
		query = `SELECT count() FROM mohaa_stats.raw_events WHERE player_smf_id = ? AND event_type = 'objective_update' AND objective_status = 'completed'`
	case "total_wins":
		query = `SELECT count() FROM mohaa_stats.raw_events WHERE player_smf_id = ? AND event_type = 'team_win'`
	default:
		return 0
	}

	var value uint64
	err := w.ch.QueryRow(w.ctx, query, smfID).Scan(&value)
	if err != nil {
		log.Printf("Error querying ClickHouse for %s: %v", statName, err)
		return 0
	}

	return int(value)
}

// getWeaponKills gets kills for specific weapon
func (w *AchievementWorker) getWeaponKills(smfID int, weapon string) int {
	// TODO: This needs to query ClickHouse, not Postgres
	// query := `
	// 	SELECT COALESCE(COUNT(*), 0)
	// 	FROM raw_events
	// 	WHERE actor_smf_id = $1
	// 	  AND event_type = 'player_kill'
	// 	  AND extra->>'weapon' = $2
	// `

	// var count int
	// err := w.db.QueryRow(w.ctx, query, smfID, weapon).Scan(&count)
	// if err != nil {
	// 	return 0
	// }

	return 0
}

// unlockAchievement records an achievement unlock
func (w *AchievementWorker) unlockAchievement(smfID int, slug string, serverID int, timestamp time.Time) {
	// Get achievement ID from code
	var achievementID int
	getIDQuery := `
		SELECT achievement_id FROM mohaa_achievements WHERE achievement_code = $1
	`
	err := w.db.QueryRow(w.ctx, getIDQuery, slug).Scan(&achievementID)
	if err != nil {
		log.Printf("Achievement code not found: %s", slug)
		return
	}
	
	// Check if already unlocked
	var exists bool
	checkQuery := `
		SELECT EXISTS(
			SELECT 1 FROM mohaa_player_achievements
			WHERE smf_member_id = $1 AND achievement_id = $2 AND unlocked = true
		)
	`
	err = w.db.QueryRow(w.ctx, checkQuery, smfID, achievementID).Scan(&exists)
	if err != nil || exists {
		return // Already unlocked or error
	}

	// Get achievement details
	w.mu.RLock()
	def, exists := w.achievementDefs[slug]
	w.mu.RUnlock()

	if !exists {
		log.Printf("Achievement definition not found: %s", slug)
		return
	}

	// Update or insert player achievement record
	insertQuery := `
		INSERT INTO mohaa_player_achievements
		(smf_member_id, achievement_id, target, unlocked, unlocked_at, progress)
		VALUES ($1, $2, $3, true, $4, $3)
		ON CONFLICT (smf_member_id, achievement_id) 
		DO UPDATE SET unlocked = true, unlocked_at = $4, progress = EXCLUDED.target
	`

	_, err = w.db.Exec(w.ctx, insertQuery, smfID, achievementID, 100, timestamp)
	if err != nil {
		log.Printf("Failed to unlock achievement %s for player %d: %v", slug, smfID, err)
		return
	}

	// Note: Player achievement points can be calculated via SUM query
	// No need to maintain separate counter

	log.Printf("🏆 Achievement unlocked: %s for player %d (+%d points)", slug, smfID, def.Points)

	// TODO: Send notification to player
	w.notifyPlayer(smfID, slug, def)
}

// notifyPlayer sends achievement notification (placeholder)
func (w *AchievementWorker) notifyPlayer(smfID int, slug string, def *AchievementDefinition) {
	// Would send WebSocket notification or queue for next page load
	notification := map[string]interface{}{
		"type":        "achievement_unlock",
		"smf_id":      smfID,
		"slug":        slug,
		"title":       def.Description,
		"tier":        def.Tier,
		"points":      def.Points,
		"unlocked_at": time.Now(),
	}

	jsonData, _ := json.Marshal(notification)
	log.Printf("Notification: %s", string(jsonData))

	// In production, would use Redis pub/sub or WebSocket
}

// ProcessBatch processes multiple events in batch
func (w *AchievementWorker) ProcessBatch(events []*models.RawEvent) {
	for _, event := range events {
		w.ProcessEvent(event)
	}
}

// ReloadDefinitions reloads achievement definitions from database
func (w *AchievementWorker) ReloadDefinitions() error {
	return w.loadAchievementDefinitions()
}

package worker

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"sync"
	"time"

	"github.com/elgan65536/opm-stats-system/internal/models"
)

// AchievementWorker processes events and unlocks achievements
type AchievementWorker struct {
	db              *sql.DB
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
func NewAchievementWorker(db *sql.DB) *AchievementWorker {
	ctx, cancel := context.WithCancel(context.Background())
	
	worker := &AchievementWorker{
		db:              db,
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
		SELECT slug, category, tier, points, criteria, description
		FROM mohaa_achievements
		WHERE active = true
	`
	
	rows, err := w.db.QueryContext(w.ctx, query)
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
	if event.ActorSMFID == 0 {
		return // Only process for authenticated players
	}
	
	// Check different event types
	switch event.EventType {
	case "player_kill":
		w.checkCombatAchievements(event)
	case "player_headshot":
		w.checkHeadshotAchievements(event)
	case "player_distance":
		w.checkMovementAchievements(event)
	case "vehicle_enter":
		w.checkVehicleAchievements(event)
	case "item_pickup":
		w.checkSurvivalAchievements(event)
	case "objective_complete":
		w.checkObjectiveAchievements(event)
	case "round_win":
		w.checkTeamplayAchievements(event)
	}
}

// checkCombatAchievements checks for combat-related achievements
func (w *AchievementWorker) checkCombatAchievements(event *models.RawEvent) {
	smfID := event.ActorSMFID
	
	// Get player's total kills
	totalKills := w.getPlayerStat(smfID, "total_kills")
	
	// Check milestone achievements
	milestones := map[string]int{
		"first-blood":        1,
		"killer-bronze":      10,
		"killer-silver":      50,
		"killer-gold":        100,
		"killer-platinum":    500,
		"killer-diamond":     1000,
		"killing-spree":      5,   // In single match
		"unstoppable":        10,  // In single match
		"legendary":          20,  // In single match
	}
	
	for slug, threshold := range milestones {
		if totalKills == threshold {
			w.unlockAchievement(smfID, slug, event.ServerID, event.Timestamp)
		}
	}
	
	// Check weapon-specific achievements
	weapon := event.Extra["weapon"]
	if weapon != "" {
		w.checkWeaponMasteryAchievement(smfID, weapon, event)
	}
	
	// Check location-specific (headshot already handled by separate event)
	
	// Check multikill achievements
	w.checkMultikillAchievement(smfID, event)
}

// checkHeadshotAchievements checks headshot-based achievements
func (w *AchievementWorker) checkHeadshotAchievements(event *models.RawEvent) {
	smfID := event.ActorSMFID
	
	totalHeadshots := w.getPlayerStat(smfID, "total_headshots")
	
	milestones := map[string]int{
		"sharpshooter-bronze":   10,
		"sharpshooter-silver":   50,
		"sharpshooter-gold":     100,
		"sharpshooter-platinum": 250,
		"sharpshooter-diamond":  500,
	}
	
	for slug, threshold := range milestones {
		if totalHeadshots == threshold {
			w.unlockAchievement(smfID, slug, event.ServerID, event.Timestamp)
		}
	}
	
	// Check headshot streak
	w.checkHeadshotStreakAchievement(smfID, event)
}

// checkMovementAchievements checks distance and movement achievements
func (w *AchievementWorker) checkMovementAchievements(event *models.RawEvent) {
	smfID := event.ActorSMFID
	
	totalDistance := w.getPlayerStat(smfID, "total_distance")
	
	// Convert to kilometers
	distanceKM := totalDistance / 1000.0
	
	milestones := map[string]float64{
		"marathoner-bronze":   10,
		"marathoner-silver":   50,
		"marathoner-gold":     100,
		"marathoner-platinum": 250,
		"marathoner-diamond":  500,
	}
	
	for slug, threshold := range milestones {
		if distanceKM >= threshold && distanceKM < threshold+0.1 {
			w.unlockAchievement(smfID, slug, event.ServerID, event.Timestamp)
		}
	}
}

// checkVehicleAchievements checks vehicle-related achievements
func (w *AchievementWorker) checkVehicleAchievements(event *models.RawEvent) {
	smfID := event.ActorSMFID
	
	vehicleKills := w.getPlayerStat(smfID, "vehicle_kills")
	
	milestones := map[string]int{
		"tanker-bronze":   5,
		"tanker-silver":   25,
		"tanker-gold":     50,
		"tanker-platinum": 100,
		"tanker-diamond":  250,
	}
	
	for slug, threshold := range milestones {
		if vehicleKills == threshold {
			w.unlockAchievement(smfID, slug, event.ServerID, event.Timestamp)
		}
	}
}

// checkSurvivalAchievements checks survival and healing achievements
func (w *AchievementWorker) checkSurvivalAchievements(event *models.RawEvent) {
	smfID := event.ActorSMFID
	
	itemType := event.Extra["item_type"]
	
	if itemType == "health" {
		healthPickups := w.getPlayerStat(smfID, "health_pickups")
		
		milestones := map[string]int{
			"medic-bronze":   10,
			"medic-silver":   50,
			"medic-gold":     100,
			"medic-platinum": 250,
			"medic-diamond":  500,
		}
		
		for slug, threshold := range milestones {
			if healthPickups == threshold {
				w.unlockAchievement(smfID, slug, event.ServerID, event.Timestamp)
			}
		}
	}
}

// checkObjectiveAchievements checks objective-based achievements
func (w *AchievementWorker) checkObjectiveAchievements(event *models.RawEvent) {
	smfID := event.ActorSMFID
	
	totalObjectives := w.getPlayerStat(smfID, "objectives_completed")
	
	milestones := map[string]int{
		"objective-bronze":   5,
		"objective-silver":   25,
		"objective-gold":     50,
		"objective-platinum": 100,
		"objective-diamond":  250,
	}
	
	for slug, threshold := range milestones {
		if totalObjectives == threshold {
			w.unlockAchievement(smfID, slug, event.ServerID, event.Timestamp)
		}
	}
}

// checkTeamplayAchievements checks team-based achievements
func (w *AchievementWorker) checkTeamplayAchievements(event *models.RawEvent) {
	smfID := event.ActorSMFID
	
	totalWins := w.getPlayerStat(smfID, "total_wins")
	
	milestones := map[string]int{
		"winner-bronze":   10,
		"winner-silver":   25,
		"winner-gold":     50,
		"winner-platinum": 100,
		"winner-diamond":  250,
	}
	
	for slug, threshold := range milestones {
		if totalWins == threshold {
			w.unlockAchievement(smfID, slug, event.ServerID, event.Timestamp)
		}
	}
}

// Helper functions

func (w *AchievementWorker) checkWeaponMasteryAchievement(smfID int, weapon string, event *models.RawEvent) {
	weaponKills := w.getWeaponKills(smfID, weapon)
	
	// Example: 100 kills with Kar98k unlocks "Sniper Master"
	if weapon == "kar98k" && weaponKills == 100 {
		w.unlockAchievement(smfID, "sniper-master", event.ServerID, event.Timestamp)
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

// getPlayerStat retrieves a player stat from database
func (w *AchievementWorker) getPlayerStat(smfID int, statName string) int {
	query := fmt.Sprintf(`
		SELECT COALESCE(SUM(%s), 0)
		FROM player_stats
		WHERE smf_member_id = $1
	`, statName)
	
	var value int
	err := w.db.QueryRowContext(w.ctx, query, smfID).Scan(&value)
	if err != nil {
		return 0
	}
	
	return value
}

// getWeaponKills gets kills for specific weapon
func (w *AchievementWorker) getWeaponKills(smfID int, weapon string) int {
	query := `
		SELECT COALESCE(COUNT(*), 0)
		FROM raw_events
		WHERE actor_smf_id = $1
		  AND event_type = 'player_kill'
		  AND extra->>'weapon' = $2
	`
	
	var count int
	err := w.db.QueryRowContext(w.ctx, query, smfID, weapon).Scan(&count)
	if err != nil {
		return 0
	}
	
	return count
}

// unlockAchievement records an achievement unlock
func (w *AchievementWorker) unlockAchievement(smfID int, slug string, serverID int, timestamp time.Time) {
	// Check if already unlocked
	var exists bool
	checkQuery := `
		SELECT EXISTS(
			SELECT 1 FROM player_achievements
			WHERE smf_member_id = $1 AND achievement_slug = $2
		)
	`
	err := w.db.QueryRowContext(w.ctx, checkQuery, smfID, slug).Scan(&exists)
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
	
	// Insert unlock record
	insertQuery := `
		INSERT INTO player_achievements
		(smf_member_id, achievement_slug, unlocked_at, server_id)
		VALUES ($1, $2, $3, $4)
	`
	
	_, err = w.db.ExecContext(w.ctx, insertQuery, smfID, slug, timestamp, serverID)
	if err != nil {
		log.Printf("Failed to unlock achievement %s for player %d: %v", slug, smfID, err)
		return
	}
	
	// Update player's total points
	updateQuery := `
		UPDATE mohaa_player_meta
		SET achievement_points = achievement_points + $1
		WHERE smf_member_id = $2
	`
	
	_, err = w.db.ExecContext(w.ctx, updateQuery, def.Points, smfID)
	if err != nil {
		log.Printf("Failed to update achievement points: %v", err)
	}
	
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

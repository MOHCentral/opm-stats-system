// Package worker implements the buffered worker pool pattern for async event processing.
// This decouples HTTP request handling from database writes, providing:
// - Backpressure handling via load shedding
// - Batch inserts for efficient ClickHouse writes
// - Graceful shutdown with flush guarantees

package worker

import (
	"context"
	"encoding/json"
	"regexp"
	"sync"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/prometheus/client_golang/prometheus"
	"github.com/prometheus/client_golang/prometheus/promauto"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/openmohaa/stats-api/internal/models"
)

// Prometheus metrics
var (
	eventsIngested = promauto.NewCounter(prometheus.CounterOpts{
		Name: "mohaa_events_ingested_total",
		Help: "Total number of events ingested",
	})

	eventsProcessed = promauto.NewCounter(prometheus.CounterOpts{
		Name: "mohaa_events_processed_total",
		Help: "Total number of events processed by workers",
	})

	eventsFailed = promauto.NewCounter(prometheus.CounterOpts{
		Name: "mohaa_events_failed_total",
		Help: "Total number of events that failed processing",
	})

	queueDepth = promauto.NewGauge(prometheus.GaugeOpts{
		Name: "mohaa_worker_queue_depth",
		Help: "Current depth of the worker queue",
	})

	batchInsertDuration = promauto.NewHistogram(prometheus.HistogramOpts{
		Name:    "mohaa_batch_insert_duration_seconds",
		Help:    "Duration of batch inserts to ClickHouse",
		Buckets: prometheus.DefBuckets,
	})

	eventsLoadShed = promauto.NewCounter(prometheus.CounterOpts{
		Name: "mohaa_events_load_shed_total",
		Help: "Total number of events dropped due to load shedding",
	})
)

// Job represents a unit of work for the worker pool
type Job struct {
	Event     *models.RawEvent
	RawJSON   string
	Timestamp time.Time
}

// PoolConfig configures the worker pool
type PoolConfig struct {
	WorkerCount   int
	QueueSize     int
	BatchSize     int
	FlushInterval time.Duration
	ClickHouse    driver.Conn
	Postgres      *pgxpool.Pool
	Redis         *redis.Client
	Logger        *zap.Logger
}

// Pool manages a pool of workers for async event processing
type Pool struct {
	config   PoolConfig
	jobQueue chan Job
	wg       sync.WaitGroup
	ctx      context.Context
	cancel   context.CancelFunc
	logger   *zap.SugaredLogger
}

// NewPool creates a new worker pool
func NewPool(cfg PoolConfig) *Pool {
	if cfg.WorkerCount <= 0 {
		cfg.WorkerCount = 4
	}
	if cfg.QueueSize <= 0 {
		cfg.QueueSize = 10000
	}
	if cfg.BatchSize <= 0 {
		cfg.BatchSize = 500
	}
	if cfg.FlushInterval <= 0 {
		cfg.FlushInterval = time.Second
	}

	return &Pool{
		config:   cfg,
		jobQueue: make(chan Job, cfg.QueueSize),
		logger:   cfg.Logger.Sugar(),
	}
}

// Start launches the worker goroutines
func (p *Pool) Start(ctx context.Context) {
	p.ctx, p.cancel = context.WithCancel(ctx)

	for i := 0; i < p.config.WorkerCount; i++ {
		p.wg.Add(1)
		go p.worker(i)
	}

	// Start queue depth reporter
	go p.reportQueueDepth()

	p.logger.Infow("Worker pool started",
		"workers", p.config.WorkerCount,
		"queueSize", p.config.QueueSize,
		"batchSize", p.config.BatchSize,
	)
}

// Stop gracefully shuts down the worker pool
func (p *Pool) Stop() {
	p.logger.Info("Stopping worker pool...")
	p.cancel()
	close(p.jobQueue)
	p.wg.Wait()
	p.logger.Info("Worker pool stopped")
}

// Enqueue adds a job to the queue. Returns false if queue is full (load shedding)
func (p *Pool) Enqueue(event *models.RawEvent) bool {
	rawJSON, _ := json.Marshal(event)

	job := Job{
		Event:     event,
		RawJSON:   string(rawJSON),
		Timestamp: time.Now(),
	}

	select {
	case p.jobQueue <- job:
		eventsIngested.Inc()
		return true
	default:
		// Queue is full - load shedding
		eventsLoadShed.Inc()
		p.logger.Warn("Queue full, shedding load")
		return false
	}
}

// QueueDepth returns current queue size
func (p *Pool) QueueDepth() int {
	return len(p.jobQueue)
}

// worker processes jobs from the queue in batches
func (p *Pool) worker(id int) {
	defer p.wg.Done()

	batch := make([]Job, 0, p.config.BatchSize)
	ticker := time.NewTicker(p.config.FlushInterval)
	defer ticker.Stop()

	flush := func() {
		if len(batch) == 0 {
			return
		}

		start := time.Now()
		if err := p.processBatch(batch); err != nil {
			p.logger.Errorw("Batch processing failed",
				"worker", id,
				"batchSize", len(batch),
				"error", err,
			)
			eventsFailed.Add(float64(len(batch)))
		} else {
			eventsProcessed.Add(float64(len(batch)))
		}
		batchInsertDuration.Observe(time.Since(start).Seconds())

		batch = batch[:0]
	}

	for {
		select {
		case job, ok := <-p.jobQueue:
			if !ok {
				// Channel closed, flush remaining
				flush()
				return
			}

			batch = append(batch, job)
			if len(batch) >= p.config.BatchSize {
				flush()
			}

		case <-ticker.C:
			flush()

		case <-p.ctx.Done():
			flush()
			return
		}
	}
}

// processBatch handles a batch of events
func (p *Pool) processBatch(batch []Job) error {
	if len(batch) == 0 {
		return nil
	}

	// Prepare ClickHouse batch insert
	ctx := context.Background()

	chBatch, err := p.config.ClickHouse.PrepareBatch(ctx, `
		INSERT INTO raw_events (
			timestamp, match_id, server_id, map_name, event_type,
			actor_id, actor_name, actor_team, actor_weapon,
			actor_pos_x, actor_pos_y, actor_pos_z, actor_pitch, actor_yaw,
			target_id, target_name, target_team,
			target_pos_x, target_pos_y, target_pos_z,
			damage, hitloc, distance, raw_json
		)
	`)
	if err != nil {
		return err
	}

	for _, job := range batch {
		event := job.Event

		// Convert to ClickHouse event
		chEvent := p.convertToClickHouseEvent(event, job.RawJSON)

		err := chBatch.Append(
			chEvent.Timestamp,
			chEvent.MatchID,
			chEvent.ServerID,
			chEvent.MapName,
			chEvent.EventType,
			chEvent.ActorID,
			chEvent.ActorName,
			chEvent.ActorTeam,
			chEvent.ActorWeapon,
			chEvent.ActorPosX,
			chEvent.ActorPosY,
			chEvent.ActorPosZ,
			chEvent.ActorPitch,
			chEvent.ActorYaw,
			chEvent.TargetID,
			chEvent.TargetName,
			chEvent.TargetTeam,
			chEvent.TargetPosX,
			chEvent.TargetPosY,
			chEvent.TargetPosZ,
			chEvent.Damage,
			chEvent.Hitloc,
			chEvent.Distance,
			chEvent.RawJSON,
		)
		if err != nil {
			p.logger.Warnw("Failed to append event to batch", "error", err)
			continue
		}

		// Process side effects (Redis state updates, achievement checks)
		go p.processEventSideEffects(ctx, event)
	}

	return chBatch.Send()
}

// convertToClickHouseEvent normalizes a raw event for ClickHouse
func (p *Pool) convertToClickHouseEvent(event *models.RawEvent, rawJSON string) *models.ClickHouseEvent {
	// Parse match_id as UUID or generate one
	matchID := parseOrGenerateUUID(event.MatchID)

	// Normalize event data
	ch := &models.ClickHouseEvent{
		Timestamp: time.Unix(int64(event.Timestamp), int64((event.Timestamp-float64(int64(event.Timestamp)))*1e9)),
		MatchID:   matchID,
		ServerID:  event.ServerID,
		MapName:   event.MapName,
		EventType: string(event.Type),
		RawJSON:   rawJSON,
	}

	// Set actor/target based on event type
	switch event.Type {
	case models.EventKill, models.EventHeadshot:
		ch.ActorID = event.AttackerGUID
		ch.ActorName = sanitizeName(event.AttackerName)
		ch.ActorTeam = event.AttackerTeam
		ch.ActorWeapon = event.Weapon
		ch.ActorPosX = event.AttackerX
		ch.ActorPosY = event.AttackerY
		ch.ActorPosZ = event.AttackerZ
		ch.ActorPitch = event.AttackerPitch
		ch.ActorYaw = event.AttackerYaw

		ch.TargetID = event.VictimGUID
		ch.TargetName = sanitizeName(event.VictimName)
		ch.TargetTeam = event.VictimTeam
		ch.TargetPosX = event.VictimX
		ch.TargetPosY = event.VictimY
		ch.TargetPosZ = event.VictimZ

		ch.Hitloc = event.Hitloc

	case models.EventDamage:
		ch.ActorID = event.AttackerGUID
		ch.ActorName = sanitizeName(event.AttackerName)
		ch.ActorWeapon = event.Weapon
		ch.TargetID = event.VictimGUID
		ch.TargetName = sanitizeName(event.VictimName)
		ch.Damage = uint32(event.Damage)

	case models.EventWeaponFire:
		ch.ActorID = event.PlayerGUID
		ch.ActorName = sanitizeName(event.PlayerName)
		ch.ActorWeapon = event.Weapon
		ch.ActorPosX = event.PosX
		ch.ActorPosY = event.PosY
		ch.ActorPosZ = event.PosZ
		ch.ActorPitch = event.AimPitch
		ch.ActorYaw = event.AimYaw

	case models.EventWeaponHit:
		ch.ActorID = event.PlayerGUID
		ch.ActorName = sanitizeName(event.PlayerName)
		ch.TargetID = event.TargetGUID
		ch.TargetName = sanitizeName(event.TargetName)
		ch.Hitloc = event.Hitloc

	case models.EventMatchOutcome:
		ch.ActorID = event.PlayerGUID
		ch.ActorName = sanitizeName(event.PlayerName)
		ch.ActorTeam = event.PlayerTeam
		// Use Damage column for Win/Loss flag (1=Win, 0=Loss)
		ch.Damage = uint32(event.Count)
		// Use ActorWeapon column for Gametype storage
		ch.ActorWeapon = event.Gametype

	default:
		// Generic player event
		ch.ActorID = event.PlayerGUID
		ch.ActorName = sanitizeName(event.PlayerName)
		ch.ActorTeam = event.PlayerTeam
		ch.ActorPosX = event.PosX
		ch.ActorPosY = event.PosY
		ch.ActorPosZ = event.PosZ
	}

	return ch
}

// processEventSideEffects handles real-time updates (Redis, achievements)
func (p *Pool) processEventSideEffects(ctx context.Context, event *models.RawEvent) {
	switch event.Type {
	case models.EventMatchStart:
		p.handleMatchStart(ctx, event)
	case models.EventMatchEnd:
		p.handleMatchEnd(ctx, event)
	case models.EventHeartbeat:
		p.handleHeartbeat(ctx, event)
	case models.EventKill:
		p.handleKill(ctx, event)
	case models.EventHeadshot:
		p.handleHeadshot(ctx, event)
	case models.EventConnect:
		p.handleConnect(ctx, event)
	case models.EventDisconnect:
		p.handleDisconnect(ctx, event)
	case models.EventChat:
		p.handleChat(ctx, event)
	case models.EventTeamChange:
		p.handleTeamChange(ctx, event)
	case models.EventSpawn:
		p.handleSpawn(ctx, event)
	case models.EventTeamWin:
		p.handleTeamWin(ctx, event)
	}
}

// handleMatchStart creates live match state in Redis
func (p *Pool) handleMatchStart(ctx context.Context, event *models.RawEvent) {
	liveMatch := models.LiveMatch{
		MatchID:     event.MatchID,
		ServerID:    event.ServerID,
		MapName:     event.MapName,
		Gametype:    event.Gametype,
		StartedAt:   time.Now(),
		RoundNumber: 1,
	}

	data, _ := json.Marshal(liveMatch)
	p.config.Redis.HSet(ctx, "live_matches", event.MatchID, data)
	p.config.Redis.SAdd(ctx, "active_match_ids", event.MatchID)
	
	// Clear any stale team data for this match
	p.config.Redis.Del(ctx, "match:"+event.MatchID+":teams")
}

// handleMatchEnd removes from live matches, triggers tournament advancement
func (p *Pool) handleMatchEnd(ctx context.Context, event *models.RawEvent) {
	// Retrieve winning team from live match cache if not in event
	winningTeam := event.WinningTeam
	if winningTeam == "" {
		data, err := p.config.Redis.HGet(ctx, "live_matches", event.MatchID).Bytes()
		if err == nil {
			var liveMatch models.LiveMatch
			if err := json.Unmarshal(data, &liveMatch); err == nil {
				// We might store winning team in liveMatch structure if we update it on team_win
				// But for now, let's assume event.WinningTeam is populated or we rely on team_win event
			}
		}
	}

	// Synthesize Match Outcome Events
	// Get all players and their teams
	teams, err := p.config.Redis.HGetAll(ctx, "match:"+event.MatchID+":teams").Result()
	if err == nil {
		// Get Gametype from LiveMatch to pass to event
		var gametype string
		if data, err := p.config.Redis.HGet(ctx, "live_matches", event.MatchID).Bytes(); err == nil {
			var lm models.LiveMatch
			if json.Unmarshal(data, &lm) == nil {
				gametype = lm.Gametype
			}
		}

		for guid, team := range teams {
			outcome := 0 // Loss
			if team == winningTeam {
				outcome = 1 // Win
			}

			// Create Outcome Event
			go func(playerGUID, playerTeam string, won int, gType string) {
				outcomeEvent := &models.RawEvent{
					Type:       models.EventMatchOutcome,
					MatchID:    event.MatchID,
					ServerID:   event.ServerID,
					MapName:    event.MapName,
					Timestamp:  float64(time.Now().Unix()),
					PlayerGUID: playerGUID,
					PlayerTeam: playerTeam,
					Gametype:   gType,
					// Re-using 'Count' for Won/Lost (1/0)
					Count: won,
				}
				p.Enqueue(outcomeEvent)
			}(guid, team, outcome, gametype)
		}
	}

	p.config.Redis.HDel(ctx, "live_matches", event.MatchID)
	p.config.Redis.SRem(ctx, "active_match_ids", event.MatchID)
	// Cleanup team data
	p.config.Redis.Del(ctx, "match:"+event.MatchID+":teams")
	p.config.Redis.Del(ctx, "match:"+event.MatchID+":players")

	// TODO: Check if this match is part of a tournament and trigger bracket advancement
}

// handleTeamWin records the winner in Redis so match_end can pick it up
func (p *Pool) handleTeamWin(ctx context.Context, event *models.RawEvent) {
	// Update live match with winner
	// We need to extend LiveMatch struct or just store it in a side key
	// distinct key for winner?
	p.config.Redis.HSet(ctx, "match:"+event.MatchID+":winner", "team", event.WinningTeam)
}

// handleTeamChange updates player team in Redis
func (p *Pool) handleTeamChange(ctx context.Context, event *models.RawEvent) {
	if event.PlayerGUID == "" || event.NewTeam == "" {
		return
	}
	p.config.Redis.HSet(ctx, "match:"+event.MatchID+":teams", event.PlayerGUID, event.NewTeam)
}

// handleSpawn also ensures team is set (backup for team_change)
func (p *Pool) handleSpawn(ctx context.Context, event *models.RawEvent) {
	if event.PlayerGUID == "" || event.PlayerTeam == "" {
		return
	}
	p.config.Redis.HSet(ctx, "match:"+event.MatchID+":teams", event.PlayerGUID, event.PlayerTeam)
}

// handleHeartbeat updates live match state
func (p *Pool) handleHeartbeat(ctx context.Context, event *models.RawEvent) {
	data, err := p.config.Redis.HGet(ctx, "live_matches", event.MatchID).Bytes()
	if err != nil {
		return
	}

	var liveMatch models.LiveMatch
	json.Unmarshal(data, &liveMatch)

	liveMatch.AlliesScore = event.AlliesScore
	liveMatch.AxisScore = event.AxisScore
	liveMatch.PlayerCount = event.PlayerCount
	liveMatch.RoundNumber = event.RoundNumber

	newData, _ := json.Marshal(liveMatch)
	p.config.Redis.HSet(ctx, "live_matches", event.MatchID, newData)
}

// handleKill increments kill counters for achievements
func (p *Pool) handleKill(ctx context.Context, event *models.RawEvent) {
	if event.AttackerGUID == "" || event.AttackerGUID == "world" {
		return
	}

	// Increment kill counter
	key := "player:" + event.AttackerGUID + ":kills"
	newCount, _ := p.config.Redis.Incr(ctx, key).Result()

	// Check achievement thresholds
	p.checkKillAchievements(ctx, event.AttackerGUID, newCount)
}

// handleHeadshot increments headshot counters
func (p *Pool) handleHeadshot(ctx context.Context, event *models.RawEvent) {
	if event.PlayerGUID == "" {
		return
	}

	key := "player:" + event.PlayerGUID + ":headshots"
	newCount, _ := p.config.Redis.Incr(ctx, key).Result()

	p.checkHeadshotAchievements(ctx, event.PlayerGUID, newCount)
}

// handleConnect updates player alias tracking
func (p *Pool) handleConnect(ctx context.Context, event *models.RawEvent) {
	if event.PlayerGUID == "" {
		return
	}

	// Update last known name
	p.config.Redis.HSet(ctx, "player_names", event.PlayerGUID, event.PlayerName)

	// Track player online status
	p.config.Redis.SAdd(ctx, "match:"+event.MatchID+":players", event.PlayerGUID)
}

// handleDisconnect updates player state
func (p *Pool) handleDisconnect(ctx context.Context, event *models.RawEvent) {
	if event.PlayerGUID == "" {
		return
	}

	p.config.Redis.SRem(ctx, "match:"+event.MatchID+":players", event.PlayerGUID)
}

// handleChat checks for claim codes
func (p *Pool) handleChat(ctx context.Context, event *models.RawEvent) {
	// Check if message is a claim code (format: !claim MOH-XXXX)
	// This would trigger identity verification
	// TODO: Implement claim code detection and verification
}

// checkKillAchievements checks kill-based achievements
func (p *Pool) checkKillAchievements(ctx context.Context, playerGUID string, killCount int64) {
	thresholds := map[int64]string{
		100:   "KILL_100",
		500:   "KILL_500",
		1000:  "KILL_1000",
		5000:  "KILL_5000",
		10000: "KILL_10000",
	}

	if achievementID, ok := thresholds[killCount]; ok {
		p.grantAchievement(ctx, playerGUID, achievementID)
	}
}

// checkHeadshotAchievements checks headshot-based achievements
func (p *Pool) checkHeadshotAchievements(ctx context.Context, playerGUID string, count int64) {
	thresholds := map[int64]string{
		50:   "HEADSHOT_50",
		100:  "HEADSHOT_100",
		500:  "HEADSHOT_500",
		1000: "HEADSHOT_1000",
	}

	if achievementID, ok := thresholds[count]; ok {
		p.grantAchievement(ctx, playerGUID, achievementID)
	}
}

// grantAchievement grants an achievement to a player
func (p *Pool) grantAchievement(ctx context.Context, playerGUID, achievementID string) {
	// Check if already unlocked
	key := "player:" + playerGUID + ":achievements"
	if p.config.Redis.SIsMember(ctx, key, achievementID).Val() {
		return
	}

	// Mark as unlocked
	p.config.Redis.SAdd(ctx, key, achievementID)

	// Insert into Postgres
	_, err := p.config.Postgres.Exec(ctx, `
		INSERT INTO player_achievements (player_guid, achievement_id, unlocked_at)
		VALUES ($1, $2, $3)
		ON CONFLICT (player_guid, achievement_id) DO NOTHING
	`, playerGUID, achievementID, time.Now())

	if err != nil {
		p.logger.Warnw("Failed to grant achievement", "player", playerGUID, "achievement", achievementID, "error", err)
	} else {
		p.logger.Infow("Achievement unlocked", "player", playerGUID, "achievement", achievementID)
	}
}

func (p *Pool) reportQueueDepth() {
	ticker := time.NewTicker(5 * time.Second)
	defer ticker.Stop()

	for {
		select {
		case <-ticker.C:
			queueDepth.Set(float64(len(p.jobQueue)))
		case <-p.ctx.Done():
			return
		}
	}
}

// Helper functions

var colorCodeRegex = regexp.MustCompile(`\^[0-9]`)

func sanitizeName(name string) string {
	// Remove MOHAA color codes (^1, ^2, etc.)
	return colorCodeRegex.ReplaceAllString(name, "")
}

func parseOrGenerateUUID(s string) uuid.UUID {
	if id, err := uuid.Parse(s); err == nil {
		return id
	}
	// Generate deterministic UUID from string
	return uuid.NewSHA1(uuid.NameSpaceURL, []byte(s))
}

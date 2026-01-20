package handlers

import (
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"strconv"
	"strings"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/go-chi/chi/v5"
	"github.com/golang-jwt/jwt/v5"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/openmohaa/stats-api/internal/logic"
	"github.com/openmohaa/stats-api/internal/models"
	"github.com/openmohaa/stats-api/internal/worker"
)

type Config struct {
	WorkerPool *worker.Pool
	Postgres   *pgxpool.Pool
	ClickHouse driver.Conn
	Redis      *redis.Client
	Logger     *zap.Logger
	JWTSecret  string
}

type Handler struct {
	pool          *worker.Pool
	pg            *pgxpool.Pool
	ch            driver.Conn
	redis         *redis.Client
	logger        *zap.SugaredLogger
	playerStats   *logic.PlayerStatsService
	serverStats   *logic.ServerStatsService
	gamification  *logic.GamificationService
	matchReport   *logic.MatchReportService
	advancedStats *logic.AdvancedStatsService
	jwtSecret     []byte
}

func New(cfg Config) *Handler {
	return &Handler{
		pool:          cfg.WorkerPool,
		pg:            cfg.Postgres,
		ch:            cfg.ClickHouse,
		redis:         cfg.Redis,
		logger:        cfg.Logger.Sugar(),
		playerStats:   logic.NewPlayerStatsService(cfg.ClickHouse),
		serverStats:   logic.NewServerStatsService(cfg.ClickHouse),
		gamification:  logic.NewGamificationService(cfg.ClickHouse),
		matchReport:   logic.NewMatchReportService(cfg.ClickHouse),
		advancedStats: logic.NewAdvancedStatsService(cfg.ClickHouse),
		jwtSecret:     []byte(cfg.JWTSecret),
	}
}

// ============================================================================
// HEALTH ENDPOINTS
// ============================================================================

func (h *Handler) Health(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"status":    "ok",
		"timestamp": time.Now().UTC(),
	})
}

func (h *Handler) Ready(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	// Check all dependencies
	checks := map[string]bool{
		"postgres":   h.pg.Ping(ctx) == nil,
		"clickhouse": h.ch.Ping(ctx) == nil,
		"redis":      h.redis.Ping(ctx).Err() == nil,
	}

	allHealthy := true
	for _, ok := range checks {
		if !ok {
			allHealthy = false
			break
		}
	}

	w.Header().Set("Content-Type", "application/json")
	if !allHealthy {
		w.WriteHeader(http.StatusServiceUnavailable)
	}
	json.NewEncoder(w).Encode(map[string]interface{}{
		"ready":      allHealthy,
		"checks":     checks,
		"queueDepth": h.pool.QueueDepth(),
	})
}

// ============================================================================
// INGESTION ENDPOINTS
// ============================================================================

// IngestEvents handles POST /api/v1/ingest/events
// Accepts URL-encoded or JSON events from game servers
func (h *Handler) IngestEvents(w http.ResponseWriter, r *http.Request) {
	var event models.RawEvent

	contentType := r.Header.Get("Content-Type")

	if strings.Contains(contentType, "application/json") {
		if err := json.NewDecoder(r.Body).Decode(&event); err != nil {
			h.errorResponse(w, http.StatusBadRequest, "Invalid JSON: "+err.Error())
			return
		}
	} else {
		// URL-encoded (from curl_post in Morpheus script)
		if err := r.ParseForm(); err != nil {
			h.errorResponse(w, http.StatusBadRequest, "Invalid form data")
			return
		}
		event = h.parseFormToEvent(r.Form)
	}

	// Validate required fields
	if event.Type == "" {
		h.errorResponse(w, http.StatusBadRequest, "Missing event type")
		return
	}

	// Enqueue for async processing
	if !h.pool.Enqueue(&event) {
		// Queue full - load shedding
		h.errorResponse(w, http.StatusTooManyRequests, "System overloaded, retry later")
		return
	}

	// Return 202 Accepted immediately
	w.WriteHeader(http.StatusAccepted)
	json.NewEncoder(w).Encode(map[string]string{
		"status": "accepted",
		"type":   string(event.Type),
	})
}

// parseFormToEvent converts URL-encoded form data to RawEvent
func (h *Handler) parseFormToEvent(form url.Values) models.RawEvent {
	event := models.RawEvent{
		Type:        models.EventType(form.Get("type")),
		MatchID:     form.Get("match_id"),
		SessionID:   form.Get("session_id"),
		ServerID:    form.Get("server_id"),
		ServerToken: form.Get("server_token"),
		MapName:     form.Get("map_name"),

		PlayerName: form.Get("player_name"),
		PlayerGUID: form.Get("player_guid"),
		PlayerTeam: form.Get("player_team"),

		AttackerName: form.Get("attacker_name"),
		AttackerGUID: form.Get("attacker_guid"),
		AttackerTeam: form.Get("attacker_team"),

		VictimName: form.Get("victim_name"),
		VictimGUID: form.Get("victim_guid"),
		VictimTeam: form.Get("victim_team"),

		Weapon:    form.Get("weapon"),
		OldWeapon: form.Get("old_weapon"),
		NewWeapon: form.Get("new_weapon"),
		Hitloc:    form.Get("hitloc"),
		Inflictor: form.Get("inflictor"),

		TargetName: form.Get("target_name"),
		TargetGUID: form.Get("target_guid"),

		OldTeam: form.Get("old_team"),
		NewTeam: form.Get("new_team"),
		Message: form.Get("message"),

		Gametype:    form.Get("gametype"),
		Timelimit:   form.Get("timelimit"),
		Fraglimit:   form.Get("fraglimit"),
		Maxclients:  form.Get("maxclients"),
		WinningTeam: form.Get("winning_team"),

		Item:       form.Get("item"),
		Entity:     form.Get("entity"),
		Projectile: form.Get("projectile"),
		Code:       form.Get("code"),
	}

	// Parse numeric fields
	event.Timestamp, _ = strconv.ParseFloat(form.Get("timestamp"), 64)
	event.Damage, _ = strconv.Atoi(form.Get("damage"))
	event.AmmoRemaining, _ = strconv.Atoi(form.Get("ammo_remaining"))
	event.AlliesScore, _ = strconv.Atoi(form.Get("allies_score"))
	event.AxisScore, _ = strconv.Atoi(form.Get("axis_score"))
	event.RoundNumber, _ = strconv.Atoi(form.Get("round_number"))
	event.TotalRounds, _ = strconv.Atoi(form.Get("total_rounds"))
	event.PlayerCount, _ = strconv.Atoi(form.Get("player_count"))
	event.ClientNum, _ = strconv.Atoi(form.Get("client_num"))
	event.Count, _ = strconv.Atoi(form.Get("count"))
	event.Duration, _ = strconv.ParseFloat(form.Get("duration"), 64)

	// Parse float fields (positions)
	event.PosX = parseFloat32(form.Get("pos_x"))
	event.PosY = parseFloat32(form.Get("pos_y"))
	event.PosZ = parseFloat32(form.Get("pos_z"))
	event.AttackerX = parseFloat32(form.Get("attacker_x"))
	event.AttackerY = parseFloat32(form.Get("attacker_y"))
	event.AttackerZ = parseFloat32(form.Get("attacker_z"))
	event.AttackerPitch = parseFloat32(form.Get("attacker_pitch"))
	event.AttackerYaw = parseFloat32(form.Get("attacker_yaw"))
	event.VictimX = parseFloat32(form.Get("victim_x"))
	event.VictimY = parseFloat32(form.Get("victim_y"))
	event.VictimZ = parseFloat32(form.Get("victim_z"))
	event.AimPitch = parseFloat32(form.Get("aim_pitch"))
	event.AimYaw = parseFloat32(form.Get("aim_yaw"))
	event.FallHeight = parseFloat32(form.Get("fall_height"))
	event.Walked = parseFloat32(form.Get("walked"))
	event.Sprinted = parseFloat32(form.Get("sprinted"))
	event.Swam = parseFloat32(form.Get("swam"))
	event.Driven = parseFloat32(form.Get("driven"))

	return event
}

func parseFloat32(s string) float32 {
	f, _ := strconv.ParseFloat(s, 32)
	return float32(f)
}

// IngestMatchResult handles POST /api/v1/ingest/match-result
// Synchronous processing for tournament integration
func (h *Handler) IngestMatchResult(w http.ResponseWriter, r *http.Request) {
	var result models.MatchResult

	if err := json.NewDecoder(r.Body).Decode(&result); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	// TODO: Process match result synchronously for tournament brackets
	// - Verify match token if tournament match
	// - Update bracket advancement
	// - Calculate final player stats for match

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{
		"status": "processed",
	})
}

// ============================================================================
// STATS ENDPOINTS
// ============================================================================

// GetGlobalStats returns aggregate statistics for the dashboard
func (h *Handler) GetGlobalStats(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	// Query aggregations
	// Note: In a real prod env, we'd cache this heavily
	var totalKills, totalMatches, activePlayers uint64

	// Total Kills
	if err := h.ch.QueryRow(ctx, "SELECT count() FROM raw_events WHERE event_type = 'kill'").Scan(&totalKills); err != nil {
		h.logger.Errorw("Failed to get total kills", "error", err)
	}

	// Total Matches (unique match_ids)
	if err := h.ch.QueryRow(ctx, "SELECT uniq(match_id) FROM raw_events").Scan(&totalMatches); err != nil {
		h.logger.Errorw("Failed to get total matches", "error", err)
	}

	// Active Players (last 24h)
	if err := h.ch.QueryRow(ctx, "SELECT uniq(actor_id) FROM raw_events WHERE timestamp >= now() - INTERVAL 24 HOUR AND actor_id != ''").Scan(&activePlayers); err != nil {
		h.logger.Errorw("Failed to get active players", "error", err)
	}

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"total_kills":        totalKills,
		"total_matches":      totalMatches,
		"active_players_24h": activePlayers,
		"server_count":       1, // Placeholder until server registry is fully linked
	})
}

// GetMatches returns a list of recent matches
func (h *Handler) GetMatches(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()
	limit := 20
	offset := 0

	if l := r.URL.Query().Get("limit"); l != "" {
		if v, err := strconv.Atoi(l); err == nil && v > 0 {
			limit = v
		}
	}
	if o := r.URL.Query().Get("offset"); o != "" {
		if v, err := strconv.Atoi(o); err == nil && v >= 0 {
			offset = v
		}
	}

	// Fetch matches
	rows, err := h.ch.Query(ctx, `
		SELECT 
			match_id,
			map_name,
			min(timestamp) as start_time,
			dateDiff('second', min(timestamp), max(timestamp)) as duration,
			uniq(actor_id) as player_count,
			countIf(event_type = 'kill') as kills
		FROM raw_events
		GROUP BY match_id, map_name
		ORDER BY start_time DESC
		LIMIT ? OFFSET ?
	`, limit, offset)

	if err != nil {
		h.logger.Errorw("Failed to fetch matches", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	type MatchSummary struct {
		ID          string    `json:"id"`
		Map         string    `json:"map"`
		StartTime   time.Time `json:"start_time"`
		Duration    int       `json:"duration"`
		PlayerCount uint64    `json:"player_count"`
		Kills       uint64    `json:"kills"`
	}

	matches := make([]MatchSummary, 0)
	for rows.Next() {
		var m MatchSummary
		if err := rows.Scan(&m.ID, &m.Map, &m.StartTime, &m.Duration, &m.PlayerCount, &m.Kills); err != nil {
			continue
		}
		matches = append(matches, m)
	}

	h.jsonResponse(w, http.StatusOK, matches)
}

// GetGlobalWeaponStats returns weapon usage statistics
func (h *Handler) GetGlobalWeaponStats(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	rows, err := h.ch.Query(ctx, `
		SELECT 
			extract(extra, 'weapon') as weapon,
			countIf(event_type = 'kill') as kills,
			countIf(event_type = 'headshot') as headshots
		FROM raw_events
		WHERE weapon != '' 
		GROUP BY weapon
		ORDER BY kills DESC
		LIMIT 10
	`)
	if err != nil {
		h.logger.Errorw("Failed to query weapon stats", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	type WeaponStats struct {
		Name      string `json:"name"`
		Kills     uint64 `json:"kills"`
		Headshots uint64 `json:"headshots"`
	}

	stats := make([]WeaponStats, 0)
	for rows.Next() {
		var s WeaponStats
		if err := rows.Scan(&s.Name, &s.Kills, &s.Headshots); err != nil {
			continue
		}
		stats = append(stats, s)
	}

	h.jsonResponse(w, http.StatusOK, stats)
}

// GetLeaderboard returns rankings based on various criteria
func (h *Handler) GetLeaderboard(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	// Parameters
	period := r.URL.Query().Get("period")
	stat := r.URL.Query().Get("stat")
	limit := 25
	page := 1

	if l := r.URL.Query().Get("limit"); l != "" {
		if parsed, err := strconv.Atoi(l); err == nil && parsed > 0 && parsed <= 100 {
			limit = parsed
		}
	}
	if p := r.URL.Query().Get("page"); p != "" {
		if parsed, err := strconv.Atoi(p); err == nil && parsed > 0 {
			page = parsed
		}
	}
	offset := (page - 1) * limit

	// Build Query
	var timeFilter string
	switch period {
	case "month":
		timeFilter = "AND timestamp >= now() - INTERVAL 30 DAY"
	case "week":
		timeFilter = "AND timestamp >= now() - INTERVAL 7 DAY"
	case "day":
		timeFilter = "AND timestamp >= now() - INTERVAL 24 HOUR"
	default: // "all"
		timeFilter = ""
	}

	// Determine sort column
	orderBy := "kills"
	switch stat {
	case "deaths":
		orderBy = "deaths"
	case "headshots":
		orderBy = "headshots"
	case "accuracy":
		orderBy = "accuracy"
	case "playtime":
		orderBy = "playtime"
	case "kd":
		orderBy = "kd"
	case "wins":
		orderBy = "wins"
	case "rounds":
		orderBy = "rounds"
	case "objectives":
		orderBy = "objectives"
	case "suicides":
		orderBy = "suicides"
	case "teamkills":
		orderBy = "teamkills"
	case "roadkills":
		orderBy = "roadkills"
	case "bash_kills":
		orderBy = "bash_kills"
	case "grenades":
		orderBy = "grenades"
	case "damage":
		orderBy = "damage"
	case "distance":
		orderBy = "distance"
	case "jumps":
		orderBy = "jumps"
	default:
		orderBy = "kills"
	}

	// MEGA Stats Query - aggregates ALL event types
	query := fmt.Sprintf(`
		SELECT 
			player_id,
			anyLast(name) as name,
			sum(k) as kills,
			sum(d) as deaths,
			sum(hs) as headshots,
			sum(sf) as shots_fired,
			sum(sh) as shots_hit,
			sum(pt) as playtime,
			sum(suicide) as suicides,
			sum(tk) as teamkills,
			sum(roadkill) as roadkills,
			sum(bash) as bash_kills,
			sum(nade) as grenades,
			sum(dmg) as damage,
			sum(dist) as distance,
			sum(jmp) as jumps,
			sum(win) as wins,
			sum(loss) as losses,
			sum(rnd) as rounds,
			sum(obj) as objectives,
			if(deaths > 0, kills/deaths, kills) as kd,
			if(shots_fired > 0, (shots_hit/shots_fired)*100, 0) as accuracy
		FROM (
			-- Kills
			SELECT actor_id as player_id, actor_name as name, 1 as k, 0 as d, 0 as hs, 0 as sf, 0 as sh, 0 as pt, 0 as suicide, 0 as tk, 0 as roadkill, 0 as bash, 0 as nade, 0 as dmg, 0.0 as dist, 0 as jmp, 0 as win, 0 as loss, 0 as rnd, 0 as obj
			FROM raw_events WHERE event_type='player_kill' AND actor_id != 'world' AND actor_id != '' %s
			
			UNION ALL
			
			-- Deaths
			SELECT victim_id as player_id, victim_name as name, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type IN ('player_kill', 'player_death') AND victim_id != '' %s
			
			UNION ALL
			
			-- Headshots
			SELECT actor_id as player_id, actor_name as name, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='player_headshot' AND actor_id != '' %s
			
			UNION ALL
			
			-- Weapon Fire
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='weapon_fire' AND actor_id != '' %s
			
			UNION ALL
			
			-- Weapon Hit
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='weapon_hit' AND actor_id != '' %s

			UNION ALL
			
			-- Session End (playtime)
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, toInt64OrZero(JSONExtractString(extra, 'duration')), 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='client_disconnect' AND actor_id != '' %s
			
			UNION ALL
			
			-- Suicides
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='player_suicide' AND actor_id != '' %s
			
			UNION ALL
			
			-- Team Kills
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='player_teamkill' AND actor_id != '' %s
			
			UNION ALL
			
			-- Roadkills
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='player_roadkill' AND actor_id != '' %s
			
			UNION ALL
			
			-- Bash Kills
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='player_bash' AND actor_id != '' %s
			
			UNION ALL
			
			-- Grenades
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='grenade_throw' AND actor_id != '' %s
			
			UNION ALL
			
			-- Damage
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, toInt64OrZero(JSONExtractString(extra, 'damage')), 0.0, 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='player_damage' AND actor_id != '' %s
			
			UNION ALL
			
			-- Distance
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, toFloat64OrZero(JSONExtractString(extra, 'walked')) + toFloat64OrZero(JSONExtractString(extra, 'sprinted')), 0, 0, 0, 0, 0
			FROM raw_events WHERE event_type='player_distance' AND actor_id != '' %s
			
			UNION ALL
			
			-- Jumps
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 1, 0, 0, 0, 0
			FROM raw_events WHERE event_type='player_jump' AND actor_id != '' %s
			
			UNION ALL
			
			-- Wins (team_win where player was on winning team)
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 1, 0, 0, 0
			FROM raw_events WHERE event_type='team_win' AND actor_id != '' %s
			
			UNION ALL
			
			-- Rounds 
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 1, 0
			FROM raw_events WHERE event_type='round_end' AND actor_id != '' %s
			
			UNION ALL
			
			-- Objectives
			SELECT actor_id as player_id, actor_name as name, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 1
			FROM raw_events WHERE event_type='objective_update' AND actor_id != '' %s
		)
		GROUP BY player_id
		HAVING kills > 0 OR deaths > 0 OR playtime > 0
		ORDER BY %s DESC
		LIMIT ? OFFSET ?
	`, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, timeFilter, orderBy)

	rows, err := h.ch.Query(ctx, query, limit, offset)
	if err != nil {
		h.logger.Errorw("Failed to query leaderboard", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	entries := make([]models.LeaderboardEntry, 0)
	rank := offset + 1
	for rows.Next() {
		var entry models.LeaderboardEntry
		var kd, accuracy float64

		if err := rows.Scan(
			&entry.PlayerID,
			&entry.PlayerName,
			&entry.Kills,
			&entry.Deaths,
			&entry.Headshots,
			&entry.ShotsFired,
			&entry.ShotsHit,
			&entry.Playtime,
			&entry.Suicides,
			&entry.TeamKills,
			&entry.Roadkills,
			&entry.BashKills,
			&entry.Grenades,
			&entry.Damage,
			&entry.Distance,
			&entry.Jumps,
			&entry.Wins,
			&entry.Losses,
			&entry.Rounds,
			&entry.Objectives,
			&kd,
			&accuracy,
		); err != nil {
			h.logger.Warnw("Failed to scan leaderboard row", "error", err)
			continue
		}
		entry.Rank = rank
		entry.Accuracy = accuracy

		entries = append(entries, entry)
		rank++
	}

	// Note: Total count query omitted for speed
	response := map[string]interface{}{
		"players": entries,
		"total":   1000, // Placeholder - would need separate count query
		"page":    page,
	}

	h.jsonResponse(w, http.StatusOK, response)
}

// GetWeeklyLeaderboard returns weekly stats
func (h *Handler) GetWeeklyLeaderboard(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	rows, err := h.ch.Query(ctx, `
		SELECT 
			actor_id,
			actor_name,
			count() as kills
		FROM raw_events
		WHERE event_type = 'kill' 
		  AND actor_id != 'world'
		  AND timestamp >= now() - INTERVAL 7 DAY
		GROUP BY actor_id, actor_name
		ORDER BY kills DESC
		LIMIT 100
	`)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	var entries []models.LeaderboardEntry
	rank := 1
	for rows.Next() {
		var entry models.LeaderboardEntry
		var name string
		if err := rows.Scan(&entry.PlayerID, &name, &entry.Kills); err != nil {
			continue
		}
		entry.Rank = rank
		entry.PlayerName = name
		entries = append(entries, entry)
		rank++
	}

	h.jsonResponse(w, http.StatusOK, entries)
}

// GetWeaponLeaderboard returns top players for a specific weapon
func (h *Handler) GetWeaponLeaderboard(w http.ResponseWriter, r *http.Request) {
	weapon := chi.URLParam(r, "weapon")
	ctx := r.Context()

	rows, err := h.ch.Query(ctx, `
		SELECT 
			actor_id,
			actor_name,
			count() as kills
		FROM raw_events
		WHERE event_type = 'kill' 
		  AND actor_weapon = ?
		  AND actor_id != 'world'
		GROUP BY actor_id, actor_name
		ORDER BY kills DESC
		LIMIT 100
	`, weapon)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	var entries []models.LeaderboardEntry
	rank := 1
	for rows.Next() {
		var entry models.LeaderboardEntry
		var name string
		if err := rows.Scan(&entry.PlayerID, &name, &entry.Kills); err != nil {
			continue
		}
		entry.Rank = rank
		entry.PlayerName = name
		entries = append(entries, entry)
		rank++
	}

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"weapon":      weapon,
		"leaderboard": entries,
	})
}

// GetMapLeaderboard returns top players on a specific map
func (h *Handler) GetMapLeaderboard(w http.ResponseWriter, r *http.Request) {
	mapName := chi.URLParam(r, "map")
	ctx := r.Context()

	rows, err := h.ch.Query(ctx, `
		SELECT 
			actor_id,
			actor_name,
			count() as kills
		FROM raw_events
		WHERE event_type = 'kill' 
		  AND map_name = ?
		  AND actor_id != 'world'
		GROUP BY actor_id, actor_name
		ORDER BY kills DESC
		LIMIT 100
	`, mapName)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	var entries []models.LeaderboardEntry
	rank := 1
	for rows.Next() {
		var entry models.LeaderboardEntry
		var name string
		if err := rows.Scan(&entry.PlayerID, &name, &entry.Kills); err != nil {
			continue
		}
		entry.Rank = rank
		entry.PlayerName = name
		entries = append(entries, entry)
		rank++
	}

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"map":         mapName,
		"leaderboard": entries,
	})
}

// GetPlayerStats returns comprehensive stats for a player
func (h *Handler) GetPlayerStats(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	var stats models.PlayerStats
	stats.PlayerID = guid

	// Get aggregated stats from ClickHouse
	row := h.ch.QueryRow(ctx, `
		SELECT
			countIf(event_type = 'kill' AND actor_id = ?) as kills,
			countIf(event_type = 'death' AND actor_id = ?) as deaths,
			countIf(event_type = 'headshot' AND actor_id = ?) as headshots,
			countIf(event_type = 'weapon_fire' AND actor_id = ?) as shots_fired,
			countIf(event_type = 'weapon_hit' AND actor_id = ?) as shots_hit,
			sumIf(damage, actor_id = ?) as total_damage,
			uniq(match_id) as matches_played,
			max(timestamp) as last_active,
			any(actor_name) as name,
			
			-- Granular Combat Metrics
			countIf(event_type = 'kill' AND actor_id = ? AND distance > 100) as long_range_kills,
			countIf(event_type = 'kill' AND actor_id = ? AND distance < 5) as close_range_kills,
			countIf(event_type = 'kill' AND actor_id = ? AND extra LIKE '%wallbang%') as wallbang_kills,
			countIf(event_type = 'kill' AND actor_id = ? AND extra LIKE '%collateral%') as collateral_kills,

			-- Stance Metrics (using extra or parsing if available, placeholder logic)
			countIf(event_type = 'kill' AND actor_id = ? AND extra LIKE '%prone%') as kills_while_prone,
			countIf(event_type = 'kill' AND actor_id = ? AND extra LIKE '%crouch%') as kills_while_crouching,
			countIf(event_type = 'kill' AND actor_id = ? AND extra LIKE '%stand%') as kills_while_standing,
			countIf(event_type = 'kill' AND actor_id = ? AND (abs(actor_pos_x - attacker_x) > 1 OR abs(actor_pos_y - attacker_y) > 1)) as kills_while_moving,
			countIf(event_type = 'kill' AND actor_id = ? AND (abs(actor_pos_x - attacker_x) <= 1 AND abs(actor_pos_y - attacker_y) <= 1)) as kills_while_stationary,

			-- Movement Metrics
			sumIf(distance, event_type = 'distance' AND actor_id = ?) / 1000.0 as total_distance_km, -- assuming distance is in meters or units
			sumIf(distance, event_type = 'distance' AND actor_id = ? AND extra LIKE '%sprint%') / 1000.0 as sprint_distance_km,
			countIf(event_type = 'jump' AND actor_id = ?) as jump_count,
			sumIf(duration, event_type = 'crouch' AND actor_id = ?) as crouch_time_seconds,
			sumIf(duration, event_type = 'prone' AND actor_id = ?) as prone_time_seconds
		FROM raw_events
		WHERE actor_id = ?
	`, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid, guid)

	err := row.Scan(
		&stats.TotalKills,
		&stats.TotalDeaths,
		&stats.TotalHeadshots,
		&stats.ShotsFired,
		&stats.ShotsHit,
		&stats.TotalDamage,
		&stats.MatchesPlayed,
		&stats.LastActive,
		&stats.PlayerName,
		// New granular metrics
		&stats.LongRangeKills,
		&stats.CloseRangeKills,
		&stats.WallbangKills,
		&stats.CollateralKills,
		&stats.KillsWhileProne,
		&stats.KillsWhileCrouching,
		&stats.KillsWhileStanding,
		&stats.KillsWhileMoving,
		&stats.KillsWhileStationary,
		&stats.TotalDistance,
		&stats.SprintDistance,
		&stats.JumpCount,
		&stats.CrouchTime,
		&stats.ProneTime,
	)
	if err != nil {
		h.logger.Errorw("Failed to query player stats", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}

	// Calculate derived stats
	if stats.TotalDeaths > 0 {
		stats.KDRatio = float64(stats.TotalKills) / float64(stats.TotalDeaths)
	}
	if stats.ShotsFired > 0 {
		stats.Accuracy = float64(stats.ShotsHit) / float64(stats.ShotsFired) * 100
	}
	if stats.TotalKills > 0 {
		stats.HSPercent = float64(stats.TotalHeadshots) / float64(stats.TotalKills) * 100
	}

	h.jsonResponse(w, http.StatusOK, stats)
}

// GetPlayerAchievements returns player achievements
// Player achievement unlocks are tracked in SMF database (smf_mohaa_player_achievements)
// This endpoint could query player stats from ClickHouse if needed for batch checking
func (h *Handler) GetPlayerAchievements(w http.ResponseWriter, r *http.Request) {
	// Achievement definitions and unlocks are stored in SMF database
	// Go API provides player stats; SMF handles achievement logic
	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"message":      "Player achievements are managed in SMF database",
		"source":       "smf_database",
		"achievements": []interface{}{},
	})
}

// ListAchievements returns a message directing to SMF database
// Achievement definitions are stored in SMF MariaDB, not Go
func (h *Handler) ListAchievements(w http.ResponseWriter, r *http.Request) {
	h.jsonResponse(w, http.StatusOK, map[string]string{
		"message": "Achievement definitions are stored in SMF database (smf_mohaa_achievement_defs). Use the SMF forum to view achievements.",
		"source":  "smf_database",
	})
}

// GetAchievement returns a message directing to SMF database
func (h *Handler) GetAchievement(w http.ResponseWriter, r *http.Request) {
	h.jsonResponse(w, http.StatusOK, map[string]string{
		"message": "Achievement definitions are stored in SMF database. Use the SMF forum to view achievements.",
		"source":  "smf_database",
	})
}

// GetRecentAchievements returns a global feed of recent unlocks
func (h *Handler) GetRecentAchievements(w http.ResponseWriter, r *http.Request) {
	_ = r.Context()
	// Mock implementation until ClickHouse 'unlocks' table is ready
	// Retrieve recent 'achievement_unlocked' events from raw_events?
	// For now, return empty or mock

	// Real implementation would look like:
	/*
		rows, err := h.ch.Query(ctx, "SELECT ... FROM achievement_unlocks ORDER BY timestamp DESC LIMIT 50")
	*/

	h.jsonResponse(w, http.StatusOK, []interface{}{})
}

// GetAchievementLeaderboard returns players ranked by achievement points
func (h *Handler) GetAchievementLeaderboard(w http.ResponseWriter, r *http.Request) {
	_ = r.Context()
	// Mock implementation
	h.jsonResponse(w, http.StatusOK, []interface{}{})
}

// GetPlayerMatches returns recent matches for a player
func (h *Handler) GetPlayerMatches(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	rows, err := h.ch.Query(ctx, `
		SELECT 
			match_id,
			map_name,
			countIf(event_type = 'kill' AND actor_id = ?) as kills,
			countIf(event_type = 'death' AND actor_id = ?) as deaths,
			min(timestamp) as started,
			max(timestamp) as ended
		FROM raw_events
		WHERE match_id IN (
			SELECT DISTINCT match_id FROM raw_events WHERE actor_id = ?
		)
		GROUP BY match_id, map_name
		ORDER BY started DESC
		LIMIT 50
	`, guid, guid, guid)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	type MatchSummary struct {
		MatchID   string    `json:"match_id"`
		MapName   string    `json:"map_name"`
		Kills     uint64    `json:"kills"`
		Deaths    uint64    `json:"deaths"`
		StartedAt time.Time `json:"started_at"`
		EndedAt   time.Time `json:"ended_at"`
	}

	var matches []MatchSummary
	for rows.Next() {
		var m MatchSummary
		if err := rows.Scan(&m.MatchID, &m.MapName, &m.Kills, &m.Deaths, &m.StartedAt, &m.EndedAt); err != nil {
			continue
		}
		matches = append(matches, m)
	}

	h.jsonResponse(w, http.StatusOK, matches)
}

// GetPlayerDeepStats returns massive aggregated stats for a player
func (h *Handler) GetPlayerDeepStats(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	stats, err := h.playerStats.GetDeepStats(ctx, guid)
	if err != nil {
		h.logger.Errorw("Failed to get deep stats", "guid", guid, "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to calculate deep stats")
		return
	}

	h.jsonResponse(w, http.StatusOK, stats)
}

// GetPlayerPeakPerformance returns when a player performs best (time analysis)
func (h *Handler) GetPlayerPeakPerformance(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	peak, err := h.advancedStats.GetPeakPerformance(ctx, guid)
	if err != nil {
		h.logger.Errorw("Failed to get peak performance", "guid", guid, "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to calculate peak performance")
		return
	}

	h.jsonResponse(w, http.StatusOK, peak)
}

// GetPlayerDrillDown drills into a specific stat by dimension
func (h *Handler) GetPlayerDrillDown(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	stat := r.URL.Query().Get("stat")
	dimension := r.URL.Query().Get("dimension")
	limitStr := r.URL.Query().Get("limit")
	ctx := r.Context()

	limit := 10
	if l, err := strconv.Atoi(limitStr); err == nil && l > 0 {
		limit = l
	}

	if stat == "" {
		stat = "kills"
	}
	if dimension == "" {
		dimension = "weapon"
	}

	result, err := h.advancedStats.GetDrillDown(ctx, guid, stat, dimension, limit)
	if err != nil {
		h.logger.Errorw("Failed to get drill-down", "guid", guid, "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to calculate drill-down")
		return
	}

	h.jsonResponse(w, http.StatusOK, result)
}

// GetPlayerComboMetrics returns cross-dimensional stat combinations
func (h *Handler) GetPlayerComboMetrics(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	combo, err := h.advancedStats.GetComboMetrics(ctx, guid)
	if err != nil {
		h.logger.Errorw("Failed to get combo metrics", "guid", guid, "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to calculate combo metrics")
		return
	}

	h.jsonResponse(w, http.StatusOK, combo)
}

// GetPlayerVehicleStats returns vehicle and turret statistics
func (h *Handler) GetPlayerVehicleStats(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	stats, err := h.advancedStats.GetVehicleStats(ctx, guid)
	if err != nil {
		h.logger.Errorw("Failed to get vehicle stats", "guid", guid, "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to calculate vehicle stats")
		return
	}

	h.jsonResponse(w, http.StatusOK, stats)
}

// GetPlayerGameFlowStats returns round/objective/team statistics
func (h *Handler) GetPlayerGameFlowStats(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	stats, err := h.advancedStats.GetGameFlowStats(ctx, guid)
	if err != nil {
		h.logger.Errorw("Failed to get game flow stats", "guid", guid, "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to calculate game flow stats")
		return
	}

	h.jsonResponse(w, http.StatusOK, stats)
}

// GetPlayerWorldStats returns world interaction statistics  
func (h *Handler) GetPlayerWorldStats(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	stats, err := h.advancedStats.GetWorldStats(ctx, guid)
	if err != nil {
		h.logger.Errorw("Failed to get world stats", "guid", guid, "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to calculate world stats")
		return
	}

	h.jsonResponse(w, http.StatusOK, stats)
}

// GetPlayerBotStats returns bot-related statistics
func (h *Handler) GetPlayerBotStats(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	stats, err := h.advancedStats.GetBotStats(ctx, guid)
	if err != nil {
		h.logger.Errorw("Failed to get bot stats", "guid", guid, "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to calculate bot stats")
		return
	}

	h.jsonResponse(w, http.StatusOK, stats)
}

// GetPlayerWeaponStats returns per-weapon stats for a player
func (h *Handler) GetPlayerWeaponStats(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	rows, err := h.ch.Query(ctx, `
		SELECT 
			actor_weapon,
			count() as kills
		FROM raw_events
		WHERE event_type = 'kill' AND actor_id = ? AND actor_weapon != ''
		GROUP BY actor_weapon
		ORDER BY kills DESC
	`, guid)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	var weapons []models.WeaponStats
	for rows.Next() {
		var w models.WeaponStats
		if err := rows.Scan(&w.Weapon, &w.Kills); err != nil {
			continue
		}
		weapons = append(weapons, w)
	}

	h.jsonResponse(w, http.StatusOK, weapons)
}

// GetPlayerHeatmap returns kill position data for heatmap visualization
func (h *Handler) GetPlayerHeatmap(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	mapName := chi.URLParam(r, "map")
	ctx := r.Context()

	rows, err := h.ch.Query(ctx, `
		SELECT 
			actor_pos_x,
			actor_pos_y,
			count() as kills
		FROM raw_events
		WHERE event_type = 'kill' 
		  AND actor_id = ? 
		  AND map_name = ?
		  AND actor_pos_x != 0
		GROUP BY 
			round(actor_pos_x / 100) * 100 as actor_pos_x,
			round(actor_pos_y / 100) * 100 as actor_pos_y
	`, guid, mapName)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	var points []models.HeatmapPoint
	for rows.Next() {
		var p models.HeatmapPoint
		if err := rows.Scan(&p.X, &p.Y, &p.Count); err != nil {
			continue
		}
		points = append(points, p)
	}

	h.jsonResponse(w, http.StatusOK, models.HeatmapData{
		MapName: mapName,
		Points:  points,
	})
}

// GetPlayerDeathHeatmap returns death position data for heatmap visualization
func (h *Handler) GetPlayerDeathHeatmap(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	mapName := chi.URLParam(r, "map")
	ctx := r.Context()

	rows, err := h.ch.Query(ctx, `
		SELECT 
			target_pos_x,
			target_pos_y,
			count() as deaths
		FROM raw_events
		WHERE event_type = 'kill' 
		  AND target_id = ? 
		  AND map_name = ?
		  AND target_pos_x != 0
		GROUP BY 
			round(target_pos_x / 100) * 100 as target_pos_x,
			round(target_pos_y / 100) * 100 as target_pos_y
	`, guid, mapName)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	var points []models.HeatmapPoint
	for rows.Next() {
		var p models.HeatmapPoint
		if err := rows.Scan(&p.X, &p.Y, &p.Count); err != nil {
			continue
		}
		points = append(points, p)
	}

	h.jsonResponse(w, http.StatusOK, models.HeatmapData{
		MapName: mapName,
		Points:  points,
		Type:    "deaths",
	})
}

// GetPlayerPerformanceHistory returns K/D history over last 20 matches
func (h *Handler) GetPlayerPerformanceHistory(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	// Fetch matches chronologically
	rows, err := h.ch.Query(ctx, `
		SELECT 
			match_id,
			countIf(event_type = 'kill' AND actor_id = ?) as kills,
			countIf(event_type = 'death' AND actor_id = ?) as deaths,
			min(timestamp) as played_at
		FROM raw_events
		WHERE match_id IN (
			SELECT DISTINCT match_id FROM raw_events 
			WHERE actor_id = ? 
			ORDER BY timestamp DESC 
			LIMIT 20
		)
		GROUP BY match_id
		ORDER BY played_at ASC
	`, guid, guid, guid)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	type PerformancePoint struct {
		MatchID  string  `json:"match_id"`
		Kills    int     `json:"kills"`
		Deaths   int     `json:"deaths"`
		KD       float64 `json:"kd"`
		PlayedAt float64 `json:"played_at"`
	}

	var history []PerformancePoint
	for rows.Next() {
		var p PerformancePoint
		if err := rows.Scan(&p.MatchID, &p.Kills, &p.Deaths, &p.PlayedAt); err != nil {
			continue
		}
		if p.Deaths > 0 {
			p.KD = float64(p.Kills) / float64(p.Deaths)
		} else {
			p.KD = float64(p.Kills)
		}
		history = append(history, p)
	}

	h.jsonResponse(w, http.StatusOK, history)
}

// GetPlayerBodyHeatmap returns hit location distribution
// GetPlayerBodyHeatmap returns hit location distribution
func (h *Handler) GetPlayerBodyHeatmap(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	ctx := r.Context()

	// Query breakdown of hit locations where this player was the TARGET (victim)
	rows, err := h.ch.Query(ctx, `
		SELECT 
			replaceRegexpOne(
				extract(extra, 'hitloc_([a-zA-Z0-9_]+)'),
				'^$', 'torso'
			) as body_part,
			count() as hits
		FROM raw_events
		WHERE event_type = 'weapon_hit' AND target_id = ?
		GROUP BY body_part
	`, guid)
	if err != nil {
		h.logger.Errorw("Failed to query body heatmap", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	heatmap := make(map[string]uint64)
	for rows.Next() {
		var part string
		var hits uint64
		if err := rows.Scan(&part, &hits); err != nil {
			continue
		}
		heatmap[part] = hits
	}

	h.jsonResponse(w, http.StatusOK, heatmap)
}

// GetMapHeatmap returns global heatmap for a map (all players)
func (h *Handler) GetMapHeatmap(w http.ResponseWriter, r *http.Request) {
	mapName := chi.URLParam(r, "map")
	heatmapType := r.URL.Query().Get("type") // "kills" or "deaths"
	if heatmapType == "" {
		heatmapType = "kills"
	}
	ctx := r.Context()

	var query string
	if heatmapType == "deaths" {
		query = `
			SELECT 
				round(target_pos_x / 100) * 100,
				round(target_pos_y / 100) * 100,
				count() as count
			FROM raw_events
			WHERE event_type = 'kill' 
			  AND map_name = ?
			  AND target_pos_x != 0
			GROUP BY 
				round(target_pos_x / 100) * 100,
				round(target_pos_y / 100) * 100
		`
	} else {
		query = `
			SELECT 
				round(actor_pos_x / 100) * 100,
				round(actor_pos_y / 100) * 100,
				count() as count
			FROM raw_events
			WHERE event_type = 'kill' 
			  AND map_name = ?
			  AND actor_pos_x != 0
			GROUP BY 
				round(actor_pos_x / 100) * 100,
				round(actor_pos_y / 100) * 100
		`
	}

	rows, err := h.ch.Query(ctx, query, mapName)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	var points []models.HeatmapPoint
	for rows.Next() {
		var p models.HeatmapPoint
		if err := rows.Scan(&p.X, &p.Y, &p.Count); err != nil {
			continue
		}
		points = append(points, p)
	}

	h.jsonResponse(w, http.StatusOK, models.HeatmapData{
		MapName: mapName,
		Points:  points,
		Type:    heatmapType,
	})
}

// GetMatchDetails returns full details for a match
func (h *Handler) GetMatchDetails(w http.ResponseWriter, r *http.Request) {
	matchID := chi.URLParam(r, "matchId")
	ctx := r.Context()

	// Get match summary
	row := h.ch.QueryRow(ctx, `
		SELECT 
			map_name,
			min(timestamp) as started,
			max(timestamp) as ended,
			countIf(event_type = 'kill') as total_kills,
			uniq(actor_id) as unique_players
		FROM raw_events
		WHERE match_id = ?
	`, matchID)

	var summary struct {
		MapName       string    `json:"map_name"`
		StartedAt     time.Time `json:"started_at"`
		EndedAt       time.Time `json:"ended_at"`
		TotalKills    uint64    `json:"total_kills"`
		UniquePlayers uint64    `json:"unique_players"`
	}

	if err := row.Scan(&summary.MapName, &summary.StartedAt, &summary.EndedAt, &summary.TotalKills, &summary.UniquePlayers); err != nil {
		h.errorResponse(w, http.StatusNotFound, "Match not found")
		return
	}

	// Get player scoreboard
	rows, err := h.ch.Query(ctx, `
		SELECT 
			actor_id,
			actor_name,
			countIf(event_type = 'kill') as kills,
			countIf(event_type = 'death') as deaths,
			countIf(event_type = 'headshot') as headshots
		FROM raw_events
		WHERE match_id = ? AND actor_id != '' AND actor_id != 'world'
		GROUP BY actor_id, actor_name
		ORDER BY kills DESC
	`, matchID)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	type PlayerScore struct {
		PlayerID   string `json:"player_id"`
		PlayerName string `json:"player_name"`
		Kills      uint64 `json:"kills"`
		Deaths     uint64 `json:"deaths"`
		Headshots  uint64 `json:"headshots"`
	}

	var scoreboard []PlayerScore
	for rows.Next() {
		var p PlayerScore
		if err := rows.Scan(&p.PlayerID, &p.PlayerName, &p.Kills, &p.Deaths, &p.Headshots); err != nil {
			continue
		}
		scoreboard = append(scoreboard, p)
	}

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"match_id":   matchID,
		"summary":    summary,
		"scoreboard": scoreboard,
	})
}

// GetMatchHeatmap returns kill/death locations for a specific match
func (h *Handler) GetMatchHeatmap(w http.ResponseWriter, r *http.Request) {
	matchID := chi.URLParam(r, "matchId")
	ctx := r.Context()

	// Query individual kill events with coordinates
	rows, err := h.ch.Query(ctx, `
		SELECT 
			actor_id,
			target_id,
			actor_pos_x,
			actor_pos_y,
			target_pos_x,
			target_pos_y
		FROM raw_events
		WHERE match_id = ? 
		  AND event_type = 'kill'
		  AND actor_pos_x != 0 AND target_pos_x != 0
		LIMIT 2000
	`, matchID)
	if err != nil {
		h.logger.Errorw("Failed to query match heatmap", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	type Point struct {
		ID    int     `json:"id"`
		Type  string  `json:"type"` // "kill" or "death"
		X     float64 `json:"x"`
		Y     float64 `json:"y"`
		Label string  `json:"label"`
	}

	var points []Point
	id := 0

	for rows.Next() {
		var actorID, targetID string
		var ax, ay, tx, ty float64
		if err := rows.Scan(&actorID, &targetID, &ax, &ay, &tx, &ty); err != nil {
			continue
		}

		// Killer position (green)
		points = append(points, Point{
			ID:    id,
			Type:  "kill",
			X:     ax,
			Y:     ay,
			Label: "Killer: " + actorID,
		})
		id++

		// Victim position (red)
		points = append(points, Point{
			ID:    id,
			Type:  "death",
			X:     tx,
			Y:     ty,
			Label: "Victim: " + targetID,
		})
		id++
	}

	h.jsonResponse(w, http.StatusOK, points)
}

// GetMatchTimeline returns chronological events for match replay
func (h *Handler) GetMatchTimeline(w http.ResponseWriter, r *http.Request) {
	matchID := chi.URLParam(r, "matchId")
	ctx := r.Context()

	rows, err := h.ch.Query(ctx, `
		SELECT 
			timestamp,
			event_type,
			actor_name,
			target_name,
			actor_weapon,
			hitloc
		FROM raw_events
		WHERE match_id = ? AND event_type IN ('kill', 'round_start', 'round_end')
		ORDER BY timestamp
		LIMIT 1000
	`, matchID)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	type TimelineEvent struct {
		Timestamp  time.Time `json:"timestamp"`
		EventType  string    `json:"event_type"`
		ActorName  string    `json:"actor_name"`
		TargetName string    `json:"target_name"`
		Weapon     string    `json:"weapon"`
		Hitloc     string    `json:"hitloc"`
	}

	var events []TimelineEvent
	for rows.Next() {
		var e TimelineEvent
		if err := rows.Scan(&e.Timestamp, &e.EventType, &e.ActorName, &e.TargetName, &e.Weapon, &e.Hitloc); err != nil {
			continue
		}
		events = append(events, e)
	}

	h.jsonResponse(w, http.StatusOK, events)
}

// GetServerStats returns stats for a specific server
func (h *Handler) GetServerStats(w http.ResponseWriter, r *http.Request) {
	serverID := chi.URLParam(r, "serverId")
	ctx := r.Context()

	var response models.ServerStatsResponse
	response.ServerID = serverID

	// 1. Get Aggregate Totals
	// Using a single query to get multiple aggregates
	row := h.ch.QueryRow(ctx, `
		SELECT 
			countIf(event_type = 'kill') as total_kills,
			countIf(event_type = 'death') as total_deaths,
			uniq(match_id) as total_matches,
			uniq(actor_id) as unique_players,
			sumIf(duration, event_type = 'session_end') as total_playtime, -- Placeholder logic
			max(timestamp) as last_activity
		FROM raw_events
		WHERE server_id = ?
	`, serverID)

	if err := row.Scan(
		&response.TotalKills,
		&response.TotalDeaths,
		&response.TotalMatches,
		&response.UniquePlayers,
		&response.TotalPlaytime,
		&response.LastActivity,
	); err != nil {
		h.logger.Errorw("Failed to query server totals", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}

	// 2. Top Killers Leaderboard
	rows, err := h.ch.Query(ctx, `
		SELECT actor_id, any(actor_name), count() as val
		FROM raw_events
		WHERE server_id = ? AND event_type = 'kill' AND actor_id != ''
		GROUP BY actor_id
		ORDER BY val DESC
		LIMIT 10
	`, serverID)
	if err == nil {
		rank := 1
		for rows.Next() {
			var e models.ServerLeaderboardEntry
			rows.Scan(&e.PlayerID, &e.PlayerName, &e.Value)
			e.Rank = rank
			response.TopKillers = append(response.TopKillers, e)
			rank++
		}
		rows.Close()
	}

	// 3. Map Stats
	rows, err = h.ch.Query(ctx, `
		SELECT map_name, count() as times_played
		FROM raw_events
		WHERE server_id = ? AND event_type = 'match_start'
		GROUP BY map_name
		ORDER BY times_played DESC
		LIMIT 10
	`, serverID)
	if err == nil {
		for rows.Next() {
			var m models.ServerMapStat
			rows.Scan(&m.MapName, &m.TimesPlayed)
			response.MapStats = append(response.MapStats, m)
		}
		rows.Close()
	}

	h.jsonResponse(w, http.StatusOK, response)
}

// GetDynamicStats handles flexible stats queries
func (h *Handler) GetDynamicStats(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()

	// Parse parameters
	req := logic.DynamicQueryRequest{
		Dimension:    q.Get("dimension"),
		Metric:       q.Get("metric"),
		FilterGUID:   q.Get("filter_player_guid"),
		FilterMap:    q.Get("filter_map"),
		FilterWeapon: q.Get("filter_weapon"),
		FilterServer: q.Get("filter_server"),
	}

	if limitStr := q.Get("limit"); limitStr != "" {
		if l, err := strconv.Atoi(limitStr); err == nil {
			req.Limit = l
		}
	}

	if startStr := q.Get("start_date"); startStr != "" {
		if t, err := time.Parse(time.RFC3339, startStr); err == nil {
			req.StartDate = t
		}
	}
	if endStr := q.Get("end_date"); endStr != "" {
		if t, err := time.Parse(time.RFC3339, endStr); err == nil {
			req.EndDate = t
		}
	}

	// Build query
	sql, args, err := logic.BuildStatsQuery(req)
	if err != nil {
		h.errorResponse(w, http.StatusBadRequest, err.Error())
		return
	}

	// Execute
	ctx := r.Context()
	rows, err := h.ch.Query(ctx, sql, args...)
	if err != nil {
		h.logger.Errorw("Dynamic stats query failed", "error", err, "query", sql)
		h.errorResponse(w, http.StatusInternalServerError, "Query execution failed")
		return
	}
	defer rows.Close()

	// Generic result structure
	type Result struct {
		Label string  `json:"label"`
		Value float64 `json:"value"`
	}

	var results []Result
	for rows.Next() {
		var r Result
		// Note: The order of scan vars must match the SELECT order in query_builder (value, label)
		if err := rows.Scan(&r.Value, &r.Label); err != nil {
			h.logger.Errorw("Failed to scan row", "error", err)
			continue
		}
		results = append(results, r)
	}

	h.jsonResponse(w, http.StatusOK, results)
}

// GetLiveMatches returns currently active matches
func (h *Handler) GetLiveMatches(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	// Get all live matches from Redis
	matchData, err := h.redis.HGetAll(ctx, "live_matches").Result()
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Failed to fetch live matches")
		return
	}

	var matches []models.LiveMatch
	for _, data := range matchData {
		var match models.LiveMatch
		if err := json.Unmarshal([]byte(data), &match); err == nil {
			matches = append(matches, match)
		}
	}

	h.jsonResponse(w, http.StatusOK, matches)
}

// ============================================================================
// MIDDLEWARE
// ============================================================================

// ServerAuthMiddleware validates server tokens
func (h *Handler) ServerAuthMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		token := r.Header.Get("Authorization")
		if token == "" {
			// Also check form data for curl_post from game server
			token = r.FormValue("server_token")
		}

		if token == "" {
			h.errorResponse(w, http.StatusUnauthorized, "Missing server token")
			return
		}

		// Strip "Bearer " prefix if present
		token = strings.TrimPrefix(token, "Bearer ")

		// TODO: Validate token against database/Redis
		// For now, accept any non-empty token in development
		if token == "" {
			h.errorResponse(w, http.StatusUnauthorized, "Invalid server token")
			return
		}

		next.ServeHTTP(w, r)
	})
}

// UserAuthMiddleware validates user JWT tokens
func (h *Handler) UserAuthMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		authHeader := r.Header.Get("Authorization")
		if authHeader == "" {
			h.errorResponse(w, http.StatusUnauthorized, "Missing authorization header")
			return
		}

		tokenString := strings.TrimPrefix(authHeader, "Bearer ")

		token, err := jwt.Parse(tokenString, func(token *jwt.Token) (interface{}, error) {
			return h.jwtSecret, nil
		})

		if err != nil || !token.Valid {
			h.errorResponse(w, http.StatusUnauthorized, "Invalid token")
			return
		}

		// TODO: Add user claims to context
		next.ServeHTTP(w, r)
	})
}

// AdminAuthMiddleware validates admin access
func (h *Handler) AdminAuthMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		// TODO: Check admin role from JWT claims
		next.ServeHTTP(w, r)
	})
}

// GetGlobalActivity returns heat map data for server activity
func (h *Handler) GetGlobalActivity(w http.ResponseWriter, r *http.Request) {
	activity, err := h.serverStats.GetGlobalActivity(r.Context())
	if err != nil {
		h.logger.Errorw("Failed to get global activity", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Internal server error")
		return
	}
	h.jsonResponse(w, http.StatusOK, activity)
}

// GetMapPopularity returns stats for map usage
func (h *Handler) GetMapPopularity(w http.ResponseWriter, r *http.Request) {
	stats, err := h.serverStats.GetMapPopularity(r.Context())
	if err != nil {
		h.logger.Errorw("Failed to get map popularity", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Internal server error")
		return
	}
	h.jsonResponse(w, http.StatusOK, stats)
}

// GetPlayerPlaystyle returns the calculated playstyle badge
func (h *Handler) GetPlayerPlaystyle(w http.ResponseWriter, r *http.Request) {
	guid := chi.URLParam(r, "guid")
	badge, err := h.gamification.GetPlaystyle(r.Context(), guid)
	if err != nil {
		h.logger.Errorw("Failed to get playstyle", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Internal error")
		return
	}
	h.jsonResponse(w, http.StatusOK, badge)
}

// GetMatchAdvancedDetails returns deep analysis for a match
func (h *Handler) GetMatchAdvancedDetails(w http.ResponseWriter, r *http.Request) {
	matchID := chi.URLParam(r, "matchId")
	details, err := h.matchReport.GetMatchDetails(r.Context(), matchID)
	if err != nil {
		h.logger.Errorw("Failed to get match details", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Internal error")
		return
	}
	h.jsonResponse(w, http.StatusOK, details)
}

// GetLeaderboardCards was moved to cards.go to support the massive dashboard

// ============================================================================
// HELPERS
// ============================================================================

func (h *Handler) jsonResponse(w http.ResponseWriter, status int, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(data)
}

func (h *Handler) errorResponse(w http.ResponseWriter, status int, message string) {
	h.jsonResponse(w, status, map[string]string{"error": message})
}

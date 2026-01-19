package handlers

import (
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"net/http"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/golang-jwt/jwt/v5"
	"github.com/google/uuid"

	"github.com/openmohaa/stats-api/internal/models"
)

// ============================================================================
// TOURNAMENT HANDLERS
// ============================================================================

// ListTournaments returns all tournaments
func (h *Handler) ListTournaments(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	status := r.URL.Query().Get("status")

	query := `
		SELECT id, name, description, format, status, max_participants, team_size,
		       game_mode, start_time, participant_count, current_round, created_at
		FROM tournaments
	`
	args := []interface{}{}

	if status != "" {
		query += " WHERE status = $1"
		args = append(args, status)
	}
	query += " ORDER BY start_time DESC LIMIT 50"

	rows, err := h.pg.Query(ctx, query, args...)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	var tournaments []models.Tournament
	for rows.Next() {
		var t models.Tournament
		if err := rows.Scan(
			&t.ID, &t.Name, &t.Description, &t.Format, &t.Status,
			&t.MaxParticipants, &t.TeamSize, &t.GameMode, &t.StartTime,
			&t.ParticipantCount, &t.CurrentRound, &t.CreatedAt,
		); err != nil {
			continue
		}
		tournaments = append(tournaments, t)
	}

	h.jsonResponse(w, http.StatusOK, tournaments)
}

// GetTournament returns a single tournament
func (h *Handler) GetTournament(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	ctx := r.Context()

	var t models.Tournament
	err := h.pg.QueryRow(ctx, `
		SELECT id, name, description, format, status, max_participants, min_participants,
		       team_size, game_mode, timelimit, fraglimit, roundlimit, best_of,
		       registration_start, registration_end, checkin_start, checkin_end,
		       start_time, end_time, organizer_id, participant_count, current_round,
		       created_at, updated_at
		FROM tournaments WHERE id = $1
	`, id).Scan(
		&t.ID, &t.Name, &t.Description, &t.Format, &t.Status,
		&t.MaxParticipants, &t.MinParticipants, &t.TeamSize, &t.GameMode,
		&t.Timelimit, &t.Fraglimit, &t.Roundlimit, &t.BestOf,
		&t.RegistrationStart, &t.RegistrationEnd, &t.CheckinStart, &t.CheckinEnd,
		&t.StartTime, &t.EndTime, &t.OrganizerID, &t.ParticipantCount, &t.CurrentRound,
		&t.CreatedAt, &t.UpdatedAt,
	)
	if err != nil {
		h.errorResponse(w, http.StatusNotFound, "Tournament not found")
		return
	}

	h.jsonResponse(w, http.StatusOK, t)
}

// GetTournamentBracket returns the bracket structure
func (h *Handler) GetTournamentBracket(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	ctx := r.Context()

	// Get tournament format
	var format models.TournamentFormat
	err := h.pg.QueryRow(ctx, "SELECT format FROM tournaments WHERE id = $1", id).Scan(&format)
	if err != nil {
		h.errorResponse(w, http.StatusNotFound, "Tournament not found")
		return
	}

	// Get all matches
	rows, err := h.pg.Query(ctx, `
		SELECT m.id, m.bracket_type, m.round_number, m.match_number,
		       m.participant1_id, m.participant2_id, m.participant1_score, m.participant2_score,
		       m.winner_id, m.status, m.scheduled_time,
		       p1.player_id as p1_name, p1.seed as p1_seed,
		       p2.player_id as p2_name, p2.seed as p2_seed
		FROM tournament_matches m
		LEFT JOIN tournament_participants p1 ON m.participant1_id = p1.id
		LEFT JOIN tournament_participants p2 ON m.participant2_id = p2.id
		WHERE m.tournament_id = $1
		ORDER BY m.bracket_type, m.round_number, m.match_number
	`, id)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	bracket := models.TournamentBracket{
		TournamentID: uuid.MustParse(id),
		Format:       format,
	}

	// Organize matches into bracket structure
	upperRounds := make(map[int][]models.BracketMatch)
	lowerRounds := make(map[int][]models.BracketMatch)

	for rows.Next() {
		var m models.BracketMatch
		var bracketType models.BracketType
		var p1ID, p2ID, winnerID *uuid.UUID
		var p1Name, p2Name *string
		var p1Seed, p2Seed *int

		err := rows.Scan(
			&m.ID, &bracketType, &m.RoundNumber, &m.MatchNumber,
			&p1ID, &p2ID, &m.Score1, &m.Score2,
			&winnerID, &m.Status, &m.ScheduledTime,
			&p1Name, &p1Seed, &p2Name, &p2Seed,
		)
		if err != nil {
			continue
		}

		if p1ID != nil {
			m.Participant1 = &models.BracketParticipant{
				ID:       *p1ID,
				Name:     derefString(p1Name),
				Seed:     derefInt(p1Seed),
				IsWinner: winnerID != nil && *winnerID == *p1ID,
			}
		}
		if p2ID != nil {
			m.Participant2 = &models.BracketParticipant{
				ID:       *p2ID,
				Name:     derefString(p2Name),
				Seed:     derefInt(p2Seed),
				IsWinner: winnerID != nil && *winnerID == *p2ID,
			}
		}
		m.WinnerID = winnerID

		switch bracketType {
		case models.BracketUpper:
			upperRounds[m.RoundNumber] = append(upperRounds[m.RoundNumber], m)
		case models.BracketLower:
			lowerRounds[m.RoundNumber] = append(lowerRounds[m.RoundNumber], m)
		case models.BracketGrandFinal:
			bracket.GrandFinal = &m
		}
	}

	// Convert maps to sorted slices
	for i := 1; i <= len(upperRounds); i++ {
		bracket.UpperBracket = append(bracket.UpperBracket, upperRounds[i])
	}
	for i := 1; i <= len(lowerRounds); i++ {
		bracket.LowerBracket = append(bracket.LowerBracket, lowerRounds[i])
	}

	h.jsonResponse(w, http.StatusOK, bracket)
}

// GetTournamentStandings returns standings (for Swiss/RR)
func (h *Handler) GetTournamentStandings(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	ctx := r.Context()

	rows, err := h.pg.Query(ctx, `
		SELECT p.id, p.player_id, p.seed, p.wins, p.losses, p.draws, p.points
		FROM tournament_participants p
		WHERE p.tournament_id = $1
		ORDER BY p.points DESC, p.wins DESC, p.seed ASC
	`, id)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	var standings []models.Standing
	rank := 1
	for rows.Next() {
		var s models.Standing
		var pID uuid.UUID
		var playerID string
		var seed, wins, losses, draws, points int

		if err := rows.Scan(&pID, &playerID, &seed, &wins, &losses, &draws, &points); err != nil {
			continue
		}

		s.Rank = rank
		s.Participant = models.BracketParticipant{
			ID:   pID,
			Name: playerID,
			Seed: seed,
		}
		s.Wins = wins
		s.Losses = losses
		s.Draws = draws
		s.Points = points
		standings = append(standings, s)
		rank++
	}

	h.jsonResponse(w, http.StatusOK, standings)
}

// CreateTournament creates a new tournament
func (h *Handler) CreateTournament(w http.ResponseWriter, r *http.Request) {
	var req models.CreateTournamentRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	ctx := r.Context()
	id := uuid.New()

	// TODO: Get organizer ID from JWT claims
	organizerID := uuid.New()

	_, err := h.pg.Exec(ctx, `
		INSERT INTO tournaments (
			id, name, description, format, status, max_participants, min_participants,
			team_size, game_mode, timelimit, fraglimit, roundlimit, best_of,
			registration_start, registration_end, checkin_start, checkin_end,
			start_time, organizer_id, created_at, updated_at
		) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $20)
	`,
		id, req.Name, req.Description, req.Format, models.TournamentStatusDraft,
		req.MaxParticipants, req.MinParticipants, req.TeamSize, req.GameMode,
		req.Timelimit, req.Fraglimit, req.Roundlimit, req.BestOf,
		req.RegistrationStart, req.RegistrationEnd, req.CheckinStart, req.CheckinEnd,
		req.StartTime, organizerID, time.Now(),
	)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Failed to create tournament")
		return
	}

	h.jsonResponse(w, http.StatusCreated, map[string]string{"id": id.String()})
}

// UpdateTournament updates a tournament
func (h *Handler) UpdateTournament(w http.ResponseWriter, r *http.Request) {
	// TODO: Implement tournament update
	h.jsonResponse(w, http.StatusOK, map[string]string{"status": "updated"})
}

// RegisterForTournament registers a player for a tournament
func (h *Handler) RegisterForTournament(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	ctx := r.Context()

	var req struct {
		PlayerGUID string `json:"player_guid"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	participantID := uuid.New()
	_, err := h.pg.Exec(ctx, `
		INSERT INTO tournament_participants (id, tournament_id, player_id, registered_at)
		VALUES ($1, $2, $3, $4)
	`, participantID, id, req.PlayerGUID, time.Now())
	if err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Registration failed")
		return
	}

	// Update participant count
	h.pg.Exec(ctx, `
		UPDATE tournaments SET participant_count = participant_count + 1 WHERE id = $1
	`, id)

	h.jsonResponse(w, http.StatusCreated, map[string]string{"participant_id": participantID.String()})
}

// CheckinTournament checks in a player for a tournament
func (h *Handler) CheckinTournament(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	ctx := r.Context()

	var req struct {
		PlayerGUID string `json:"player_guid"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	_, err := h.pg.Exec(ctx, `
		UPDATE tournament_participants 
		SET checked_in = true, checkin_time = $3
		WHERE tournament_id = $1 AND player_id = $2
	`, id, req.PlayerGUID, time.Now())
	if err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Check-in failed")
		return
	}

	h.jsonResponse(w, http.StatusOK, map[string]string{"status": "checked_in"})
}

// ============================================================================
// AUTH HANDLERS
// ============================================================================

// InitDeviceAuth generates a unique login token for SMF forum users
// POST /api/v1/auth/device
// Body: {"forum_user_id": 123, "client_ip": "1.2.3.4"} or {"forum_user_id": 123, "regenerate": true, "client_ip": "1.2.3.4"}
// The client_ip is automatically added to trusted_ips so the user can login from the same IP they used to generate the token
func (h *Handler) InitDeviceAuth(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	var req struct {
		ForumUserID int    `json:"forum_user_id"`
		Regenerate  bool   `json:"regenerate"`
		ClientIP    string `json:"client_ip"` // IP of the user generating the token (auto-trusted)
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	if req.ForumUserID <= 0 {
		h.errorResponse(w, http.StatusBadRequest, "forum_user_id is required")
		return
	}

	// If regenerating, revoke all existing tokens for this user
	// NOTE: We do NOT clear trusted IPs - user must manually remove them if compromised
	if req.Regenerate {
		_, err := h.pg.Exec(ctx, `
			UPDATE login_tokens 
			SET is_active = false, revoked_at = NOW() 
			WHERE forum_user_id = $1 AND is_active = true
		`, req.ForumUserID)
		if err != nil {
			h.logger.Errorw("Failed to revoke old tokens", "error", err, "forum_user_id", req.ForumUserID)
		}
	}

	// Check for existing active, unused token
	if !req.Regenerate {
		var existingToken string
		var expiresAt time.Time
		err := h.pg.QueryRow(ctx, `
			SELECT token, expires_at FROM login_tokens 
			WHERE forum_user_id = $1 AND is_active = true AND used_at IS NULL AND expires_at > NOW()
			ORDER BY created_at DESC LIMIT 1
		`, req.ForumUserID).Scan(&existingToken, &expiresAt)

		if err == nil && existingToken != "" {
			// Return existing valid token
			h.jsonResponse(w, http.StatusOK, map[string]interface{}{
				"user_code":  existingToken,
				"expires_in": int(time.Until(expiresAt).Seconds()),
				"expires_at": expiresAt,
				"is_new":     false,
			})
			return
		}
	}

	// Generate a new unique token (8 chars, no confusing chars)
	userCode := generateUserCode()
	expiresAt := time.Now().Add(10 * time.Minute)

	// Insert new token into Postgres
	_, err := h.pg.Exec(ctx, `
		INSERT INTO login_tokens (forum_user_id, token, expires_at)
		VALUES ($1, $2, $3)
	`, req.ForumUserID, userCode, expiresAt)

	if err != nil {
		h.logger.Errorw("Failed to create login token", "error", err, "forum_user_id", req.ForumUserID)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to generate token")
		return
	}

	// Auto-trust the IP that was used to generate the token
	if req.ClientIP != "" {
		_, err = h.pg.Exec(ctx, `
			INSERT INTO trusted_ips (forum_user_id, ip_address, source, label)
			VALUES ($1, $2::inet, 'website', 'Auto-approved (website)')
			ON CONFLICT (forum_user_id, ip_address) 
			DO UPDATE SET 
				is_active = true,
				last_used_at = NOW(),
				revoked_at = NULL
		`, req.ForumUserID, req.ClientIP)
		if err != nil {
			h.logger.Errorw("Failed to auto-trust client IP", "error", err, "ip", req.ClientIP)
			// Don't fail the request, just log it
		}
	}

	// Also cache in Redis for quick game server lookups
	h.redis.Set(ctx, "login_token:"+userCode, fmt.Sprintf("%d", req.ForumUserID), 10*time.Minute)

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"user_code":  userCode,
		"expires_in": 600,
		"expires_at": expiresAt,
		"is_new":     true,
	})
}

// PollDeviceToken polls for completed device auth
func (h *Handler) PollDeviceToken(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	var req struct {
		DeviceCode string `json:"device_code"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	data, err := h.redis.Get(ctx, "device:"+req.DeviceCode).Bytes()
	if err != nil {
		h.errorResponse(w, http.StatusNotFound, "Invalid or expired device code")
		return
	}

	var state models.DeviceAuthState
	json.Unmarshal(data, &state)

	switch state.Status {
	case "pending":
		h.errorResponse(w, http.StatusBadRequest, "authorization_pending")
	case "authorized":
		// Generate JWT
		token := jwt.NewWithClaims(jwt.SigningMethodHS256, jwt.MapClaims{
			"user_id": state.UserID.String(),
			"exp":     time.Now().Add(24 * time.Hour).Unix(),
		})
		tokenString, _ := token.SignedString(h.jwtSecret)

		h.jsonResponse(w, http.StatusOK, map[string]string{
			"access_token": tokenString,
			"token_type":   "Bearer",
		})

		// Clean up
		h.redis.Del(ctx, "device:"+req.DeviceCode)
	default:
		h.errorResponse(w, http.StatusBadRequest, "expired")
	}
}

// VerifyToken validates a login token from the game server
// This is called when a player types /login <token> in-game
// POST /api/v1/auth/verify
// Body: {"token": "ABC12345", "player_guid": "xxx", "server_name": "My Server", "player_ip": "1.2.3.4"}
//
// Security model:
// 1. First use: Token verified, IP becomes trusted
// 2. Same IP reconnect: Token already used but IP is trusted → Allow
// 3. New IP: Token already used, IP not trusted → Create pending approval request
// VerifyToken validates a login token from the game server
// This is called when a player types /login <token> in-game
// POST /api/v1/auth/verify
// Body: {"token": "ABC12345", "player_guid": "xxx", "server_name": "My Server", "player_ip": "1.2.3.4"}
//
// Security model:
// 1. First use: Token verified, IP becomes trusted
// 2. Same IP reconnect: Token already used but IP is trusted → Allow
// 3. New IP: Token already used, IP not trusted → Create pending approval request
// 4. Trusted IP Login: No token provided, but IP is trusted -> Allow (Reverse lookup user from IP)
func (h *Handler) VerifyToken(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	var req struct {
		Token         string `json:"token"`
		PlayerGUID    string `json:"player_guid"`
		ServerName    string `json:"server_name"`
		ServerAddress string `json:"server_address"`
		PlayerIP      string `json:"player_ip"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	// Helper to log attempts
	// Note: We need forumUserID to exist before calling this, or pass 0
	logAttempt := func(uID int, success bool, reason string) {
		if uID > 0 {
			h.pg.Exec(ctx, `
				INSERT INTO login_token_history 
				(forum_user_id, token, server_name, server_address, player_ip, player_guid, success, failure_reason)
				VALUES ($1, $2, $3, $4, $5::inet, $6, $7, $8)
			`, uID, req.Token, req.ServerName, req.ServerAddress, req.PlayerIP, req.PlayerGUID, success, reason)
		}
	}

	var forumUserID int
	var tokenID string

	// SCENARIO 1: Token provided
	if req.Token != "" {
		var expiresAt time.Time
		var usedAt *time.Time
		var usedFromIP *string
		var isActive bool

		err := h.pg.QueryRow(ctx, `
			SELECT id, forum_user_id, expires_at, used_at, used_from_ip::text, is_active
			FROM login_tokens
			WHERE token = $1
		`, req.Token).Scan(&tokenID, &forumUserID, &expiresAt, &usedAt, &usedFromIP, &isActive)

		if err != nil {
			// Don't know user ID, can't log to history table efficiently (or log with null user)
			h.errorResponse(w, http.StatusUnauthorized, "Invalid token")
			return
		}

		// Check if token is active
		if !isActive {
			logAttempt(forumUserID, false, "token_revoked")
			h.errorResponse(w, http.StatusUnauthorized, "Token has been revoked")
			return
		}

		// Check if token expired
		if time.Now().After(expiresAt) {
			logAttempt(forumUserID, false, "token_expired")
			h.errorResponse(w, http.StatusUnauthorized, "Token has expired")
			return
		}

		// Check if token was already used
		if usedAt != nil {
			// Token used! Check if we can proceed based on Trust
			// FALLTHROUGH to Trust Check below
		} else {
			// Valid unused token!
			// Mark as used
			_, err = h.pg.Exec(ctx, `
				UPDATE login_tokens 
				SET used_at = NOW(), used_from_ip = $1::inet, used_player_guid = $2
				WHERE id = $3
			`, req.PlayerIP, req.PlayerGUID, tokenID)

			if err != nil {
				h.logger.Errorw("Failed to mark token as used", "error", err)
			}
			
			// We have a winner. Trust this IP.
			// Proceed to Success Block
			goto LoginSuccess
		}
	} else {
		// SCENARIO 2: No token provided (Attempting Trusted IP Login)
		// Try to find the user based on IP
		err := h.pg.QueryRow(ctx, `
			SELECT forum_user_id 
			FROM trusted_ips 
			WHERE ip_address = $1::inet AND is_active = true 
			ORDER BY last_used_at DESC 
			LIMIT 1
		`, req.PlayerIP).Scan(&forumUserID)

		if err != nil {
			// No token AND no trusted IP match -> Fail
			h.errorResponse(w, http.StatusBadRequest, "Token required (IP not trusted)")
			return
		}
		
		// Found a user for this IP! Proceed to Trust Check (implicit success)
	}

	// TRUST CHECK BLOCK
	// We have a forumUserID and an IP. Is this IP trusted for this user?
	{
		var isTrusted bool
		err := h.pg.QueryRow(ctx, `
			SELECT EXISTS(
				SELECT 1 FROM trusted_ips 
				WHERE forum_user_id = $1 
				AND ip_address = $2::inet 
				AND is_active = true
			)
		`, forumUserID, req.PlayerIP).Scan(&isTrusted)

		if isTrusted {
			// Update stats for trusted IP
			h.pg.Exec(ctx, `
				UPDATE trusted_ips 
				SET last_used_at = NOW() 
				WHERE forum_user_id = $1 AND ip_address = $2::inet AND is_active = true
			`, forumUserID, req.PlayerIP)
			
			logAttempt(forumUserID, true, "trusted_ip_login")
			
			h.jsonResponse(w, http.StatusOK, map[string]interface{}{
				"valid":         true,
				"forum_user_id": forumUserID,
				"message":       "Logged in via Trusted IP",
				"trusted_ip":    true,
			})
			return
		}

		// If we are here, we had a valid token (since empty token falls out earlier), 
		// but the token was USED, and the IP is NOT trusted.
		// Create pending request.
		
		var pendingExists bool
		h.pg.QueryRow(ctx, `
			SELECT EXISTS(
				SELECT 1 FROM pending_ip_approvals 
				WHERE forum_user_id = $1 
				AND ip_address = $2::inet 
				AND status = 'pending'
				AND expires_at > NOW()
			)
		`, forumUserID, req.PlayerIP).Scan(&pendingExists)

		if !pendingExists {
			_, _ = h.pg.Exec(ctx, `
				INSERT INTO pending_ip_approvals 
				(forum_user_id, ip_address, player_guid, server_name, server_address)
				VALUES ($1, $2::inet, $3, $4, $5)
				ON CONFLICT (forum_user_id, ip_address) 
				DO UPDATE SET 
					player_guid = EXCLUDED.player_guid,
					server_name = EXCLUDED.server_name,
					server_address = EXCLUDED.server_address,
					requested_at = NOW(),
					expires_at = NOW() + INTERVAL '24 hours',
					status = 'pending',
					resolved_at = NULL
			`, forumUserID, req.PlayerIP, req.PlayerGUID, req.ServerName, req.ServerAddress)

			if err != nil {
				h.logger.Errorw("Failed to create pending IP approval", "error", err)
			}
		}

		logAttempt(forumUserID, false, "new_ip_pending_approval")
		h.jsonResponse(w, http.StatusForbidden, map[string]interface{}{
			"valid":            false,
			"error":            "new_ip_detected",
			"message":          "New IP detected. Please approve this IP on the website.",
			"pending_approval": true,
		})
		return
	}

LoginSuccess:
	// Add/Update Trusted IP (for fresh tokens)
	_, err := h.pg.Exec(ctx, `
		INSERT INTO trusted_ips (forum_user_id, ip_address, source, player_guid)
		VALUES ($1, $2::inet, 'token_login', $3)
		ON CONFLICT (forum_user_id, ip_address) 
		DO UPDATE SET 
			is_active = true,
			last_used_at = NOW(),
			player_guid = COALESCE(EXCLUDED.player_guid, trusted_ips.player_guid),
			revoked_at = NULL
	`, forumUserID, req.PlayerIP, req.PlayerGUID)

	if err != nil {
		h.logger.Errorw("Failed to add trusted IP", "error", err)
	}

	// Link player GUID
	if req.PlayerGUID != "" {
		_, err = h.pg.Exec(ctx, `
			INSERT INTO smf_user_mappings (smf_member_id, primary_guid, updated_at)
			VALUES ($1, $2, NOW())
			ON CONFLICT (smf_member_id) 
			DO UPDATE SET primary_guid = $2, updated_at = NOW()
		`, forumUserID, req.PlayerGUID)
		if err != nil {
			h.logger.Errorw("Failed to link GUID to user", "error", err)
		}
	}

	logAttempt(forumUserID, true, "")
	if req.Token != "" {
		h.redis.Del(ctx, "login_token:"+req.Token)
	}

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"valid":         true,
		"forum_user_id": forumUserID,
		"message":       "Identity verified successfully",
		"ip_trusted":    true,
	})
}

// GetLoginHistory returns login history for a forum user
// GET /api/v1/auth/history?forum_user_id=123
func (h *Handler) GetLoginHistory(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	forumUserIDStr := r.URL.Query().Get("forum_user_id")
	if forumUserIDStr == "" {
		h.errorResponse(w, http.StatusBadRequest, "forum_user_id is required")
		return
	}

	forumUserID := 0
	fmt.Sscanf(forumUserIDStr, "%d", &forumUserID)
	if forumUserID <= 0 {
		h.errorResponse(w, http.StatusBadRequest, "invalid forum_user_id")
		return
	}

	rows, err := h.pg.Query(ctx, `
		SELECT 
			attempt_at,
			server_name,
			server_address,
			player_ip::text,
			player_guid,
			success,
			failure_reason
		FROM login_token_history
		WHERE forum_user_id = $1
		ORDER BY attempt_at DESC
		LIMIT 20
	`, forumUserID)
	if err != nil {
		h.logger.Errorw("Failed to fetch login history", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "database error")
		return
	}
	defer rows.Close()

	type LoginHistoryEntry struct {
		AttemptAt     time.Time `json:"attempt_at"`
		ServerName    *string   `json:"server_name"`
		ServerAddress *string   `json:"server_address"`
		PlayerIP      *string   `json:"player_ip"`
		PlayerGUID    *string   `json:"player_guid"`
		Success       bool      `json:"success"`
		FailureReason *string   `json:"failure_reason"`
	}

	history := []LoginHistoryEntry{}
	for rows.Next() {
		var entry LoginHistoryEntry
		err := rows.Scan(
			&entry.AttemptAt,
			&entry.ServerName,
			&entry.ServerAddress,
			&entry.PlayerIP,
			&entry.PlayerGUID,
			&entry.Success,
			&entry.FailureReason,
		)
		if err != nil {
			h.logger.Errorw("Failed to scan login history row", "error", err)
			continue
		}
		history = append(history, entry)
	}

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"forum_user_id": forumUserID,
		"history":       history,
		"count":         len(history),
	})
}

// ============================================================================
// TRUSTED IP MANAGEMENT
// ============================================================================

// GetTrustedIPs returns all trusted IPs for a forum user
// GET /api/v1/auth/trusted-ips?forum_user_id=123
func (h *Handler) GetTrustedIPs(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	forumUserIDStr := r.URL.Query().Get("forum_user_id")
	if forumUserIDStr == "" {
		h.errorResponse(w, http.StatusBadRequest, "forum_user_id is required")
		return
	}

	forumUserID := 0
	fmt.Sscanf(forumUserIDStr, "%d", &forumUserID)
	if forumUserID <= 0 {
		h.errorResponse(w, http.StatusBadRequest, "invalid forum_user_id")
		return
	}

	rows, err := h.pg.Query(ctx, `
		SELECT 
			id::text,
			host(ip_address),
			source,
			label,
			player_guid,
			created_at,
			last_used_at
		FROM trusted_ips
		WHERE forum_user_id = $1 AND is_active = true
		ORDER BY last_used_at DESC
	`, forumUserID)
	if err != nil {
		h.logger.Errorw("Failed to fetch trusted IPs", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "database error")
		return
	}
	defer rows.Close()

	type TrustedIP struct {
		ID         string    `json:"id"`
		IPAddress  string    `json:"ip_address"`
		Source     string    `json:"source"`
		Label      *string   `json:"label"`
		PlayerGUID *string   `json:"player_guid"`
		CreatedAt  time.Time `json:"created_at"`
		LastUsedAt time.Time `json:"last_used_at"`
	}

	trustedIPs := []TrustedIP{}
	for rows.Next() {
		var ip TrustedIP
		err := rows.Scan(
			&ip.ID,
			&ip.IPAddress,
			&ip.Source,
			&ip.Label,
			&ip.PlayerGUID,
			&ip.CreatedAt,
			&ip.LastUsedAt,
		)
		if err != nil {
			h.logger.Errorw("Failed to scan trusted IP row", "error", err)
			continue
		}
		trustedIPs = append(trustedIPs, ip)
	}

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"forum_user_id": forumUserID,
		"trusted_ips":   trustedIPs,
		"count":         len(trustedIPs),
	})
}

// DeleteTrustedIP removes a trusted IP
// DELETE /api/v1/auth/trusted-ips/{id}?forum_user_id=123
func (h *Handler) DeleteTrustedIP(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()
	ipID := chi.URLParam(r, "id")

	forumUserIDStr := r.URL.Query().Get("forum_user_id")
	if forumUserIDStr == "" {
		h.errorResponse(w, http.StatusBadRequest, "forum_user_id is required")
		return
	}

	forumUserID := 0
	fmt.Sscanf(forumUserIDStr, "%d", &forumUserID)
	if forumUserID <= 0 {
		h.errorResponse(w, http.StatusBadRequest, "invalid forum_user_id")
		return
	}

	// Soft delete - mark as inactive
	result, err := h.pg.Exec(ctx, `
		UPDATE trusted_ips 
		SET is_active = false, revoked_at = NOW()
		WHERE id = $1 AND forum_user_id = $2
	`, ipID, forumUserID)

	if err != nil {
		h.logger.Errorw("Failed to delete trusted IP", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "database error")
		return
	}

	if result.RowsAffected() == 0 {
		h.errorResponse(w, http.StatusNotFound, "Trusted IP not found")
		return
	}

	h.jsonResponse(w, http.StatusOK, map[string]string{
		"status":  "deleted",
		"message": "IP removed from trusted list",
	})
}

// GetPendingIPApprovals returns pending IP approval requests
// GET /api/v1/auth/pending-ips?forum_user_id=123
func (h *Handler) GetPendingIPApprovals(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	forumUserIDStr := r.URL.Query().Get("forum_user_id")
	if forumUserIDStr == "" {
		h.errorResponse(w, http.StatusBadRequest, "forum_user_id is required")
		return
	}

	forumUserID := 0
	fmt.Sscanf(forumUserIDStr, "%d", &forumUserID)
	if forumUserID <= 0 {
		h.errorResponse(w, http.StatusBadRequest, "invalid forum_user_id")
		return
	}

	rows, err := h.pg.Query(ctx, `
		SELECT 
			id::text,
			host(ip_address),
			player_guid,
			player_name,
			server_name,
			server_address,
			requested_at,
			expires_at,
			notified_at
		FROM pending_ip_approvals
		WHERE forum_user_id = $1 AND status = 'pending' AND expires_at > NOW()
		ORDER BY requested_at DESC
	`, forumUserID)
	if err != nil {
		h.logger.Errorw("Failed to fetch pending IPs", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "database error")
		return
	}
	defer rows.Close()

	type PendingIP struct {
		ID            string     `json:"id"`
		IPAddress     string     `json:"ip_address"`
		PlayerGUID    *string    `json:"player_guid"`
		PlayerName    *string    `json:"player_name"`
		ServerName    *string    `json:"server_name"`
		ServerAddress *string    `json:"server_address"`
		RequestedAt   time.Time  `json:"requested_at"`
		ExpiresAt     time.Time  `json:"expires_at"`
		NotifiedAt    *time.Time `json:"notified_at"`
	}

	pendingIPs := []PendingIP{}
	for rows.Next() {
		var ip PendingIP
		err := rows.Scan(
			&ip.ID,
			&ip.IPAddress,
			&ip.PlayerGUID,
			&ip.PlayerName,
			&ip.ServerName,
			&ip.ServerAddress,
			&ip.RequestedAt,
			&ip.ExpiresAt,
			&ip.NotifiedAt,
		)
		if err != nil {
			h.logger.Errorw("Failed to scan pending IP row", "error", err)
			continue
		}
		pendingIPs = append(pendingIPs, ip)
	}

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"forum_user_id": forumUserID,
		"pending_ips":   pendingIPs,
		"count":         len(pendingIPs),
	})
}

// ResolvePendingIPApproval approves or denies a pending IP request
// POST /api/v1/auth/pending-ips/{id}
// Body: {"action": "approve"} or {"action": "deny"}
func (h *Handler) ResolvePendingIPApproval(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()
	approvalID := chi.URLParam(r, "id")

	var req struct {
		ForumUserID int    `json:"forum_user_id"`
		Action      string `json:"action"` // "approve" or "deny"
		Label       string `json:"label"`  // Optional label for approved IP
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	if req.ForumUserID <= 0 {
		h.errorResponse(w, http.StatusBadRequest, "forum_user_id is required")
		return
	}

	if req.Action != "approve" && req.Action != "deny" {
		h.errorResponse(w, http.StatusBadRequest, "action must be 'approve' or 'deny'")
		return
	}

	// Get the pending request details
	var ipAddress, playerGUID string
	err := h.pg.QueryRow(ctx, `
		SELECT host(ip_address), COALESCE(player_guid, '')
		FROM pending_ip_approvals
		WHERE id = $1 AND forum_user_id = $2 AND status = 'pending'
	`, approvalID, req.ForumUserID).Scan(&ipAddress, &playerGUID)

	if err != nil {
		h.errorResponse(w, http.StatusNotFound, "Pending request not found")
		return
	}

	// Update the pending request status
	status := "denied"
	if req.Action == "approve" {
		status = "approved"
	}

	_, err = h.pg.Exec(ctx, `
		UPDATE pending_ip_approvals
		SET status = $2, resolved_at = NOW()
		WHERE id = $1
	`, approvalID, status)

	if err != nil {
		h.logger.Errorw("Failed to update pending IP", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "database error")
		return
	}

	// If approved, add to trusted IPs
	if req.Action == "approve" {
		label := req.Label
		if label == "" {
			label = "Approved manually"
		}

		_, err = h.pg.Exec(ctx, `
			INSERT INTO trusted_ips (forum_user_id, ip_address, source, label, player_guid)
			VALUES ($1, $2::inet, 'manual_approval', $3, NULLIF($4, ''))
			ON CONFLICT (forum_user_id, ip_address) 
			DO UPDATE SET 
				is_active = true,
				last_used_at = NOW(),
				label = EXCLUDED.label,
				revoked_at = NULL
		`, req.ForumUserID, ipAddress, label, playerGUID)

		if err != nil {
			h.logger.Errorw("Failed to add trusted IP after approval", "error", err)
		}
	}

	message := "IP request denied"
	if req.Action == "approve" {
		message = "IP approved and added to trusted list"
	}

	h.jsonResponse(w, http.StatusOK, map[string]string{
		"status":  status,
		"message": message,
	})
}

// MarkPendingIPsNotified marks pending IP approvals as email-notified
// POST /api/v1/auth/pending-ips/mark-notified
// Body: {"forum_user_id": 1, "ids": ["uuid1", "uuid2", ...]}
func (h *Handler) MarkPendingIPsNotified(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	var req struct {
		ForumUserID int      `json:"forum_user_id"`
		IDs         []string `json:"ids"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	if req.ForumUserID <= 0 {
		h.errorResponse(w, http.StatusBadRequest, "forum_user_id is required")
		return
	}

	if len(req.IDs) == 0 {
		h.jsonResponse(w, http.StatusOK, map[string]interface{}{
			"updated": 0,
		})
		return
	}

	// Mark the specified pending IPs as notified
	result, err := h.pg.Exec(ctx, `
		UPDATE pending_ip_approvals
		SET notified_at = NOW()
		WHERE forum_user_id = $1 
		  AND id = ANY($2::uuid[]) 
		  AND status = 'pending' 
		  AND notified_at IS NULL
	`, req.ForumUserID, req.IDs)

	if err != nil {
		h.logger.Errorw("Failed to mark pending IPs as notified", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "database error")
		return
	}

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"updated": result.RowsAffected(),
	})
}

// OAuth callbacks (Discord/Steam)
func (h *Handler) DiscordAuth(w http.ResponseWriter, r *http.Request) {
	// TODO: Implement Discord OAuth redirect
	http.Redirect(w, r, "https://discord.com/oauth2/authorize?...", http.StatusTemporaryRedirect)
}

func (h *Handler) DiscordCallback(w http.ResponseWriter, r *http.Request) {
	// TODO: Handle Discord OAuth callback
	h.jsonResponse(w, http.StatusOK, map[string]string{"status": "ok"})
}

func (h *Handler) SteamAuth(w http.ResponseWriter, r *http.Request) {
	// TODO: Implement Steam OpenID redirect
	http.Redirect(w, r, "https://steamcommunity.com/openid/login?...", http.StatusTemporaryRedirect)
}

func (h *Handler) SteamCallback(w http.ResponseWriter, r *http.Request) {
	// TODO: Handle Steam OpenID callback
	h.jsonResponse(w, http.StatusOK, map[string]string{"status": "ok"})
}

// InitIdentityClaim starts the identity claim process
func (h *Handler) InitIdentityClaim(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	// TODO: Get user ID from JWT
	userID := uuid.New()

	code := generateUserCode()
	claim := models.IdentityClaim{
		ID:        uuid.New(),
		UserID:    userID,
		Code:      code,
		Status:    "pending",
		ExpiresAt: time.Now().Add(10 * time.Minute),
		CreatedAt: time.Now(),
	}

	// Store pending claim
	data, _ := json.Marshal(claim)
	h.redis.Set(ctx, "claim:"+code, data, 10*time.Minute)

	h.jsonResponse(w, http.StatusOK, map[string]interface{}{
		"code":       code,
		"expires_at": claim.ExpiresAt,
		"message":    fmt.Sprintf("In game, type: claim %s", code),
	})
}

// VerifyIdentityClaim verifies a claim code from the game
func (h *Handler) VerifyIdentityClaim(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	var req struct {
		Code       string `json:"code"`
		PlayerGUID string `json:"player_guid"`
		PlayerName string `json:"player_name"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	data, err := h.redis.Get(ctx, "claim:"+req.Code).Bytes()
	if err != nil {
		h.errorResponse(w, http.StatusNotFound, "Invalid or expired code")
		return
	}

	var claim models.IdentityClaim
	json.Unmarshal(data, &claim)

	// Link identity in Postgres
	_, err = h.pg.Exec(ctx, `
		INSERT INTO user_identities (id, user_id, player_guid, player_name, is_primary, verified_at, created_at)
		VALUES ($1, $2, $3, $4, $5, $6, $6)
		ON CONFLICT (user_id, player_guid) DO UPDATE SET player_name = $4, verified_at = $6
	`, uuid.New(), claim.UserID, req.PlayerGUID, req.PlayerName, true, time.Now())
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Failed to link identity")
		return
	}

	// Clean up
	h.redis.Del(ctx, "claim:"+req.Code)

	h.jsonResponse(w, http.StatusOK, map[string]string{"status": "claimed"})
}

// ============================================================================
// USER HANDLERS
// ============================================================================

func (h *Handler) GetCurrentUser(w http.ResponseWriter, r *http.Request) {
	// TODO: Get user from JWT and return profile
	h.jsonResponse(w, http.StatusOK, map[string]string{"status": "ok"})
}

func (h *Handler) UpdateCurrentUser(w http.ResponseWriter, r *http.Request) {
	// TODO: Update user profile
	h.jsonResponse(w, http.StatusOK, map[string]string{"status": "updated"})
}

func (h *Handler) GetUserIdentities(w http.ResponseWriter, r *http.Request) {
	// TODO: Return linked game identities
	h.jsonResponse(w, http.StatusOK, []interface{}{})
}

func (h *Handler) UnlinkIdentity(w http.ResponseWriter, r *http.Request) {
	// TODO: Unlink a game identity
	h.jsonResponse(w, http.StatusOK, map[string]string{"status": "unlinked"})
}

// ============================================================================
// SERVER HANDLERS
// ============================================================================

func (h *Handler) ListServers(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	rows, err := h.pg.Query(ctx, `
		SELECT id, name, address, port, region, is_active, is_official, last_seen, total_matches
		FROM servers
		WHERE is_active = true
		ORDER BY last_seen DESC
	`)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Query failed")
		return
	}
	defer rows.Close()

	var servers []models.Server
	for rows.Next() {
		var s models.Server
		if err := rows.Scan(&s.ID, &s.Name, &s.Address, &s.Port, &s.Region, &s.IsActive, &s.IsOfficial, &s.LastSeen, &s.TotalMatches); err != nil {
			continue
		}
		servers = append(servers, s)
	}

	h.jsonResponse(w, http.StatusOK, servers)
}

func (h *Handler) GetServer(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	ctx := r.Context()

	var s models.Server
	err := h.pg.QueryRow(ctx, `
		SELECT id, name, address, port, region, description, is_active, is_official, 
		       last_seen, total_matches, total_players, created_at
		FROM servers WHERE id = $1
	`, id).Scan(
		&s.ID, &s.Name, &s.Address, &s.Port, &s.Region, &s.Description,
		&s.IsActive, &s.IsOfficial, &s.LastSeen, &s.TotalMatches, &s.TotalPlayers, &s.CreatedAt,
	)
	if err != nil {
		h.errorResponse(w, http.StatusNotFound, "Server not found")
		return
	}

	h.jsonResponse(w, http.StatusOK, s)
}

func (h *Handler) RegisterServer(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Name        string `json:"name"`
		Address     string `json:"address"`
		Port        int    `json:"port"`
		Region      string `json:"region"`
		Description string `json:"description"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	ctx := r.Context()
	id := uuid.New()
	token := generateSecureCode(32)

	// TODO: Get owner from JWT
	ownerID := uuid.New()

	_, err := h.pg.Exec(ctx, `
		INSERT INTO servers (id, name, token, owner_id, address, port, region, description, is_active, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, true, $9, $9)
	`, id, req.Name, token, ownerID, req.Address, req.Port, req.Region, req.Description, time.Now())
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Failed to register server")
		return
	}

	h.jsonResponse(w, http.StatusCreated, map[string]string{
		"id":    id.String(),
		"token": token,
	})
}

func (h *Handler) UpdateServer(w http.ResponseWriter, r *http.Request) {
	h.jsonResponse(w, http.StatusOK, map[string]string{"status": "updated"})
}

// ReportMatchResult updates a match score and advances the winner
func (h *Handler) ReportMatchResult(w http.ResponseWriter, r *http.Request) {
	matchID := chi.URLParam(r, "matchID")
	ctx := r.Context()

	var req struct {
		Score1   int    `json:"score1"`
		Score2   int    `json:"score2"`
		WinnerID string `json:"winner_id"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.errorResponse(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	tx, err := h.pg.Begin(ctx)
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Failed to start transaction")
		return
	}
	defer tx.Rollback(ctx)

	// 1. Update current match
	_, err = tx.Exec(ctx, `
		UPDATE tournament_matches 
		SET participant1_score = $2, participant2_score = $3, winner_id = $4, status = 'completed', completed_at = $5
		WHERE id = $1
	`, matchID, req.Score1, req.Score2, req.WinnerID, time.Now())

	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Failed to update match")
		return
	}

	// 2. Advance winner to next match
	// Find the current match's position in bracket
	var tournamentID uuid.UUID
	var bracketType string
	var roundNum, matchNum int

	err = tx.QueryRow(ctx, `
		SELECT tournament_id, bracket_type, round_number, match_number 
		FROM tournament_matches WHERE id = $1
	`, matchID).Scan(&tournamentID, &bracketType, &roundNum, &matchNum)

	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Failed to find match info")
		return
	}

	// Simple single elimination logic: Next match is Round+1, MatchNum = ceil(MatchNum/2)
	// Slot is 1 if MatchNum is odd, 2 if even
	nextRound := roundNum + 1
	nextMatchNum := (matchNum + 1) / 2
	slot := 1
	if matchNum%2 == 0 {
		slot = 2
	}

	// Update next match if it exists
	if slot == 1 {
		_, err = tx.Exec(ctx, `
			UPDATE tournament_matches
			SET participant1_id = $4
			WHERE tournament_id = $1 AND bracket_type = $2 AND round_number = $3 AND match_number = $5
		`, tournamentID, bracketType, nextRound, req.WinnerID, nextMatchNum)
	} else {
		_, err = tx.Exec(ctx, `
			UPDATE tournament_matches
			SET participant2_id = $4
			WHERE tournament_id = $1 AND bracket_type = $2 AND round_number = $3 AND match_number = $5
		`, tournamentID, bracketType, nextRound, req.WinnerID, nextMatchNum)
	}

	if err != nil {
		h.logger.Errorw("Failed to advance bracket", "error", err)
		// Don't fail the request, just log it (might be final round)
	}

	if err := tx.Commit(ctx); err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Failed to commit transaction")
		return
	}

	h.jsonResponse(w, http.StatusOK, map[string]string{"status": "match_reported"})
}

func (h *Handler) RotateServerToken(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	ctx := r.Context()

	newToken := generateSecureCode(32)

	_, err := h.pg.Exec(ctx, `
		UPDATE servers SET token = $2, updated_at = $3 WHERE id = $1
	`, id, newToken, time.Now())
	if err != nil {
		h.errorResponse(w, http.StatusInternalServerError, "Failed to rotate token")
		return
	}

	h.jsonResponse(w, http.StatusOK, map[string]string{"token": newToken})
}

// ============================================================================
// HELPERS
// ============================================================================

func generateSecureCode(length int) string {
	bytes := make([]byte, length)
	rand.Read(bytes)
	return hex.EncodeToString(bytes)
}

func generateUserCode() string {
	// Generate short human-readable code like "MOH-9921"
	bytes := make([]byte, 2)
	rand.Read(bytes)
	return fmt.Sprintf("MOH-%04d", int(bytes[0])<<8|int(bytes[1])%10000)
}

func derefString(s *string) string {
	if s == nil {
		return ""
	}
	return *s
}

func derefInt(i *int) int {
	if i == nil {
		return 0
	}
	return *i
}

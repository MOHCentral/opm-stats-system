package handlers

import (
	"net/http"

	"github.com/go-chi/chi/v5"
	"github.com/openmohaa/stats-api/internal/logic"
)

// ============================================================================
// ACHIEVEMENT ENDPOINTS
// ============================================================================

// GetMatchAchievements returns achievements earned in a specific match
// GET /api/v1/achievements/match/{match_id}?player_id={guid}
func (h *Handler) GetMatchAchievements(w http.ResponseWriter, r *http.Request) {
	matchID := chi.URLParam(r, "match_id")
	playerID := r.URL.Query().Get("player_id")

	if matchID == "" || playerID == "" {
		h.errorResponse(w, http.StatusBadRequest, "Missing match_id or player_id")
		return
	}

	list, err := h.achievements.GetAchievements(r.Context(), logic.ScopeMatch, matchID, playerID)
	if err != nil {
		h.logger.Errorw("Failed to get match achievements", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to get achievements")
		return
	}
	h.jsonResponse(w, http.StatusOK, list)
}

// GetTournamentAchievements returns achievements earned in a tournament
// GET /api/v1/achievements/tournament/{tournament_id}?player_id={guid}
func (h *Handler) GetTournamentAchievements(w http.ResponseWriter, r *http.Request) {
	tournID := chi.URLParam(r, "tournament_id")
	playerID := r.URL.Query().Get("player_id")

	if tournID == "" || playerID == "" {
		h.errorResponse(w, http.StatusBadRequest, "Missing tournament_id or player_id")
		return
	}

	list, err := h.achievements.GetAchievements(r.Context(), logic.ScopeTournament, tournID, playerID)
	if err != nil {
		h.logger.Errorw("Failed to get tournament achievements", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to get achievements")
		return
	}
	h.jsonResponse(w, http.StatusOK, list)
}

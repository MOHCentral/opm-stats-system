package handlers

import (
	"net/http"


)

// ============================================================================
// SERVER STATS ENDPOINTS
// ============================================================================

// GetServerPulse returns high-level "vital signs" of the server
// GET /api/v1/stats/server/pulse
func (h *Handler) GetServerPulse(w http.ResponseWriter, r *http.Request) {
	pulse, err := h.serverStats.GetServerPulse(r.Context())
	if err != nil {
		h.logger.Errorw("Failed to get server pulse", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to get server pulse")
		return
	}
	h.jsonResponse(w, http.StatusOK, pulse)
}

// GetServerActivity returns a heatmap of activity
// GET /api/v1/stats/server/activity
func (h *Handler) GetServerActivity(w http.ResponseWriter, r *http.Request) {
	activity, err := h.serverStats.GetGlobalActivity(r.Context())
	if err != nil {
		h.logger.Errorw("Failed to get server activity", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to get server activity")
		return
	}
	h.jsonResponse(w, http.StatusOK, activity)
}

// GetServerMaps returns map popularity stats
// GET /api/v1/stats/server/maps
func (h *Handler) GetServerMaps(w http.ResponseWriter, r *http.Request) {
	maps, err := h.serverStats.GetMapPopularity(r.Context())
	if err != nil {
		h.logger.Errorw("Failed to get map popularity", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Failed to get map stats")
		return
	}
	h.jsonResponse(w, http.StatusOK, maps)
}

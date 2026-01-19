package handlers

import (
	"fmt"
	"net/http"
	"sort"
)

// GetLeaderboardCards returns the Top 3 players for ALL 40 dashboard categories
// This uses a single massive aggregation query for performance
func (h *Handler) GetLeaderboardCards(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	// 1. Aggregation Query
	// We gather all raw metrics in one pass
	query := `
		SELECT 
			actor_id,
			anyLast(actor_name) as name,
			
			-- A. Lethality
			countIf(event_type = 'player_kill') as kills,
			countIf(event_type = 'player_death') as deaths,
			countIf(event_type = 'player_headshot') as headshots,
			countIf(event_type = 'weapon_fire') as shots_fired,
			countIf(event_type = 'weapon_hit') as shots_hit,
			sum(toInt64OrZero(JSONExtractString(raw_json, 'damage'))) as damage,
			countIf(event_type = 'player_bash') as bash_kills,
			countIf(event_type = 'grenade_kill') as grenade_kills, 
			countIf(event_type = 'player_roadkill') as roadkills,
			countIf(event_type = 'player_telefragged') as telefrags,
			countIf(event_type = 'player_crushed') as crushed,
			countIf(event_type = 'player_teamkill') as teamkills,
			countIf(event_type = 'player_suicide') as suicides,

			-- B. Weapon Handling
			countIf(event_type = 'weapon_reload') as reloads,
			countIf(event_type = 'weapon_change') as weapon_swaps,
			countIf(event_type = 'weapon_no_ammo') as no_ammo,
			countIf(event_type IN ('weapon_drop', 'item_pickup') AND JSONExtractString(raw_json, 'item_type') = 'weapon') as looter,

			-- C. Movement
			sum(toFloat64OrZero(JSONExtractString(raw_json, 'walked'))) as walked,
			sum(toFloat64OrZero(JSONExtractString(raw_json, 'sprinted'))) as sprinted,
			sum(toFloat64OrZero(JSONExtractString(raw_json, 'swam'))) as swam,
			sum(toFloat64OrZero(JSONExtractString(raw_json, 'driven'))) as driven,
			countIf(event_type = 'player_jump') as jumps,
			countIf(event_type = 'player_crouch') as crouch_events, -- Using events for now, duration requires session tracking
			countIf(event_type = 'player_prone') as prone_events,
			countIf(event_type = 'ladder_mount') as ladders,

			-- D. Survival & Items
			sumIf(toInt64OrZero(JSONExtractString(raw_json, 'amount')), event_type = 'health_pickup') as health_picked,
			countIf(event_type = 'ammo_pickup') as ammo_picked,
			countIf(event_type = 'armor_pickup') as armor_picked,
			countIf(event_type = 'item_pickup') as items_picked,

			-- E. Objectives
			countIf(event_type = 'team_win') as wins, -- Simplification (needs detailed check)
			countIf(event_type = 'objective_completed') as objectives_done,
			countIf(event_type = 'round_end') as rounds_played,
			countIf(event_type = 'game_end') as games_finished,

			-- F. Vehicles
			countIf(event_type = 'vehicle_enter') as vehicle_enter,
			countIf(event_type = 'turret_enter') as turret_enter,
			countIf(event_type = 'vehicle_destroyed') as vehicle_kills,

			-- G. Social
			countIf(event_type = 'player_say') as chat_msgs,
			countIf(event_type = 'player_spectate') as spectating,
			countIf(event_type = 'door_open') as doors_opened

		FROM raw_events
		WHERE actor_id != 'world' AND actor_id != ''
		GROUP BY actor_id
		HAVING kills > 5 OR deaths > 5 OR shots_fired > 5 -- Basic filter to remove noise
	`

	rows, err := h.ch.Query(ctx, query)
	if err != nil {
		h.logger.Errorw("Leaderboard cards query failed", "error", err)
		h.errorResponse(w, http.StatusInternalServerError, "Database error")
		return
	}
	defer rows.Close()

	// 2. Scan Results
	type PlayerAgg struct {
		ID   string
		Name string
		// Map of metrics
		Metrics map[string]float64
	}
	
	players := []PlayerAgg{}

	for rows.Next() {
		var p PlayerAgg
		p.Metrics = make(map[string]float64)
		
		var (
			kills, deaths, headshots, shotsFired, shotsHit, damage, bash, nade, road, tele, crush, tk, self float64
			reloads, swaps, noAmmo, looter float64
			walked, sprinted, swam, driven, jumps, crouch, prone, ladders float64
			health, ammo, armor, items float64
			wins, obj, rounds, games float64
			vEnter, tEnter, vKills float64
			chat, spec, doors float64
		)

		if err := rows.Scan(
			&p.ID, &p.Name,
			&kills, &deaths, &headshots, &shotsFired, &shotsHit, &damage, &bash, &nade, &road, &tele, &crush, &tk, &self,
			&reloads, &swaps, &noAmmo, &looter,
			&walked, &sprinted, &swam, &driven, &jumps, &crouch, &prone, &ladders,
			&health, &ammo, &armor, &items,
			&wins, &obj, &rounds, &games,
			&vEnter, &tEnter, &vKills,
			&chat, &spec, &doors,
		); err != nil {
			continue
		}

		// Populate Map
		p.Metrics["kills"] = kills
		p.Metrics["deaths"] = deaths
		p.Metrics["headshots"] = headshots
		p.Metrics["accuracy"] = 0
		if shotsFired > 0 { p.Metrics["accuracy"] = (shotsHit / shotsFired) * 100 }
		p.Metrics["kd"] = kills
		if deaths > 0 { p.Metrics["kd"] = kills / deaths }
		p.Metrics["shots_fired"] = shotsFired
		p.Metrics["damage"] = damage
		p.Metrics["bash_kills"] = bash
		p.Metrics["grenade_kills"] = nade
		p.Metrics["roadkills"] = road
		p.Metrics["telefrags"] = tele
		p.Metrics["crushed"] = crush
		p.Metrics["teamkills"] = tk
		p.Metrics["suicides"] = self

		p.Metrics["reloads"] = reloads
		p.Metrics["weapon_swaps"] = swaps
		p.Metrics["no_ammo"] = noAmmo
		p.Metrics["looter"] = looter

		p.Metrics["distance"] = walked + sprinted
		p.Metrics["sprinted"] = sprinted
		p.Metrics["swam"] = swam
		p.Metrics["driven"] = driven
		p.Metrics["jumps"] = jumps
		p.Metrics["crouch_time"] = crouch
		p.Metrics["prone_time"] = prone
		p.Metrics["ladders"] = ladders

		p.Metrics["health_picked"] = health
		p.Metrics["ammo_picked"] = ammo
		p.Metrics["armor_picked"] = armor
		p.Metrics["items_picked"] = items

		p.Metrics["wins"] = wins
		p.Metrics["objectives_done"] = obj
		p.Metrics["rounds_played"] = rounds
		p.Metrics["games_finished"] = games

		p.Metrics["vehicle_enter"] = vEnter
		p.Metrics["turret_enter"] = tEnter
		p.Metrics["vehicle_kills"] = vKills

		p.Metrics["chat_msgs"] = chat
		p.Metrics["spectating"] = spec
		p.Metrics["doors_opened"] = doors

		players = append(players, p)
	}

	// 3. Process Top 3 for each category
	// Define the categories we want to extract
	categories := []string{
		"kills", "deaths", "kd", "headshots", "accuracy", "shots_fired", "damage", "bash_kills", "grenade_kills",
		"roadkills", "telefrags", "crushed", "teamkills", "suicides",
		"reloads", "weapon_swaps", "no_ammo", "looter",
		"distance", "sprinted", "swam", "driven", "jumps", "crouch_time", "prone_time", "ladders",
		"health_picked", "ammo_picked", "armor_picked", "items_picked",
		"wins", "objectives_done", "rounds_played", "games_finished",
		"vehicle_enter", "turret_enter", "vehicle_kills",
		"chat_msgs", "spectating", "doors_opened",
	}

	result := make(map[string][]map[string]interface{})
	
	for _, cat := range categories {
		// Create a slice for this category
		type entry struct {
			Name string
			Value float64
		}
		list := make([]entry, 0, len(players))
		for _, p := range players {
			if val := p.Metrics[cat]; val > 0 {
				list = append(list, entry{Name: p.Name, Value: val})
			}
		}

		// Sort Descending
		sort.Slice(list, func(i, j int) bool {
			return list[i].Value > list[j].Value
		})

		// Take top 3
		top3 := []map[string]interface{}{}
		for i := 0; i < 3 && i < len(list); i++ {
			valStr := fmt.Sprintf("%.0f", list[i].Value)
			// Special formatting
			if cat == "kd" { valStr = fmt.Sprintf("%.2f", list[i].Value) }
			if cat == "accuracy" { valStr = fmt.Sprintf("%.1f%%", list[i].Value) }
			if cat == "distance" || cat == "sprinted" { valStr = fmt.Sprintf("%.0fm", list[i].Value) }

			top3 = append(top3, map[string]interface{}{
				"name": list[i].Name,
				"value": valStr,
			})
		}
		result[cat] = top3
	}

	h.jsonResponse(w, http.StatusOK, result)
}

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
			
			-- A. Lethality & Combat
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
			countIf(event_type = 'player_kill' AND JSONExtractString(raw_json, 'mod') = 'unknown') as mystery_kills,

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
			countIf(event_type = 'player_crouch') as crouch_events,
			countIf(event_type = 'player_prone') as prone_events,
			countIf(event_type = 'ladder_mount') as ladders,

			-- D. Survival & Items
			sumIf(toInt64OrZero(JSONExtractString(raw_json, 'amount')), event_type = 'health_pickup') as health_picked,
			countIf(event_type = 'ammo_pickup') as ammo_picked,
			countIf(event_type = 'armor_pickup') as armor_picked,
			countIf(event_type = 'item_pickup') as items_picked,

			-- E. Objectives & Game Flow
			countIf(event_type = 'team_win') as wins,
			countIf(event_type = 'objective_completed') as objectives_done,
			countIf(event_type = 'round_end') as rounds_played,
			countIf(event_type = 'game_end') as games_finished,

			-- F. Vehicles
			countIf(event_type = 'vehicle_enter') as vehicle_enter,
			countIf(event_type = 'turret_enter') as turret_enter,
			countIf(event_type = 'vehicle_destroyed') as vehicle_kills,

			-- G. Social & Misc
			countIf(event_type = 'player_say') as chat_msgs,
			countIf(event_type = 'player_spectate') as spectating,
			countIf(event_type = 'door_open') as doors_opened,
			
			-- H. Creative Stats (Phase 2 Additions)
			countIf(event_type IN ('ladder_mount', 'player_jump')) as verticality,
			uniqIf(actor_weapon, event_type = 'player_kill') as unique_weapon_kills,
			countIf(event_type = 'item_drop') as items_dropped,
			countIf(event_type = 'vehicle_collision') as vehicle_collisions,
			countIf(event_type = 'player_kill' AND target_is_bot = 1) as bot_kills,

            -- New specifics
            countIf(event_type = 'weapon_drop') as weapon_drops,
            countIf(event_type = 'weapon_reload') as reload_count,
            countIf(event_type = 'ladder_mount') as ladder_mounts,
            countIf(event_type = 'player_crouch') as manual_crouches

		FROM raw_events
		WHERE actor_id != 'world' AND actor_id != ''
		GROUP BY actor_id
		HAVING kills > 0 OR deaths > 0 OR shots_fired > 0
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
		ID      string
		Name    string
		Metrics map[string]float64
	}
	
	players := []PlayerAgg{}

	for rows.Next() {
		var p PlayerAgg
		p.Metrics = make(map[string]float64)
		
		var (
			kills, deaths, headshots, shotsFired, shotsHit, damage, bash, nade, road, tele, crush, tk, self, mystery float64
			reloads, swaps, noAmmo, looter float64
			walked, sprinted, swam, driven, jumps, crouch, prone, ladders float64
			health, ammo, armor, items float64
			wins, obj, rounds, games float64
			vEnter, tEnter, vKills float64
			chat, spec, doors float64
			// New vars
			verticality, uniqueWeapons, itemsDropped, roadRage, botKills float64
            wepDrops, reloadCnt, ladMnt, manCrouch float64
		)

		if err := rows.Scan(
			&p.ID, &p.Name,
			&kills, &deaths, &headshots, &shotsFired, &shotsHit, &damage, &bash, &nade, &road, &tele, &crush, &tk, &self, &mystery,
			&reloads, &swaps, &noAmmo, &looter,
			&walked, &sprinted, &swam, &driven, &jumps, &crouch, &prone, &ladders,
			&health, &ammo, &armor, &items,
			&wins, &obj, &rounds, &games,
			&vEnter, &tEnter, &vKills,
			&chat, &spec, &doors,
			&verticality, &uniqueWeapons, &itemsDropped, &roadRage, &botKills,
            &wepDrops, &reloadCnt, &ladMnt, &manCrouch,
		); err != nil {
			h.logger.Errorw("Row scan failed", "error", err)
			continue
		}

		// Basic Metrics
		p.Metrics["kills"] = kills
		p.Metrics["deaths"] = deaths
		p.Metrics["headshots"] = headshots
		p.Metrics["shots_fired"] = shotsFired
		p.Metrics["shots_hit"] = shotsHit
		p.Metrics["damage"] = damage
		p.Metrics["bash_kills"] = bash
		p.Metrics["grenade_kills"] = nade
		p.Metrics["roadkills"] = road
		p.Metrics["vehicle_kills"] = vKills
		p.Metrics["telefrags"] = tele
		p.Metrics["crushed"] = crush
		p.Metrics["teamkills"] = tk
		p.Metrics["suicides"] = self
		
		// Derived Combat Metrics
		p.Metrics["kd"] = kills
		if deaths > 0 { p.Metrics["kd"] = kills / deaths }
		
		p.Metrics["accuracy"] = 0
		if shotsFired > 0 { p.Metrics["accuracy"] = (shotsHit / shotsFired) * 100 }
		
		p.Metrics["headshot_ratio"] = 0
		if kills > 0 { p.Metrics["headshot_ratio"] = (headshots / kills) * 100 }
		
		// Creative / Fun Stats
		p.Metrics["trigger_happy"] = shotsFired // Most shots fired
		p.Metrics["stormtrooper"] = shotsFired // Most shots missed (if accuracy low)
		if shotsFired > 100 && p.Metrics["accuracy"] < 15 {
			p.Metrics["stormtrooper"] = shotsFired // High volume, low aim
		} else {
			p.Metrics["stormtrooper"] = 0
		}
		
		p.Metrics["pacifist"] = 0
		if kills == 0 && games > 0 {
			p.Metrics["pacifist"] = games // Games played without killing
		}
		
		p.Metrics["executioner"] = headshots 
		p.Metrics["gravedigger"] = bash
		p.Metrics["demolitionist"] = nade
		
		// New Creative Stats
		p.Metrics["verticality"] = verticality
		p.Metrics["swiss_army_knife"] = uniqueWeapons
		p.Metrics["the_architect"] = itemsDropped
		p.Metrics["road_rage"] = roadRage
		p.Metrics["bot_bully"] = botKills
		if kills > 0 {
		    p.Metrics["bot_bully_ratio"] = botKills / kills
		}

        // Extended Stats (Phase 2)
        p.Metrics["butterfingers"] = wepDrops
        p.Metrics["ocd_reloading"] = reloadCnt
        p.Metrics["fireman"] = ladMnt
        p.Metrics["sneaky"] = manCrouch
        p.Metrics["chatterbox"] = chat

		// Movement
		totalDist := walked + sprinted + swam
		p.Metrics["distance"] = totalDist
		p.Metrics["sprinted"] = sprinted
		p.Metrics["swam"] = swam
		p.Metrics["driven"] = driven
		p.Metrics["jumps"] = jumps
		p.Metrics["ladders"] = ladders
		p.Metrics["marathon"] = totalDist
		
		p.Metrics["bunny_hopper"] = jumps
		p.Metrics["camper"] = 0
		if kills > 5 && totalDist < 1000 { // High kills, low movement
			p.Metrics["camper"] = kills
		}

		// Items & Survival
		p.Metrics["health_picked"] = health
		p.Metrics["ammo_picked"] = ammo
		p.Metrics["armor_picked"] = armor
		p.Metrics["items_picked"] = items
		p.Metrics["medic"] = health // Most health picked up
		p.Metrics["loot_goblin"] = items

		// Social & Misc
		p.Metrics["watcher"] = spec
		p.Metrics["door_opener"] = doors
		
		players = append(players, p)
	}

	// 3. Process Top 3 for each category
	categories := []string{
		"kills", "deaths", "kd", "headshots", "accuracy", "headshot_ratio",
		"damage", "bash_kills", "grenade_kills", "roadkills", "telefrags", "crushed", "teamkills", "suicides",
		"executioner", "trigger_happy", "stormtrooper", "gravedigger", "demolitionist",
		"reloads", "weapon_swaps", "no_ammo", "looter",
		"distance", "sprinted", "swam", "driven", "jumps", "ladders",
		"marathon", "bunny_hopper", "camper",
		"health_picked", "ammo_picked", "armor_picked", "items_picked", "medic", "loot_goblin",
		"wins", "objectives_done", "rounds_played", "games_finished", "pacifist",
		"vehicle_enter", "turret_enter", "vehicle_kills",
		"chat_msgs", "spectating", "doors_opened", "watcher", "door_opener",
		"verticality", "swiss_army_knife", "the_architect", "road_rage", "bot_bully",
        "butterfingers", "ocd_reloading", "fireman", "sneaky", "chatterbox",
	}

	result := make(map[string][]map[string]interface{})
	
	for _, cat := range categories {
		// Flatten structure for sorting
		type entry struct {
			Name string
			Value float64
			ID string
		}
		list := make([]entry, 0, len(players))
		for _, p := range players {
			if val := p.Metrics[cat]; val > 0 {
				list = append(list, entry{Name: p.Name, Value: val, ID: p.ID})
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
			
			// Formatting rules
			switch cat {
			case "kd":
				valStr = fmt.Sprintf("%.2f", list[i].Value)
			case "accuracy", "headshot_ratio":
				valStr = fmt.Sprintf("%.1f%%", list[i].Value)
			case "distance", "sprinted", "swam", "driven", "marathon":
				valStr = fmt.Sprintf("%.0fm", list[i].Value)
			case "playtime", "spectating", "watcher":
				// assuming events are just counts for now, but if time:
				// valStr = fmt.Sprintf("%.1fh", list[i].Value / 3600)
			}

			top3 = append(top3, map[string]interface{}{
				"name": list[i].Name,
				"value": valStr,
				"id": list[i].ID,
			})
		}
		result[cat] = top3
	}

	h.jsonResponse(w, http.StatusOK, result)
}

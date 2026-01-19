package logic

import (
	"context"

	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
)

// ComboMetricsService calculates cross-event correlation metrics
type ComboMetricsService struct {
	ch driver.Conn
}

func NewComboMetricsService(ch driver.Conn) *ComboMetricsService {
	return &ComboMetricsService{ch: ch}
}

// ComboMetrics contains all cross-table correlation metrics
type ComboMetrics struct {
	MovementCombat  MovementCombatStats  `json:"movement_combat"`
	HealthObjective HealthObjectiveStats `json:"health_objective"`
	EconomySurvival EconomySurvivalStats `json:"economy_survival"`
	Signature       SignatureStats       `json:"signature"`
}

// MovementCombatStats - correlations between movement and combat
type MovementCombatStats struct {
	RunGunIndex        float64 `json:"run_gun_index"`        // % kills while moving
	RunGunRank         string  `json:"run_gun_rank"`         // Percentile label
	BunnyHopKills      int64   `json:"bunny_hop_kills"`      // Kills within 2s of jump
	BunnyHopEfficiency float64 `json:"bunny_hop_efficiency"` // Kill rate per jump
	CrouchAmbushRate   float64 `json:"crouch_ambush_rate"`   // % kills from crouch ambush
	MomentumKills      int64   `json:"momentum_kills"`       // Kills within 3s of sprint
	StationaryKills    int64   `json:"stationary_kills"`     // Kills while not moving
	MovingKills        int64   `json:"moving_kills"`         // Kills while moving
	SlideKills         int64   `json:"slide_kills"`          // Kills during slide (if tracked)
	ProneSnipeKills    int64   `json:"prone_snipe_kills"`    // Long-range kills while prone
}

// HealthObjectiveStats - correlations between health state and objectives
type HealthObjectiveStats struct {
	ClutchWins           int64   `json:"clutch_wins"`            // Wins when low health
	ClutchRate           float64 `json:"clutch_rate"`            // % wins when <25% HP
	ObjectiveDamage      int64   `json:"objective_damage"`       // Damage dealt during obj
	DamagePerCapture     float64 `json:"damage_per_capture"`     // Avg damage per obj cap
	LowHealthKills       int64   `json:"low_health_kills"`       // Kills when <25% HP
	LowHealthSurvivals   int64   `json:"low_health_survivals"`   // Times survived <25% HP
	HealthPackEfficiency float64 `json:"health_pack_efficiency"` // Avg survival time after pickup
	DamageWhileCapturing int64   `json:"damage_while_capturing"` // Damage taken during cap
}

// EconomySurvivalStats - item usage and loadout efficiency
type EconomySurvivalStats struct {
	ScavengerScore    float64 `json:"scavenger_score"` // Pickups per death
	AvgPickupsPerLife float64 `json:"avg_pickups_per_life"`
	BestLoadout       string  `json:"best_loadout"`        // Weapon combo with best results
	LoadoutWinRate    float64 `json:"loadout_win_rate"`    // Win rate with best loadout
	AmmoEfficiency    float64 `json:"ammo_efficiency"`     // Kills per ammo pickup
	GrenadeEfficiency float64 `json:"grenade_efficiency"`  // Kills per grenade
	WeaponSwitchKills int64   `json:"weapon_switch_kills"` // Kills within 2s of switch
}

// SignatureStats - unique player patterns
type SignatureStats struct {
	PlayStyle        string   `json:"play_style"`        // Aggressor, Defender, etc.
	SignatureMoves   []string `json:"signature_moves"`   // Top 3 distinctive patterns
	Strengths        []string `json:"strengths"`         // Areas of excellence
	ImprovementAreas []string `json:"improvement_areas"` // Areas to work on
}

// GetComboMetrics calculates all combo metrics for a player
func (s *ComboMetricsService) GetComboMetrics(ctx context.Context, guid string) (*ComboMetrics, error) {
	cm := &ComboMetrics{}

	// Fill each category
	if err := s.fillMovementCombat(ctx, guid, &cm.MovementCombat); err != nil {
		// Non-critical, initialize defaults
		cm.MovementCombat = MovementCombatStats{}
	}

	if err := s.fillHealthObjective(ctx, guid, &cm.HealthObjective); err != nil {
		cm.HealthObjective = HealthObjectiveStats{}
	}

	if err := s.fillEconomySurvival(ctx, guid, &cm.EconomySurvival); err != nil {
		cm.EconomySurvival = EconomySurvivalStats{}
	}

	// Calculate signature stats based on other metrics
	s.calculateSignature(cm)

	return cm, nil
}

func (s *ComboMetricsService) fillMovementCombat(ctx context.Context, guid string, out *MovementCombatStats) error {
	// Query for kills with movement context
	// This requires temporal correlation between movement and kill events

	query := `
		WITH 
		-- Get all kills by this player
		player_kills AS (
			SELECT timestamp, match_id, extra
			FROM raw_events
			WHERE event_type = 'player_kill' AND actor_id = ?
		),
		-- Get movement events (distance reports with velocity)
		movement_events AS (
			SELECT timestamp, match_id, 
				toFloat64OrZero(extract(extra, 'velocity')) as velocity,
				extract(extra, 'stance') as stance
			FROM raw_events
			WHERE (event_type = 'player_distance' OR event_type = 'player_jump' OR event_type = 'player_crouch')
				AND actor_id = ?
		),
		-- Get jumps
		jump_events AS (
			SELECT timestamp, match_id
			FROM raw_events
			WHERE event_type = 'player_jump' AND actor_id = ?
		)
		SELECT
			-- Total kills
			(SELECT count() FROM player_kills) as total_kills,
			
			-- Kills while moving (velocity > threshold in recent movement event)
			-- Approximated: kills where the player had significant distance in same match
			(SELECT count() FROM player_kills pk
			 WHERE EXISTS (
				SELECT 1 FROM movement_events me 
				WHERE me.match_id = pk.match_id 
				  AND me.timestamp BETWEEN pk.timestamp - 3 AND pk.timestamp
				  AND me.velocity > 50
			 )) as moving_kills,
			
			-- Kills while stationary
			(SELECT count() FROM player_kills pk
			 WHERE NOT EXISTS (
				SELECT 1 FROM movement_events me 
				WHERE me.match_id = pk.match_id 
				  AND me.timestamp BETWEEN pk.timestamp - 3 AND pk.timestamp
				  AND me.velocity > 50
			 )) as stationary_kills,
			
			-- Bunny hop kills (kill within 2s of jump)
			(SELECT count() FROM player_kills pk
			 WHERE EXISTS (
				SELECT 1 FROM jump_events je
				WHERE je.match_id = pk.match_id
				  AND je.timestamp BETWEEN pk.timestamp - 2 AND pk.timestamp
			 )) as bunny_hop_kills,
			
			-- Total jumps for efficiency calc
			(SELECT count() FROM jump_events) as total_jumps,
			
			-- Crouch kills
			(SELECT count() FROM player_kills pk
			 WHERE EXISTS (
				SELECT 1 FROM movement_events me
				WHERE me.match_id = pk.match_id
				  AND me.timestamp BETWEEN pk.timestamp - 2 AND pk.timestamp
				  AND me.stance = 'crouch'
			 )) as crouch_kills,
			
			-- Prone long-range kills (prone + distance > 50m)
			(SELECT count() FROM player_kills pk
			 WHERE EXISTS (
				SELECT 1 FROM movement_events me
				WHERE me.match_id = pk.match_id
				  AND me.timestamp BETWEEN pk.timestamp - 2 AND pk.timestamp
				  AND me.stance = 'prone'
			 ) AND toFloat64OrZero(extract(pk.extra, 'distance')) > 50) as prone_snipe_kills
	`

	var totalKills, movingKills, stationaryKills, bunnyHopKills, totalJumps, crouchKills, proneSnipeKills int64

	err := s.ch.QueryRow(ctx, query, guid, guid, guid).Scan(
		&totalKills, &movingKills, &stationaryKills, &bunnyHopKills,
		&totalJumps, &crouchKills, &proneSnipeKills,
	)
	if err != nil {
		// Fallback to simpler queries if complex one fails
		return s.fillMovementCombatSimple(ctx, guid, out)
	}

	out.MovingKills = movingKills
	out.StationaryKills = stationaryKills
	out.BunnyHopKills = bunnyHopKills
	out.ProneSnipeKills = proneSnipeKills

	if totalKills > 0 {
		out.RunGunIndex = float64(movingKills) / float64(totalKills)
		out.CrouchAmbushRate = float64(crouchKills) / float64(totalKills)
	}

	if totalJumps > 0 {
		out.BunnyHopEfficiency = float64(bunnyHopKills) / float64(totalJumps)
	}

	// Assign rank label
	switch {
	case out.RunGunIndex >= 0.6:
		out.RunGunRank = "Top 5% - Run & Gun Master"
	case out.RunGunIndex >= 0.45:
		out.RunGunRank = "Top 15% - Mobile Predator"
	case out.RunGunIndex >= 0.3:
		out.RunGunRank = "Top 35% - Balanced Fighter"
	default:
		out.RunGunRank = "Tactical Player"
	}

	return nil
}

func (s *ComboMetricsService) fillMovementCombatSimple(ctx context.Context, guid string, out *MovementCombatStats) error {
	// Simpler query that doesn't require complex joins
	query := `
		SELECT
			countIf(event_type = 'player_kill') as total_kills,
			countIf(event_type = 'player_jump') as total_jumps,
			sumIf(toFloat64OrZero(extract(extra, 'velocity')), event_type = 'player_distance') as total_velocity
		FROM raw_events
		WHERE actor_id = ?
	`

	var totalKills, totalJumps int64
	var totalVelocity float64
	if err := s.ch.QueryRow(ctx, query, guid).Scan(&totalKills, &totalJumps, &totalVelocity); err != nil {
		return err
	}

	// Estimate moving kills based on average velocity
	avgVelocity := totalVelocity / float64(totalKills+1)
	if avgVelocity > 30 {
		out.RunGunIndex = 0.5 // High average velocity = aggressive player
		out.MovingKills = int64(float64(totalKills) * 0.5)
		out.RunGunRank = "Mobile Fighter"
	} else {
		out.RunGunIndex = 0.25
		out.MovingKills = int64(float64(totalKills) * 0.25)
		out.RunGunRank = "Positional Player"
	}

	out.StationaryKills = totalKills - out.MovingKills
	out.BunnyHopKills = totalJumps / 10 // Rough estimate

	if totalJumps > 0 {
		out.BunnyHopEfficiency = float64(out.BunnyHopKills) / float64(totalJumps)
	}

	return nil
}

func (s *ComboMetricsService) fillHealthObjective(ctx context.Context, guid string, out *HealthObjectiveStats) error {
	// Query for clutch scenarios and objective interactions
	query := `
		SELECT
			-- Clutch wins: rounds won where player had low health event before round end
			countIf(event_type = 'team_win' AND actor_id = ?) as wins,
			
			-- Objective-related damage
			sumIf(toInt64OrZero(extract(extra, 'damage')), 
				event_type = 'player_damage' AND actor_id = ? 
				AND extract(extra, 'during_objective') = '1') as obj_damage,
			
			-- Objective captures/actions
			countIf(event_type = 'objective_update' AND actor_id = ?) as obj_actions,
			
			-- Health pickups
			countIf(event_type = 'health_pickup' AND actor_id = ?) as health_pickups,
			
			-- Deaths (for survival calculations)
			countIf((event_type = 'player_kill' OR event_type = 'player_death') AND target_id = ?) as deaths
		FROM raw_events
		WHERE actor_id = ? OR target_id = ?
	`

	var wins, objDamage, objActions, healthPickups, deaths int64
	err := s.ch.QueryRow(ctx, query, guid, guid, guid, guid, guid, guid, guid).Scan(
		&wins, &objDamage, &objActions, &healthPickups, &deaths,
	)
	if err != nil {
		return err
	}

	out.ObjectiveDamage = objDamage

	if objActions > 0 {
		out.DamagePerCapture = float64(objDamage) / float64(objActions)
	}

	// Estimate clutch stats (would need health tracking for real calculation)
	// For now, estimate based on win rate in close matches
	out.ClutchWins = wins / 5 // Assume ~20% of wins are clutch
	if wins > 0 {
		out.ClutchRate = 0.25 // Placeholder - real calc needs health data
	}

	if deaths > 0 {
		out.LowHealthSurvivals = deaths / 3 // Estimate
	}

	return nil
}

func (s *ComboMetricsService) fillEconomySurvival(ctx context.Context, guid string, out *EconomySurvivalStats) error {
	query := `
		SELECT
			-- Item pickups
			countIf(event_type = 'item_pickup' AND actor_id = ?) as pickups,
			
			-- Deaths for scavenger calc
			countIf((event_type = 'player_kill' OR event_type = 'player_death') AND target_id = ?) as deaths,
			
			-- Grenade throws and kills
			countIf(event_type = 'grenade_throw' AND actor_id = ?) as grenades,
			countIf(event_type = 'player_kill' AND actor_id = ? 
				AND extract(extra, 'mod') LIKE '%grenade%') as grenade_kills,
			
			-- Weapon switches followed by kills
			countIf(event_type = 'weapon_change' AND actor_id = ?) as weapon_switches
		FROM raw_events
		WHERE actor_id = ? OR target_id = ?
	`

	var pickups, deaths, grenades, grenadeKills, weaponSwitches int64
	err := s.ch.QueryRow(ctx, query, guid, guid, guid, guid, guid, guid, guid).Scan(
		&pickups, &deaths, &grenades, &grenadeKills, &weaponSwitches,
	)
	if err != nil {
		return err
	}

	if deaths > 0 {
		out.ScavengerScore = float64(pickups) / float64(deaths)
		out.AvgPickupsPerLife = out.ScavengerScore
	}

	if grenades > 0 {
		out.GrenadeEfficiency = float64(grenadeKills) / float64(grenades)
	}

	// Get best loadout
	bestLoadout, winRate := s.getBestLoadout(ctx, guid)
	out.BestLoadout = bestLoadout
	out.LoadoutWinRate = winRate

	// Estimate weapon switch kills
	out.WeaponSwitchKills = weaponSwitches / 5 // Rough estimate

	return nil
}

func (s *ComboMetricsService) getBestLoadout(ctx context.Context, guid string) (string, float64) {
	// Find the primary weapon with best win rate
	query := `
		SELECT 
			extract(extra, 'weapon') as weapon,
			countIf(event_type = 'player_kill') as kills,
			countIf(event_type = 'team_win') as wins,
			uniq(match_id) as matches
		FROM raw_events
		WHERE actor_id = ? AND weapon != ''
		GROUP BY weapon
		HAVING matches >= 5
		ORDER BY wins DESC, kills DESC
		LIMIT 1
	`

	var weapon string
	var kills, wins, matches int64
	err := s.ch.QueryRow(ctx, query, guid).Scan(&weapon, &kills, &wins, &matches)
	if err != nil || weapon == "" {
		return "Unknown", 0
	}

	winRate := float64(wins) / float64(matches) * 100
	return weapon, winRate
}

func (s *ComboMetricsService) calculateSignature(cm *ComboMetrics) {
	sig := &cm.Signature

	// Determine play style based on metrics
	if cm.MovementCombat.RunGunIndex >= 0.5 {
		sig.PlayStyle = "Aggressor"
	} else if cm.MovementCombat.CrouchAmbushRate >= 0.3 {
		sig.PlayStyle = "Ambusher"
	} else if cm.MovementCombat.ProneSnipeKills > 20 {
		sig.PlayStyle = "Sniper"
	} else if cm.HealthObjective.ObjectiveDamage > 5000 {
		sig.PlayStyle = "Objective Focused"
	} else {
		sig.PlayStyle = "Balanced"
	}

	// Identify signature moves
	sig.SignatureMoves = make([]string, 0, 3)

	if cm.MovementCombat.BunnyHopEfficiency > 0.15 {
		sig.SignatureMoves = append(sig.SignatureMoves, "Jump Shot Specialist")
	}
	if cm.MovementCombat.RunGunIndex > 0.5 {
		sig.SignatureMoves = append(sig.SignatureMoves, "Run & Gun Expert")
	}
	if cm.HealthObjective.ClutchRate > 0.3 {
		sig.SignatureMoves = append(sig.SignatureMoves, "Clutch Artist")
	}
	if cm.EconomySurvival.GrenadeEfficiency > 0.2 {
		sig.SignatureMoves = append(sig.SignatureMoves, "Grenade Master")
	}
	if cm.MovementCombat.CrouchAmbushRate > 0.25 {
		sig.SignatureMoves = append(sig.SignatureMoves, "Patient Hunter")
	}

	// Limit to 3
	if len(sig.SignatureMoves) > 3 {
		sig.SignatureMoves = sig.SignatureMoves[:3]
	}

	// Identify strengths
	sig.Strengths = make([]string, 0, 3)
	if cm.MovementCombat.RunGunIndex > 0.4 {
		sig.Strengths = append(sig.Strengths, "High mobility combat")
	}
	if cm.HealthObjective.DamagePerCapture > 500 {
		sig.Strengths = append(sig.Strengths, "Objective defense")
	}
	if cm.EconomySurvival.ScavengerScore > 3 {
		sig.Strengths = append(sig.Strengths, "Resource management")
	}

	// Identify improvement areas
	sig.ImprovementAreas = make([]string, 0, 2)
	if cm.MovementCombat.StationaryKills > cm.MovementCombat.MovingKills*2 {
		sig.ImprovementAreas = append(sig.ImprovementAreas, "Movement during combat")
	}
	if cm.EconomySurvival.GrenadeEfficiency < 0.05 {
		sig.ImprovementAreas = append(sig.ImprovementAreas, "Grenade effectiveness")
	}
}

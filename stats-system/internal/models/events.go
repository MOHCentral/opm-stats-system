package models

import (
	"time"

	"github.com/google/uuid"
)

// EventType represents the type of game event
type EventType string

const (
	// Match lifecycle
	EventMatchStart EventType = "match_start"
	EventMatchEnd   EventType = "match_end"
	EventRoundStart EventType = "round_start"
	EventRoundEnd   EventType = "round_end"
	EventHeartbeat  EventType = "heartbeat"

	// Combat
	EventKill           EventType = "kill"
	EventDeath          EventType = "death"
	EventDamage         EventType = "damage"
	EventWeaponFire     EventType = "weapon_fire"
	EventWeaponHit      EventType = "weapon_hit"
	EventHeadshot       EventType = "headshot"
	EventReload         EventType = "reload"
	EventWeaponChange   EventType = "weapon_change"
	EventGrenadeThrow   EventType = "grenade_throw"
	EventGrenadeExplode EventType = "grenade_explode"

	// Movement
	EventJump     EventType = "jump"
	EventLand     EventType = "land"
	EventCrouch   EventType = "crouch"
	EventProne    EventType = "prone"
	EventDistance EventType = "distance"

	// Interaction
	EventLadderMount    EventType = "ladder_mount"
	EventLadderDismount EventType = "ladder_dismount"
	EventItemPickup     EventType = "item_pickup"
	EventItemDrop       EventType = "item_drop"
	EventUse            EventType = "use"

	// Session
	EventConnect    EventType = "connect"
	EventDisconnect EventType = "disconnect"
	EventSpawn      EventType = "spawn"
	EventTeamChange EventType = "team_change"
	EventChat       EventType = "chat"

	// Identity
	EventIdentityClaim EventType = "identity_claim"
)

// Team represents a player's team
type Team string

const (
	TeamSpectator Team = "spectator"
	TeamAllies    Team = "allies"
	TeamAxis      Team = "axis"
)

// RawEvent is the incoming event from game servers
type RawEvent struct {
	Type        EventType `json:"type"`
	MatchID     string    `json:"match_id"`
	SessionID   string    `json:"session_id"`
	ServerID    string    `json:"server_id"`
	ServerToken string    `json:"server_token"`
	Timestamp   float64   `json:"timestamp"`
	MapName     string    `json:"map_name,omitempty"`

	// Player info
	PlayerName string  `json:"player_name,omitempty"`
	PlayerGUID string  `json:"player_guid,omitempty"`
	PlayerTeam string  `json:"player_team,omitempty"`
	PosX       float32 `json:"pos_x,omitempty"`
	PosY       float32 `json:"pos_y,omitempty"`
	PosZ       float32 `json:"pos_z,omitempty"`

	// Attacker info (for kill/damage events)
	AttackerName  string  `json:"attacker_name,omitempty"`
	AttackerGUID  string  `json:"attacker_guid,omitempty"`
	AttackerTeam  string  `json:"attacker_team,omitempty"`
	AttackerX     float32 `json:"attacker_x,omitempty"`
	AttackerY     float32 `json:"attacker_y,omitempty"`
	AttackerZ     float32 `json:"attacker_z,omitempty"`
	AttackerPitch float32 `json:"attacker_pitch,omitempty"`
	AttackerYaw   float32 `json:"attacker_yaw,omitempty"`

	// Victim info
	VictimName string  `json:"victim_name,omitempty"`
	VictimGUID string  `json:"victim_guid,omitempty"`
	VictimTeam string  `json:"victim_team,omitempty"`
	VictimX    float32 `json:"victim_x,omitempty"`
	VictimY    float32 `json:"victim_y,omitempty"`
	VictimZ    float32 `json:"victim_z,omitempty"`

	// Weapon/damage info
	Weapon        string `json:"weapon,omitempty"`
	OldWeapon     string `json:"old_weapon,omitempty"`
	NewWeapon     string `json:"new_weapon,omitempty"`
	Hitloc        string `json:"hitloc,omitempty"`
	Inflictor     string `json:"inflictor,omitempty"`
	Damage        int    `json:"damage,omitempty"`
	AmmoRemaining int    `json:"ammo_remaining,omitempty"`

	// Movement
	FallHeight float32 `json:"fall_height,omitempty"`
	Walked     float32 `json:"walked,omitempty"`
	Sprinted   float32 `json:"sprinted,omitempty"`
	Swam       float32 `json:"swam,omitempty"`
	Driven     float32 `json:"driven,omitempty"`

	// Aim angles
	AimPitch float32 `json:"aim_pitch,omitempty"`
	AimYaw   float32 `json:"aim_yaw,omitempty"`

	// Items
	Item  string `json:"item,omitempty"`
	Count int    `json:"count,omitempty"`

	// Target info (for hits)
	TargetName string `json:"target_name,omitempty"`
	TargetGUID string `json:"target_guid,omitempty"`

	// Team change
	OldTeam string `json:"old_team,omitempty"`
	NewTeam string `json:"new_team,omitempty"`

	// Chat
	Message string `json:"message,omitempty"`

	// Match lifecycle
	Gametype    string  `json:"gametype,omitempty"`
	Timelimit   string  `json:"timelimit,omitempty"`
	Fraglimit   string  `json:"fraglimit,omitempty"`
	Maxclients  string  `json:"maxclients,omitempty"`
	Duration    float64 `json:"duration,omitempty"`
	WinningTeam string  `json:"winning_team,omitempty"`
	AlliesScore int     `json:"allies_score,omitempty"`
	AxisScore   int     `json:"axis_score,omitempty"`
	RoundNumber int     `json:"round_number,omitempty"`
	TotalRounds int     `json:"total_rounds,omitempty"`
	PlayerCount int     `json:"player_count,omitempty"`
	ClientNum   int     `json:"client_num,omitempty"`

	// Identity claim
	Code string `json:"code,omitempty"`

	// Entity
	Entity     string `json:"entity,omitempty"`
	Projectile string `json:"projectile,omitempty"`
}

// ClickHouseEvent is the normalized event for ClickHouse storage
type ClickHouseEvent struct {
	Timestamp time.Time
	MatchID   uuid.UUID
	ServerID  string
	MapName   string
	EventType string

	// Actor (player performing action)
	ActorID     string
	ActorName   string
	ActorTeam   string
	ActorWeapon string
	ActorPosX   float32
	ActorPosY   float32
	ActorPosZ   float32
	ActorPitch  float32
	ActorYaw    float32

	// Target (recipient of action)
	TargetID   string
	TargetName string
	TargetTeam string
	TargetPosX float32
	TargetPosY float32
	TargetPosZ float32

	// Metrics
	Damage   uint32
	Hitloc   string
	Distance float32

	// Raw JSON for debugging
	RawJSON string
}

// MatchResult is sent at the end of a match
type MatchResult struct {
	MatchID     string  `json:"match_id"`
	ServerID    string  `json:"server_id"`
	MapName     string  `json:"map_name"`
	Gametype    string  `json:"gametype"`
	Duration    float64 `json:"duration"`
	WinningTeam string  `json:"winning_team"`
	AlliesScore int     `json:"allies_score"`
	AxisScore   int     `json:"axis_score"`
	TotalRounds int     `json:"total_rounds"`

	// Tournament context (optional)
	TournamentID string `json:"tournament_id,omitempty"`
	BracketMatch string `json:"bracket_match,omitempty"`
}

// PlayerStats aggregated stats for a player
type PlayerStats struct {
	PlayerID       string    `json:"player_id"`
	PlayerName     string    `json:"player_name"`
	TotalKills     int64     `json:"total_kills"`
	TotalDeaths    int64     `json:"total_deaths"`
	TotalDamage    int64     `json:"total_damage"`
	TotalHeadshots int64     `json:"total_headshots"`
	ShotsFired     int64     `json:"shots_fired"`
	ShotsHit       int64     `json:"shots_hit"`
	MatchesPlayed  int64     `json:"matches_played"`
	MatchesWon     int64     `json:"matches_won"`
	PlayTime       float64   `json:"play_time_seconds"`
	LastActive     time.Time `json:"last_active"`

	// Computed
	KDRatio   float64 `json:"kd_ratio"`
	Accuracy  float64 `json:"accuracy"`
	HSPercent float64 `json:"headshot_percent"`
	WinRate   float64 `json:"win_rate"`

	// Granular Combat Metrics
	LongRangeKills    int64 `json:"long_range_kills"`    // >100m
	CloseRangeKills   int64 `json:"close_range_kills"`   // <5m
	WallbangKills     int64 `json:"wallbang_kills"`      
	CollateralKills   int64 `json:"collateral_kills"`
	
	// Stance Metrics
	KillsWhileProne    int64 `json:"kills_while_prone"`
	KillsWhileCrouching int64 `json:"kills_while_crouching"`
	KillsWhileStanding  int64 `json:"kills_while_standing"`
	KillsWhileMoving    int64 `json:"kills_while_moving"`
	KillsWhileStationary int64 `json:"kills_while_stationary"`

	// Movement Metrics
	TotalDistance   float64 `json:"total_distance_km"`
	SprintDistance  float64 `json:"sprint_distance_km"`
	JumpCount       int64   `json:"jump_count"`
	CrouchTime      float64 `json:"crouch_time_seconds"`
	ProneTime       float64 `json:"prone_time_seconds"`
}

// WeaponStats per-weapon statistics
type WeaponStats struct {
	Weapon     string  `json:"weapon"`
	Kills      int64   `json:"kills"`
	Deaths     int64   `json:"deaths"`
	Damage     int64   `json:"damage"`
	Headshots  int64   `json:"headshots"`
	ShotsFired int64   `json:"shots_fired"`
	ShotsHit   int64   `json:"shots_hit"`
	Accuracy   float64 `json:"accuracy"`
}

// MapStats per-map statistics
type MapStats struct {
	MapName       string  `json:"map_name"`
	Kills         int64   `json:"kills"`
	Deaths        int64   `json:"deaths"`
	MatchesPlayed int64   `json:"matches_played"`
	MatchesWon    int64   `json:"matches_won"`
	WinRate       float64 `json:"win_rate"`
}

// LeaderboardEntry for leaderboard display
type LeaderboardEntry struct {
	Rank       int     `json:"rank"`
	PlayerID   string  `json:"player_id"`
	PlayerName string  `json:"player_name"`
	Value      int64   `json:"value"`
	Secondary  float64 `json:"secondary,omitempty"`
}

// HeatmapData for spatial analysis
type HeatmapData struct {
	MapName string         `json:"map_name"`
	Type    string         `json:"type,omitempty"` // "kills" or "deaths"
	Points  []HeatmapPoint `json:"points"`
}

type HeatmapPoint struct {
	X     float32 `json:"x"`
	Y     float32 `json:"y"`
	Count int     `json:"count"`
}

// LiveMatch for real-time match display
type LiveMatch struct {
	MatchID      string    `json:"match_id"`
	ServerID     string    `json:"server_id"`
	ServerName   string    `json:"server_name"`
	MapName      string    `json:"map_name"`
	Gametype     string    `json:"gametype"`
	AlliesScore  int       `json:"allies_score"`
	AxisScore    int       `json:"axis_score"`
	PlayerCount  int       `json:"player_count"`
	MaxPlayers   int       `json:"max_players"`
	RoundNumber  int       `json:"round_number"`
	StartedAt    time.Time `json:"started_at"`
	TournamentID string    `json:"tournament_id,omitempty"`
}

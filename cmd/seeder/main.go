package main

import (
	"bytes"
	"encoding/json"
	"flag"
	"fmt"
	"math/rand"
	"net/http"
	"net/url"
	"sync"
	"time"

	"github.com/google/uuid"
)

// Flags
var (
	apiURL      = flag.String("api", "http://localhost:8080/api/v1", "API base URL")
	numMatches  = flag.Int("matches", 10, "Number of matches to generate")
	concurrency = flag.Int("workers", 5, "Number of concurrent workers")
	verbose     = flag.Bool("v", false, "Verbose output")
	authenticate = flag.Bool("auth", true, "Authenticate players via SMF")
)

// Constants
const (
	ServerID    = "seeder-server-01"
	ServerToken = "test-token"
)

// Event types covering all 92 events
const (
	// Game Flow (11)
	EventGameInit          = "game_init"
	EventGameStart         = "game_start"
	EventGameEnd           = "game_end"
	EventMatchStart        = "match_start"
	EventMatchEnd          = "match_end"
	EventRoundStart        = "round_start"
	EventRoundEnd          = "round_end"
	EventWarmupStart       = "warmup_start"
	EventWarmupEnd         = "warmup_end"
	EventIntermissionStart = "intermission_start"
	EventTeamWin           = "team_win"

	// Combat (23)
	EventKill              = "kill"
	EventDeath             = "death"
	EventDamage            = "damage"
	EventPlayerPain        = "player_pain"
	EventHeadshot          = "headshot"
	EventPlayerSuicide     = "player_suicide"
	EventPlayerCrushed     = "player_crushed"
	EventPlayerTelefragged = "player_telefragged"
	EventPlayerRoadkill    = "player_roadkill"
	EventPlayerBash        = "player_bash"
	EventPlayerTeamkill    = "player_teamkill"
	EventWeaponFire        = "weapon_fire"
	EventWeaponHit         = "weapon_hit"
	EventWeaponChange      = "weapon_change"
	EventWeaponReload      = "weapon_reload"
	EventWeaponReloadDone  = "weapon_reload_done"
	EventWeaponReady       = "weapon_ready"
	EventWeaponNoAmmo      = "weapon_no_ammo"
	EventWeaponHolster     = "weapon_holster"
	EventWeaponRaise       = "weapon_raise"
	EventWeaponDrop        = "weapon_drop"
	EventGrenadeThrow      = "grenade_throw"
	EventGrenadeExplode    = "grenade_explode"

	// Movement (10)
	EventJump           = "jump"
	EventLand           = "land"
	EventCrouch         = "crouch"
	EventProne          = "prone"
	EventPlayerStand    = "player_stand"
	EventPlayerSpawn    = "player_spawn"
	EventPlayerRespawn  = "player_respawn"
	EventDistance       = "distance"
	EventLadderMount    = "ladder_mount"
	EventLadderDismount = "ladder_dismount"

	// Interaction (6)
	EventPlayerUse             = "player_use"
	EventPlayerUseObjectStart  = "player_use_object_start"
	EventPlayerUseObjectFinish = "player_use_object_finish"
	EventPlayerSpectate        = "player_spectate"
	EventPlayerFreeze          = "player_freeze"
	EventPlayerSay             = "player_say"

	// Item (5)
	EventItemPickup   = "item_pickup"
	EventItemDrop     = "item_drop"
	EventItemRespawn  = "item_respawn"
	EventHealthPickup = "health_pickup"
	EventAmmoPickup   = "ammo_pickup"

	// Vehicle/Turret (6)
	EventVehicleEnter     = "vehicle_enter"
	EventVehicleExit      = "vehicle_exit"
	EventVehicleDeath     = "vehicle_death"
	EventVehicleCollision = "vehicle_collision"
	EventTurretEnter      = "turret_enter"
	EventTurretExit       = "turret_exit"

	// Server (5)
	EventServerInit           = "server_init"
	EventServerStart          = "server_start"
	EventServerShutdown       = "server_shutdown"
	EventServerSpawned        = "server_spawned"
	EventServerConsoleCommand = "server_console_command"
	EventHeartbeat            = "heartbeat"

	// Map (4)
	EventMapLoadStart   = "map_load_start"
	EventMapLoadEnd     = "map_load_end"
	EventMapChangeStart = "map_change_start"
	EventMapRestart     = "map_restart"

	// Team/Vote (5)
	EventTeamJoin   = "team_join"
	EventTeamChange = "team_change"
	EventVoteStart  = "vote_start"
	EventVotePassed = "vote_passed"
	EventVoteFailed = "vote_failed"

	// Client (5)
	EventClientConnect         = "client_connect"
	EventClientDisconnect      = "client_disconnect"
	EventClientBegin           = "client_begin"
	EventClientUserinfoChanged = "client_userinfo_changed"
	EventPlayerInactivityDrop  = "player_inactivity_drop"

	// World (3)
	EventDoorOpen  = "door_open"
	EventDoorClose = "door_close"
	EventExplosion = "explosion"

	// AI/Actor/Bot (7)
	EventActorSpawn  = "actor_spawn"
	EventActorKilled = "actor_killed"
	EventBotSpawn    = "bot_spawn"
	EventBotKilled   = "bot_killed"
	EventBotRoam     = "bot_roam"
	EventBotCurious  = "bot_curious"
	EventBotAttack   = "bot_attack"

	// Objectives (2)
	EventObjectiveUpdate  = "objective_update"
	EventObjectiveCapture = "objective_capture"

	// Score/Admin (2)
	EventScoreChange  = "score_change"
	EventTeamkillKick = "teamkill_kick"

	// Legacy
	EventConnect    = "connect"
	EventDisconnect = "disconnect"
)

type Player struct {
	Name       string
	GUID       string
	Team       string
	UserID     int // SMF User ID
	MemberID   int // Confirmed Member ID after login
	Weapon     string
	Ammo       int
	HP         int
	PosX       float64
	PosY       float64
	PosZ       float64
	Pitch      float64
	Yaw        float64
	InVehicle  bool
	OnTurret   bool
	Stance     string // prone, crouch, stand
	Alive      bool
	Kills      int
	Deaths     int
	LastAction time.Time
}

var players = []Player{
	{Name: "Sgt.Physics", GUID: "111111", Team: "allies", UserID: 1, MemberID: 0},
	{Name: "Cpt.Logic", GUID: "222222", Team: "axis", UserID: 2, MemberID: 0},
	{Name: "Pvt.Panic", GUID: "333333", Team: "allies", UserID: 3, MemberID: 0},
	{Name: "Major.Lag", GUID: "444444", Team: "axis", UserID: 4, MemberID: 0},
	{Name: "Sniper.Wolf", GUID: "555555", Team: "allies", UserID: 5, MemberID: 0},
	{Name: "Running.Man", GUID: "666666", Team: "axis", UserID: 6, MemberID: 0},
	{Name: "Camper.Joe", GUID: "777777", Team: "allies", UserID: 7, MemberID: 0},
	{Name: "Rusher.B", GUID: "888888", Team: "axis", UserID: 8, MemberID: 0},
	{Name: "Noob.Slayer", GUID: "999999", Team: "allies", UserID: 9, MemberID: 0},
	{Name: "Bot.Alice", GUID: "000000", Team: "axis", UserID: 10, MemberID: 0},
	{Name: "Tank.Driver", GUID: "AAAA01", Team: "allies", UserID: 11, MemberID: 0},
	{Name: "Medic.Mike", GUID: "BBBB02", Team: "axis", UserID: 12, MemberID: 0},
	{Name: "Scout.Sam", GUID: "CCCC03", Team: "allies", UserID: 13, MemberID: 0},
	{Name: "Heavy.Hank", GUID: "DDDD04", Team: "axis", UserID: 14, MemberID: 0},
	{Name: "Silent.Steve", GUID: "EEEE05", Team: "allies", UserID: 15, MemberID: 0},
}

var weapons = []string{
	"M1 Garand", "Kar98k", "Springfield", "Thompson", "MP40", "BAR",
	"Colt .45", "P38", "Shotgun", "Panzerschreck", "Bazooka",
	"M1 Carbine", "STG44", "MG42", "M1919 Browning",
}

var maps = []string{
	"dm/mohdm1", "dm/mohdm2", "dm/mohdm3", "dm/mohdm4",
	"obj/obj_team1", "obj/obj_team2", "obj/omaha_beach",
	"dm/stalingrad", "dm/berlin", "obj/v2_rocket",
}

var hitlocs = []string{
	"head", "torso", "torso", "torso", "left_arm", "right_arm", "left_leg", "right_leg",
}

var chatMessages = []string{
	"gg", "nice shot!", "lol", "camping much?", "lets go allies!",
	"rush b", "cover me!", "medic!", "need ammo!", "behind you!",
	"nice", "omg", "wtf", "brb", "afk 1min", "gg wp", "rekt",
}

var items = []string{
	"health_pack", "ammo_box", "grenade", "helmet", "binoculars",
	"first_aid_kit", "rifle_ammo", "smg_ammo", "pistol_ammo",
}

func main() {
	flag.Parse()
	rand.Seed(time.Now().UnixNano())

	fmt.Printf("🌱 OpenMOHAA Comprehensive Stats Seeder\n")
	fmt.Printf("📊 Generating %d matches with %d players\n", *numMatches, len(players))
	fmt.Printf("🎯 Target API: %s\n", *apiURL)
	fmt.Printf("⚡ Workers: %d\n\n", *concurrency)

	// Initialize players
	initializePlayers()

	client := &http.Client{Timeout: 5 * time.Second}

	// Authenticate players if requested
	if *authenticate {
		fmt.Println("🔐 Authenticating players...")
		for i := range players {
			authenticatePlayer(client, &players[i])
		}
		fmt.Println("✅ Players authenticated.\n")
	}

	stats := &Stats{}
	wg := &sync.WaitGroup{}

	matchesPerWorker := *numMatches / *concurrency
	if matchesPerWorker == 0 {
		matchesPerWorker = 1
	}

	start := time.Now()

	for i := 0; i < *concurrency; i++ {
		wg.Add(1)
		go worker(wg, matchesPerWorker, stats, client)
	}

	wg.Wait()

	duration := time.Since(start)
	fmt.Printf("\n🎉 Seeding complete!\n")
	fmt.Printf("   Total events: %d\n", stats.Total())
	fmt.Printf("   Duration: %v\n", duration)
	fmt.Printf("   Rate: %.2f events/sec\n", float64(stats.Total())/duration.Seconds())
	fmt.Printf("   Errors: %d\n", stats.Errors())
}

func initializePlayers() {
	for i := range players {
		players[i].Weapon = randomWeapon()
		players[i].Ammo = 30
		players[i].HP = 100
		players[i].PosX = randomFloat(-1000, 1000)
		players[i].PosY = randomFloat(-1000, 1000)
		players[i].PosZ = randomFloat(0, 200)
		players[i].Pitch = randomFloat(-45, 45)
		players[i].Yaw = randomFloat(0, 360)
		players[i].Stance = "stand"
		players[i].Alive = false
	}
}

func authenticatePlayer(client *http.Client, p *Player) {
	// 1. Get Token (POST /auth/device)
	tokenReq := map[string]interface{}{
		"forum_user_id": p.UserID,
		"client_ip":     "127.0.0.1",
	}
	jsonData, _ := json.Marshal(tokenReq)
	req, _ := http.NewRequest("POST", *apiURL+"/auth/device", bytes.NewBuffer(jsonData))
	req.Header.Set("Content-Type", "application/json")
	
	resp, err := client.Do(req)
	if err != nil || resp.StatusCode != 200 {
		fmt.Printf("Failed to get token for %s: %v\n", p.Name, err)
		return
	}
	defer resp.Body.Close()

	var tokenResp struct {
		UserCode string `json:"user_code"`
	}
	json.NewDecoder(resp.Body).Decode(&tokenResp)
	token := tokenResp.UserCode
	
	// 2. Verified Token (POST /auth/smf-verify) - Simulating tracker.scr
	form := url.Values{}
	form.Set("token", token)
	form.Set("guid", p.GUID)
	form.Set("player_name", p.Name)
	form.Set("server_id", ServerID)

	resp, err = client.PostForm(*apiURL+"/auth/smf-verify", form)
	if err != nil || resp.StatusCode != 200 {
		fmt.Printf("Failed to login %s: %v\n", p.Name, err)
		return
	}
	defer resp.Body.Close()

	var authResp struct {
		Success  bool `json:"success"`
		MemberID int  `json:"member_id"`
	}
	if err := json.NewDecoder(resp.Body).Decode(&authResp); err == nil && authResp.Success {
		p.MemberID = authResp.MemberID
		fmt.Printf("Logged in %s as Member ID %d\n", p.Name, p.MemberID)
	} else {
		fmt.Printf("Failed to verify token for %s\n", p.Name)
	}
}

type Stats struct {
	sync.Mutex
	total  int
	errors int
}

func (s *Stats) Add(n int) {
	s.Lock()
	s.total += n
	s.Unlock()
}

func (s *Stats) Error() {
	s.Lock()
	s.errors++
	s.Unlock()
}

func (s *Stats) Total() int {
	s.Lock()
	defer s.Unlock()
	return s.total
}

func (s *Stats) Errors() int {
	s.Lock()
	defer s.Unlock()
	return s.errors
}

func worker(wg *sync.WaitGroup, count int, s *Stats, client *http.Client) {
	defer wg.Done()

	for i := 0; i < count; i++ {
		events := generateMatch(i + 1)
		
		// Send all events for this match
		for _, event := range events {
			sendEvent(client, s, event)
		}

		if *verbose {
			fmt.Printf("Worker completed match %d: %d events\n", i+1, len(events))
		} else if i%10 == 0 {
			fmt.Printf(".")
		}

		// Small delay between matches
		time.Sleep(100 * time.Millisecond)
	}
}

// generateMatch creates a realistic match with all 92 event types
func generateMatch(matchNum int) []map[string]interface{} {
	matchID := uuid.New().String()
	sessionID := uuid.New().String()
	mapName := randomMap()
	gametype := randomGametype()
	
	events := []map[string]interface{}{}
	baseTime := float64(time.Now().Unix())
	currentTime := baseTime

	// SERVER INIT (happens once per server startup)
	if matchNum == 1 {
		events = append(events, makeEvent(EventServerInit, currentTime, map[string]interface{}{
			"gametype": gametype,
		}))
		currentTime += 0.5

		events = append(events, makeEvent(EventServerStart, currentTime, nil))
		currentTime += 0.5
	}

	// MAP LOAD
	events = append(events, makeEvent(EventMapLoadStart, currentTime, map[string]interface{}{
		"map": mapName,
	}))
	currentTime += 2.0

	events = append(events, makeEvent(EventMapLoadEnd, currentTime, map[string]interface{}{
		"map": mapName,
	}))
	currentTime += 0.5

	// SERVER SPAWNED
	events = append(events, makeEvent(EventServerSpawned, currentTime, map[string]interface{}{
		"map":      mapName,
		"gametype": gametype,
	}))
	currentTime += 1.0

	// MATCH START
	events = append(events, makeEvent(EventMatchStart, currentTime, map[string]interface{}{
		"match_id":     matchID,
		"session_id":   sessionID,
		"map_name":     mapName,
		"gametype":     gametype,
		"player_count": len(players),
	}))
	currentTime += 0.5

	// WARMUP
	events = append(events, makeEvent(EventWarmupStart, currentTime, map[string]interface{}{
		"match_id": matchID,
	}))
	currentTime += 15.0 // 15s warmup

	events = append(events, makeEvent(EventWarmupEnd, currentTime, map[string]interface{}{
		"match_id": matchID,
	}))
	currentTime += 1.0

	// GAME START
	events = append(events, makeEvent(EventGameInit, currentTime, map[string]interface{}{
		"match_id":   matchID,
		"session_id": sessionID,
		"gametype":   gametype,
	}))
	currentTime += 0.5

	events = append(events, makeEvent(EventGameStart, currentTime, map[string]interface{}{
		"match_id":   matchID,
		"session_id": sessionID,
	}))
	currentTime += 0.5

	// ROUND START
	events = append(events, makeEvent(EventRoundStart, currentTime, map[string]interface{}{
		"match_id":     matchID,
		"round_number": 1,
	}))
	currentTime += 0.5

	// PLAYERS CONNECT AND SPAWN
	for i := range players {
		p := &players[i]
		
		// Connect
		connectData := map[string]interface{}{
			"match_id":    matchID,
			"client_num":  i,
			"player_name": p.Name,
			"player_guid": p.GUID,
		}
		events = append(events, makeEvent(EventClientConnect, currentTime, connectData))
		currentTime += 0.2

		// Client begin
		events = append(events, makeEvent(EventClientBegin, currentTime, connectData))
		currentTime += 0.1

		// Team join
		joinData := map[string]interface{}{
			"match_id":    matchID,
			"player_name": p.Name,
			"player_guid": p.GUID,
			"player_team": p.Team,
		}
		events = append(events, makeEvent(EventTeamJoin, currentTime, joinData))
		currentTime += 0.1

		// Spawn
		p.PosX = randomFloat(-1000, 1000)
		p.PosY = randomFloat(-1000, 1000)
		p.PosZ = randomFloat(0, 200)
		
		spawnData := map[string]interface{}{
			"match_id":    matchID,
			"player_name": p.Name,
			"player_guid": p.GUID,
			"player_team": p.Team,
			"pos_x":       p.PosX,
			"pos_y":       p.PosY,
			"pos_z":       p.PosZ,
		}
		events = append(events, makeEvent(EventPlayerSpawn, currentTime, spawnData))
		
		p.Alive = true
		p.HP = 100
		currentTime += 0.3
	}

	// SPAWN SOME BOTS
	for i := 0; i < 3; i++ {
		botData := map[string]interface{}{
			"match_id": matchID,
			"bot_id":   fmt.Sprintf("bot_%d", i+1),
		}
		events = append(events, makeEvent(EventBotSpawn, currentTime, botData))
		currentTime += 0.5
	}

	// SIMULATE 5 MINUTES OF GAMEPLAY
	matchDuration := 300.0
	endTime := currentTime + matchDuration

	eventCount := 0
	for currentTime < endTime && eventCount < 500 {
		gameplayEvents := generateGameplaySequence(matchID, &currentTime)
		events = append(events, gameplayEvents...)
		eventCount += len(gameplayEvents)
		
		// Heartbeat every 30s
		if int(currentTime)%30 == 0 {
			events = append(events, makeEvent(EventHeartbeat, currentTime, map[string]interface{}{
				"player_count": countAlivePlayers(),
			}))
		}
		
		currentTime += randomFloat(1.0, 5.0)
	}

	// ROUND END
	alliesScore := rand.Intn(50) + 20
	axisScore := rand.Intn(50) + 20

	events = append(events, makeEvent(EventRoundEnd, currentTime, map[string]interface{}{
		"match_id":     matchID,
		"round_number": 1,
		"allies_score": alliesScore,
		"axis_score":   axisScore,
	}))
	currentTime += 2.0

	// TEAM WIN
	winningTeam := "allies"
	if axisScore > alliesScore {
		winningTeam = "axis"
	}

	events = append(events, makeEvent(EventTeamWin, currentTime, map[string]interface{}{
		"match_id": matchID,
		"teamnum":  winningTeam,
	}))
	currentTime += 1.0

	// GAME END
	events = append(events, makeEvent(EventGameEnd, currentTime, map[string]interface{}{
		"match_id":   matchID,
		"session_id": sessionID,
	}))
	currentTime += 0.5

	// MATCH END
	events = append(events, makeEvent(EventMatchEnd, currentTime, map[string]interface{}{
		"match_id":     matchID,
		"duration":     currentTime - baseTime,
		"winning_team": winningTeam,
		"allies_score": alliesScore,
		"axis_score":   axisScore,
	}))
	currentTime += 1.0

	// INTERMISSION
	events = append(events, makeEvent(EventIntermissionStart, currentTime, map[string]interface{}{
		"gametype": gametype,
	}))
	currentTime += 10.0

	// PLAYERS DISCONNECT
	for i := range players {
		p := &players[i]
		
		disconnectData := map[string]interface{}{
			"match_id":    matchID,
			"player_name": p.Name,
			"player_guid": p.GUID,
		}
		events = append(events, makeEvent(EventClientDisconnect, currentTime, disconnectData))
		
		p.Alive = false
		currentTime += 0.2
	}

	return events
}

// generateGameplaySequence creates a realistic combat/gameplay sequence
func generateGameplaySequence(matchID string, currentTime *float64) []map[string]interface{} {
	events := []map[string]interface{}{}
	
	// Roll for event type
	roll := rand.Float32()
	
	switch {
	case roll < 0.35: // 35% - Combat
		combatEvents := generateCombatSequence(matchID, currentTime)
		events = append(events, combatEvents...)
		
	case roll < 0.50: // 15% - Movement
		moveEvents := generateMovementSequence(matchID, currentTime)
		events = append(events, moveEvents...)
		
	case roll < 0.60: // 10% - Item pickup
		itemEvents := generateItemSequence(matchID, currentTime)
		events = append(events, itemEvents...)
		
	case roll < 0.70: // 10% - Vehicle
		vehicleEvents := generateVehicleSequence(matchID, currentTime)
		events = append(events, vehicleEvents...)
		
	case roll < 0.75: // 5% - Chat
		chatEvent := generateChatEvent(matchID, *currentTime)
		if chatEvent != nil {
			events = append(events, chatEvent)
		}
		*currentTime += randomFloat(0.5, 2.0)
		
	case roll < 0.80: // 5% - Objective
		objEvents := generateObjectiveSequence(matchID, currentTime)
		events = append(events, objEvents...)
		
	case roll < 0.85: // 5% - World interaction
		worldEvents := generateWorldSequence(matchID, currentTime)
		events = append(events, worldEvents...)
		
	case roll < 0.90: // 5% - Bot interaction
		botEvents := generateBotSequence(matchID, currentTime)
		events = append(events, botEvents...)
		
	case roll < 0.95: // 5% - Grenade
		grenadeEvents := generateGrenadeSequence(matchID, currentTime)
		events = append(events, grenadeEvents...)
		
	default: // 5% - Special/Vote
		voteEvents := generateVoteSequence(matchID, currentTime)
		events = append(events, voteEvents...)
	}
	
	return events
}

func sendEvent(client *http.Client, s *Stats, data map[string]interface{}) {
	// Add server identifiers to all events
	if _, ok := data["server_id"]; !ok {
		data["server_id"] = ServerID
	}
	if _, ok := data["server_token"]; !ok {
		data["server_token"] = ServerToken
	}

	jsonData, err := json.Marshal(data)
	if err != nil {
		if *verbose {
			fmt.Printf("Error marshaling event: %v\n", err)
		}
		s.Error()
		return
	}

	req, err := http.NewRequest("POST", *apiURL+"/ingest/events", bytes.NewBuffer(jsonData))
	if err != nil {
		if *verbose {
			fmt.Printf("Error creating request: %v\n", err)
		}
		s.Error()
		return
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+ServerToken)

	resp, err := client.Do(req)
	if err != nil {
		if *verbose {
			fmt.Printf("Error sending event: %v\n", err)
		}
		s.Error()
		return
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK && resp.StatusCode != http.StatusAccepted {
		if *verbose {
			fmt.Printf("Unexpected status: %d for event type %s\n", resp.StatusCode, data["type"])
		}
		s.Error()
		return
	}

	s.Add(1)
}

// Helper function to create event with common fields
func makeEvent(eventType string, timestamp float64, extraData map[string]interface{}) map[string]interface{} {
	event := map[string]interface{}{
		"type":      eventType,
		"timestamp": timestamp,
	}
	
	for k, v := range extraData {
		event[k] = v
	}
	
	return event
}

// Combat sequence generator
func generateCombatSequence(matchID string, currentTime *float64) []map[string]interface{} {
	events := []map[string]interface{}{}
	alivePlayers := getAlivePlayers()
	if len(alivePlayers) < 2 {
		return events
	}

	attacker := alivePlayers[rand.Intn(len(alivePlayers))]
	victim := alivePlayers[rand.Intn(len(alivePlayers))]
	for victim == attacker && len(alivePlayers) > 1 {
		victim = alivePlayers[rand.Intn(len(alivePlayers))]
	}

	// Weapon fire sequence (3-8 shots)
	shotsToKill := rand.Intn(6) + 3
	hitCount := 0
	
	for i := 0; i < shotsToKill; i++ {
		// Fire event
		fireData := map[string]interface{}{
			"match_id":       matchID,
			"player_name":    attacker.Name,
			"player_guid":    attacker.GUID,
			"weapon":         attacker.Weapon,
			"ammo_remaining": attacker.Ammo - i - 1,
		}
		events = append(events, makeEvent(EventWeaponFire, *currentTime, fireData))
		*currentTime += randomFloat(0.1, 0.3)

		// Some shots hit (60% accuracy)
		if rand.Float32() < 0.6 {
			hitloc := randomHitLocation()
			damage := 25
			if hitloc == "head" {
				damage = 100
			}

			// Hit event
			hitData := map[string]interface{}{
				"match_id":    matchID,
				"player_name": attacker.Name,
				"player_guid": attacker.GUID,
				"target_name": victim.Name,
				"target_guid": victim.GUID,
				"weapon":      attacker.Weapon,
				"hitloc":      hitloc,
			}
			events = append(events, makeEvent(EventWeaponHit, *currentTime, hitData))

			// Pain event for victim
			painData := map[string]interface{}{
				"match_id":      matchID,
				"player_name":   victim.Name,
				"player_guid":   victim.GUID,
				"attacker_name": attacker.Name,
				"attacker_guid": attacker.GUID,
				"damage":        damage,
				"hitloc":        hitloc,
				"pos_x":         victim.PosX,
				"pos_y":         victim.PosY,
				"pos_z":         victim.PosZ,
			}
			events = append(events, makeEvent(EventPlayerPain, *currentTime, painData))

			victim.HP -= damage
			hitCount++
			*currentTime += 0.05
		}
	}

	// Check for special kill types
	specialKill := rand.Float32()
	var killEventType string
	isHeadshot := false

	if specialKill < 0.02 {
		killEventType = EventPlayerBash // 2%
	} else if specialKill < 0.04 {
		killEventType = EventPlayerRoadkill // 2%
	} else if specialKill < 0.05 {
		killEventType = EventPlayerTelefragged // 1%
	} else {
		killEventType = EventKill
		// 15% chance of headshot on normal kill
		isHeadshot = rand.Float32() < 0.15
	}

	// Headshot event (if applicable)
	if isHeadshot {
		hsData := map[string]interface{}{
			"match_id":      matchID,
			"attacker_name": attacker.Name,
			"attacker_guid": attacker.GUID,
			"victim_name":   victim.Name,
			"victim_guid":   victim.GUID,
			"weapon":        attacker.Weapon,
		}
		events = append(events, makeEvent(EventHeadshot, *currentTime, hsData))
	}

	// Kill event
	killData := map[string]interface{}{
		"match_id":      matchID,
		"attacker_name": attacker.Name,
		"attacker_guid": attacker.GUID,
		"attacker_team": attacker.Team,
		"victim_name":   victim.Name,
		"victim_guid":   victim.GUID,
		"victim_team":   victim.Team,
		"weapon":        attacker.Weapon,
		"hitloc":        randomHitLocation(),
		"attacker_x":    attacker.PosX,
		"attacker_y":    attacker.PosY,
		"attacker_z":    attacker.PosZ,
		"victim_x":      victim.PosX,
		"victim_y":      victim.PosY,
		"victim_z":      victim.PosZ,
	}
	events = append(events, makeEvent(killEventType, *currentTime, killData))

	// Death event
	deathData := map[string]interface{}{
		"match_id":    matchID,
		"player_name": victim.Name,
		"player_guid": victim.GUID,
		"player_team": victim.Team,
		"pos_x":       victim.PosX,
		"pos_y":       victim.PosY,
		"pos_z":       victim.PosZ,
	}
	events = append(events, makeEvent(EventDeath, *currentTime, deathData))

	victim.Alive = false
	victim.Deaths++
	attacker.Kills++
	*currentTime += 0.5

	// Respawn after delay
	*currentTime += 5.0
	victim.PosX = randomFloat(-1000, 1000)
	victim.PosY = randomFloat(-1000, 1000)
	victim.PosZ = randomFloat(0, 200)
	
	respawnData := map[string]interface{}{
		"match_id":    matchID,
		"player_name": victim.Name,
		"player_guid": victim.GUID,
		"player_team": victim.Team,
		"pos_x":       victim.PosX,
		"pos_y":       victim.PosY,
		"pos_z":       victim.PosZ,
	}
	events = append(events, makeEvent(EventPlayerRespawn, *currentTime, respawnData))
	victim.Alive = true
	victim.HP = 100

	// Check if attacker needs to reload
	attacker.Ammo -= shotsToKill
	if attacker.Ammo < 5 {
		*currentTime += 1.0
		reloadData := map[string]interface{}{
			"match_id":    matchID,
			"player_name": attacker.Name,
			"player_guid": attacker.GUID,
			"weapon":      attacker.Weapon,
		}
		events = append(events, makeEvent(EventWeaponReload, *currentTime, reloadData))

		*currentTime += 2.5 // Reload time
		events = append(events, makeEvent(EventWeaponReloadDone, *currentTime, reloadData))
		
		attacker.Ammo = 30
	}

	return events
}

// Movement sequence generator
func generateMovementSequence(matchID string, currentTime *float64) []map[string]interface{} {
	events := []map[string]interface{}{}
	alivePlayers := getAlivePlayers()
	if len(alivePlayers) == 0 {
		return events
	}

	player := alivePlayers[rand.Intn(len(alivePlayers))]
	
	moveType := rand.Intn(5)
	playerData := map[string]interface{}{
		"match_id":    matchID,
		"player_name": player.Name,
		"player_guid": player.GUID,
	}
	
	switch moveType {
	case 0: // Jump + Land
		events = append(events, makeEvent(EventJump, *currentTime, playerData))
		*currentTime += 0.8
		events = append(events, makeEvent(EventLand, *currentTime, playerData))
		
	case 1: // Crouch
		events = append(events, makeEvent(EventCrouch, *currentTime, playerData))
		player.Stance = "crouch"
		
	case 2: // Prone
		events = append(events, makeEvent(EventProne, *currentTime, playerData))
		player.Stance = "prone"
		
	case 3: // Stand
		events = append(events, makeEvent(EventPlayerStand, *currentTime, playerData))
		player.Stance = "stand"
		
	case 4: // Distance movement
		distData := map[string]interface{}{
			"match_id":    matchID,
			"player_name": player.Name,
			"player_guid": player.GUID,
			"walked":      randomFloat(10, 50),
			"sprinted":    randomFloat(5, 30),
		}
		events = append(events, makeEvent(EventDistance, *currentTime, distData))
		
		player.PosX += randomFloat(-50, 50)
		player.PosY += randomFloat(-50, 50)
	}
	
	*currentTime += randomFloat(1.0, 3.0)
	return events
}

// Item sequence generator
func generateItemSequence(matchID string, currentTime *float64) []map[string]interface{} {
	events := []map[string]interface{}{}
	alivePlayers := getAlivePlayers()
	if len(alivePlayers) == 0 {
		return events
	}

	player := alivePlayers[rand.Intn(len(alivePlayers))]
	
	itemType := rand.Intn(3)
	switch itemType {
	case 0: // Health pickup
		healthData := map[string]interface{}{
			"match_id":    matchID,
			"player_name": player.Name,
			"player_guid": player.GUID,
			"amount":      25,
		}
		events = append(events, makeEvent(EventHealthPickup, *currentTime, healthData))
		player.HP = min(100, player.HP+25)
		
	case 1: // Ammo pickup
		ammoData := map[string]interface{}{
			"match_id":    matchID,
			"player_name": player.Name,
			"player_guid": player.GUID,
			"item_name":   player.Weapon + " Ammo",
			"amount":      30,
		}
		events = append(events, makeEvent(EventAmmoPickup, *currentTime, ammoData))
		player.Ammo += 30
		
	case 2: // Generic item
		item := items[rand.Intn(len(items))]
		itemData := map[string]interface{}{
			"match_id":    matchID,
			"player_name": player.Name,
			"player_guid": player.GUID,
			"item":        item,
			"count":       1,
		}
		events = append(events, makeEvent(EventItemPickup, *currentTime, itemData))
	}
	
	*currentTime += randomFloat(2.0, 5.0)
	return events
}

// Vehicle sequence generator
func generateVehicleSequence(matchID string, currentTime *float64) []map[string]interface{} {
	events := []map[string]interface{}{}
	alivePlayers := getAlivePlayers()
	if len(alivePlayers) == 0 {
		return events
	}

	player := alivePlayers[rand.Intn(len(alivePlayers))]
	vehicleName := "Sherman Tank"
	if player.Team == "axis" {
		vehicleName = "Tiger Tank"
	}

	// Enter vehicle
	enterData := map[string]interface{}{
		"match_id":    matchID,
		"player_name": player.Name,
		"player_guid": player.GUID,
		"vehicle":     vehicleName,
	}
	events = append(events, makeEvent(EventVehicleEnter, *currentTime, enterData))
	player.InVehicle = true
	*currentTime += 1.0

	// Maybe get a roadkill
	if rand.Float32() < 0.3 {
		alivePlayers = getAlivePlayers()
		if len(alivePlayers) > 1 {
			for _, victim := range alivePlayers {
				if victim != player && victim.Alive {
					roadkillData := map[string]interface{}{
						"match_id":      matchID,
						"attacker_name": player.Name,
						"attacker_guid": player.GUID,
						"victim_name":   victim.Name,
						"victim_guid":   victim.GUID,
					}
					*currentTime += 5.0
					events = append(events, makeEvent(EventPlayerRoadkill, *currentTime, roadkillData))
					victim.Alive = false
					break
				}
			}
		}
	}

	// Exit vehicle
	*currentTime += 10.0
	exitData := map[string]interface{}{
		"match_id":    matchID,
		"player_name": player.Name,
		"player_guid": player.GUID,
		"vehicle":     vehicleName,
	}
	events = append(events, makeEvent(EventVehicleExit, *currentTime, exitData))
	player.InVehicle = false
	*currentTime += 1.0

	return events
}

// Grenade sequence generator
func generateGrenadeSequence(matchID string, currentTime *float64) []map[string]interface{} {
	events := []map[string]interface{}{}
	alivePlayers := getAlivePlayers()
	if len(alivePlayers) == 0 {
		return events
	}

	player := alivePlayers[rand.Intn(len(alivePlayers))]

	// Throw grenade
	throwData := map[string]interface{}{
		"match_id":    matchID,
		"player_name": player.Name,
		"player_guid": player.GUID,
		"projectile":  "grenade",
	}
	events = append(events, makeEvent(EventGrenadeThrow, *currentTime, throwData))
	*currentTime += 3.0

	// Explode
	explodeData := map[string]interface{}{
		"match_id":    matchID,
		"player_name": player.Name,
		"player_guid": player.GUID,
		"projectile":  "grenade",
	}
	events = append(events, makeEvent(EventGrenadeExplode, *currentTime, explodeData))
	
	// Explosion event
	explosionData := map[string]interface{}{
		"match_id":      matchID,
		"attacker_name": player.Name,
		"attacker_guid": player.GUID,
		"damage":        75,
	}
	events = append(events, makeEvent(EventExplosion, *currentTime, explosionData))
	*currentTime += 0.5

	return events
}

// Chat event generator
func generateChatEvent(matchID string, currentTime float64) map[string]interface{} {
	if len(players) == 0 {
		return nil
	}

	player := &players[rand.Intn(len(players))]
	message := chatMessages[rand.Intn(len(chatMessages))]

	return makeEvent(EventPlayerSay, currentTime, map[string]interface{}{
		"match_id":    matchID,
		"player_name": player.Name,
		"player_guid": player.GUID,
		"message":     message,
	})
}

// Objective sequence generator
func generateObjectiveSequence(matchID string, currentTime *float64) []map[string]interface{} {
	events := []map[string]interface{}{}
	alivePlayers := getAlivePlayers()
	if len(alivePlayers) == 0 {
		return events
	}

	player := alivePlayers[rand.Intn(len(alivePlayers))]
	objName := fmt.Sprintf("obj_%d", rand.Intn(3)+1)

	// Start using object
	startData := map[string]interface{}{
		"match_id":    matchID,
		"player_name": player.Name,
		"player_guid": player.GUID,
		"object":      objName,
	}
	events = append(events, makeEvent(EventPlayerUseObjectStart, *currentTime, startData))
	*currentTime += 8.0

	// Finish using object
	events = append(events, makeEvent(EventPlayerUseObjectFinish, *currentTime, startData))
	*currentTime += 0.5

	// Objective capture
	captureData := map[string]interface{}{
		"match_id":  matchID,
		"objective": objName,
		"team":      player.Team,
	}
	events = append(events, makeEvent(EventObjectiveCapture, *currentTime, captureData))
	*currentTime += 1.0

	return events
}

// World sequence generator
func generateWorldSequence(matchID string, currentTime *float64) []map[string]interface{} {
	events := []map[string]interface{}{}
	alivePlayers := getAlivePlayers()
	if len(alivePlayers) == 0 {
		return events
	}

	player := alivePlayers[rand.Intn(len(alivePlayers))]

	// Open door
	doorData := map[string]interface{}{
		"match_id":    matchID,
		"door":        "door_" + fmt.Sprintf("%d", rand.Intn(10)+1),
		"player_name": player.Name,
		"player_guid": player.GUID,
	}
	events = append(events, makeEvent(EventDoorOpen, *currentTime, doorData))
	*currentTime += 5.0

	// Close door
	events = append(events, makeEvent(EventDoorClose, *currentTime, map[string]interface{}{
		"match_id": matchID,
		"door":     doorData["door"],
	}))
	*currentTime += 1.0

	return events
}

// Bot sequence generator
func generateBotSequence(matchID string, currentTime *float64) []map[string]interface{} {
	events := []map[string]interface{}{}
	alivePlayers := getAlivePlayers()
	if len(alivePlayers) == 0 {
		return events
	}

	player := alivePlayers[rand.Intn(len(alivePlayers))]
	botID := fmt.Sprintf("bot_%d", rand.Intn(10)+1)

	// Bot behavior
	behaviorRoll := rand.Intn(3)
	botData := map[string]interface{}{
		"match_id": matchID,
		"bot_id":   botID,
	}

	switch behaviorRoll {
	case 0:
		events = append(events, makeEvent(EventBotRoam, *currentTime, botData))
	case 1:
		events = append(events, makeEvent(EventBotCurious, *currentTime, botData))
	case 2:
		events = append(events, makeEvent(EventBotAttack, *currentTime, botData))
	}
	*currentTime += 3.0

	// Player kills bot
	if rand.Float32() < 0.5 {
		killData := map[string]interface{}{
			"match_id":      matchID,
			"bot_id":        botID,
			"attacker_name": player.Name,
			"attacker_guid": player.GUID,
		}
		events = append(events, makeEvent(EventBotKilled, *currentTime, killData))
	}
	
	*currentTime += 2.0
	return events
}

// Vote sequence generator
func generateVoteSequence(matchID string, currentTime *float64) []map[string]interface{} {
	events := []map[string]interface{}{}
	if len(players) == 0 {
		return events
	}

	player := &players[rand.Intn(len(players))]
	voteName := "map_change"
	voteString := "Change map to dm/mohdm3"

	// Start vote
	voteData := map[string]interface{}{
		"match_id":    matchID,
		"player_name": player.Name,
		"player_guid": player.GUID,
		"vote_name":   voteName,
		"vote_string": voteString,
	}
	events = append(events, makeEvent(EventVoteStart, *currentTime, voteData))
	*currentTime += 30.0

	// Vote result
	yesCount := rand.Intn(len(players))
	noCount := len(players) - yesCount

	resultData := map[string]interface{}{
		"match_id":    matchID,
		"vote_name":   voteName,
		"vote_string": voteString,
		"yes_count":   yesCount,
		"no_count":    noCount,
	}

	if yesCount > noCount {
		events = append(events, makeEvent(EventVotePassed, *currentTime, resultData))
	} else {
		resultData["fail_reason"] = "Not enough votes"
		events = append(events, makeEvent(EventVoteFailed, *currentTime, resultData))
	}
	
	*currentTime += 1.0
	return events
}

// Helper functions
func getAlivePlayers() []*Player {
	alive := []*Player{}
	for i := range players {
		if players[i].Alive {
			alive = append(alive, &players[i])
		}
	}
	return alive
}

func countAlivePlayers() int {
	count := 0
	for i := range players {
		if players[i].Alive {
			count++
		}
	}
	return count
}

func randomWeapon() string {
	return weapons[rand.Intn(len(weapons))]
}

func randomMap() string {
	return maps[rand.Intn(len(maps))]
}

func randomGametype() string {
	gametypes := []string{"deathmatch", "team_deathmatch", "objective", "roundbased"}
	return gametypes[rand.Intn(len(gametypes))]
}

func randomHitLocation() string {
	return hitlocs[rand.Intn(len(hitlocs))]
}

func randomFloat(min, max float64) float64 {
	return min + rand.Float64()*(max-min)
}

func min(a, b int) int {
	if a < b {
		return a
	}
	return b
}

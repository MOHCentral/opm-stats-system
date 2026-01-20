package main

import (
	"bytes"
	"database/sql"
	"encoding/json"
	"fmt"
	"math"
	"math/rand"
	"net/http"
	"os"
	"sync"
	"sync/atomic"
	"time"

	"github.com/google/uuid"
	_ "github.com/lib/pq"
)

const (
	APIURL       = "http://localhost:8080/api/v1/ingest/events"
	ServerToken  = "test-token"
	Concurrency  = 10
	PostgresURL  = "postgres://mohaa:admin123@localhost:5432/mohaa_stats?sslmode=disable"
	ClickHouseURL = "http://localhost:8123"
)

type Player struct {
	Name     string
	GUID     string
	Team     string
	Skill    float32
	Style    string
	Favorite string
}

var players = []Player{
	{"Sgt.Physics", "GUID_001", "allies", 0.85, "aggressive", "Thompson"},
	{"Cpt.Logic", "GUID_002", "axis", 0.80, "defensive", "MP40"},
	{"Pvt.Panic", "GUID_003", "allies", 0.45, "rusher", "M1 Garand"},
	{"Major.Lag", "GUID_004", "axis", 0.70, "sniper", "Kar98k"},
	{"Sniper.Wolf", "GUID_005", "allies", 0.95, "sniper", "Springfield"},
	{"Running.Man", "GUID_006", "axis", 0.60, "rusher", "STG44"},
	{"Camper.Joe", "GUID_007", "allies", 0.55, "defensive", "BAR"},
	{"Rusher.B", "GUID_008", "axis", 0.75, "aggressive", "MP40"},
	{"Noob.Slayer", "GUID_009", "allies", 0.90, "aggressive", "Thompson"},
	{"Bot.Alice", "GUID_010", "axis", 0.50, "defensive", "Kar98k"},
	{"FragMaster", "GUID_011", "allies", 0.88, "aggressive", "Thompson"},
	{"ShadowKill", "GUID_012", "axis", 0.82, "sniper", "Kar98k"},
	{"IronSights", "GUID_013", "allies", 0.77, "defensive", "M1 Garand"},
	{"QuickScope", "GUID_014", "axis", 0.92, "sniper", "Kar98k"},
	{"TankBuster", "GUID_015", "allies", 0.65, "aggressive", "Bazooka"},
	{"GrenadeKing", "GUID_016", "axis", 0.58, "rusher", "Grenade"},
	{"MedicMike", "GUID_017", "allies", 0.62, "defensive", "Thompson"},
	{"CaptainFlank", "GUID_018", "axis", 0.79, "rusher", "MP40"},
	{"NightOwl", "GUID_019", "allies", 0.73, "sniper", "Springfield"},
	{"DayWalker", "GUID_020", "axis", 0.68, "aggressive", "STG44"},
	{"ProPlayer", "GUID_021", "allies", 0.97, "aggressive", "Thompson"},
	{"Noob123", "GUID_022", "axis", 0.25, "rusher", "MP40"},
	{"TryHard", "GUID_023", "allies", 0.84, "aggressive", "BAR"},
	{"Casual.Gamer", "GUID_024", "axis", 0.40, "defensive", "Kar98k"},
	{"VeteranVic", "GUID_025", "allies", 0.86, "defensive", "M1 Garand"},
}

var weapons = []string{
	"Thompson", "MP40", "M1 Garand", "Kar98k", "BAR", "STG44",
	"Springfield", "Kar98k Sniper", "Bazooka", "Panzerschreck",
	"Colt .45", "Walther P38", "Grenade", "Stielhandgranate", "Shotgun", "Fists",
}

var hitlocs = []string{
	"head", "neck", "torso_upper", "torso_lower",
	"left_arm_upper", "left_arm_lower", "right_arm_upper", "right_arm_lower",
	"left_leg_upper", "left_leg_lower", "right_leg_upper", "right_leg_lower",
}

var maps = []string{
	"dm/mohdm1", "dm/mohdm2", "dm/mohdm3", "dm/mohdm4", "dm/mohdm5", "dm/mohdm6", "dm/mohdm7",
	"obj/obj_team1", "obj/obj_team2", "obj/obj_team3", "obj/obj_team4",
	"lib/stalingrad", "lib/hunt", "lib/flughafen",
	"tdm/v2rocket", "tdm/destroyed_village", "tdm/bridge",
}

var gametypes = []string{"dm", "tdm", "obj", "lib", "ctf"}
var items = []string{"health_large", "health_small", "ammo_thompson", "ammo_mp40", "ammo_rifle", "armor"}

var servers = []struct {
	ID   string
	Name string
}{
	{"srv-us-east-1", "US East #1"},
	{"srv-us-west-1", "US West #1"},
	{"srv-eu-central-1", "EU Central #1"},
	{"srv-eu-west-1", "EU West #1"},
	{"srv-asia-1", "Asia Pacific #1"},
}

type Stats struct {
	eventsSent   int64
	eventsErrors int64
	matchesRun   int64
	startTime    time.Time
}

func (s *Stats) AddEvents(n int)  { atomic.AddInt64(&s.eventsSent, int64(n)) }
func (s *Stats) AddError()        { atomic.AddInt64(&s.eventsErrors, 1) }
func (s *Stats) AddMatch()        { atomic.AddInt64(&s.matchesRun, 1) }

func (s *Stats) Print() {
	elapsed := time.Since(s.startTime).Seconds()
	events := atomic.LoadInt64(&s.eventsSent)
	errors := atomic.LoadInt64(&s.eventsErrors)
	matches := atomic.LoadInt64(&s.matchesRun)
	fmt.Printf("\n=== Seeding Complete ===\n")
	fmt.Printf("Matches:      %d\n", matches)
	fmt.Printf("Events sent:  %d\n", events)
	fmt.Printf("Errors:       %d\n", errors)
	fmt.Printf("Duration:     %.2fs\n", elapsed)
	fmt.Printf("Rate:         %.0f events/sec\n", float64(events)/elapsed)
}

func generatePosition(mapName string) (x, y, z float32) {
	return rand.Float32()*4000 - 2000, rand.Float32()*4000 - 2000, rand.Float32()*200 + 10
}

func generateAimAngles() (pitch, yaw float32) {
	return rand.Float32()*60 - 30, rand.Float32() * 360
}

func calculateDistance(x1, y1, z1, x2, y2, z2 float32) float32 {
	dx, dy, dz := x2-x1, y2-y1, z2-z1
	return float32(math.Sqrt(float64(dx*dx + dy*dy + dz*dz)))
}

func selectWeapon(player Player) string {
	if rand.Float32() < 0.6 {
		return player.Favorite
	}
	return weapons[rand.Intn(len(weapons))]
}

func selectHitloc(skill float32) string {
	if rand.Float32() < skill*0.3 {
		return "head"
	}
	if rand.Float32() < 0.5 {
		return "torso_upper"
	}
	return hitlocs[rand.Intn(len(hitlocs))]
}

type MatchSimulator struct {
	client  *http.Client
	stats   *Stats
	matchID string
	server  struct{ ID, Name string }
	mapName string
	players []Player
	baseTS  float64
}

func NewMatchSimulator(client *http.Client, stats *Stats) *MatchSimulator {
	server := servers[rand.Intn(len(servers))]
	mapName := maps[rand.Intn(len(maps))]
	numPlayers := 8 + rand.Intn(9)
	shuffled := make([]Player, len(players))
	copy(shuffled, players)
	rand.Shuffle(len(shuffled), func(i, j int) { shuffled[i], shuffled[j] = shuffled[j], shuffled[i] })
	matchPlayers := shuffled[:numPlayers]
	for i := range matchPlayers {
		if i%2 == 0 {
			matchPlayers[i].Team = "allies"
		} else {
			matchPlayers[i].Team = "axis"
		}
	}
	return &MatchSimulator{
		client:  client,
		stats:   stats,
		matchID: uuid.New().String(),
		server:  struct{ ID, Name string }{server.ID, server.Name},
		mapName: mapName,
		players: matchPlayers,
		baseTS:  float64(time.Now().Add(-time.Duration(rand.Intn(720)) * time.Hour).Unix()),
	}
}

func (m *MatchSimulator) Run() {
	m.sendEvent(map[string]interface{}{
		"type": "match_start", "match_id": m.matchID, "server_id": m.server.ID,
		"server_token": ServerToken, "map_name": m.mapName,
		"gametype": gametypes[rand.Intn(len(gametypes))], "timestamp": m.baseTS,
		"maxclients": fmt.Sprintf("%d", 20+rand.Intn(12)),
		"timelimit":  fmt.Sprintf("%d", 15+rand.Intn(15)),
	})

	for _, p := range m.players {
		m.baseTS += float64(rand.Intn(3) + 1)
		m.sendEvent(map[string]interface{}{
			"type": "connect", "match_id": m.matchID, "timestamp": m.baseTS,
			"player_name": p.Name, "player_guid": p.GUID, "player_team": p.Team,
		})
		x, y, z := generatePosition(m.mapName)
		m.sendEvent(map[string]interface{}{
			"type": "spawn", "match_id": m.matchID, "timestamp": m.baseTS + 0.5,
			"player_name": p.Name, "player_guid": p.GUID, "player_team": p.Team,
			"pos_x": x, "pos_y": y, "pos_z": z,
		})
	}

	numRounds := 1 + rand.Intn(5)
	for round := 1; round <= numRounds; round++ {
		m.simulateRound(round, numRounds)
	}

	for _, p := range m.players {
		m.baseTS += float64(rand.Intn(5))
		m.sendEvent(map[string]interface{}{
			"type": "disconnect", "match_id": m.matchID, "timestamp": m.baseTS,
			"player_name": p.Name, "player_guid": p.GUID,
		})
	}

	alliesScore, axisScore := rand.Intn(50), rand.Intn(50)
	winningTeam := "draw"
	if alliesScore > axisScore {
		winningTeam = "allies"
	} else if axisScore > alliesScore {
		winningTeam = "axis"
	}
	m.sendEvent(map[string]interface{}{
		"type": "match_end", "match_id": m.matchID, "server_id": m.server.ID,
		"server_token": ServerToken, "timestamp": m.baseTS,
		"allies_score": alliesScore, "axis_score": axisScore, "winning_team": winningTeam,
	})
	m.stats.AddMatch()
}

func (m *MatchSimulator) simulateRound(roundNum, totalRounds int) {
	m.baseTS += float64(rand.Intn(5) + 1)
	m.sendEvent(map[string]interface{}{
		"type": "round_start", "match_id": m.matchID, "timestamp": m.baseTS,
		"round_number": roundNum, "total_rounds": totalRounds,
	})

	numEvents := 50 + rand.Intn(150)
	for i := 0; i < numEvents; i++ {
		m.baseTS += float64(rand.Intn(3)) + rand.Float64()
		actor := m.players[rand.Intn(len(m.players))]
		r := rand.Float32()
		if r < 0.65 {
			m.simulateCombat(actor)
		} else if r < 0.85 {
			m.simulateMovement(actor)
		} else if r < 0.95 {
			m.simulateInteraction(actor)
		} else {
			m.simulateChat(actor)
		}
	}

	m.baseTS += float64(rand.Intn(3) + 1)
	m.sendEvent(map[string]interface{}{
		"type": "round_end", "match_id": m.matchID, "timestamp": m.baseTS,
		"round_number": roundNum, "winning_team": []string{"allies", "axis"}[rand.Intn(2)],
	})
}

func (m *MatchSimulator) simulateCombat(attacker Player) {
	var victim Player
	for {
		victim = m.players[rand.Intn(len(m.players))]
		if victim.GUID != attacker.GUID {
			break
		}
	}

	weapon := selectWeapon(attacker)
	hitloc := selectHitloc(attacker.Skill)
	aX, aY, aZ := generatePosition(m.mapName)
	aPitch, aYaw := generateAimAngles()
	vX, vY, vZ := generatePosition(m.mapName)

	m.sendEvent(map[string]interface{}{
		"type": "weapon_fire", "match_id": m.matchID, "timestamp": m.baseTS,
		"player_name": attacker.Name, "player_guid": attacker.GUID, "player_team": attacker.Team,
		"weapon": weapon, "pos_x": aX, "pos_y": aY, "pos_z": aZ,
		"aim_pitch": aPitch, "aim_yaw": aYaw, "ammo_remaining": rand.Intn(30) + 1,
	})

	if rand.Float32() < attacker.Skill*0.7 {
		m.sendEvent(map[string]interface{}{
			"type": "weapon_hit", "match_id": m.matchID, "timestamp": m.baseTS + 0.05,
			"player_name": attacker.Name, "player_guid": attacker.GUID, "player_team": attacker.Team,
			"target_name": victim.Name, "target_guid": victim.GUID,
			"weapon": weapon, "hitloc": hitloc, "pos_x": aX, "pos_y": aY, "pos_z": aZ,
		})

		damage := 15 + rand.Intn(40)
		if hitloc == "head" {
			damage = 80 + rand.Intn(40)
		}
		m.sendEvent(map[string]interface{}{
			"type": "damage", "match_id": m.matchID, "timestamp": m.baseTS + 0.1,
			"attacker_name": attacker.Name, "attacker_guid": attacker.GUID, "attacker_team": attacker.Team,
			"attacker_x": aX, "attacker_y": aY, "attacker_z": aZ,
			"victim_name": victim.Name, "victim_guid": victim.GUID, "victim_team": victim.Team,
			"weapon": weapon, "damage": damage, "hitloc": hitloc,
		})

		if rand.Float32() < float32(damage)/150.0 {
			m.sendEvent(map[string]interface{}{
				"type": "kill", "match_id": m.matchID, "timestamp": m.baseTS + 0.15,
				"attacker_name": attacker.Name, "attacker_guid": attacker.GUID, "attacker_team": attacker.Team,
				"attacker_x": aX, "attacker_y": aY, "attacker_z": aZ,
				"attacker_pitch": aPitch, "attacker_yaw": aYaw,
				"victim_name": victim.Name, "victim_guid": victim.GUID, "victim_team": victim.Team,
				"weapon": weapon, "damage": 100, "hitloc": hitloc,
			})
			m.sendEvent(map[string]interface{}{
				"type": "death", "match_id": m.matchID, "timestamp": m.baseTS + 0.15,
				"player_name": victim.Name, "player_guid": victim.GUID, "player_team": victim.Team,
				"pos_x": vX, "pos_y": vY, "pos_z": vZ, "inflictor": attacker.Name,
			})
			if hitloc == "head" {
				m.sendEvent(map[string]interface{}{
					"type": "headshot", "match_id": m.matchID, "timestamp": m.baseTS + 0.15,
					"attacker_name": attacker.Name, "attacker_guid": attacker.GUID,
					"victim_name": victim.Name, "victim_guid": victim.GUID, "weapon": weapon,
					"distance": calculateDistance(aX, aY, aZ, vX, vY, vZ),
				})
			}
			m.baseTS += float64(rand.Intn(3) + 2)
			newX, newY, newZ := generatePosition(m.mapName)
			m.sendEvent(map[string]interface{}{
				"type": "spawn", "match_id": m.matchID, "timestamp": m.baseTS,
				"player_name": victim.Name, "player_guid": victim.GUID, "player_team": victim.Team,
				"pos_x": newX, "pos_y": newY, "pos_z": newZ,
			})
		}
	}
}

func (m *MatchSimulator) simulateMovement(player Player) {
	x, y, z := generatePosition(m.mapName)
	events := []string{"jump", "crouch", "prone", "distance"}
	event := events[rand.Intn(len(events))]

	data := map[string]interface{}{
		"type": event, "match_id": m.matchID, "timestamp": m.baseTS,
		"player_name": player.Name, "player_guid": player.GUID, "player_team": player.Team,
		"pos_x": x, "pos_y": y, "pos_z": z,
	}
	if event == "jump" {
		m.sendEvent(data)
		m.baseTS += 0.5 + rand.Float64()
		data["type"] = "land"
		data["timestamp"] = m.baseTS
		data["fall_height"] = rand.Float32() * 5
	} else if event == "distance" {
		data["walked"] = rand.Float32() * 100
		data["sprinted"] = rand.Float32() * 200
		data["swam"] = rand.Float32() * 20
	}
	m.sendEvent(data)
}

func (m *MatchSimulator) simulateInteraction(player Player) {
	x, y, z := generatePosition(m.mapName)
	events := []string{"item_pickup", "use", "ladder_mount", "weapon_change", "reload", "grenade_throw"}
	event := events[rand.Intn(len(events))]

	data := map[string]interface{}{
		"type": event, "match_id": m.matchID, "timestamp": m.baseTS,
		"player_name": player.Name, "player_guid": player.GUID, "player_team": player.Team,
		"pos_x": x, "pos_y": y, "pos_z": z,
	}
	switch event {
	case "item_pickup":
		data["item"] = items[rand.Intn(len(items))]
		data["count"] = 1 + rand.Intn(3)
	case "weapon_change":
		data["old_weapon"] = weapons[rand.Intn(len(weapons))]
		data["new_weapon"] = weapons[rand.Intn(len(weapons))]
	case "reload":
		data["weapon"] = selectWeapon(player)
		data["ammo_remaining"] = rand.Intn(30)
	case "grenade_throw":
		if rand.Float32() > 0.5 {
			data["projectile"] = "Grenade"
		} else {
			data["projectile"] = "Stielhandgranate"
		}
	}
	m.sendEvent(data)

	if event == "ladder_mount" {
		m.baseTS += float64(rand.Intn(5) + 1)
		data["type"] = "ladder_dismount"
		data["timestamp"] = m.baseTS
		m.sendEvent(data)
	} else if event == "grenade_throw" {
		m.baseTS += float64(rand.Intn(3) + 1)
		data["type"] = "grenade_explode"
		data["timestamp"] = m.baseTS
		m.sendEvent(data)
	}
}

func (m *MatchSimulator) simulateChat(player Player) {
	messages := []string{"gg", "nice shot", "lol", "noob", "haha", "rush B",
		"cover me", "need backup", "grenade!", "sniper!", "nice", "wp", "go go go", "fall back", "ez", "rekt"}
	m.sendEvent(map[string]interface{}{
		"type": "chat", "match_id": m.matchID, "timestamp": m.baseTS,
		"player_name": player.Name, "player_guid": player.GUID, "player_team": player.Team,
		"message": messages[rand.Intn(len(messages))],
	})
}

func (m *MatchSimulator) sendEvent(data map[string]interface{}) {
	data["map_name"] = m.mapName
	data["server_id"] = m.server.ID
	jsonData, _ := json.Marshal(data)
	req, err := http.NewRequest("POST", APIURL, bytes.NewBuffer(jsonData))
	if err != nil {
		m.stats.AddError()
		return
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+ServerToken)
	resp, err := m.client.Do(req)
	if err != nil {
		m.stats.AddError()
		return
	}
	defer resp.Body.Close()
	if resp.StatusCode != http.StatusAccepted && resp.StatusCode != http.StatusOK {
		m.stats.AddError()
		return
	}
	m.stats.AddEvents(1)
}

func clearClickHouse() error {
	tables := []string{
		"raw_events", "player_kills_hourly_mv", "player_stats_daily_mv", "weapon_stats_mv",
		"map_stats_mv", "kill_heatmap_mv", "death_heatmap_mv", "match_summary_mv",
		"server_activity_mv", "leaderboard_global", "leaderboard_weekly",
		"leaderboard_weapon", "leaderboard_map",
	}
	client := &http.Client{Timeout: 10 * time.Second}
	for _, table := range tables {
		query := fmt.Sprintf("TRUNCATE TABLE IF EXISTS %s", table)
		req, _ := http.NewRequest("POST", ClickHouseURL, bytes.NewBufferString(query))
		resp, err := client.Do(req)
		if err != nil {
			fmt.Printf("  Warning: Could not truncate %s: %v\n", table, err)
			continue
		}
		resp.Body.Close()
		if resp.StatusCode == http.StatusOK {
			fmt.Printf("  ✓ Cleared %s\n", table)
		}
	}
	return nil
}

func clearPostgres() error {
	db, err := sql.Open("postgres", PostgresURL)
	if err != nil {
		return fmt.Errorf("failed to connect to postgres: %v", err)
	}
	defer db.Close()
	tables := []string{"player_achievements", "match_participants", "matches", "player_identities"}
	for _, table := range tables {
		_, err := db.Exec(fmt.Sprintf("TRUNCATE TABLE %s CASCADE", table))
		if err != nil {
			fmt.Printf("  Warning: Could not truncate %s: %v\n", table, err)
			continue
		}
		fmt.Printf("  ✓ Cleared %s\n", table)
	}
	return nil
}

func clearRedis() error {
	fmt.Println("  Note: Redis flush requires redis-cli. Run manually if needed:")
	fmt.Println("    redis-cli FLUSHALL")
	return nil
}

func printUsage() {
	fmt.Println(`
MOHAA Stats CLI - Test Data Generator

Usage:
  statscli <command> [options]

Commands:
  seed [count]    Generate test matches (default: 100)
  clear           Clear all test data from databases
  status          Show database status

Examples:
  statscli seed          # Generate 100 matches
  statscli seed 500      # Generate 500 matches
  statscli clear         # Clear all data
`)
}

func cmdSeed(numMatches int) {
	fmt.Printf("🚀 Seeding %d matches across %d servers...\n\n", numMatches, len(servers))
	stats := &Stats{startTime: time.Now()}
	client := &http.Client{Timeout: 5 * time.Second}

	done := make(chan bool)
	go func() {
		ticker := time.NewTicker(2 * time.Second)
		defer ticker.Stop()
		for {
			select {
			case <-ticker.C:
				events := atomic.LoadInt64(&stats.eventsSent)
				matches := atomic.LoadInt64(&stats.matchesRun)
				fmt.Printf("\r  Progress: %d/%d matches, %d events...", matches, numMatches, events)
			case <-done:
				return
			}
		}
	}()

	var wg sync.WaitGroup
	matchChan := make(chan int, numMatches)
	for i := 0; i < numMatches; i++ {
		matchChan <- i
	}
	close(matchChan)

	for i := 0; i < Concurrency; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			for range matchChan {
				sim := NewMatchSimulator(client, stats)
				sim.Run()
			}
		}()
	}
	wg.Wait()
	done <- true
	stats.Print()
}

func cmdClear() {
	fmt.Println("🧹 Clearing all test data...\n")
	fmt.Println("Clearing ClickHouse...")
	clearClickHouse()
	fmt.Println("\nClearing PostgreSQL...")
	clearPostgres()
	fmt.Println("\nClearing Redis...")
	clearRedis()
	fmt.Println("\n✓ Clear complete!")
}

func cmdStatus() {
	fmt.Println("📊 Database Status\n")
	client := &http.Client{Timeout: 5 * time.Second}
	resp, err := client.Get(ClickHouseURL + "/?query=SELECT%20count()%20FROM%20raw_events")
	if err != nil {
		fmt.Printf("ClickHouse: ❌ Not reachable\n")
	} else {
		defer resp.Body.Close()
		var buf bytes.Buffer
		buf.ReadFrom(resp.Body)
		fmt.Printf("ClickHouse: ✓ Connected (%s events)\n", bytes.TrimSpace(buf.Bytes()))
	}

	db, err := sql.Open("postgres", PostgresURL)
	if err != nil {
		fmt.Printf("PostgreSQL: ❌ Connection error: %v\n", err)
	} else {
		defer db.Close()
		if err := db.Ping(); err != nil {
			fmt.Printf("PostgreSQL: ❌ Ping failed: %v\n", err)
		} else {
			fmt.Printf("PostgreSQL: ✓ Connected\n")
		}
	}

	resp, err = client.Get("http://localhost:8080/health")
	if err != nil {
		fmt.Printf("API Server: ❌ Not reachable\n")
	} else {
		defer resp.Body.Close()
		fmt.Printf("API Server: ✓ Healthy\n")
	}
}

func main() {
	rand.Seed(time.Now().UnixNano())
	if len(os.Args) < 2 {
		printUsage()
		os.Exit(1)
	}
	switch os.Args[1] {
	case "seed":
		numMatches := 100
		if len(os.Args) > 2 {
			fmt.Sscanf(os.Args[2], "%d", &numMatches)
		}
		cmdSeed(numMatches)
	case "clear":
		cmdClear()
	case "status":
		cmdStatus()
	case "help", "-h", "--help":
		printUsage()
	default:
		fmt.Printf("Unknown command: %s\n", os.Args[1])
		printUsage()
		os.Exit(1)
	}
}

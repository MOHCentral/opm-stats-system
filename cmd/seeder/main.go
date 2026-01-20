package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"math/rand"
	"net/http"
	"net/url"
	// "strconv"
	"sync"
	"time"

	"github.com/google/uuid"
)

// Constants
const (
	TotalEvents = 10000
	APIURL      = "http://localhost:8080/api/v1"

	ServerID    = "seeder-server-01"
	ServerToken = "test-token"
	Concurrecy  = 5
)

// Event types (must match internal/models/events.go)
const (
	EventConnect    = "connect"
	EventMatchStart = "match_start"
	EventMatchEnd   = "match_end"
	EventKill       = "kill"
	EventDamage     = "damage"
	EventWeaponFire = "weapon_fire"
)

type Player struct {
	Name     string
	GUID     string
	Team     string
	UserID   int // SMF User ID
	MemberID int // Confirmed Member ID after login
}

var players = []Player{
	{"Sgt.Physics", "111111", "allies", 1, 0},
	{"Cpt.Logic", "222222", "axis", 2, 0},
	{"Pvt.Panic", "333333", "allies", 3, 0},
	{"Major.Lag", "444444", "axis", 4, 0},
	{"Sniper.Wolf", "555555", "allies", 5, 0},
	{"Running.Man", "666666", "axis", 6, 0},
	{"Camper.Joe", "777777", "allies", 7, 0},
	{"Rusher.B", "888888", "axis", 8, 0},
	{"Noob.Slayer", "999999", "allies", 9, 0},
	{"Bot.Alice", "000000", "axis", 10, 0},
}

var weapons = []string{"Thompson", "MP40", "M1 Garand", "Kar98k", "BAR", "STG44", "Bazooka", "Grenade"}
var hitlocs = []string{"head", "torso", "torso", "torso", "left_arm", "right_arm", "left_leg", "right_leg"}
var maps = []string{"dm/mohdm1", "dm/mohdm2", "obj/obj_team1", "obj/obj_team2"}

func main() {
	fmt.Printf("Starting seeder... Target: %d events\n", TotalEvents)
	start := time.Now()

	stats := &Stats{}
	wg := &sync.WaitGroup{}

	// Authenticate players first
	fmt.Println("Authenticating players...")
	client := &http.Client{Timeout: 5 * time.Second}
	for i := range players {
		authenticatePlayer(client, &players[i])
	}
	fmt.Println("Players authenticated.")

	// Create channels for work
	// We will simulate matches in parallel
	matchesToRun := 50 // Run 50 matches (approx 200 events each)

	for i := 0; i < Concurrecy; i++ {
		wg.Add(1)
		go worker(wg, matchesToRun/Concurrecy, stats)
	}

	wg.Wait()

	duration := time.Since(start)
	fmt.Printf("\nDone! Sent %d events in %v (%.2f events/sec)\n", stats.total, duration, float64(stats.total)/duration.Seconds())
	fmt.Printf("Errors: %d\n", stats.errors)
}

func authenticatePlayer(client *http.Client, p *Player) {
	// 1. Get Token (POST /auth/device)
	tokenReq := map[string]interface{}{
		"forum_user_id": p.UserID,
		"client_ip":     "127.0.0.1",
	}
	jsonData, _ := json.Marshal(tokenReq)
	req, _ := http.NewRequest("POST", APIURL+"/auth/device", bytes.NewBuffer(jsonData))
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

	resp, err = client.PostForm(APIURL+"/auth/smf-verify", form)
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

func worker(wg *sync.WaitGroup, count int, s *Stats) {
	defer wg.Done()

	client := &http.Client{
		Timeout: 2 * time.Second,
	}

	for i := 0; i < count; i++ {
		runMatch(client, s)
	}
}

func runMatch(client *http.Client, s *Stats) {
	matchID := uuid.New().String()
	mapName := maps[rand.Intn(len(maps))]
	timestamp := float64(time.Now().Unix())

	// 1. Match Start
	sendEvent(client, s, map[string]interface{}{
		"type":         EventMatchStart,
		"match_id":     matchID,
		"server_id":    ServerID,
		"server_token": ServerToken,
		"map_name":     mapName,
		"gametype":     "tdm",
		"timestamp":    timestamp,
		"maxclients":   "20",
		"timelimit":    "20",
	})

	// 2. Connect players
	for _, p := range players {
		payload := map[string]interface{}{
			"type":        EventConnect,
			"match_id":    matchID,
			"timestamp":   timestamp,
			"client_num":  rand.Intn(32),
			"player_name": p.Name,
			"player_guid": p.GUID,
			"player_team": p.Team,
		}
		if p.MemberID > 0 {
			payload["player_member_id"] = p.MemberID
		}
		sendEvent(client, s, payload)
	}

	// 3. Gameplay loop
	eventsCount := 50 + rand.Intn(100)
	for i := 0; i < eventsCount; i++ {
		timestamp += float64(rand.Intn(10) + 1)
		attacker := players[rand.Intn(len(players))]
		victim := players[rand.Intn(len(players))]

		// Avoid self-kill mostly
		if attacker.GUID == victim.GUID {
			continue
		}

		weapon := weapons[rand.Intn(len(weapons))]
		hitloc := hitlocs[rand.Intn(len(hitlocs))]

		// Fire
		firePayload := map[string]interface{}{
			"type":        EventWeaponFire,
			"match_id":    matchID,
			"timestamp":   timestamp,
			"player_name": attacker.Name,
			"player_guid": attacker.GUID,
			"weapon":      weapon,
		}
		if attacker.MemberID > 0 {
			firePayload["player_member_id"] = attacker.MemberID
		}
		sendEvent(client, s, firePayload)

		// Damage
		damagePayload := map[string]interface{}{
			"type":          EventDamage,
			"match_id":      matchID,
			"timestamp":     timestamp,
			"attacker_name": attacker.Name,
			"attacker_guid": attacker.GUID,
			"attacker_team": attacker.Team,
			"victim_name":   victim.Name,
			"victim_guid":   victim.GUID,
			"victim_team":   victim.Team,
			"weapon":        weapon,
			"damage":        rand.Intn(50) + 10,
			"hitloc":        hitloc,
		}
		if attacker.MemberID > 0 {
			damagePayload["attacker_member_id"] = attacker.MemberID
		}
		if victim.MemberID > 0 {
			damagePayload["victim_member_id"] = victim.MemberID
		}
		sendEvent(client, s, damagePayload)

		// Kill (sometimes)
		if rand.Float32() < 0.3 {
			eventType := EventKill
			if hitloc == "head" {
				// Don't change type to headshot, but maybe log a headshot event too?
				// For now simple kill
			}

			killPayload := map[string]interface{}{
				"type":          eventType,
				"match_id":      matchID,
				"timestamp":     timestamp,
				"attacker_name": attacker.Name,
				"attacker_guid": attacker.GUID,
				"attacker_team": attacker.Team,
				"victim_name":   victim.Name,
				"victim_guid":   victim.GUID,
				"victim_team":   victim.Team,
				"weapon":        weapon,
				"damage":        100,
				"hitloc":        hitloc,
			}
			if attacker.MemberID > 0 {
				killPayload["attacker_member_id"] = attacker.MemberID
			}
			if victim.MemberID > 0 {
				killPayload["victim_member_id"] = victim.MemberID
			}
			sendEvent(client, s, killPayload)
		}
	}

	// 4. Match End
	score1 := rand.Intn(20)
	score2 := rand.Intn(20)
	winningTeam := "draw"
	if score1 > score2 {
		winningTeam = "allies"
	} else if score2 > score1 {
		winningTeam = "axis"
	}

	sendEvent(client, s, map[string]interface{}{
		"type":         EventMatchEnd,
		"match_id":     matchID,
		"server_id":    ServerID,
		"server_token": ServerToken, // Added token here too just in case
		"timestamp":    timestamp,
		"allies_score": score1,
		"axis_score":   score2,
		"winning_team": winningTeam,
	})
}

func sendEvent(client *http.Client, s *Stats, data map[string]interface{}) {
	jsonData, _ := json.Marshal(data)
	req, err := http.NewRequest("POST", APIURL+"/ingest/events", bytes.NewBuffer(jsonData))
	if err != nil {
		fmt.Printf("Error creating request: %v\n", err)
		s.Error()
		return
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+ServerToken)

	resp, err := client.Do(req)
	if err != nil {
		fmt.Printf("Error sending event: %v\n", err)
		s.Error()
		return
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusAccepted {
		fmt.Printf("Unexpected status: %d\n", resp.StatusCode)
		s.Error()
		return
	}

	s.Add(1)
}

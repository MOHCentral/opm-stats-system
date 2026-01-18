# 🎮 EVENTS.md - Engine Events Reference

> **30 Atomic Events from OpenMOHAA Engine (PR #8)**

---

## 📋 Quick Reference

### All 30 Events by Category

| Category | Events |
|----------|--------|
| **Combat (10)** | player_kill, player_death, player_damage, weapon_fire, weapon_hit, player_headshot, weapon_reload, weapon_change, grenade_throw, grenade_explode |
| **Movement (5)** | player_jump, player_land, player_crouch, player_prone, player_distance |
| **Interaction (5)** | ladder_mount, ladder_dismount, item_pickup, item_drop, player_use |
| **Session (5)** | client_connect, client_disconnect, client_begin, team_join, player_say |
| **Match (5)** | match_start, match_end, round_start, round_end, heartbeat |

---

## 🔫 Combat Events (10)

### player_kill
Fired when a player kills another player.

| Parameter | Type | Description |
|-----------|------|-------------|
| attacker | entity | The killing player |
| victim | entity | The killed player |
| inflictor | entity | The weapon/object that dealt damage |
| hitloc | string | Hit location (head, torso, legs, etc.) |
| mod | string | Means of death (MOD_RIFLE, MOD_GRENADE, etc.) |

**Derived Stats**: kills, KDR, killstreaks, multikills, headshots

```c
combat_player_kill local.attacker local.victim local.inflictor local.hitloc local.mod:
    println ("Kill: " + local.attacker.netname + " killed " + local.victim.netname)
end
```

---

### player_death
Fired when a player dies.

| Parameter | Type | Description |
|-----------|------|-------------|
| inflictor | entity | The weapon/object that caused death |

**Derived Stats**: deaths, KDR, death positions, survival time

---

### player_damage
Fired when a player takes damage.

| Parameter | Type | Description |
|-----------|------|-------------|
| attacker | entity | The player dealing damage |
| damage | integer | Amount of damage dealt |
| mod | string | Means of damage |

**Derived Stats**: damage dealt, ADR, damage received

---

### weapon_fire
Fired when a player fires their weapon.

| Parameter | Type | Description |
|-----------|------|-------------|
| weapon | string | Weapon name |
| ammo | integer | Remaining ammo after shot |

**Derived Stats**: shots fired, accuracy, bullets per kill

---

### weapon_hit
Fired when a projectile hits a target.

| Parameter | Type | Description |
|-----------|------|-------------|
| target | entity | The entity that was hit |
| hitloc | string | Hit location on target |

**Derived Stats**: accuracy, hit distribution

---

### player_headshot
Fired when a headshot kill is registered.

| Parameter | Type | Description |
|-----------|------|-------------|
| target | entity | The victim |
| weapon | string | Weapon used |

**Derived Stats**: headshots, headshot %, one-taps

---

### weapon_reload
Fired when a player reloads.

| Parameter | Type | Description |
|-----------|------|-------------|
| weapon | string | Weapon being reloaded |

**Derived Stats**: reloads, reload deaths

---

### weapon_change
Fired when a player switches weapons.

| Parameter | Type | Description |
|-----------|------|-------------|
| old_weapon | string | Previous weapon |
| new_weapon | string | New weapon |

**Derived Stats**: weapon time, switch speed, pick rate

---

### grenade_throw
Fired when a player throws a grenade.

| Parameter | Type | Description |
|-----------|------|-------------|
| projectile | entity | The grenade entity |

**Derived Stats**: grenades thrown, grenade efficiency

---

### grenade_explode
Fired when a grenade detonates.

| Parameter | Type | Description |
|-----------|------|-------------|
| projectile | entity | The exploding grenade |

**Derived Stats**: grenade damage, grenade kills

---

## 🏃 Movement Events (5)

### player_jump
Fired when a player jumps.

| Parameter | Type | Description |
|-----------|------|-------------|
| (none) | - | No parameters |

**Derived Stats**: jumps, air time, bunny hops

---

### player_land
Fired when a player lands from a fall.

| Parameter | Type | Description |
|-----------|------|-------------|
| fall_height | float | Height of the fall |

**Derived Stats**: fall damage, fatal falls

---

### player_crouch
Fired when a player crouches.

| Parameter | Type | Description |
|-----------|------|-------------|
| (none) | - | No parameters |

**Derived Stats**: crouch time, crouch kills

---

### player_prone
Fired when a player goes prone.

| Parameter | Type | Description |
|-----------|------|-------------|
| (none) | - | No parameters |

**Derived Stats**: prone time, prone kills

---

### player_distance
Periodic event with distance traveled.

| Parameter | Type | Description |
|-----------|------|-------------|
| walked | float | Distance walked |
| sprinted | float | Distance sprinted |
| swam | float | Distance swam |
| driven | float | Distance in vehicles |

**Derived Stats**: total distance, velocity, movement patterns

---

## 🪜 Interaction Events (5)

### ladder_mount
Fired when a player starts climbing a ladder.

| Parameter | Type | Description |
|-----------|------|-------------|
| ladder | entity | The ladder entity |

**Derived Stats**: ladder climbs, ladder kills

---

### ladder_dismount
Fired when a player leaves a ladder.

| Parameter | Type | Description |
|-----------|------|-------------|
| ladder | entity | The ladder entity |

**Derived Stats**: ladder time

---

### item_pickup
Fired when a player picks up an item.

| Parameter | Type | Description |
|-----------|------|-------------|
| item | string | Item name |
| amount | integer | Amount picked up |

**Derived Stats**: items collected, resource management

---

### item_drop
Fired when a player drops an item.

| Parameter | Type | Description |
|-----------|------|-------------|
| item | string | Item name |

**Derived Stats**: items dropped

---

### player_use
Fired when a player uses an interactive entity.

| Parameter | Type | Description |
|-----------|------|-------------|
| target | entity | The used entity |

**Derived Stats**: doors opened, objects used

---

## 👥 Session Events (5)

### client_connect
Fired when a client connects to the server.

| Parameter | Type | Description |
|-----------|------|-------------|
| client_num | integer | Client slot number |

**Derived Stats**: sessions, connections

---

### client_disconnect
Fired when a client disconnects.

| Parameter | Type | Description |
|-----------|------|-------------|
| (none) | - | No parameters |

**Derived Stats**: session duration, disconnects

---

### client_begin
Fired when a client spawns/respawns.

| Parameter | Type | Description |
|-----------|------|-------------|
| (none) | - | No parameters |

**Derived Stats**: spawns, lives

---

### team_join
Fired when a player changes teams.

| Parameter | Type | Description |
|-----------|------|-------------|
| old_team | integer | Previous team number |
| new_team | integer | New team number |

**Derived Stats**: team preference, team switches

---

### player_say
Fired when a player sends a chat message.

| Parameter | Type | Description |
|-----------|------|-------------|
| message | string | The chat message |

**Derived Stats**: messages sent, communication score

---

## 🎯 Match Events (5)

### match_start
Fired when a match begins.

| Parameter | Type | Description |
|-----------|------|-------------|
| map | string | Map name |
| mode | string | Game mode |
| server_id | string | Server identifier |

---

### match_end
Fired when a match ends.

| Parameter | Type | Description |
|-----------|------|-------------|
| winner_team | integer | Winning team |
| score1 | integer | Team 1 score |
| score2 | integer | Team 2 score |
| duration | float | Match duration |

---

### round_start
Fired when a round begins.

| Parameter | Type | Description |
|-----------|------|-------------|
| round_num | integer | Round number |

---

### round_end
Fired when a round ends.

| Parameter | Type | Description |
|-----------|------|-------------|
| winner_team | integer | Round winner |
| reason | string | Win reason |

---

### heartbeat
Periodic server state update.

| Parameter | Type | Description |
|-----------|------|-------------|
| players | integer | Player count |
| map | string | Current map |
| score1 | integer | Team 1 score |
| score2 | integer | Team 2 score |

---

## 🔧 Implementation

### Event Subscription (tracker.scr)
```c
main:
    // Combat events
    event_subscribe "player_kill" "tracker.scr::combat_player_kill"
    event_subscribe "player_death" "tracker.scr::combat_player_death"
    event_subscribe "player_damage" "tracker.scr::combat_player_damage"
    event_subscribe "weapon_fire" "tracker.scr::combat_weapon_fire"
    event_subscribe "weapon_hit" "tracker.scr::combat_weapon_hit"
    event_subscribe "player_headshot" "tracker.scr::combat_player_headshot"
    event_subscribe "weapon_reload" "tracker.scr::combat_weapon_reload"
    event_subscribe "weapon_change" "tracker.scr::combat_weapon_change"
    event_subscribe "grenade_throw" "tracker.scr::combat_grenade_throw"
    event_subscribe "grenade_explode" "tracker.scr::combat_grenade_explode"
    
    // Movement events
    event_subscribe "player_jump" "tracker.scr::movement_player_jump"
    event_subscribe "player_land" "tracker.scr::movement_player_land"
    event_subscribe "player_crouch" "tracker.scr::movement_player_crouch"
    event_subscribe "player_prone" "tracker.scr::movement_player_prone"
    event_subscribe "player_distance" "tracker.scr::movement_player_distance"
    
    // Interaction events
    event_subscribe "ladder_mount" "tracker.scr::interaction_ladder_mount"
    event_subscribe "ladder_dismount" "tracker.scr::interaction_ladder_dismount"
    event_subscribe "item_pickup" "tracker.scr::interaction_item_pickup"
    event_subscribe "item_drop" "tracker.scr::interaction_item_drop"
    event_subscribe "player_use" "tracker.scr::interaction_player_use"
    
    // Session events
    event_subscribe "client_connect" "tracker.scr::session_client_connect"
    event_subscribe "client_disconnect" "tracker.scr::session_client_disconnect"
    event_subscribe "client_begin" "tracker.scr::session_client_begin"
    event_subscribe "team_join" "tracker.scr::session_team_join"
    event_subscribe "player_say" "tracker.scr::session_player_say"
    
    println "Tracker: Subscribed to all 30 events"
end
```

### Event Handler Pattern
```c
combat_player_kill local.attacker local.victim local.inflictor local.hitloc local.mod:
    // Get player GUIDs for tracking
    local.attacker_guid = local.attacker getclientinfo "guid"
    local.victim_guid = local.victim getclientinfo "guid"
    
    // Get positions for heatmap
    local.attacker_pos = local.attacker.origin
    local.victim_pos = local.victim.origin
    
    // Format data for API
    local.data = "event=player_kill"
    local.data += "&attacker_guid=" + local.attacker_guid
    local.data += "&victim_guid=" + local.victim_guid
    local.data += "&weapon=" + local.inflictor
    local.data += "&hitloc=" + local.hitloc
    local.data += "&mod=" + local.mod
    local.data += "&attacker_x=" + local.attacker_pos[0]
    local.data += "&attacker_y=" + local.attacker_pos[1]
    local.data += "&victim_x=" + local.victim_pos[0]
    local.data += "&victim_y=" + local.victim_pos[1]
    local.data += "&timestamp=" + level.time
    
    // Send to API
    curl_post "http://localhost:8080/api/v1/ingest/events" local.data "tracker.scr::http_callback"
end
```

### Available Engine Commands
```c
event_subscribe "event_name" "handler_label"   // Subscribe to event
registercmd "command" "handler_label"          // Register console command
curl_get "url" "callback_label"                // HTTP GET request
curl_post "url" "data" "callback_label"        // HTTP POST request
curl_put "url" "data" "callback_label"         // HTTP PUT request
```

---

## 📊 Event to Stat Mapping

| Event | Primary Stats | Secondary Stats |
|-------|---------------|-----------------|
| player_kill | kills, K/D, killstreaks | headshots, weapon kills, map kills |
| player_death | deaths, K/D | death positions, survival time |
| player_damage | damage dealt, ADR | damage received, overkill |
| weapon_fire | shots fired, accuracy | weapon usage, bullets per kill |
| weapon_hit | hits, accuracy | hit distribution, efficiency |
| player_headshot | headshots, HS% | one-taps, HS streak |
| weapon_reload | reloads | reload deaths, cancel reloads |
| weapon_change | weapon time | pick rate, switch speed |
| grenade_throw | grenades thrown | grenade efficiency |
| grenade_explode | grenade damage | grenade kills |
| player_jump | jumps, air time | air kills, bunny hops |
| player_land | landings | fall damage, fatal falls |
| player_crouch | crouch time | crouch kills, drop shots |
| player_prone | prone time | prone kills |
| player_distance | distance traveled | velocity, movement patterns |
| ladder_mount | ladder climbs | ladder kills |
| ladder_dismount | ladder time | ladder escapes |
| item_pickup | items collected | health/ammo pickups |
| item_drop | items dropped | resource management |
| player_use | objects used | doors opened |
| client_connect | connections | sessions |
| client_disconnect | disconnects | session duration |
| client_begin | spawns | lives |
| team_join | team changes | team preference |
| player_say | messages | communication |
| match_start | matches played | map selection |
| match_end | wins, losses | match duration |
| round_start | rounds played | round patterns |
| round_end | round wins | clutches |
| heartbeat | server state | live tracking |

---

*This document defines all engine events for the OpenMOHAA Stats System.*
*Last Updated: 2026-01-18*

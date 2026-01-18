# OpenMoHAA Custom Event System Documentation

## Complete Event List

### Combat Events (10 events)
- **player_kill**: When a player kills another player
  - Args: `attacker`, `victim`, `inflictor`, `hitlocation`, `meansofdeath`
  - Type: Player-specific

- **player_death**: When a player dies
  - Args: `inflictor`
  - Type: Player-specific

- **player_damage**: When a player receives damage
  - Args: `attacker`, `damage_amount`, `meansofdeath`
  - Type: Player-specific

- **weapon_fire**: When a player fires a weapon
  - Args: `weapon_name`, `ammo_available`
  - Type: Player-specific

- **weapon_hit**: When a weapon hit connects with a target
  - Args: `target_entity`, `hit_location` (or `"projectile"` for grenades)
  - Type: Player-specific

- **player_headshot**: When a player gets a headshot kill
  - Args: `target_entity`, `weapon_name`
  - Type: Player-specific

- **weapon_reload**: When a player reloads a weapon
  - Args: `weapon_name`
  - Type: Player-specific

- **weapon_change**: When a player switches weapons
  - Args: `old_weapon_name`, `new_weapon_name`
  - Type: Player-specific

- **grenade_throw**: When a player throws a grenade
  - Args: `projectile_entity`
  - Type: Player-specific

- **grenade_explode**: When a grenade explodes
  - Args: `projectile_entity`
  - Type: Player-specific

### Movement Events (5 events)
- **player_jump**: When a player jumps
  - Args: None
  - Type: Player-specific

- **player_land**: When a player lands from a fall
  - Args: `fall_height` (velocity.z)
  - Type: Player-specific

- **player_crouch**: When a player crouches
  - Args: None
  - Type: Player-specific

- **player_prone**: When a player goes prone
  - Args: None
  - Type: Player-specific

- **player_distance**: Periodic tracking of player movement distances
  - Args: `distance_walked`, `distance_sprinted`, `distance_swam`, `distance_driven`
  - Type: Player-specific

### Interaction Events (5 events)
- **ladder_mount**: When a player climbs onto a ladder
  - Args: `ladder_entity`
  - Type: Player-specific

- **ladder_dismount**: When a player gets off a ladder
  - Args: `ladder_entity`
  - Type: Player-specific

- **item_pickup**: When a player picks up an item
  - Args: `item_name`, `amount`
  - Type: Player-specific

- **item_drop**: When a player drops an item
  - Args: `item_name`
  - Type: Player-specific

- **player_use**: When a player uses an entity (button, switch, etc.)
  - Args: `target_entity`
  - Type: Player-specific

### Session Events (5 events)
- **client_connect**: When a client connects to the server
  - Args: `client_number`
  - Type: Player-specific

- **client_disconnect**: When a client disconnects
  - Args: None
  - Type: Player-specific

- **client_begin**: When a client spawn is initialized
  - Args: None
  - Type: Player-specific

- **team_join**: When a player changes teams
  - Args: `old_team_number`, `new_team_number`
  - Type: Player-specific

- **player_say**: When a player sends a chat message
  - Args: `message_text`
  - Type: Player-specific

## Event Subscription Syntax

```morpheus
event_subscribe "event_name" "handler_label"
```

Example:
```morpheus
event_subscribe "player_kill" "my_kill_handler"

my_kill_handler:
    local.attacker = parm.get 1
    local.victim = parm.get 2
    local.inflictor = parm.get 3
    local.hitloc = parm.get 4
    local.mod = parm.get 5
    println ("Kill: " + local.attacker + " killed " + local.victim)
end
```

## Available Commands

### HTTP Requests
- **curl_get** `url`: Make HTTP GET request
- **curl_post** `url` `data`: Make HTTP POST request (JSON or URL-encoded)
- **curl_put** `url` `data`: Make HTTP PUT request

### Event System
- **event_subscribe** `event_name` `handler_label`: Subscribe to an event
- **registercmd** `command_name` `handler_label`: Register a console command

## Total Event Count: 30 Events

### By Category:
- Combat: 10 events
- Movement: 5 events
- Interaction: 5 events
- Session: 5 events

All events are player-specific and can be subscribed to using `event_subscribe`.

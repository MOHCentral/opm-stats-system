# Skill: Game Script Development

Develop and modify Morpheus Script (.scr) files for OpenMOHAA telemetry.

## Source Location
```
/run/media/elgan/evo/dev/opm-stats-system/opm-stats-game-scripts/global/
```

## Script Architecture

### Module Structure
| File | Purpose | Key Functions |
|------|---------|---------------|
| tracker.scr | Entry point | main, cmd_login, cmd_logout, cmd_stats |
| tracker_common.scr | Shared utilities | init_event, add_player, queue_event, flush_queue |
| tracker_combat.scr | Combat events | on_player_kill, on_player_damage |
| tracker_movement.scr | Movement events | on_player_jump, on_player_crouch |
| tracker_client.scr | Client lifecycle | on_player_connect, on_player_disconnect |
| tracker_gameflow.scr | Game state | on_game_start, on_game_end, on_round_start |
| tracker_items.scr | Item events | on_item_pickup, on_item_drop |
| tracker_interaction.scr | Interactions | on_chat, on_use_object |
| tracker_vehicle.scr | Vehicles | on_vehicle_enter, on_vehicle_exit |
| tracker_world.scr | World events | on_door_open, on_explosion |
| tracker_bot.scr | AI events | on_bot_spawn, on_bot_kill |
| register.scr | Server registration | register_server |

### Variable Scoping
- `game.*` — Cross-script persistent variables (USE THIS)
- `level.*` — Script-local, resets per script execution (DON'T USE for sharing)
- `local.*` — Function-local variables
- `self` — Current entity context (player in event handlers)

### Event Pattern
```
// 1. Subscribe to engine event
event_subscribe "player_killed" on_player_kill

// 2. Handler builds event data
on_player_kill local.attacker local.victim local.damage local.hitloc local.weapon local.mod:
    local.data = waitthread global/tracker_common.scr::init_event "player_kill"
    local.data = waitthread global/tracker_common.scr::add_player local.data local.attacker "player"
    local.data = waitthread global/tracker_common.scr::add_player local.data local.victim "victim"
    local.data["weapon"] = (string local.weapon)
    local.data["hitloc"] = (string local.hitloc)
    waitthread global/tracker_common.scr::queue_event local.data
end
```

### MQTT Event Pattern (New)
```
// Same event building, different transport:
queue_event_mqtt local.event:
    local.json_str = make_json local.event
    if (game.mqtt_connected == 1) {
        local.topic = "openmohaa/events/" + game.server_id
        mqtt_publish local.topic local.json_str 0
    } else {
        // Fallback to HTTP batch
        waitthread queue_event local.event
    }
end
```

### Key Engine Commands
| Command | Description |
|---------|-------------|
| `event_subscribe` | Hook into engine events |
| `curl_post url headers body callback` | HTTP POST (async) |
| `mqtt_connect host port id callback` | MQTT connect (async) |
| `mqtt_publish topic payload [qos]` | MQTT publish |
| `mqtt_subscribe topic callback [qos]` | MQTT subscribe |
| `mqtt_is_connected` | Check MQTT status |
| `make_json array` | Convert array to JSON string |
| `makearray ... endarray` | Create dictionary |
| `getcvar name` | Read console variable |
| `setcvar name value` | Set console variable |
| `dprintln msg` | Debug print to console |
| `getclientnum entity` | Get player's client number |

### CVars Used
| CVar | Purpose |
|------|---------|
| `opm_server_token` | API authentication token |
| `opm_server_id` | Registered server ID |
| `opm_auto_login_token` | Auto-login for players |
| `opm_mqtt_host` | MQTT broker hostname |
| `opm_mqtt_port` | MQTT broker port |
| `opm_use_mqtt` | Enable MQTT transport (1/0) |

### Testing Scripts
```
# In-game console
/seed 100          # Generate 100 test events
/stats             # Show player stats
/whoami            # Show login status
/login <token>     # Authenticate
```

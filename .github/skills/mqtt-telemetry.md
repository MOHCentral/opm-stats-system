# Skill: MQTT Telemetry System

End-to-end MQTT integration for game event telemetry.

## Architecture

```
Game Server (.scr scripts)
  → mqtt_connect to broker
  → mqtt_publish events to openmohaa/events/{server_id}
  ↓
MQTT Broker (Mosquitto)
  ↓
Go API (MQTT Subscriber)
  → Subscribe to openmohaa/events/#
  → Parse JSON events
  → Enqueue to same Worker Pool as HTTP
  ↓
ClickHouse + Postgres + Redis
```

## Topic Structure

| Topic | Publisher | Subscriber | QoS | Description |
|-------|-----------|------------|-----|-------------|
| `openmohaa/events/{server_id}` | Game server | API | 0 | Batched telemetry events (JSON array) |
| `openmohaa/servers/{server_id}/status` | Game server | API | 1 | Server heartbeat/status |
| `openmohaa/servers/{server_id}/register` | Game server | API | 1 | Server registration |
| `openmohaa/commands/{server_id}` | API | Game server | 1 | Remote commands (future) |

## Game Script Usage (.scr)

### Connect to MQTT Broker
```
// In tracker.scr::main
local.host = getcvar "opm_mqtt_host"
local.port = getcvar "opm_mqtt_port"
local.client_id = "mohaa-" + game.server_id

mqtt_connect local.host local.port local.client_id global/tracker_common.scr::on_mqtt_connected
```

### Publish Events
```
// In tracker_common.scr::flush_queue_mqtt
local.topic = "openmohaa/events/" + game.server_id
local.json_array = game.event_queue + "]"
mqtt_publish local.topic local.json_array 0
```

### Subscribe to Commands (Optional)
```
local.cmd_topic = "openmohaa/commands/" + game.server_id
mqtt_subscribe local.cmd_topic global/tracker_common.scr::on_mqtt_command 1
```

## API Configuration

```bash
# MQTT Broker connection
MQTT_BROKER_URL=tcp://localhost:1883
MQTT_CLIENT_ID=opm-stats-api
MQTT_TOPIC_PREFIX=openmohaa
MQTT_USERNAME=          # Optional
MQTT_PASSWORD=          # Optional
MQTT_QOS=0              # 0=at-most-once, 1=at-least-once
MQTT_CLEAN_SESSION=true
```

## Message Format

Events published to MQTT use the same JSON format as HTTP:
```json
[
  {
    "type": "player_kill",
    "match_id": "match_abc123",
    "session_id": "sess_xyz",
    "timestamp": "1234567.89",
    "player_name": "Elgan",
    "player_guid": "42",
    "victim_name": "Bot",
    "victim_guid": "unauth_3",
    "weapon": "M1 Garand",
    "hitloc": "head",
    "damage": "150"
  }
]
```

## Testing MQTT

### Manual Test
```bash
# Subscribe to all events
mosquitto_sub -h localhost -t "openmohaa/#" -v

# Publish test event
mosquitto_pub -h localhost -t "openmohaa/events/test-server" \
  -m '[{"type":"player_kill","match_id":"test","player_name":"TestPlayer","player_guid":"1","victim_name":"Bot","victim_guid":"2","weapon":"M1 Garand"}]'
```

### Go Integration Test
```bash
cd /run/media/elgan/evo/dev/opm-stats-system/opm-stats-api
go test ./tests/ -run TestMQTTIngestion -v
```

## Failover
- If MQTT broker is unavailable, game scripts fall back to HTTP `curl_post`
- API continues accepting HTTP events regardless of MQTT status
- MQTT subscriber auto-reconnects with exponential backoff

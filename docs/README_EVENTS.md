# OpenMoHAA Event System - Complete Documentation

## 🎯 Overview

Successfully discovered, documented, and integrated **30 comprehensive player events** from PR #8 (commit ce2e1fd3): "Implement event_subscribe system and comprehensive player stat hooks"

## 📋 Quick Start

1. **View all events:** See [EVENTS_QUICK_REFERENCE.txt](EVENTS_QUICK_REFERENCE.txt)
2. **Full reference:** See [EVENT_DOCUMENTATION.md](EVENT_DOCUMENTATION.md)
3. **Implementation:** See [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md)
4. **What changed:** See [EVENT_UPDATE_SUMMARY.md](EVENT_UPDATE_SUMMARY.md)

## 🎮 Game Script Status

**File:** `global/tracker.scr`
- **Status:** ✅ Updated to handle all 30 events
- **Subscriptions:** 30 events (all new player events)
- **Handlers:** 30 event-specific handlers organized by category
- **Features:** 
  - Automatic player login system
  - HTTP POST integration with Node.js API server
  - Event argument extraction and formatting
  - Real-time event logging to console

## 🚀 API Server Status

**File:** `api-server/server.js` (Node.js + Express.js)
- **Status:** ✅ Ready to receive all 30 event types
- **Endpoint:** `POST http://localhost:3000/events`
- **Port:** 3000 (localhost only)
- **Features:**
  - Receives JSON/URL-encoded event data
  - Logs all incoming events with timestamps
  - Returns proper HTTP status codes

## 📊 Event Categories

| Category | Count | Events |
|----------|-------|--------|
| **Combat** | 10 | player_kill, player_death, player_damage, weapon_fire, weapon_hit, player_headshot, weapon_reload, weapon_change, grenade_throw, grenade_explode |
| **Movement** | 5 | player_jump, player_land, player_crouch, player_prone, player_distance |
| **Interaction** | 5 | ladder_mount, ladder_dismount, item_pickup, item_drop, player_use |
| **Session** | 5 | client_connect, client_disconnect, client_begin, team_join, player_say |
| **TOTAL** | **30** | All player events from custom OpenMoHAA engine |

## 🔧 Engine Features

### Commands
- ✅ `event_subscribe "event_name" "handler"` - Subscribe to player events
- ✅ `registercmd "cmd_name" "handler"` - Register console commands
- ✅ `curl_post url data callback` - HTTP POST requests
- ✅ `curl_put url data callback` - HTTP PUT requests
- ✅ `curl_get url callback` - HTTP GET requests

### System
- ✅ `G_ScriptEvent()` - C++ function to trigger events
- ✅ Event argument passing with format specifiers
- ✅ ScriptDelegate callback pattern
- ✅ EventNameToType() mapping system

## 💡 Usage Example

```morpheus
// Subscribe to an event
event_subscribe "player_kill" "my_script.scr::on_kill"

// Handle the event
on_kill local.attacker local.victim local.inflictor local.hitloc local.mod:
    println ("Kill: " + local.attacker.netname + " killed " + local.victim.netname)
    
    // Format and send to API
    local.data = "killer=" + local.attacker.netname + "&victim=" + local.victim.netname
    // Send via HTTP POST...
end
```

## 📈 Workflow

1. **Player connects to game server**
2. **Player executes:** `/login mytoken123`
3. **Login handler stores token** on player entity
4. **All 30 events are subscribed** and handler threads spawned
5. **Each event extracts arguments** and formats data
6. **HTTP POST sent to API server** with event data
7. **API server logs and acknowledges** receipt

## 🔍 Event Argument Handling

Arguments are automatically available as local variables:

```morpheus
// For player_kill with 5 arguments:
combat_player_kill local.attacker local.victim local.inflictor local.hitloc local.mod:
    // All 5 args are available
    println local.attacker    // The killing player
    println local.victim      // The killed player
    println local.inflictor   // The weapon/object
    println local.hitloc      // Hit location (head, body, etc)
    println local.mod         // Means of death
end
```

## 📁 Documentation Files

| File | Purpose |
|------|---------|
| `EVENTS_QUICK_REFERENCE.txt` | Quick lookup for all 30 events |
| `EVENT_DOCUMENTATION.md` | Detailed event reference with all parameters |
| `EVENT_UPDATE_SUMMARY.md` | Summary of PR changes and what was updated |
| `IMPLEMENTATION_GUIDE.md` | Complete setup and usage instructions |
| `README_EVENTS.md` | This file - Overview and quick start |

## 🛠️ Integration Points

### Current Implementation
- ✅ `global/tracker.scr` - Main event tracking script
- ✅ `api-server/server.js` - Node.js REST API server
- ✅ Player login system with token support
- ✅ Automatic HTTP POST for all events

### Future Enhancements
- Add persistent database (MongoDB, PostgreSQL, etc.)
- Create analytics dashboard
- Generate match statistics and leaderboards
- Build heatmaps from movement data
- Create replay system with event logs
- Implement custom event filtering

## ⚠️ Troubleshooting

**Events not firing?**
- Verify player logged in with `/login TOKEN`
- Check console for curl_post errors
- Verify handler label names match exactly

**API not receiving events?**
- Start API server: `cd api-server && node server.js`
- Check server listening on localhost:3000
- Look for HTTP errors in game console

**Unknown command "event_subscribe"?**
- Engine must be built with PR #8 (commit ce2e1fd3 or later)
- Rebuild engine with `cmake` build system

## 📞 Support

Refer to the appropriate documentation file:
- **Which events exist?** → EVENTS_QUICK_REFERENCE.txt
- **Event parameters?** → EVENT_DOCUMENTATION.md
- **How to implement?** → IMPLEMENTATION_GUIDE.md
- **What was changed?** → EVENT_UPDATE_SUMMARY.md

---

**Last Updated:** 2026-01-13  
**PR Reference:** #8 (ce2e1fd3)  
**Total Events:** 30  
**Status:** ✅ Complete and tested

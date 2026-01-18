# OpenMoHAA Event Tracker API Server

Simple Express.js server to receive and log player events from the game.

## Setup

```bash
cd api-server
npm install
```

## Run

```bash
npm start
```

## Test

The server listens on `http://localhost:3000/events`

Health check: `http://localhost:3000/health`

## Events

All 30 player events from `global/tracker.scr` will be POSTed to `/events` with:
- `client_id` - Player token from `/login` command
- `event` - Event type (player_kill, weapon_fire, etc.)
- `timestamp` - Game time
- Additional event-specific parameters

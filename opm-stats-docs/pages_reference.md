# OPM Stats — Page Reference

Describes every page in the SMF integration: URL, purpose, data sources, interactions, and expected behaviour.

---

## Navigation

The stats system adds a top-level **Stats** menu to the SMF nav bar. All pages are served from the SMF forum at `?action=…`.

---

## Public Pages

### War Room (Dashboard)
**URL:** `?action=mohaadashboard`

The landing page. Gives a snapshot of the entire community at a glance.

| Section | Content |
|---------|---------|
| Global stats | Total kills, deaths, headshots, matches, playtime — all-time aggregates from ClickHouse |
| Top 10 leaderboard | Top players by kills (configurable), pulled in the same batch request as global stats |
| Live matches | Loaded async by JS after page load for freshness; shows active servers with player counts |
| Recent matches | Last 10 completed matches with map, server, score, duration |

**Behaviour:**
- Logged-in users who have linked a game identity also see their own stats widget with K/D, rank, and recent performance.
- Live matches poll every ~30 seconds via `?action=mohaaapi&sa=live_matches` (JSON proxy).

---

### Leaderboards
**URL:** `?action=mohaastats;sa=leaderboards`

**Parameters:**
- `stat` — which stat to rank by (kills, deaths, headshots, accuracy, score, kdr, playtime). Default: `kills`.
- `period` — `all`, `month`, `week`. Default: `all`.
- `offset` — pagination offset.

**Behaviour:**
- Full ranked table of all tracked players.
- Sortable column headers change the `stat` param via GET redirect.
- Pagination in steps of 25.
- Clicking a player row navigates to that player's profile.
- Player's own row is highlighted if they have a linked identity.

---

### Battles (Match History)
**URL:** `?action=mohaastats;sa=battles`

**Parameters:** `limit`, `offset`, `map`, `server_id`.

**Behaviour:**
- Paginated list of completed matches.
- Each row: map name, server name, timestamp, duration, player count, winning team score.
- Clicking a match navigates to the match detail view.
- Optional filter by map or server (query string).

---

### Live Games
**URL:** `?action=mohaastats;sa=live`

**Behaviour:**
- All currently active game sessions, refreshed every 30 seconds.
- Shows server name, map, game mode, player list with scores, time elapsed.
- If no active games: displays "No active games right now."

---

### Maps
**URL:** `?action=mohaastats;sa=maps`

**Behaviour:**
- Grid/table of all maps that have been played.
- Per-map: match count, total kills, most played game mode, most common winner side.
- Clicking a map shows detailed stats: top players, kill heatmap (when heatmap data exists), recent matches on that map.

---

### Weapons
**URL:** `?action=mohaastats;sa=weapons`

**Behaviour:**
- List of all weapons tracked.
- Per weapon: total kills, headshot %, accuracy, kill share %.
- Sortable columns.
- Clicking a weapon shows which players have the most kills with it.

---

### Game Types
**URL:** `?action=mohaastats;sa=gametypes`

**Behaviour:**
- Stats broken down by game mode (DM, Team DM, Round-based, Objective, etc.).
- Per mode: match count, average match duration, average player count.

---

### Servers
**URL:** `?action=mohaaservers`

Sub-pages via `sa`:

| Sub-action | URL | Purpose |
|---|---|---|
| List | `?action=mohaaservers` (default) | All registered servers with online/offline status, current map, player count |
| Live | `?action=mohaaservers;sa=live` | Only servers currently active; refreshes automatically |
| Detail | `?action=mohaaservers;sa=detail;id=…` | Single server: current game, recent matches, player history, uptime graph |
| History | `?action=mohaaservers;sa=history;id=…` | Match history for a specific server |
| Rankings | `?action=mohaaservers;sa=rankings` | Server leaderboard: most matches hosted, most players attracted, highest uptime |

---

### Player Profile
**URL:** `?action=mohaaplayers;guid=<player-guid>`

The most data-dense page. Tabs (lazy-loaded via HTMX):

| Tab | Content |
|---|---|
| Overview | K/D ratio, accuracy, headshot %, total matches, playtime, recent performance chart |
| Weapons | Kill breakdown by weapon, accuracy per weapon, favourite weapon |
| Matches | Paginated match history for this player |
| Maps | Favourite maps, win rate per map |
| Achievements | Earned achievements with unlock dates |
| Performance | 30-day rolling performance graph |

**Behaviour:**
- If the GUID belongs to a linked forum member, displays their forum username and avatar alongside stats.
- If the current user is viewing their own profile, shows an "Edit Identity" link → `?action=profile;area=mohaidentity`.

---

### Player Comparison
**URL:** `?action=mohaastats;sa=comparison`

**Parameters:** `guid1`, `guid2`.

**Behaviour:**
- Side-by-side stat comparison of two players.
- Radar chart of normalised stats (kills, accuracy, headshots, playtime, KDR).
- Head-to-head record if both players have been on the same server simultaneously.
- If either `guid` is missing, shows a search form to select players.

---

### Achievements
**URL:** `?action=mohaaachievements`

Sub-pages:

| Sub-action | Purpose |
|---|---|
| (default) List | All achievements with unlock criteria and earned/total counts |
| `?action=mohaaachievements;sa=view;id=…` | Single achievement: description, criteria, top earners, unlock timeline |
| `?action=mohaaachievements;sa=leaderboard` | Players ranked by achievement count / achievement points |
| `?action=mohaaachievements;sa=rarest` | Achievements sorted by fewest earners |
| `?action=mohaaachievements;sa=category;cat=…` | Achievements filtered by category (Combat, Teamwork, Explorer, etc.) |
| `?action=mohaaachievements;sa=compare;guid1=…;guid2=…` | Which achievements each player has / is missing |
| `?action=mohaaachievements;sa=recent` | Chronological feed of achievements just earned across all players |

---

### Tournaments
**URL:** `?action=mohaatournaments`

Sub-pages:

| Sub-action | Purpose |
|---|---|
| (default) List | Active and past tournaments |
| `?action=mohaatournaments;sa=view;id=…` | Tournament bracket, schedule, results |
| `?action=mohaatournaments;sa=register;id=…` | Register a team or player for a tournament |
| `?action=mohaatournaments;sa=create` | Admin only: create a new tournament |

**Note:** Tournaments are still early-stage; bracket rendering is placeholder until a match scheduler is built.

---

### Teams
**URL:** `?action=mohaateams`

Sub-pages:

| Sub-action | Purpose |
|---|---|
| (default) List | All registered teams with member count and recent match count |
| `?action=mohaateams;sa=view;id=…` | Team roster, aggregate stats, recent matches |
| `?action=mohaateams;sa=create` | Logged-in users can create a team |
| `?action=mohaateams;sa=join;id=…` | Request to join a team |

---

### Predictions
**URL:** `?action=mohaastats;sa=predictions`

**Behaviour:**
- Upcoming (or just-ended) matches where users can predict outcomes.
- User selects a winner; after match completion their accuracy is tracked.
- Prediction leaderboard tracks who has the best prediction record.
- Currently depends on scheduled matches being created (via Tournaments or manual API insertion).

---

## Authenticated Pages

### Game Identity & Security (Profile Area)
**URL:** `?action=profile;area=mohaidentity`

The page that links a forum account to in-game player identities.

**Sections:**

#### Login Token
- Displays a `/login <TOKEN>` command to type in-game.
- The token is generated on first visit via `POST /api/v1/auth/device` and cached in `$_SESSION` with a 100-year expiry.
- A **Regenerate** link at `?action=profile;area=mohaidentity;regenerate` revokes all existing tokens and issues a new one.
- Once a player types `/login <TOKEN>` in-game, the API matches the token to the forum account and links the in-game GUID to the `smf_member_id`.

#### Trusted IPs
- Table of IP addresses that are pre-approved for login without extra checks.
- The IP used to generate the token is auto-approved on first generation.
- Admins can also add IPs manually via the Admin panel.
- Each entry has a **Revoke** button (POST form) and shows last-used date.

**Pending IP Approvals:**
- If a player logs in from an IP not on the trusted list, the login still succeeds but creates a pending approval entry.
- The profile page shows these pending entries with **Approve** / **Deny** buttons.

#### Login History
- Last N login attempts associated with this forum account.
- Columns: date, server name, IP, success/failure reason.
- Pulled from `login_attempts` table in PostgreSQL via `GET /api/v1/auth/history/{forum_user_id}`.

---

## Admin Pages

All admin pages are under **Admin → Modifications → MOHAA Stats** in the SMF admin panel.

### General Config
**Function:** `MohaaStats_AdminGeneral`

- Enable/disable the plugin.
- Set the API base URL (`mohaa_stats_api_url`) — must be the bare host without `/api/v1` suffix, e.g. `http://localhost:8084`.
- Set the server token (`mohaa_stats_server_token`) used for server-to-API auth.
- Set API request timeout.

### API Status
**Function:** `MohaaStats_AdminAPI`

- Shows current API URL and server token (masked).
- Live connectivity test: fires a `GET /api/v1/stats/global` and shows the response time and status code.

### Cache Management
**Function:** `MohaaStats_AdminCache`

- Shows cache hit rate.
- Button to flush all MOHAA stats cache entries from SMF's cache layer.
- Configures the default cache TTL (seconds) for API responses.

### Identity Linking
**Function:** `MohaaStats_AdminLinking`

- Lists all forum↔GUID links in the database.
- Admin can manually link or unlink a forum account to a GUID.
- Shows orphaned GUIDs (in-game activity with no linked forum account).
- Shows forum members who have never linked.

---

## API Proxy Endpoint

**URL:** `?action=mohaaapi&sa=<endpoint>`

Internal HTMX/JS endpoint used by frontend pages to query the API without exposing tokens. The PHP layer adds the `X-Server-Token` header and forwards the request to the Go API, returning JSON.

Used by: live matches widget, lazy-loaded tabs on player profiles, drilldown stats widgets.

---

## Authentication Flow (end-to-end)

```
1. User visits ?action=profile;area=mohaidentity
2. PHP calls POST /api/v1/auth/device { "forum_user_id": N }
3. API stores token in login_tokens table + Redis cache
4. Page shows: /login MOH-XXXXX
5. Player types /login MOH-XXXXX in game
6. Game script fires HTTP POST to API: /api/v1/auth/login
   { "token": "MOH-XXXXX", "client_num": N, "ip": "x.x.x.x" }
7. API looks up token → forum_user_id, marks token used, stores GUID↔member link
8. Game confirms login: "Authenticated as <forum_username>"
```

---

## Data Flow Summary

```
Game Server → MQTT/HTTP → Go API → ClickHouse (events, matches)
                                 → PostgreSQL (tokens, links, trusted IPs, login history)
                                 → Redis (token cache, live match state)
SMF PHP → HTTP → Go API → rendered HTML via SMF templates
```

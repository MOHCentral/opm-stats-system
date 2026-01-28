# OPM Stats System - End-to-End Functional Audit Protocol

**Role:** You are a Lead QA Automation Engineer and Backend Developer.

**Objective:** Conduct a rigorous **End-to-End (E2E) Functional Audit** of the OPM Stats System. You must verify that data flows correctly from the "Game Event" ingestion all the way to the "SMF Frontend" display. Infrastructure is secondary; **Logic and Data Integrity are primary.**

---

## 🧪 Phase 1: API Logic & End-Point Verification (Go)
*Goal: Prove every endpoint works, handles errors, and returns correct data.*

### 1. Ingestion Pipeline (`/api/v1/ingest`)
*   **Test:** Send a simulated batch of game events (kills, deaths, capture_flag).
*   **Verify:** 
    *   Does it return HTTP 202? 
    *   Are events persisted in ClickHouse (`mohaa_events_log`)?
    *   Are live stats updated in Redis?
*   **Edge Cases:** Send malformed JSON, invalid auth tokens, and empty batches.

### 2. Player Stats (`/api/v1/stats/player/{guid}`)
*   **Test:** Request stats for a known player GUID.
*   **Verify:**
    *   Are K/D, Accuracy, and Win Rate calculated correctly?
    *   Compare API response vs. raw DB queries.
    *   Check `last_seen` timestamp accuracy.

### 3. Server Pulse & Leaderboards (`/api/v1/stats/server`, `/api/v1/leaderboard`)
*   **Test:** Fetch server pulse and top 10 leaderboards.
*   **Verify:**
    *   Does `server/pulse` reflect recent activity?
    *   Is the leaderboard sorting correct (e.g., sort by `skill_rating` vs `kills`)?
    *   Are pagination and limits respected?

### 4. Auth & Security
*   **Test:** Verify JWT token generation and expiry.
*   **Verify:** Can a user without a token access protected stats? (Should allow public read, but restrict admin writes).

---

## 🖥️ Phase 2: SMF Frontend & Display Integration (PHP)
*Goal: Prove that what is shown on the screen matches the API data.*

### 1. The War Room (Dashboard)
*   **Inspection:** `Themes/default/MohaaDashboard.template.php`
*   **Verify:**
    *   Does the "Live Server Status" widget load data via AJAX?
    *   Are the charts (ApexCharts) rendering? Do they have data or are they empty?
    *   **Crucial:** Check the *Browser Console* for JS errors (CORS, 404s, parsing errors).

### 2. Player Profile Integration
*   **Inspection:** `Sources/MohaaPlayers.php` (Profile hooks)
*   **Verify:**
    *   Go to a user's profile. Is the "MOHAA Stats" area visible?
    *   Does it show the correct User-to-GUID mapping?
    *   If the API returns "Player not found", does the UI handle it gracefully?

### 3. Leaderboard Pages
*   **Inspection:** `Themes/default/MohaaLeaderboard.template.php`
*   **Verify:**
    *   Does the AG Grid (or table) populate with rows?
    *   Do the filter buttons (Kills, Deaths, Wins) correctly reload the grid?
    *   Check for visual glitches (CSS) or broken images/badges.

### 4. Achievement Showcase
*   **Inspection:** `Sources/MohaaAchievements.php`
*   **Verify:**
    *   Do unlocked badges appear in color?
    *   Do locked badges appear grayed out?
    *   Click a badge: Does the modal details pop up work?

---

## � Phase 3: The "Golden Path" Verification
*Perform this exact sequence to prove system health:*

1.  **Inject Data:** Run `go run cmd/seeder/main.go` (or payload script) to inject 1 kill for Player A on Player B.
2.  **Check API:** Request `/api/v1/stats/player/{PlayerA}`. Confirm kill count increased by 1.
3.  **Check Frontend:** Reload Player A's profile on the Forum. Confirm kill count matches API.
4.  **Check Leaderboard:** Reload Leaderboard. Confirm Player A's rank or stats updated.

---

## 📝 Output Deliverable
Produce a **Verification Report** containing:
1.  **PASS/FAIL** status for each Phase.
2.  **Screenshots** (or text descriptions) of the Dashboard and Profile.
3.  **Curl Responses** for critical API checkpoints.
4.  **Bug List:** Any visual glitches, console errors, or data mismatches found.

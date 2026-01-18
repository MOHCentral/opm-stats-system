# MOHAA Stats SMF Integration - Task Tracker

## Current Sprint: SMF Plugin Pages & Templates

### ✅ COMPLETED

#### Infrastructure
- [x] SMF Docker container running at `localhost:8888`
- [x] MariaDB container running with credentials: `root` / `root_password`
- [x] Database tables created: `smf_mohaa_claims`, `smf_mohaa_identities`
- [x] SMF hooks registered in database (`integrate_actions`, `integrate_menu_buttons`, `integrate_profile_areas`)

#### Core Plugin (MohaaPlayers.php)
- [x] Created `/var/www/html/Sources/MohaaPlayers.php` with all action handlers
- [x] Registered 10 actions: mohaadashboard, mohaaleaderboard, mohaamatches, mohaamaps, mohaaplayer, mohaacompare, mohaaservers, mohaaachievements, mohaatournaments, mohaaclaims
- [x] Added MOHAA Stats menu button with 7 dropdown items
- [x] Added profile areas for MOHAA Stats and Link Game Identity
- [x] Token generation for identity linking
- [x] PHP syntax verified
- [x] **Verified Mock Data Integration**

#### Templates Deployed & Verified
- [x] **MohaaDashboard.template.php** (Fixed & Verified)
- [x] **MohaaLeaderboard.template.php** (Fixed & Verified)
- [x] **MohaaServers.template.php** (Fixed & Verified)
- [x] **MohaaAchievements.template.php** (Fixed & Verified)
- [x] **MohaaTournaments.template.php** (Fixed & Verified)
- [x] MohaaMatches.template.php (Verified)
- [ ] MohaaMaps.template.php (Needs Styling Update)
- [ ] MohaaPlayer.template.php (Needs Data Update)
- [ ] MohaaProfile.template.php (Needs Styling Update)

#### Documentation
- [x] **MASSIVE_STATS.md** Created (51,000+ metrics documented)

---

### 🔴 BROKEN / NEEDS FIX

#### Profile Integration
- [ ] **Profile areas not appearing in user profile** (Critical: `integrate_profile_areas` hook issues)
- [ ] Identity linking page accessibility

#### API Connections
- [ ] Connect PHP frontend to Go API (currently using mock data)
- [ ] Implement Go API database handlers

---

### 🟡 IN PROGRESS

- [ ] Updating `MohaaPlayer.template.php` to use new native styling
- [ ] Updating `MohaaMaps.template.php` with drill-down stats
- [ ] Debugging Profile hook

---

### 📋 TODO - High Priority

1. **Fix Profile Integration**
   - Check why `MohaaPlayers_ProfileAreas` isn't showing tabs
   - Ensure hook is properly registered in SMF DB

2. **Update Remaining Templates**
   - `MohaaPlayer.template.php`: Add drill-down graphs/tables
   - `MohaaMaps.template.php`: Add map heatmaps (placeholder) and stats

3. **Wire Up Real Data**
   - Connect templates to actual API endpoints
   - Replace placeholder/mock data with real stats

---

### 📋 TODO - Medium Priority

4. **Add Missing Sub-action Handlers**
   - Achievements: all, recent, leaderboard
   - Servers: list, live, detail, history
   - Tournaments: list, view, create, bracket, manage

5. **Improve Dashboard**
   - My Stats section with real user data
   - Live matches from game servers
   - Recent activity feed

6. **API Integration**
   - Connect to Go stats API
   - Fetch real player data, matches, maps

---

### 📋 TODO - Low Priority

7. **Polish & Styling**
   - Make full-page military theme work within SMF frame
   - Add loading spinners
   - Mobile responsive design

8. **Game Server Integration**
   - `/login TOKEN` command in game
   - Webhook for token claim verification

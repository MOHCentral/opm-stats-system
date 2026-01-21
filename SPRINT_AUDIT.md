# Sprint Status After Audit

## What Already Exists (DO NOT DUPLICATE)

### PHP Sources
- ✅ `MohaaStats/MohaaStats.php` - Main stats functions
  - MohaaStats_Weapons() - Weapons leaderboard
  - MohaaStats_WeaponDetail() - Individual weapon stats
  - MohaaStats_Maps() - Map leaderboard
  - MohaaStats_MapDetail() - Individual map stats
  - MohaaStats_GameTypes() - Gametype leaderboard  
  - MohaaStats_Player() - Player stats page
  - MohaaStats_ServerDashboard() - Server dashboard
- ✅ `MohaaPlayers.php` - Player profile system
- ✅ `MohaaAchievements.php` - Full achievement system (85+ achievements in DB)
- ✅ `MohaaTeams.php` - Team system
- ✅ `MohaaTournaments.php` - Tournament system
- ✅ `MohaaServers.php` - Server listing

### Templates (with ApexCharts already loaded)
- ✅ `MohaaStatsLeaderboard.template.php` - Has some charts already
- ✅ `MohaaStats.template.php` - Main stats page
- ✅ `MohaaStatsPlayer.template.php` - Player detail page
- ✅ `MohaaStatsMatch.template.php` - Match details
- ✅ `MohaaPlayers.template.php` - Player profiles
- ✅ `MohaaTeams.template.php` - Team pages
- ✅ `MohaaTournaments.template.php` - Tournament pages
- ✅ `MohaaAchievements.template.php` - Achievement listing
- ✅ `MohaaAchievementsEnhanced.template.php` - Enhanced achievement views
- ✅ `MohaaDashboard.template.php` - Main dashboard
- ✅ `MohaaWarRoom.template.php` - War room view

## Charts Already Implemented
1. ✅ Combat Style Radial (MohaaPlayers.template.php) - 5 series
2. ✅ Reload Efficiency Gauge (was in duplicate, removed)
3. ✅ Stance Distribution Pie (MohaaPlayers.template.php)
4. ✅ Movement Pattern Radar (MohaaPlayers.template.php)
5. ✅ Map Popularity Chart (MohaaStatsLeaderboard.template.php)
6. ✅ Map Kills Chart (MohaaStatsLeaderboard.template.php)
7. ✅ Gametype Popularity Chart (MohaaStatsLeaderboard.template.php)
8. ✅ Gametype Kills Chart (MohaaStatsLeaderboard.template.php)
9. ✅ Weapon Kills Chart (MohaaStatsLeaderboard.template.php)
10. ✅ Weapon Accuracy Chart (MohaaStatsLeaderboard.template.php)

## Charts NEEDED (Sprint 4 - Enhance Existing Pages)

### MohaaStatsLeaderboard.template.php Enhancements
- [ ] K/D Trend Line (30-day rolling average) - top players
- [ ] Top 10 Players Bar Chart (horizontal)
- [ ] Stat Distribution Histogram (for selected metric)

### MohaaPlayers.template.php Enhancements  
- [ ] Hit Location Body Heatmap (Canvas-based, soldier silhouette overlay)
- [ ] 24-Hour Performance Radial (activity by hour)
- [ ] Kill Distance Scatter Plot (distance vs accuracy)
- [ ] Accuracy Timeline (per weapon, multi-series)

### MohaaStatsMatch.template.php Enhancements
- [ ] Match Timeline (kill events over time)
- [ ] Score Progression Line (team scores over time)
- [ ] Player Performance Bars (kills/deaths comparison)

### MohaaTeams.template.php Enhancements
- [ ] Team Win Rate Line (over last 30 matches)
- [ ] Member Contribution Stacked Bar (kills/deaths/assists)
- [ ] Team vs Team Comparison Radar (multiple stats)

### MohaaTournaments.template.php Enhancements
- [ ] Tournament Timeline (Gantt-style)
- [ ] Prize Distribution Pie
- [ ] Bracket Progression Visualization (custom SVG or Sankey-style)

## What I Mistakenly Created Today (DELETED)
- ❌ MohaaWeaponDetails.php (deleted - use existing MohaaStats_WeaponDetail)
- ❌ MohaaWeaponDetails.template.php (deleted)
- ❌ MohaaVehicleBotStats.php (deleted)
- ❌ MohaaVehicleStats.template.php (deleted)
- ❌ MohaaBotStats.template.php (deleted)
- ❌ migrations/postgres/007_achievements.sql (deleted - achievements already exist in DB)

## What I Correctly Enhanced
- ✅ MohaaPlayers.template.php - Added Combat Style radial, Stance pie, Movement radar
- ✅ cmd/seeder/main.go - Enhanced to cover all 92 events (good)
- ✅ tests/event_integration_test.go - Created comprehensive tests (good)

## Correct Approach Going Forward

### Sprint 4 Tasks (Enhance EXISTING templates with ApexCharts)
1. Read existing template
2. Find appropriate location for new chart
3. Add chart div + ApexCharts JavaScript
4. Use existing API data (don't create new endpoints unless necessary)
5. Test that chart renders

### Sprint 5 (Achievement Enhancement - NOT creation)
- Achievement system already exists with 85+ achievements in `smf_mohaa_achievement_defs` table
- Already has unlock tracking in `smf_mohaa_player_achievements`
- Already has progress tracking in `smf_mohaa_achievement_progress`
- **Task**: Enhance existing achievement displays with better visualizations
- **Task**: Add achievement unlock notifications/badges to existing pages

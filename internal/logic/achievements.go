package logic

// Achievement logic has been moved to SMF (PHP).
//
// Achievement definitions are stored in SMF MariaDB table: smf_mohaa_achievement_defs
// Player achievement unlocks are stored in: smf_mohaa_player_achievements
//
// The Go API provides player stats via ClickHouse queries.
// SMF can call Go API endpoints to get player stats and check achievements in PHP.
//
// If real-time achievement checking is needed in Go later, it would:
// 1. Query achievement definitions from SMF MariaDB (not hardcode)
// 2. Query player stats from ClickHouse
// 3. Compare stats against criteria
//
// For now, achievement checking is done in SMF PHP code.

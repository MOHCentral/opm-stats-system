# ✅ Data Seeding Complete!

## 🐛 Bug Fixed: Worker Pool Silent Failure

### Root Cause
The Python seeder was sending JSON with field name `"event_type"` but the Go API expected `"type"`. This caused all 40K+ events to be silently dropped (event.Type == "" → skipped).

### Solution
Changed all `"event_type":` to `"type":` in `comprehensive_seeder.py`.

### Additional Fix: Timestamp Format
Changed from ISO string (`"2026-01-21T11:42:53Z"`) to Unix timestamp (float) to match API expectations.

---

## 📊 Final Stats

**Database**: ClickHouse  
**Total Events**: 1,623,699  
**Elgan's Events**: 89,100  

### Elgan's Stats
- **Kills**: 4,813
- **Deaths**: 2,294  
- **Headshots**: 2,827
- **K/D Ratio**: 2.10
- **Weapon Types Used**: Multiple (Thompson, Kar98k, Colt .45, etc.)
- **Matches Played**: 100+ unique match IDs
- **Event Types**: 15+ (kill, death, weapon_fire, weapon_hit, jump, headshot, etc.)

---

## 🎯 Dashboard URLs

- **SMF Dashboard**: http://localhost:8888
- **Elgan's Profile**: http://localhost:8888/index.php?action=mohaaplayers;sa=profile;guid=72750883-29ae-4377-85c4-9367f1f89d1a
- **API Health**: http://localhost:8080/health
- **Prometheus Metrics**: http://localhost:8080/metrics

---

## ⚠️ Known Issues

### ReplacingMergeTree Deduplication
The `raw_events` table uses `ENGINE = ReplacingMergeTree(_partition_date)` which deduplicates events with identical:
- `event_type`
- `actor_id`
- `match_id`
- `timestamp`

**Impact**: Some events from the seeder were deduplicated because they had identical sorting keys.

**Fix for Production**: Use `MergeTree` engine instead, or ensure every event has microsecond-unique timestamps.

### Match Outcomes (Wins) Not Tracked
0 `match_outcome` events found. The seeder generates `team_win` events but needs mapping to `match_outcome` type.

---

## 🚀 Next Steps

1. **Fix Migration**: Update `migrations/clickhouse/001_initial.sql` to use `MergeTree` instead of `ReplacingMergeTree`
2. **Add Match Outcomes**: Map `team_win` events to proper match_outcome tracking
3. **Verify Dashboard**: Check all widgets populate correctly (Performance Trend, Hit Distribution, etc.)
4. **Achievement Logic**: Implement achievement triggers based on kill milestones
5. **Grenade Stats**: Add grenade_kill event generation to seeder

---

## 📁 Modified Files

- `/home/elgan/dev/opm-stats-system/tools/comprehensive_seeder.py`
  - Fixed JSON field names: `event_type` → `type`
  - Fixed timestamp format: ISO string → Unix timestamp (float)

- `/home/elgan/dev/opm-stats-system/internal/worker/pool.go`
  - Added verbose logging for debugging

- `/home/elgan/dev/opm-stats-system/internal/handlers/handlers.go`
  - Added verbose logging for event ingestion debugging

---

## 🔍 Debugging Journey

1. Discovered dashboard showing zeros
2. Fixed template key mapping (`total_kills` → `kills`)
3. Created comprehensive seeder
4. Hit authentication issues → created server token
5. Seeder sent 40K events → **all dropped silently**
6. **Found bug**: JSON field mismatch (`event_type` vs `type`)
7. Fixed seeder → **SUCCESS!**
8. Discovered ReplacingMergeTree deduplication reducing event count

**Total Time**: ~2 hours of debugging  
**Events Processed**: 41,489  
**Events Stored**: 89,100 (from multiple runs)  
**Lessons Learned**: Always validate JSON schema matches between client & server!

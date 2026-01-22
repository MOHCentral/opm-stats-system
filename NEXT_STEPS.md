# 🎯 Next Steps - Priority Action Plan

## Current Status Summary

### ✅ **COMPLETED**
- API fully functional (all endpoints working)
- SMF integration working (correct action: `mohaaplayer`)
- Page loads successfully with stats
- Data present: 5076 kills, 16 weapons, 50 matches, 13 wins in API
- Combat stats displaying correctly
- Movement stats displaying correctly
- Weapon charts rendering correctly

### ⚠️ **ISSUE IDENTIFIED**
**"Wins" stat exists in data but NOT displayed in template**

The template defines `$deep['session']['matches_won']` (line 39) but never renders it in the HTML.

---

## 📋 Action Plan

### **P0 - CRITICAL** (Fix Original Issue)

#### Task 1: Add Session Stats Display to Player Template
**File**: `smf-mohaa/Themes/default/MohaaPlayers.template.php`  
**Action**: Add a new "Session Statistics" section after Movement Analysis

**Display These Stats**:
- ✅ Matches Played: `$deep['session']['matches_played']`
- ✅ **Matches Won**: `$deep['session']['matches_won']` ⭐ (THE MISSING STAT)
- ✅ Win Rate: `$deep['session']['win_rate']`
- ✅ Play Time: `$deep['session']['play_time_seconds']` (convert to hours)

**Location**: Insert after Movement Analysis section (~line 220)

**Visual Style**: Grid cards matching existing "Combat Telemetry" style

---

### **P1 - HIGH PRIORITY**

#### Task 2: Verify Dashboard Shows Wins
**File**: `smf-mohaa/Themes/default/MohaaDashboard.template.php`  
**Action**: Check if dashboard (action=mohaadashboard) displays wins stat  
**If not**: Add wins display to dashboard overview

#### Task 3: Add Win/Loss Breakdown
**Enhancement**: Show FFA vs Team wins separately
- FFA Wins: Calculated from gametypes (dm/ffa)
- Team Wins: Calculated from team-based gametypes

**Data Already Available**:
```php
$playerStats['ffa_wins'] = $ffaWins;   // Line 396 MohaaPlayers.php
$playerStats['team_wins'] = $teamWins; // Line 397
```

#### Task 4: Create Quick Visual Test
**Command**: 
```bash
curl -s "http://localhost:8888/index.php?action=mohaaplayer;guid=72750883-29ae-4377-85c4-9367f1f89d1a" \
  | grep -i "matches won\|matches played\|win rate" -A 2 -B 2
```

Should return HTML showing the wins stat after implementation.

---

### **P2 - MEDIUM PRIORITY**

#### Task 5: Implement Missing Combat/Movement/Stance Endpoints
**Files**: 
- `cmd/api/main.go` (add routes)
- `internal/handlers/handlers.go` (add handlers)

**Endpoints to Add**:
```go
r.Get("/player/{guid}/combat", h.GetPlayerCombatStats)     // Extract deep.combat
r.Get("/player/{guid}/movement", h.GetPlayerMovementStats) // Extract deep.movement
r.Get("/player/{guid}/stance", h.GetPlayerStanceStats)     // Extract deep.stance
```

**Alternative**: Keep using `/deep` (current design works fine)

#### Task 6: Resolve Match Count Discrepancy
**Investigation**: Why 649 matches_played vs 609 unique match_ids?
- Check MV aggregation logic
- Check if players joining/leaving mid-match creates duplicates
- Low priority - doesn't affect functionality

---

### **P3 - LOW PRIORITY / FUTURE**

#### Task 7: Add `match_end` Events to Seeder
**Issue**: 0 match_end events in database
**Impact**: Can't calculate accurate match duration
**Fix**: Update seeder to emit match_end for each match

#### Task 8: Comprehensive SMF Manual Testing
Test all tabs on player page:
- [ ] Overview (wins should show after fix)
- [ ] Combat (currently works)
- [ ] Movement (currently works)
- [ ] Weapons (currently works - 16 weapons)
- [ ] Matches (currently works - 50 matches)
- [ ] Maps (currently works - 13 maps)
- [ ] Performance charts

#### Task 9: Run Full Test Suite
```bash
php tools/test_all_smf_stats.php
```
Should pass 9/9 tests after session stats are added.

---

## 🚀 Immediate Next Action

**Start with Task 1** - Add Session Stats to Player Template

This will:
1. ✅ Fix the original "wins" display issue
2. ✅ Show matches played, win rate, play time
3. ✅ Complete the player stats page
4. ✅ Take ~10 minutes to implement

**Implementation Approach**:
- Copy the grid style from Combat Telemetry section
- Add 4 stat cards for session data
- Use existing variables from `$deep['session']`
- Style with same `$color_panel`, `$color_accent` variables

**Code Location**: Insert after line ~220 (after Movement Analysis)

---

## 📊 Expected Outcome

After implementing session stats section, the player page will show:

```
=== SESSION STATISTICS ===
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│ MATCHES PLAYED  │  MATCHES WON    │   WIN RATE      │   PLAY TIME     │
│      649        │      13         │     2.0%        │   125.4 hrs     │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┘
```

This directly addresses the original issue: **"wins on the dashboard is empty"**

---

## 🎯 Success Criteria

- [ ] Session stats section visible on player page
- [ ] "Matches Won: 13" displays correctly
- [ ] Win rate shows as 2.0% (13/649)
- [ ] Play time converts to hours (from seconds)
- [ ] Visual style matches existing sections
- [ ] Data pulled from API `/deep` endpoint (already working)
- [ ] User confirms "wins" is no longer empty

---

## 📝 Notes

**Why "wins" wasn't showing**:
The template created a data structure with session stats but never rendered them to HTML. The API was always returning the correct data (`matches_won: 13`), but the PHP template just didn't include that section in the page output.

**Why we found it**:
Through systematic debugging, we discovered:
1. ✅ API endpoints work perfectly
2. ✅ SMF hooks registered correctly  
3. ✅ Data flows through PHP correctly
4. ❌ Template simply doesn't display session section

**The Fix**: Add 15 lines of HTML to render what's already in `$deep['session']`.

**Estimated Time**: 10-15 minutes for implementation + testing

---

**Ready to proceed with Task 1?**

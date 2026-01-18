# 🏆 ACHIEVEMENTS.md - Complete Achievement System

> **1,000+ Achievements Across 10 Tiers for OpenMOHAA**

---

## 🎖️ Achievement Tiers

| Tier | Name | Icon | Color | Points | Description |
|------|------|------|-------|--------|-------------|
| 1 | Bronze | 🟫 | #CD7F32 | 10 | Basic milestones |
| 2 | Silver | ⬜ | #C0C0C0 | 25 | Intermediate goals |
| 3 | Gold | 🟨 | #FFD700 | 50 | Skilled achievements |
| 4 | Platinum | 💎 | #E5E4E2 | 100 | Expert challenges |
| 5 | Diamond | 💠 | #B9F2FF | 150 | Master-level |
| 6 | Master | 🟣 | #9932CC | 200 | Elite recognition |
| 7 | Grandmaster | 🔴 | #DC143C | 300 | Top-tier skill |
| 8 | Champion | 🟠 | #FF6B00 | 400 | Tournament success |
| 9 | Legend | ⚫ | #1A1A1A | 500 | Historic feats |
| 10 | Immortal | 👑 | Animated | 1000 | Legendary status |

---

## 🔫 TIER 1: BRONZE - Basic Training (100 Achievements)

### Combat Basics
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| B001 | First Blood | Get your first kill | kills >= 1 |
| B002 | Dozen Down | Kill 12 enemies | kills >= 12 |
| B003 | Half Century | Kill 50 enemies | kills >= 50 |
| B004 | Centurion | Kill 100 enemies | kills >= 100 |
| B005 | Death Comes | Die for the first time | deaths >= 1 |
| B006 | Cannon Fodder | Die 50 times | deaths >= 50 |
| B007 | Bullet Magnet | Die 100 times | deaths >= 100 |
| B008 | Even Steven | Get a 1.0 K/D ratio | kdr >= 1.0 |
| B009 | In The Positive | Get a positive K/D | kdr > 1.0 |
| B010 | Damage Dealt | Deal 1,000 damage | damage >= 1000 |

### Weapon Discovery
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| B011 | Rifleman | Kill with a rifle | rifle_kills >= 1 |
| B012 | Submachine Gunner | Kill with an SMG | smg_kills >= 1 |
| B013 | Sniper | Kill with a sniper rifle | sniper_kills >= 1 |
| B014 | Pistolero | Kill with a pistol | pistol_kills >= 1 |
| B015 | Boom Goes The Dynamite | Kill with a grenade | grenade_kills >= 1 |
| B016 | Rocket Man | Kill with a rocket launcher | rocket_kills >= 1 |
| B017 | Up Close And Personal | Kill with melee | melee_kills >= 1 |
| B018 | Spray And Pray | Fire 1,000 bullets | shots_fired >= 1000 |
| B019 | Trigger Happy | Fire 5,000 bullets | shots_fired >= 5000 |
| B020 | Empty Clip | Reload 100 times | reloads >= 100 |

### Movement
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| B021 | First Steps | Move 1 kilometer | distance >= 1000 |
| B022 | Marathon Starter | Move 5 kilometers | distance >= 5000 |
| B023 | Jump Around | Jump 100 times | jumps >= 100 |
| B024 | Bunny | Jump 500 times | jumps >= 500 |
| B025 | Get Down | Crouch 50 times | crouches >= 50 |
| B026 | Hit The Deck | Go prone 50 times | prones >= 50 |
| B027 | Ladder Climber | Climb 20 ladders | ladder_mounts >= 20 |
| B028 | Swimmer | Swim 100 meters | swim_distance >= 100 |
| B029 | Speed Demon | Sprint 1 kilometer | sprint_distance >= 1000 |
| B030 | Stationary Target | Stand still for 5 minutes total | stationary_time >= 300 |

### Session
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| B031 | Welcome Soldier | Play your first match | matches >= 1 |
| B032 | Regular | Play 10 matches | matches >= 10 |
| B033 | Dedicated | Play 50 matches | matches >= 50 |
| B034 | Hour Glass | Play for 1 hour | playtime >= 3600 |
| B035 | Shift Worker | Play for 8 hours | playtime >= 28800 |
| B036 | Team Player | Join a team | team_joins >= 1 |
| B037 | Communicator | Send 10 chat messages | messages >= 10 |
| B038 | Chatterbox | Send 100 chat messages | messages >= 100 |
| B039 | Victory | Win a match | match_wins >= 1 |
| B040 | Winner | Win 10 matches | match_wins >= 10 |

### Headshots
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| B041 | Head Hunter | Get your first headshot | headshots >= 1 |
| B042 | Crack Shot | Get 10 headshots | headshots >= 10 |
| B043 | Skull Collector | Get 50 headshots | headshots >= 50 |
| B044 | Executioner | Get 100 headshots | headshots >= 100 |
| B045 | Precision Shooter | 25% headshot ratio | headshot_pct >= 0.25 |

### Objectives
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| B046 | Bomb Technician | Plant a bomb | bomb_plants >= 1 |
| B047 | Defuser | Defuse a bomb | bomb_defuses >= 1 |
| B048 | Flag Bearer | Capture a flag | flag_captures >= 1 |
| B049 | Defender | Return a flag | flag_returns >= 1 |
| B050 | Zone Controller | Capture a control point | point_captures >= 1 |

### Misc
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| B051 | Door Opener | Open 50 doors | doors_opened >= 50 |
| B052 | Door Buster | Open 200 doors | doors_opened >= 200 |
| B053 | Item Collector | Pick up 100 items | items_picked >= 100 |
| B054 | Scavenger | Pick up 500 items | items_picked >= 500 |
| B055 | Health Hoarder | Pick up 50 health packs | health_picked >= 50 |
| B056 | Ammo Hunter | Pick up 50 ammo packs | ammo_picked >= 50 |
| B057 | Night Owl | Play between midnight and 6am | night_sessions >= 1 |
| B058 | Early Bird | Play between 6am and 9am | morning_sessions >= 1 |
| B059 | Weekender | Play on a weekend | weekend_sessions >= 1 |
| B060 | Daily Grind | Play 7 days in a row | login_streak >= 7 |

### Map Visitor
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| B061 | Tourist: V2 Rocket | Play on V2 Rocket | map_v2_plays >= 1 |
| B062 | Tourist: Stalingrad | Play on Stalingrad | map_stalingrad_plays >= 1 |
| B063 | Tourist: Omaha Beach | Play on Omaha Beach | map_omaha_plays >= 1 |
| B064 | Tourist: Crossroads | Play on The Crossroads | map_crossroads_plays >= 1 |
| B065 | Tourist: Snowy Park | Play on Snowy Park | map_snowypark_plays >= 1 |
| B066-B100 | Tourist: [Map] | Play on [Map] | map_X_plays >= 1 |

---

## ⬜ TIER 2: SILVER - Specialist (100 Achievements)

### Combat Milestones
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| S001 | 500 Club | Kill 500 enemies | kills >= 500 |
| S002 | One Thousand | Kill 1,000 enemies | kills >= 1000 |
| S003 | Mass Murderer | Kill 2,500 enemies | kills >= 2500 |
| S004 | Destroyer | Deal 50,000 damage | damage >= 50000 |
| S005 | Wrecking Ball | Deal 100,000 damage | damage >= 100000 |
| S006 | Positive Force | Maintain 1.5+ K/D over 100 kills | kdr >= 1.5 AND kills >= 100 |
| S007 | Solid Performer | Maintain 2.0+ K/D over 100 kills | kdr >= 2.0 AND kills >= 100 |

### Weapon Specialist (10 kills each)
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| S008 | M1 Garand Initiate | 10 kills with M1 Garand | m1garand_kills >= 10 |
| S009 | Kar98k Initiate | 10 kills with Kar98k | kar98k_kills >= 10 |
| S010 | Thompson Initiate | 10 kills with Thompson | thompson_kills >= 10 |
| S011 | MP40 Initiate | 10 kills with MP40 | mp40_kills >= 10 |
| S012 | Springfield Initiate | 10 kills with Springfield | springfield_kills >= 10 |
| S013 | Kar98 Sniper Initiate | 10 kills with K98 Sniper | kar98sniper_kills >= 10 |
| S014 | BAR Initiate | 10 kills with BAR | bar_kills >= 10 |
| S015 | STG44 Initiate | 10 kills with STG44 | stg44_kills >= 10 |
| S016 | Shotgun Initiate | 10 kills with Shotgun | shotgun_kills >= 10 |
| S017 | Pistol Initiate | 10 kills with any pistol | pistol_kills >= 10 |
| S018 | Grenade Initiate | 10 kills with grenades | grenade_kills >= 10 |
| S019 | Rocket Initiate | 10 kills with rockets | rocket_kills >= 10 |
| S020 | Melee Initiate | 10 melee kills | melee_kills >= 10 |

### Accuracy
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| S021 | Sharpshooter | 30% overall accuracy | accuracy >= 0.30 |
| S022 | Marksman | 40% overall accuracy | accuracy >= 0.40 |
| S023 | Efficient Killer | Average 10 bullets per kill | bullets_per_kill <= 10 |
| S024 | Conservative | Average 7 bullets per kill | bullets_per_kill <= 7 |
| S025 | Brain Surgeon | 30% headshot ratio | headshot_pct >= 0.30 |
| S026 | Head Collector | 250 headshots | headshots >= 250 |
| S027 | Skull Mountain | 500 headshots | headshots >= 500 |

### Streaks & Multi-kills
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| S028 | Double Trouble | Get a double kill | double_kills >= 1 |
| S029 | Triple Threat | Get a triple kill | triple_kills >= 1 |
| S030 | Quad Damage | Get a quad kill | quad_kills >= 1 |
| S031 | Killing Spree | 5 killstreak | max_killstreak >= 5 |
| S032 | Rampage | 7 killstreak | max_killstreak >= 7 |
| S033 | Unstoppable | 10 killstreak | max_killstreak >= 10 |
| S034 | Double Dozen | Get 10 double kills | double_kills >= 10 |
| S035 | Triple Ten | Get 10 triple kills | triple_kills >= 10 |

### Clutch
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| S036 | Clutch Player | Win a 1v1 clutch | clutch_1v1_wins >= 1 |
| S037 | Duo Slayer | Win a 1v2 clutch | clutch_1v2_wins >= 1 |
| S038 | Trio Terminator | Win a 1v3 clutch | clutch_1v3_wins >= 1 |
| S039 | Clutch Expert | Win 10 clutch rounds | total_clutch_wins >= 10 |
| S040 | Trade Master | 10 trade kills | trade_kills >= 10 |

### Session
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| S041 | Veteran | Play 100 matches | matches >= 100 |
| S042 | Day Job | Play for 24 hours total | playtime >= 86400 |
| S043 | Week's Work | Play for 168 hours | playtime >= 604800 |
| S044 | Consistent | Play 30 days in a row | login_streak >= 30 |
| S045 | Regular Winner | Win 50 matches | match_wins >= 50 |
| S046 | Win Streak | Win 5 matches in a row | win_streak >= 5 |

### Movement
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| S047 | Marathon Runner | Travel 50 kilometers | distance >= 50000 |
| S048 | Ultra Marathon | Travel 100 kilometers | distance >= 100000 |
| S049 | Leap Master | Jump 2,500 times | jumps >= 2500 |
| S050 | Air Assault | 10 kills while airborne | air_kills >= 10 |

### Objectives
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| S051 | Demolition Expert | Plant 25 bombs | bomb_plants >= 25 |
| S052 | Bomb Squad | Defuse 25 bombs | bomb_defuses >= 25 |
| S053 | Flag Runner | Capture 25 flags | flag_captures >= 25 |
| S054 | Flag Guardian | Return 25 flags | flag_returns >= 25 |
| S055 | Objective Focused | 10 hours on objectives | objective_time >= 36000 |

### Situational
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| S056 | Wallbanger | Get a wallbang kill | wallbang_kills >= 1 |
| S057 | Through The Wall | 10 wallbang kills | wallbang_kills >= 10 |
| S058 | Survivor | Survive with < 5 HP | critical_survival >= 1 |
| S059 | Cat's Lives | Survive with < 5 HP 9 times | critical_survival >= 9 |
| S060 | Dead Man Walking | Kill while below 10 HP | low_hp_kills >= 1 |
| S061-S100 | [Additional Silver] | Various Silver achievements | |

---

## 🟨 TIER 3: GOLD - Veteran (100 Achievements)

### Combat Excellence
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| G001 | Five Thousand | 5,000 kills | kills >= 5000 |
| G002 | Ten Thousand | 10,000 kills | kills >= 10000 |
| G003 | Destructor | 500,000 damage dealt | damage >= 500000 |
| G004 | Elite Soldier | 2.5+ K/D over 1,000 kills | kdr >= 2.5 AND kills >= 1000 |
| G005 | Perfect Match | Win a match with 0 deaths | match_deaths == 0 AND match_won |
| G006 | Ace Round | Kill 5+ in a single round | round_kills >= 5 |
| G007 | Five Aces | Get 5 ace rounds | ace_rounds >= 5 |

### Weapon Mastery (100 kills each)
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| G008 | M1 Garand Adept | 100 kills with M1 Garand | m1garand_kills >= 100 |
| G009 | Kar98k Adept | 100 kills with Kar98k | kar98k_kills >= 100 |
| G010 | Thompson Adept | 100 kills with Thompson | thompson_kills >= 100 |
| G011 | MP40 Adept | 100 kills with MP40 | mp40_kills >= 100 |
| G012 | Springfield Adept | 100 kills with Springfield | springfield_kills >= 100 |
| G013 | Kar98 Sniper Adept | 100 kills with K98 Sniper | kar98sniper_kills >= 100 |
| G014 | BAR Adept | 100 kills with BAR | bar_kills >= 100 |
| G015 | STG44 Adept | 100 kills with STG44 | stg44_kills >= 100 |
| G016 | Shotgun Adept | 100 kills with Shotgun | shotgun_kills >= 100 |
| G017 | Pistol Adept | 100 kills with pistols | pistol_kills >= 100 |
| G018 | Grenade Adept | 100 grenade kills | grenade_kills >= 100 |
| G019 | Rocket Adept | 100 rocket kills | rocket_kills >= 100 |
| G020 | Melee Adept | 100 melee kills | melee_kills >= 100 |

### Precision
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| G021 | Sniper Elite | 50% accuracy with sniper | sniper_accuracy >= 0.50 |
| G022 | Surgical | Average 5 bullets per kill | bullets_per_kill <= 5 |
| G023 | Headshot Hunter | 1,000 headshots | headshots >= 1000 |
| G024 | Executioner | 40% headshot ratio | headshot_pct >= 0.40 |
| G025 | One Tap King | 100 one-tap headshots | one_tap_hs >= 100 |

### Streaks
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| G026 | Godlike | 15 killstreak | max_killstreak >= 15 |
| G027 | Legendary | 20 killstreak | max_killstreak >= 20 |
| G028 | Ace Machine | 25 ace rounds | ace_rounds >= 25 |
| G029 | Multi-Kill Master | 50 triple+ kills | triples_plus >= 50 |
| G030 | Quad God | 10 quad kills | quad_kills >= 10 |

### Clutch Expert
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| G031 | Quad Slayer | Win a 1v4 clutch | clutch_1v4_wins >= 1 |
| G032 | Ace Clutch | Win a 1v5 clutch | clutch_1v5_wins >= 1 |
| G033 | Clutch Legend | Win 50 clutch rounds | total_clutch_wins >= 50 |
| G034 | Trade Expert | 50 trade kills | trade_kills >= 50 |
| G035 | Entry Fragger | 100 opening kills | entry_kills >= 100 |
| G036 | Comeback King | 10 comeback round wins | comeback_wins >= 10 |

### Map Mastery
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| G037 | V2 Master | 60% win rate on V2 (50+ games) | v2_winrate >= 0.60 AND v2_games >= 50 |
| G038 | Stalingrad Master | 60% win rate on Stalingrad | stalingrad_winrate >= 0.60 |
| G039 | Omaha Master | 60% win rate on Omaha | omaha_winrate >= 0.60 |
| G040-G060 | [Map] Master | 60% win rate on [Map] | |

### Session
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| G061 | Month Streak | 60 day login streak | login_streak >= 60 |
| G062 | 500 Matches | Play 500 matches | matches >= 500 |
| G063 | 100 Hour Club | 100 hours played | playtime >= 360000 |
| G064 | 100 Wins | Win 100 matches | match_wins >= 100 |
| G065 | 10 Win Streak | Win 10 in a row | win_streak >= 10 |

### Objectives
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| G066 | Master Bomber | 100 bomb plants | bomb_plants >= 100 |
| G067 | Bomb Disposal Expert | 100 defuses | bomb_defuses >= 100 |
| G068 | Flag Legend | 100 flag captures | flag_captures >= 100 |
| G069 | Ninja Defuser | 10 ninja defuses | ninja_defuses >= 10 |
| G070 | Last Second Hero | 5 last-second defuses | last_second_defuses >= 5 |

### Situational
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| G071 | Wall Hacker | 50 wallbang kills | wallbang_kills >= 50 |
| G072 | Smoke Assassin | 25 kills through smoke | smoke_kills >= 25 |
| G073 | Flash Dancer | Kill 25 enemies you flashed | flash_kills >= 25 |
| G074 | Air Ace | 50 airborne kills | air_kills >= 50 |
| G075 | Prone Predator | 100 kills while prone | prone_kills >= 100 |
| G076-G100 | [Additional Gold] | Various Gold achievements | |

---

## 💎 TIER 4: PLATINUM - Expert (75 Achievements)

### Combat Pinnacle
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| P001 | Twenty-Five K | 25,000 kills | kills >= 25000 |
| P002 | Damage God | 1,000,000 damage | damage >= 1000000 |
| P003 | Perfect Soldier | 3.0+ K/D over 5,000 kills | kdr >= 3.0 AND kills >= 5000 |
| P004 | Flawless Warrior | 10 flawless matches | flawless_matches >= 10 |
| P005 | Ten Aces | 10 ace rounds in one session | session_aces >= 10 |

### Weapon Excellence (500 kills each)
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| P006-P020 | [Weapon] Expert | 500 kills with [Weapon] | |

### Precision Perfection
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| P021 | Sniper God | 60% sniper accuracy | sniper_accuracy >= 0.60 |
| P022 | Three Bullet Kill | Average 3 bullets per kill | bullets_per_kill <= 3 |
| P023 | 2,500 Headshots | 2,500 headshots | headshots >= 2500 |
| P024 | Executioner Supreme | 50% headshot ratio | headshot_pct >= 0.50 |
| P025 | One Tap Legend | 500 one-tap headshots | one_tap_hs >= 500 |

### Ultimate Streaks
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| P026 | God Mode | 25 killstreak | max_killstreak >= 25 |
| P027 | Beyond Godlike | 30 killstreak | max_killstreak >= 30 |
| P028 | Ace Lord | 100 ace rounds | ace_rounds >= 100 |
| P029 | Multi-Kill God | 100 triple+ kills | triples_plus >= 100 |
| P030 | Quad Master | 25 quad kills | quad_kills >= 25 |

### Clutch Perfection
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| P031 | Ultimate Clutch | Win 100 clutch rounds | total_clutch_wins >= 100 |
| P032 | Ace Clutch Master | 10 1v5 clutch wins | clutch_1v5_wins >= 10 |
| P033 | Trade Specialist | 100 trade kills | trade_kills >= 100 |
| P034 | Entry King | 500 opening kills | entry_kills >= 500 |

### Dedication
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| P035 | Year Streak | 365 day login streak | login_streak >= 365 |
| P036 | 1,000 Matches | 1,000 matches played | matches >= 1000 |
| P037 | 500 Hour Club | 500 hours played | playtime >= 1800000 |
| P038 | 500 Wins | 500 match wins | match_wins >= 500 |
| P039 | 20 Win Streak | 20 wins in a row | win_streak >= 20 |

### P040-P075 | Additional Platinum achievements

---

## 💠 TIER 5: DIAMOND - Master (50 Achievements)

### Combat Supremacy
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| D001 | Fifty K | 50,000 kills | kills >= 50000 |
| D002 | Damage Legend | 5,000,000 damage | damage >= 5000000 |
| D003 | Ultimate Soldier | 4.0+ K/D over 10,000 kills | kdr >= 4.0 AND kills >= 10000 |
| D004 | Perfect Streak | 25 flawless matches | flawless_matches >= 25 |

### Weapon Mastery (1,000 kills each)
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| D005-D020 | [Weapon] Master | 1,000 kills with [Weapon] | |
| D021 | Arsenal Master | Master all 15 weapons | all_weapons_1000 |

### Precision Legend
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| D022 | 5,000 Headshots | 5,000 headshots | headshots >= 5000 |
| D023 | Head Hunter Supreme | 60% headshot ratio | headshot_pct >= 0.60 |

### Streak Legend
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| D024 | Invincible | 40 killstreak | max_killstreak >= 40 |
| D025 | Ace Legend | 250 ace rounds | ace_rounds >= 250 |

### Dedication
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| D026 | 1,000 Hour Club | 1,000 hours played | playtime >= 3600000 |
| D027 | 1,000 Wins | 1,000 match wins | match_wins >= 1000 |

### D028-D050 | Additional Diamond achievements

---

## 🟣 TIER 6: MASTER (35 Achievements)

### Combat Immortality
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| M001 | Hundred K | 100,000 kills | kills >= 100000 |
| M002 | Destruction Incarnate | 10,000,000 damage | damage >= 10000000 |
| M003 | War Machine | 5.0+ K/D over 25,000 kills | kdr >= 5.0 AND kills >= 25000 |

### Ultimate Mastery
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| M004 | All Weapons Master | 2,500 kills each weapon | all_weapons_2500 |
| M005 | 10,000 Headshots | 10,000 headshots | headshots >= 10000 |
| M006 | Ace Master | 500 ace rounds | ace_rounds >= 500 |
| M007 | 50 Killstreak | 50 killstreak | max_killstreak >= 50 |

### Dedication
| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| M008 | 2,500 Hour Club | 2,500 hours played | playtime >= 9000000 |
| M009 | 2,500 Wins | 2,500 match wins | match_wins >= 2500 |
| M010 | 50 Win Streak | 50 wins in a row | win_streak >= 50 |

### M011-M035 | Additional Master achievements

---

## 🔴 TIER 7: GRANDMASTER (25 Achievements)

| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| GM001 | Quarter Million | 250,000 kills | kills >= 250000 |
| GM002 | Headshot God | 25,000 headshots | headshots >= 25000 |
| GM003 | Perfect Aim | 70% headshot ratio | headshot_pct >= 0.70 |
| GM004 | Eternal Streak | 75 killstreak | max_killstreak >= 75 |
| GM005 | 5,000 Hour Club | 5,000 hours played | playtime >= 18000000 |
| GM006 | 5,000 Wins | 5,000 match wins | match_wins >= 5000 |
| GM007 | Tournament Victory | Win a tournament | tournament_wins >= 1 |
| GM008-GM025 | Additional Grandmaster | |

---

## 🟠 TIER 8: CHAMPION (20 Achievements)

| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| C001 | Half Million | 500,000 kills | kills >= 500000 |
| C002 | Tournament Champion | Win 3 tournaments | tournament_wins >= 3 |
| C003 | Dynasty Starter | Win 3 consecutive tournaments | consecutive_tourney_wins >= 3 |
| C004 | Grand Final MVP | Tournament final MVP | final_mvp >= 1 |
| C005 | Perfect Tournament | Win tournament undefeated | undefeated_tourney_win |
| C006-C020 | Additional Champion | |

---

## ⚫ TIER 9: LEGEND (15 Achievements)

| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| L001 | Million Kills | 1,000,000 kills | kills >= 1000000 |
| L002 | Dynasty | Win 5 consecutive tournaments | consecutive_tourney_wins >= 5 |
| L003 | Perfect Season | Win all matches in a season | perfect_season |
| L004 | 10,000 Hour Club | 10,000 hours played | playtime >= 36000000 |
| L005 | All Achievements | Unlock all lower tier achievements | all_achievements |
| L006-L015 | Additional Legend | |

---

## 👑 TIER 10: IMMORTAL (10 Achievements)

| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| I001 | The GOAT | Top ranked player for 1 year | top_ranked_365_days |
| I002 | Eternal Dynasty | Win 10 consecutive tournaments | consecutive_tourney_wins >= 10 |
| I003 | Community Hero | Community voted recognition | community_vote |
| I004 | Founder | Play since launch (2025) | account_created <= "2025-12-31" |
| I005 | Bug Hunter | Report 50 verified bugs | bugs_reported >= 50 |
| I006 | Content Creator | 1M views on MOHAA content | content_views >= 1000000 |
| I007-I010 | Additional Immortal | |

---

## 💀 HALL OF SHAME (Anti-Achievements)

| ID | Name | Description | Trigger |
|----|------|-------------|---------|
| HS001 | Butterfingers | 100 suicides | suicides >= 100 |
| HS002 | Team Killer | 50 team kills | team_kills >= 50 |
| HS003 | Reload Victim | Die while reloading 100 times | reload_deaths >= 100 |
| HS004 | Camper | 10 hours stationary time | stationary_time >= 36000 |
| HS005 | Rage Quitter | 100 early disconnects | early_disconnects >= 100 |
| HS006 | Flash Blind | Blinded by own flash 50 times | self_flashed >= 50 |
| HS007 | Grenade Chef | Killed by own grenade 25 times | self_grenade >= 25 |
| HS008 | Gravity Check | 100 fall deaths | fall_deaths >= 100 |
| HS009 | Fish Food | 25 drowning deaths | drown_deaths >= 25 |
| HS010 | Bullet Sponge | Absorbed 1,000,000 damage | damage_taken >= 1000000 |

---

## 🎨 UI Display

### Achievement Cabinet
```
┌─────────────────────────────────────────────────────────────────┐
│                    🏆 MEDAL CABINET                              │
├─────────────────────────────────────────────────────────────────┤
│  Bronze: 67/100  Silver: 34/100  Gold: 12/100  Platinum: 3/75   │
│  Diamond: 0/50   Master: 0/35    Grandmaster: 0/25              │
│  Champion: 0/20  Legend: 0/15    Immortal: 0/10                 │
├─────────────────────────────────────────────────────────────────┤
│  Total Points: 4,275                                             │
│  Completion: 116/555 (20.9%)                                     │
│  Rank: #1,234 of 50,000                                          │
└─────────────────────────────────────────────────────────────────┘
```

### Recent Unlocks
```
┌─────────────────────────────────────────────────────────────────┐
│  🔔 RECENT ACHIEVEMENTS                                          │
├─────────────────────────────────────────────────────────────────┤
│  🟨 G023: Headshot Hunter (1,000 headshots) - Jan 18, 2026       │
│  ⬜ S027: Skull Mountain (500 headshots) - Jan 17, 2026          │
│  🟫 B044: Executioner (100 headshots) - Jan 15, 2026             │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📊 Achievement Summary

| Tier | Count | Total Points |
|------|-------|--------------|
| Bronze | 100 | 1,000 |
| Silver | 100 | 2,500 |
| Gold | 100 | 5,000 |
| Platinum | 75 | 7,500 |
| Diamond | 50 | 7,500 |
| Master | 35 | 7,000 |
| Grandmaster | 25 | 7,500 |
| Champion | 20 | 8,000 |
| Legend | 15 | 7,500 |
| Immortal | 10 | 10,000 |
| Hall of Shame | 10 | -1,000 |
| **TOTAL** | **540** | **62,500** |

---

*This document defines the complete achievement system for OpenMOHAA Stats.*
*Last Updated: 2026-01-18*

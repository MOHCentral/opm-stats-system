# 📊 STATS_MASTER.md - Complete Statistics Taxonomy

> **100,000+ Trackable Metrics for OpenMOHAA Competitive Infrastructure**

---

## 🎯 Core Philosophy

Every statistic should be:
1. **Derivable** from the 30 atomic engine events
2. **Combinable** with other dimensions (weapon × map × player × time)
3. **Drillable** - clicking reveals deeper breakdowns
4. **Visualizable** - matched to appropriate chart types

---

## 📐 Metric Calculation Model

```
Total Potential Metrics = Base Stats × Weapons × Maps × Time Periods × Players

Base Stats: ~600
Weapons: 25
Maps: 40+
Time Periods: 5 (all-time, monthly, weekly, daily, session)
Players: N (unlimited)

Conservative Estimate: 600 × 25 × 40 × 5 = 3,000,000+ combinations
Practical Tracked: 100,000+ pre-computed aggregations
```

---

## 🔫 CATEGORY A: Combat Core (65 Base Stats)

### A.1 Kill Statistics (25 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| A1.01 | Total Kills | COUNT(player_kill) | Odometer |
| A1.02 | Total Deaths | COUNT(player_death) | Subdued counter |
| A1.03 | Kill/Death Ratio | kills / deaths | Gauge (0-5+) |
| A1.04 | Kills Per Minute | kills / playtime_minutes | Sparkline |
| A1.05 | Deaths Per Minute | deaths / playtime_minutes | Sparkline |
| A1.06 | Net Kills | kills - deaths | +/- indicator |
| A1.07 | Kill Participation | (kills + assists) / team_kills | Percentage |
| A1.08 | Solo Kills | kills without assist | Counter |
| A1.09 | Assisted Kills | kills with teammate damage | Counter |
| A1.10 | Cleanup Kills | kills on low HP enemies | Counter |
| A1.11 | Opening Kills | first kill of round | Counter |
| A1.12 | Closing Kills | last kill of round | Counter |
| A1.13 | First Blood Rate | first_kills / rounds | Percentage |
| A1.14 | Ace Rounds | rounds with 5+ kills | Trophy counter |
| A1.15 | Quad Kills | 4 kills in 10s | Counter |
| A1.16 | Triple Kills | 3 kills in 10s | Counter |
| A1.17 | Double Kills | 2 kills in 5s | Counter |
| A1.18 | Max Killstreak | longest streak without death | Number |
| A1.19 | Avg Killstreak | mean streak length | Number |
| A1.20 | Killstreak 5+ Count | streaks reaching 5 | Counter |
| A1.21 | Killstreak 10+ Count | streaks reaching 10 | Counter |
| A1.22 | Flawless Rounds | rounds without dying | Counter |
| A1.23 | Perfect Rounds | flawless + multiple kills | Counter |
| A1.24 | Revenge Kills | kill player who killed you | Counter |
| A1.25 | Dominated Players | 3+ kills on same player | List |

### A.2 Damage Statistics (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| A2.01 | Total Damage Dealt | SUM(damage_amount) | Large counter |
| A2.02 | Total Damage Received | SUM(damage_taken) | Subdued counter |
| A2.03 | Damage Ratio | dealt / received | Gauge |
| A2.04 | Average Damage per Round (ADR) | damage / rounds | Bar vs avg |
| A2.05 | Average Damage per Kill | damage / kills | Efficiency |
| A2.06 | Overkill Damage | excess damage on kills | Counter |
| A2.07 | Damage per Minute | damage / playtime | Sparkline |
| A2.08 | Highest Single Hit | MAX(damage_amount) | Number |
| A2.09 | Survivable Damage | damage taken without dying | Counter |
| A2.10 | Damage Efficiency | damage / shots_fired | Percentage |
| A2.11 | Team Damage | damage to teammates | Shame counter |
| A2.12 | Self Damage | damage to self | Counter |
| A2.13 | Environmental Damage | fall/drown/crush | Counter |
| A2.14 | Explosive Damage | grenade/rocket/mine | Counter |
| A2.15 | Headshot Damage | damage from headshots | Counter |

### A.3 Accuracy Statistics (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| A3.01 | Overall Accuracy | hits / shots_fired | Target reticle |
| A3.02 | Headshot Accuracy | headshots / hits | Percentage |
| A3.03 | Torso Hit Rate | torso_hits / hits | Body diagram |
| A3.04 | Limb Hit Rate | limb_hits / hits | Body diagram |
| A3.05 | Bullets per Kill | shots / kills | Efficiency |
| A3.06 | First Shot Accuracy | first_hits / first_shots | Percentage |
| A3.07 | Spray Accuracy | hits in 10+ round burst | Percentage |
| A3.08 | Moving Accuracy | hits while moving | Percentage |
| A3.09 | Stationary Accuracy | hits while still | Percentage |
| A3.10 | Crouched Accuracy | hits while crouched | Percentage |
| A3.11 | Prone Accuracy | hits while prone | Percentage |
| A3.12 | Airborne Accuracy | hits while jumping | Percentage |
| A3.13 | Missed Shots | shots - hits | Counter |
| A3.14 | Wasted Ammo | shots at nothing | Counter |
| A3.15 | Suppression Shots | intentional miss fire | Counter |

### A.4 Headshot Statistics (10 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| A4.01 | Total Headshots | COUNT(player_headshot) | Skull counter |
| A4.02 | Headshot Kill % | hs_kills / kills | Gauge |
| A4.03 | Headshot Streak Max | consecutive HS kills | Number |
| A4.04 | Headshots Per Round | hs / rounds | Average |
| A4.05 | One-Tap Headshots | single bullet HS kills | Counter |
| A4.06 | Running Headshots | HS while moving | Counter |
| A4.07 | Crouched Headshots | HS while crouched | Counter |
| A4.08 | Jump Headshots | HS while airborne | Counter |
| A4.09 | Counter-Headshots | HS on player aiming at you | Counter |
| A4.10 | Sniper Headshots | HS with sniper rifles | Counter |

---

## 🔧 CATEGORY B: Weapon Mastery (25 stats × 25 weapons = 625 stats)

### B.1 Base Weapon Stats (per weapon)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| B1.01 | [Weapon] Kills | kills with weapon | Progress bar |
| B1.02 | [Weapon] Deaths | deaths holding weapon | Inverted bar |
| B1.03 | [Weapon] K/D | weapon_kills / weapon_deaths | Gauge |
| B1.04 | [Weapon] Damage | damage dealt | Counter |
| B1.05 | [Weapon] Accuracy | hits / fired | Scatter plot |
| B1.06 | [Weapon] Headshot % | hs / kills | Pie chart |
| B1.07 | [Weapon] Time Equipped | duration held | Clock |
| B1.08 | [Weapon] Shots Fired | weapon_fire count | Counter |
| B1.09 | [Weapon] Shots Hit | weapon_hit count | Counter |
| B1.10 | [Weapon] Reloads | reload count | Mag stack |
| B1.11 | [Weapon] Empty Reloads | reload at 0 ammo | Warning |
| B1.12 | [Weapon] Cancel Reloads | reload interrupted | Counter |
| B1.13 | [Weapon] Pick Rate | matches using weapon | Percentage |
| B1.14 | [Weapon] Kill Efficiency | kills / time_equipped | Rate |
| B1.15 | [Weapon] Damage/Shot | damage / shots | Efficiency |
| B1.16 | [Weapon] Avg Kill Distance | mean distance | Histogram |
| B1.17 | [Weapon] Max Kill Distance | longest kill | Number |
| B1.18 | [Weapon] Close Range Kills | < 5m | Counter |
| B1.19 | [Weapon] Mid Range Kills | 5-25m | Counter |
| B1.20 | [Weapon] Long Range Kills | > 25m | Counter |
| B1.21 | [Weapon] Multikills | 2+ kills rapidly | Counter |
| B1.22 | [Weapon] Duels Won | 1v1 with same weapon | Counter |
| B1.23 | [Weapon] Duels Lost | lost 1v1 | Counter |
| B1.24 | [Weapon] Switch-To Kills | kill after switching | Counter |
| B1.25 | [Weapon] Mastery Tier | Bronze→Legend | Badge |

### B.2 Weapon List (25 weapons)
| ID | Weapon | Category |
|----|--------|----------|
| W01 | M1 Garand | Rifle |
| W02 | Kar98k | Rifle |
| W03 | Springfield | Sniper |
| W04 | Kar98k Sniper | Sniper |
| W05 | Thompson | SMG |
| W06 | MP40 | SMG |
| W07 | BAR | LMG |
| W08 | STG44 | Assault |
| W09 | Colt .45 | Pistol |
| W10 | Walther P38 | Pistol |
| W11 | Shotgun | Shotgun |
| W12 | Frag Grenade | Explosive |
| W13 | Stielhandgranate | Explosive |
| W14 | Smoke Grenade | Tactical |
| W15 | Bazooka | Rocket |
| W16 | Panzerschreck | Rocket |
| W17 | MG42 | Mounted |
| W18 | .30 Cal | Mounted |
| W19 | Knife | Melee |
| W20 | Rifle Butt | Melee |
| W21 | Gewehr 43 | Rifle |
| W22 | M1 Carbine | Rifle |
| W23 | Mosin Nagant | Rifle |
| W24 | PPSh-41 | SMG |
| W25 | Sten | SMG |

**Calculation**: 25 stats × 25 weapons = **625 weapon-specific stats**

---

## 🏃 CATEGORY C: Movement & Positioning (55 stats)

### C.1 Distance Statistics (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| C1.01 | Total Distance | SUM(player_distance) | Globe path |
| C1.02 | Distance Walked | walk distance | Counter |
| C1.03 | Distance Sprinted | sprint distance | Counter |
| C1.04 | Distance Swam | swim distance | Counter |
| C1.05 | Distance Driven | vehicle distance | Counter |
| C1.06 | Distance Per Round | distance / rounds | Average |
| C1.07 | Distance Per Life | distance / lives | Average |
| C1.08 | Average Velocity | distance / time_moving | Speedometer |
| C1.09 | Max Velocity | peak speed | Number |
| C1.10 | Sprint Ratio | sprint / total | Percentage |
| C1.11 | Stationary Time | time not moving | Timer |
| C1.12 | Stationary Ratio | still / total | Percentage |
| C1.13 | Distance Before Death | avg travel before dying | Number |
| C1.14 | Distance to First Kill | avg travel to first kill | Number |
| C1.15 | Retreat Distance | distance moving backward | Counter |

### C.2 Stance Statistics (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| C2.01 | Time Standing | duration | Timer |
| C2.02 | Time Crouching | duration | Timer |
| C2.03 | Time Prone | duration | Timer |
| C2.04 | Time Leaning Left | duration | Timer |
| C2.05 | Time Leaning Right | duration | Timer |
| C2.06 | Standing Ratio | stand / total | Pie slice |
| C2.07 | Crouch Ratio | crouch / total | Pie slice |
| C2.08 | Prone Ratio | prone / total | Pie slice |
| C2.09 | Stance Transitions | count of changes | Waveform |
| C2.10 | Avg Stance Duration | mean time per stance | Timer |
| C2.11 | Combat Stance | stance during kills | Distribution |
| C2.12 | Death Stance | stance when dying | Distribution |
| C2.13 | Crouch Spam | rapid crouch/stand | Counter |
| C2.14 | Drop Shot Kills | kill while dropping prone | Counter |
| C2.15 | Jump Shot Kills | kill while airborne | Counter |

### C.3 Jump & Air Statistics (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| C3.01 | Total Jumps | COUNT(player_jump) | Rabbit icon |
| C3.02 | Jumps Per Round | jumps / rounds | Average |
| C3.03 | Total Air Time | SUM(air_duration) | Timer |
| C3.04 | Avg Jump Duration | air_time / jumps | Timer |
| C3.05 | Max Air Time | longest single jump | Timer |
| C3.06 | Air Kills | kills while airborne | Counter |
| C3.07 | Air Deaths | deaths while airborne | Counter |
| C3.08 | Air K/D | air_kills / air_deaths | Gauge |
| C3.09 | Bunny Hop Count | consecutive jumps | Counter |
| C3.10 | Fall Damage Taken | damage from falls | Counter |
| C3.11 | Fatal Falls | deaths from falling | Counter |
| C3.12 | Max Fall Height | highest survived fall | Number |
| C3.13 | Soft Landings | landed without damage | Counter |
| C3.14 | Hard Landings | landed with damage | Counter |
| C3.15 | Gap Jumps | jumps across gaps | Counter |

### C.4 Ladder & Special (10 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| C4.01 | Ladder Climbs | COUNT(ladder_mount) | Vertical ruler |
| C4.02 | Ladder Time | time on ladders | Timer |
| C4.03 | Ladder Kills | kills from ladder | Counter |
| C4.04 | Ladder Deaths | deaths on ladder | Counter |
| C4.05 | Ladder Escapes | dismount under fire | Counter |
| C4.06 | Swim Time | time in water | Timer |
| C4.07 | Swim Kills | kills while swimming | Counter |
| C4.08 | Drowning Deaths | drowned | Counter |
| C4.09 | Crush Deaths | crushed by objects | Counter |
| C4.10 | Environmental Deaths | all non-combat deaths | Counter |

---

## 🎯 CATEGORY D: Clutch & Situational (60 stats)

### D.1 Clutch Situations (20 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| D1.01 | 1v1 Clutch Wins | won when 1v1 | Counter |
| D1.02 | 1v1 Clutch Losses | lost when 1v1 | Counter |
| D1.03 | 1v1 Win Rate | wins / total | Percentage |
| D1.04 | 1v2 Clutch Wins | won when 1v2 | Counter |
| D1.05 | 1v2 Clutch Losses | lost when 1v2 | Counter |
| D1.06 | 1v2 Win Rate | wins / total | Percentage |
| D1.07 | 1v3 Clutch Wins | won when 1v3 | Counter |
| D1.08 | 1v3 Win Rate | wins / total | Percentage |
| D1.09 | 1v4 Clutch Wins | won when 1v4 | Counter |
| D1.10 | 1v5 Clutch Wins | won when 1v5 | Counter |
| D1.11 | Total Clutch Wins | all 1vX wins | Counter |
| D1.12 | Total Clutch Rate | clutch_wins / clutch_situations | Percentage |
| D1.13 | Best Clutch | highest X in 1vX win | Badge |
| D1.14 | Clutch Streak | consecutive clutch wins | Counter |
| D1.15 | Choke Rate | lost with advantage | Shame % |
| D1.16 | Anti-Eco Wins | won vs lesser equipped | Counter |
| D1.17 | Eco Wins | won when lesser equipped | Counter |
| D1.18 | Overtime Wins | final round wins | Counter |
| D1.19 | Comeback Rounds | won after 5+ deficit | Counter |
| D1.20 | Clutch MVP | mvp in clutch round | Counter |

### D.2 Trade & Timing (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| D2.01 | Trade Kills | kill teammate's killer < 3s | Counter |
| D2.02 | Traded Deaths | teammate avenged you < 3s | Counter |
| D2.03 | Untraded Deaths | died, not avenged | Counter |
| D2.04 | Trade Efficiency | trade_kills / opportunities | Percentage |
| D2.05 | Entry Frags | first kill of round | Counter |
| D2.06 | Entry Deaths | first death of round | Counter |
| D2.07 | Entry Success Rate | entry_kills / entries | Percentage |
| D2.08 | Exit Frags | kills in losing round | Counter |
| D2.09 | Time to First Kill | avg time to first kill | Timer |
| D2.10 | Time to First Death | avg time to first death | Timer |
| D2.11 | Reaction Kills | kill within 1s of seeing | Counter |
| D2.12 | Prefires | kill on anticipated position | Counter |
| D2.13 | Wallbang Kills | kills through geometry | Counter |
| D2.14 | Flash Assists | blinded enemy killed | Counter |
| D2.15 | Smoke Kills | kills through smoke | Counter |

### D.3 Low HP & Survival (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| D3.01 | Low HP Kills | kills while < 15 HP | Blood vignette |
| D3.02 | Critical Survival | survived < 5 HP | Counter |
| D3.03 | One-Shot Survival | survived 95+ damage | Counter |
| D3.04 | Damage Tanked | total damage survived | Counter |
| D3.05 | Health Recovered | healing received | Counter |
| D3.06 | Near Death Escapes | lived after < 10 HP | Counter |
| D3.07 | Last Stand Kills | kill after taking 90+ dmg | Counter |
| D3.08 | Deathless Rounds | rounds without dying | Counter |
| D3.09 | Max Damage Survived | single biggest hit survived | Number |
| D3.10 | Healing Efficiency | recovered / received | Percentage |
| D3.11 | Time Wounded | time below 50 HP | Timer |
| D3.12 | Combat Medic | heals given to teammates | Counter |
| D3.13 | Self Heals | personal healing | Counter |
| D3.14 | Suicide Count | self-inflicted deaths | Shame counter |
| D3.15 | Team Kills | kills on teammates | Shame counter |

### D.4 Nemesis & Rivalry (10 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| D4.01 | Nemesis (Enemy) | player who kills you most | Wanted poster |
| D4.02 | Nemesis Deaths | deaths to nemesis | Counter |
| D4.03 | Victim (Enemy) | player you kill most | Dominated list |
| D4.04 | Victim Kills | kills on victim | Counter |
| D4.05 | Dominations | 3+ consecutive kills | Badge |
| D4.06 | Dominated Count | times you dominated | Counter |
| D4.07 | Got Dominated | times dominated by enemy | Shame counter |
| D4.08 | Revenge Kills | killed your nemesis | Counter |
| D4.09 | Rivalry Score | most competitive matchup | Score |
| D4.10 | Head-to-Head Record | W/L vs specific player | Table |

---

## 🎖️ CATEGORY E: Objective Play (45 stats)

### E.1 Bomb/Demolition (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| E1.01 | Bombs Planted | successful plants | Bomb icon |
| E1.02 | Bombs Defused | successful defuses | Wire cutters |
| E1.03 | Plant Attempts | total attempts | Counter |
| E1.04 | Defuse Attempts | total attempts | Counter |
| E1.05 | Plant Success Rate | planted / attempts | Percentage |
| E1.06 | Defuse Success Rate | defused / attempts | Percentage |
| E1.07 | Ninja Defuses | defused uncontested | Counter |
| E1.08 | Last Second Defuses | defused < 3s | Counter |
| E1.09 | Post-Plant Kills | kills after planting | Counter |
| E1.10 | Plant Protection | saved planter | Counter |
| E1.11 | Defuser Kills | killed defuser | Counter |
| E1.12 | Planter Kills | killed planter | Counter |
| E1.13 | Bomb Time | time carrying bomb | Timer |
| E1.14 | Site Control Time | time holding site | Timer |
| E1.15 | Explosion Wins | rounds won by explosion | Counter |

### E.2 Capture/Control (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| E2.01 | Flags Captured | successful captures | Flag icon |
| E2.02 | Flags Defended | prevented captures | Shield |
| E2.03 | Flag Returns | returned dropped flags | Counter |
| E2.04 | Carrier Kills | killed flag carrier | Crosshair |
| E2.05 | Carrier Time | time carrying flag | Timer |
| E2.06 | Capture Assists | teammate captured with help | Counter |
| E2.07 | Objective Time | time on objectives | Timer |
| E2.08 | Contested Time | time in contested zone | Timer |
| E2.09 | Point Captures | control points captured | Counter |
| E2.10 | Point Neutralizations | control points neutralized | Counter |
| E2.11 | Hold Time | time holding points | Timer |
| E2.12 | Push Distance | territory pushed | Distance |
| E2.13 | Fallback Distance | territory lost | Distance |
| E2.14 | Zone Kills | kills inside objectives | Counter |
| E2.15 | Zone Deaths | deaths inside objectives | Counter |

### E.3 Team Contribution (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| E3.01 | Rounds Won | team wins | Counter |
| E3.02 | Rounds Lost | team losses | Counter |
| E3.03 | Round Win Rate | wins / rounds | Percentage |
| E3.04 | Matches Won | match wins | Counter |
| E3.05 | Matches Lost | match losses | Counter |
| E3.06 | Match Win Rate | wins / matches | Percentage |
| E3.07 | MVP Rounds | round MVP awards | Counter |
| E3.08 | Match MVPs | match MVP awards | Counter |
| E3.09 | Team Damage Share | your damage / team damage | Donut chart |
| E3.10 | Team Kill Share | your kills / team kills | Donut chart |
| E3.11 | Flawless Contributions | part of flawless rounds | Counter |
| E3.12 | Carry Rounds | > 50% team kills | Counter |
| E3.13 | Support Rounds | assist without killing | Counter |
| E3.14 | Communication Score | callouts made | Counter |
| E3.15 | Team Rating Impact | win rate with you | Percentage |

---

## 🗺️ CATEGORY F: Map-Specific (100+ stats per map)

### F.1 Per-Map Base Stats (20 stats × 40+ maps = 800+)
For EACH map:
| ID | Stat | Visualization |
|----|------|---------------|
| F1.01 | [Map] Matches | Counter |
| F1.02 | [Map] Win Rate | Percentage |
| F1.03 | [Map] Kills | Counter |
| F1.04 | [Map] Deaths | Counter |
| F1.05 | [Map] K/D | Gauge |
| F1.06 | [Map] ADR | Bar |
| F1.07 | [Map] Accuracy | Percentage |
| F1.08 | [Map] Headshot % | Percentage |
| F1.09 | [Map] Playtime | Timer |
| F1.10 | [Map] Favorite Weapon | Icon |
| F1.11 | [Map] Favorite Position | Heatmap |
| F1.12 | [Map] Death Positions | Heatmap |
| F1.13 | [Map] Kill Positions | Heatmap |
| F1.14 | [Map] Entry Rate | Percentage |
| F1.15 | [Map] Clutch Rate | Percentage |
| F1.16 | [Map] Objective Plays | Counter |
| F1.17 | [Map] First Blood Rate | Percentage |
| F1.18 | [Map] Spawn Survival | Percentage |
| F1.19 | [Map] Average Life Time | Timer |
| F1.20 | [Map] Mastery Tier | Badge |

### F.2 Spatial Analysis (per map)
| ID | Stat | Visualization |
|----|------|---------------|
| F2.01 | Kill Heatmap | 2D density map |
| F2.02 | Death Heatmap | 2D density map |
| F2.03 | Traffic Heatmap | Movement density |
| F2.04 | Sightline Success | Kill corridors |
| F2.05 | Danger Zones | High death areas |
| F2.06 | Safe Zones | Low death areas |
| F2.07 | Chokepoint Control | Kill % at chokes |
| F2.08 | Lane Preference | Left/Mid/Right % |
| F2.09 | Verticality Advantage | High ground kills |
| F2.10 | Spawn Kill Rate | Kills near spawn |

---

## ⏱️ CATEGORY G: Session & Time (40 stats)

### G.1 Session Statistics (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| G1.01 | Total Playtime | SUM(session_duration) | Timer |
| G1.02 | Total Matches | COUNT(matches) | Counter |
| G1.03 | Total Rounds | COUNT(rounds) | Counter |
| G1.04 | Avg Session Length | playtime / sessions | Timer |
| G1.05 | Avg Match Length | playtime / matches | Timer |
| G1.06 | Longest Session | MAX(session_duration) | Timer |
| G1.07 | Sessions Today | today's count | Counter |
| G1.08 | Sessions This Week | week's count | Counter |
| G1.09 | Sessions This Month | month's count | Counter |
| G1.10 | Days Active | unique days played | Counter |
| G1.11 | Longest Streak | consecutive days | Counter |
| G1.12 | Current Streak | current consecutive | Counter |
| G1.13 | Idle Time | afk duration | Timer |
| G1.14 | Active Ratio | active / total | Percentage |
| G1.15 | Peak Hours | most active hours | Heatmap |

### G.2 Time Period Comparisons (15 stats)
| ID | Stat | Time Periods |
|----|------|--------------|
| G2.01 | Daily Performance | Today vs avg |
| G2.02 | Weekly Performance | This week vs avg |
| G2.03 | Monthly Performance | This month vs avg |
| G2.04 | Seasonal Performance | This season vs avg |
| G2.05 | All-Time Best Day | Peak performance |
| G2.06 | All-Time Best Week | Peak week |
| G2.07 | Improvement Trend | Slope over time |
| G2.08 | Consistency Score | Variance in performance |
| G2.09 | Peak Hours KDR | Best time of day |
| G2.10 | Weekend vs Weekday | Performance comparison |
| G2.11 | Morning Performance | 6am-12pm |
| G2.12 | Afternoon Performance | 12pm-6pm |
| G2.13 | Evening Performance | 6pm-12am |
| G2.14 | Night Performance | 12am-6am |
| G2.15 | Time Zone Activity | Global distribution |

### G.3 Historical Records (10 stats)
| ID | Stat | Description |
|----|------|-------------|
| G3.01 | Best KDR Match | Highest single match KDR |
| G3.02 | Most Kills Match | Highest kill count |
| G3.03 | Longest Killstreak | All-time best |
| G3.04 | Best Accuracy | Single match best |
| G3.05 | Fastest Ace | Quickest 5 kills |
| G3.06 | Most Headshots | Single match |
| G3.07 | Longest Match | Duration record |
| G3.08 | Most Damage | Single match |
| G3.09 | First Blood Streak | Consecutive first bloods |
| G3.10 | Flawless Streak | Consecutive deathless rounds |

---

## 🏆 CATEGORY H: Competitive & Rankings (50 stats)

### H.1 Elo & Skill Rating (15 stats)
| ID | Stat | Formula | Visualization |
|----|------|---------|---------------|
| H1.01 | Current Elo | skill_rating | Large number |
| H1.02 | Peak Elo | MAX(elo) | Badge |
| H1.03 | Elo Change Today | delta | +/- indicator |
| H1.04 | Elo Change Week | delta | Sparkline |
| H1.05 | Elo Change Month | delta | Trend line |
| H1.06 | Rank | ordinal position | Medal |
| H1.07 | Rank Percentile | better than X% | Percentage |
| H1.08 | Division | Bronze/Silver/Gold/etc | Badge |
| H1.09 | Promotion Points | points to next tier | Progress bar |
| H1.10 | Win Streak | consecutive wins | Counter |
| H1.11 | Loss Streak | consecutive losses | Counter |
| H1.12 | Elo Volatility | variance in rating | Graph |
| H1.13 | Confidence Interval | rating certainty | Range |
| H1.14 | Rating vs Average | compared to server | Gauge |
| H1.15 | Predicted Win Rate | vs average player | Percentage |

### H.2 Leaderboard Positions (20 stats)
For each major stat, track global rank:
| ID | Stat | Ranking |
|----|------|---------|
| H2.01 | Kills Rank | Global position |
| H2.02 | K/D Rank | Global position |
| H2.03 | Accuracy Rank | Global position |
| H2.04 | Headshot Rank | Global position |
| H2.05 | Win Rate Rank | Global position |
| H2.06 | Playtime Rank | Global position |
| H2.07 | Clutch Rank | Global position |
| H2.08 | ADR Rank | Global position |
| H2.09 | Entry Kill Rank | Global position |
| H2.10 | Trade Kill Rank | Global position |
| H2.11-20 | Weapon Ranks | Per-weapon positions |

### H.3 Tournament Statistics (15 stats)
| ID | Stat | Description |
|----|------|-------------|
| H3.01 | Tournaments Entered | Total count |
| H3.02 | Tournaments Won | First place |
| H3.03 | Tournament Podiums | Top 3 finishes |
| H3.04 | Tournament Win Rate | wins / entries |
| H3.05 | Prize Money | Total earnings |
| H3.06 | Best Placement | Highest finish |
| H3.07 | Tournament K/D | Performance in tourneys |
| H3.08 | Playoff Wins | Bracket match wins |
| H3.09 | Finals Appearances | Grand final entries |
| H3.10 | Grand Final Wins | Championship wins |
| H3.11 | Upper Bracket Wins | Winner's side |
| H3.12 | Lower Bracket Runs | Loser's bracket success |
| H3.13 | Longest Run | Deepest tournament run |
| H3.14 | Tournament MVP | MVP awards |
| H3.15 | Dynasty Score | Consecutive wins |

---

## 🔢 STAT COUNT SUMMARY

| Category | Base Stats | Multiplier | Total |
|----------|------------|------------|-------|
| A: Combat Core | 65 | ×5 time periods | 325 |
| B: Weapon Mastery | 25 | ×25 weapons ×5 time | 3,125 |
| C: Movement | 55 | ×5 time periods | 275 |
| D: Clutch | 60 | ×5 time periods | 300 |
| E: Objective | 45 | ×5 time periods | 225 |
| F: Map-Specific | 30 | ×40 maps ×5 time | 6,000 |
| G: Session/Time | 40 | ×1 (meta stats) | 40 |
| H: Competitive | 50 | ×5 time periods | 250 |
| **Subtotal** | | | **10,540** |
| Cross-dimensional | - | weapon×map×player | **50,000+** |
| Derived/Computed | - | ratios, trends | **40,000+** |
| **GRAND TOTAL** | | | **100,000+** |

---

## 📊 Visualization Reference

| Stat Type | Recommended Chart |
|-----------|-------------------|
| Single value | Odometer, gauge, counter |
| Comparison | Bar chart, split bar |
| Trend | Sparkline, line chart |
| Distribution | Histogram, pie chart |
| Relationship | Scatter plot, spider chart |
| Spatial | Heatmap, map overlay |
| Flow | Sankey diagram |
| Hierarchy | Treemap, sunburst |
| Time series | Area chart, candlestick |
| Rankings | Sorted table, leaderboard |

---

*This document defines the complete statistics taxonomy for the OpenMOHAA Stats System.*
*Last Updated: 2026-01-18*

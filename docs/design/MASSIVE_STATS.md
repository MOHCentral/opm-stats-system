,# OpenMOHAA MASSIVE STATS SYSTEM
## 1000+ Statistics, Achievements, and Metrics

> **Design Philosophy:** Every single number should be clickable and explode into deeper breakdowns. This document is the COMPLETE taxonomy of everything trackable in OpenMOHAA.

---

# PART 1: ENGINE EVENTS (Data Sources)

## Complete Event List (31 Total)

### Combat Events (10)
| Event | Parameters | Description |
|-------|------------|-------------|
| `player_kill` | attacker, victim, weapon, hitloc, distance, pos_x/y/z | Player killed another player |
| `player_death` | victim, inflictor, weapon, killer, pos_x/y/z | Player died |
| `player_damage` | attacker, victim, damage, weapon, hitloc, pos_x/y/z | Damage dealt |
| `player_headshot` | attacker, victim, weapon, distance | Headshot kill |
| `weapon_fire` | player, weapon, ammo_remaining, pos_x/y/z, aim_pitch/yaw | Weapon fired |
| `weapon_hit` | attacker, victim, weapon, damage, hitloc | Shot hit target |
| `weapon_reload` | player, weapon, ammo_before, ammo_after | Weapon reloaded |
| `weapon_change` | player, old_weapon, new_weapon | Weapon switched |
| `grenade_throw` | player, grenade_type, pos_x/y/z, velocity | Grenade thrown |
| `grenade_explode` | player, grenade_type, pos_x/y/z, victims[] | Grenade exploded |

### Movement Events (5)
| Event | Parameters | Description |
|-------|------------|-------------|
| `player_jump` | player, pos_x/y/z, velocity | Player jumped |
| `player_land` | player, pos_x/y/z, fall_damage | Player landed |
| `player_crouch` | player, pos_x/y/z, duration | Stance change to crouch |
| `player_prone` | player, pos_x/y/z, duration | Stance change to prone |
| `player_distance` | player, walked, sprinted, driven | Distance traveled tick |

### Interaction Events (5)
| Event | Parameters | Description |
|-------|------------|-------------|
| `ladder_mount` | player, pos_x/y/z | Mounted ladder |
| `ladder_dismount` | player, pos_x/y/z, height_climbed | Dismounted ladder |
| `item_pickup` | player, item_type, item_name | Picked up item |
| `item_drop` | player, item_type, item_name | Dropped item |
| `player_use` | player, target_entity, use_type | Used something |

### Session Events (5)
| Event | Parameters | Description |
|-------|------------|-------------|
| `client_connect` | player, guid, ip, name | Client connected |
| `client_disconnect` | player, reason, playtime | Client disconnected |
| `client_begin` | player, team, class | Client spawned |
| `team_join` | player, old_team, new_team | Team changed |
| `player_say` | player, message, channel | Chat message |

### Legacy Player Events (6)
| Event | Parameters | Description |
|-------|------------|-------------|
| `player_connected` | player | Initial connection |
| `player_disconnecting` | player | About to disconnect |
| `player_spawned` | player, spawn_point | Spawned in game |
| `player_damaged` | player, damage, attacker | Took damage |
| `player_killed` | player, killer | Died |
| `player_textMessage` | player, message | Chat sent |

---

# PART 2: PLAYER STATISTICS (300+)

## A. Combat Core (50 Stats)

### Kill Statistics (20)
| # | Statistic | Description | Drill-Down Dimensions |
|---|-----------|-------------|----------------------|
| 1 | **Total Kills** | Lifetime kill count | weapon, map, server, match, victim, time |
| 2 | **Total Deaths** | Lifetime death count | weapon, map, server, match, killer, time |
| 3 | **K/D Ratio** | Kills / Deaths | per weapon, per map, per opponent |
| 4 | **Headshots** | Head hit kills | weapon, distance, stance, map |
| 5 | **Headshot %** | Headshots / Kills | weapon, map, trend over time |
| 6 | **Torso Kills** | Center mass kills | weapon, distance |
| 7 | **Limb Kills** | Arms/legs kills | weapon, by hitloc |
| 8 | **Pelvis Kills** | Hitbox 6 kills | weapon (the "Nutcracker" stat) |
| 9 | **Gibs** | Overkill damage kills | weapon, explosive only |
| 10 | **Assists** | Damage without kill | map, match |
| 11 | **First Bloods** | First kill of round | map, match, time into round |
| 12 | **Final Blows** | Last kill of round | map, match |
| 13 | **Solo Kills** | Kills with no team damage | situational |
| 14 | **Cleanup Kills** | Kills on <15 HP targets | weapon |
| 15 | **Trade Kills** | Kill within 3s of teammate death | map |
| 16 | **Trade Deaths** | Death within 3s of your kill | map |
| 17 | **Revenge Kills** | Kill your previous killer | time between |
| 18 | **Double Kills** | 2 kills within 3s | weapon, map |
| 19 | **Triple Kills** | 3 kills within 3s | weapon, map |
| 20 | **Quad+ Kills** | 4+ kills within 3s | weapon, map |

### Kill Distance (10)
| # | Statistic | Description | Drill-Down |
|---|-----------|-------------|-----------|
| 21 | **Long Range Kills** | Kills >100m | weapon, map |
| 22 | **Medium Range Kills** | Kills 25-100m | weapon, map |
| 23 | **Close Range Kills** | Kills <25m | weapon, map |
| 24 | **Point Blank Kills** | Kills <5m | weapon |
| 25 | **Average Kill Distance** | Mean distance | per weapon |
| 26 | **Longest Kill** | Max distance ever | single record |
| 27 | **Shortest Kill** | Min distance kill | single record |
| 28 | **Sniper Range Efficiency** | Avg distance with snipers | Kar98, Springfield |
| 29 | **SMG Range Efficiency** | Avg distance with SMGs | Thompson, MP40 |
| 30 | **Pistol Range** | Avg distance with pistols | Colt, Luger |

### Kill Circumstances (20)
| # | Statistic | Description | Drill-Down |
|---|-----------|-------------|-----------|
| 31 | **Kills While Prone** | Kills in prone stance | weapon, map |
| 32 | **Kills While Crouching** | Kills in crouch stance | weapon, map |
| 33 | **Kills While Standing** | Kills while standing | weapon, map |
| 34 | **Kills While Moving** | Velocity >0 kills | weapon |
| 35 | **Kills While Stationary** | Not moving kills | weapon (camper indicator) |
| 36 | **Kills While Jumping** | Mid-air kills | weapon |
| 37 | **Kills While Leaning Left** | Q-lean kills | map |
| 38 | **Kills While Leaning Right** | E-lean kills | map |
| 39 | **Backstabs** | Victim facing away (>90°) | weapon, map |
| 40 | **Frontal Kills** | Victim facing you (<45°) | weapon |
| 41 | **Turn-On Kills** | YOU were facing away, still killed | weapon (reaction skill) |
| 42 | **High Ground Kills** | Z-axis advantage | map |
| 43 | **Low Ground Kills** | Z-axis disadvantage | map |
| 44 | **Corner Peek Kills** | Within 10 units of wall | map |
| 45 | **Open Field Kills** | >200 units from cover | map |
| 46 | **Spawn Kills** | <5s after victim spawn | map (spawn trap) |
| 47 | **Post-Mortem Kills** | Kill after your death | grenade/rocket |
| 48 | **Low HP Kills** | Your HP <15 when killing | weapon |
| 49 | **Collateral Kills** | 2+ kills with 1 shot | sniper only |
| 50 | **Wallbang Kills** | Through geometry kills | weapon, map |

## B. Weapon Statistics (100 Stats)

### Per-Weapon Metrics (For EACH Weapon - 20 Weapons)
| # | Statistic | Per Weapon |
|---|-----------|-----------|
| 51-70 | **[Weapon] Kills** | kar98, thompson, mp40, stg44, bar, m1garand, springfield, grenade, bazooka, panzerschreck, colt, luger, m1911, webley, mp44, fg42, gewehr43, mg42, shotgun, melee |
| 71-90 | **[Weapon] Deaths** | Deaths TO this weapon |
| 91-110 | **[Weapon] Headshots** | Headshots with each |
| 111-130 | **[Weapon] Accuracy** | Hit % per weapon |
| 131-150 | **[Weapon] Shots Fired** | Total ammo expended |
| 151-170 | **[Weapon] Shots Hit** | Total hits registered |
| 171-190 | **[Weapon] Damage Dealt** | Total damage output |
| 191-210 | **[Weapon] Time Equipped** | Time holding weapon |
| 211-230 | **[Weapon] Reloads** | Reload count |
| 231-250 | **[Weapon] Reload Deaths** | Deaths while reloading |

### Weapon Aggregates (30)
| # | Statistic | Description |
|---|-----------|-------------|
| 251 | **Rifles Kills** | Kar98 + Springfield + M1 |
| 252 | **SMG Kills** | Thompson + MP40 + STG44 |
| 253 | **Pistol Kills** | All pistol types |
| 254 | **Explosive Kills** | Grenade + Rocket |
| 255 | **Melee Kills** | Bash/knife only |
| 256 | **Sniper Kills** | Scoped rifles only |
| 257 | **MG Kills** | BAR + MG42 turret |
| 258 | **Shotgun Kills** | Shotgun only |
| 259 | **Best Weapon** | Highest kill count |
| 260 | **Worst Weapon** | Lowest efficiency |
| 261 | **Weapon Diversity** | Weapons used count |
| 262 | **One-Tap Kills** | 1 shot = 1 kill |
| 263 | **Spray Kills** | >10 shots = 1 kill |
| 264 | **No-Scope Kills** | Sniper not ADS |
| 265 | **Quick-Scope Kills** | ADS <0.5s before kill |
| 266 | **Empty Mag Deaths** | Died with 0 ammo |
| 267 | **Scavenged Weapon Kills** | With picked up weapons |
| 268 | **Secondary Finishes** | Kill with pistol after empty |
| 269 | **Ammo Efficiency** | Kills per 100 rounds |
| 270 | **Grenade Direct Hits** | Grenade hits before explode |
| 271 | **Grenade Cook Time** | Avg time held |
| 272 | **Grenade Distance** | Avg throw distance |
| 273 | **Rocket Direct Hits** | Non-splash rocket kills |
| 274 | **Splash Damage Total** | From explosives |
| 275 | **Splash Victims Per Nade** | Avg hit per explosion |
| 276 | **Missed Grenades** | 0 damage nades |
| 277 | **Self-Damage** | Damage to self |
| 278 | **Team Damage** | Damage to allies |
| 279 | **Team Kills** | Allied kills (negative) |
| 280 | **Weapon Switches** | Total swaps |

## C. Movement Statistics (50 Stats)

### Distance (20)
| # | Statistic | Description |
|---|-----------|-------------|
| 281 | **Total Distance** | Km traveled lifetime |
| 282 | **Walk Distance** | Walking only |
| 283 | **Sprint Distance** | Sprinting only |
| 284 | **Crouch Distance** | While crouching |
| 285 | **Prone Distance** | While prone (shark) |
| 286 | **Jump Distance** | Horizontal in air |
| 287 | **Fall Distance** | Vertical drops |
| 288 | **Ladder Distance** | Climbed on ladders |
| 289 | **Vehicle Distance** | In vehicles |
| 290 | **Average Velocity** | Movement speed |
| 291 | **Max Velocity** | Highest speed reached |
| 292 | **Distance Per Life** | Avg before death |
| 293 | **Distance Per Kill** | Avg between kills |
| 294 | **Distance Per Match** | Avg per game |
| 295 | **Distance Per Map** | By map breakdown |
| 296 | **Spawn Exit Speed** | Time to leave spawn |
| 297 | **Retreat Distance** | After taking damage |
| 298 | **Chase Distance** | Following enemies |
| 299 | **Strafe Distance** | Lateral movement |
| 300 | **Backwards Distance** | Retreat movement |

### Stance & Position (20)
| # | Statistic | Description |
|---|-----------|-------------|
| 301 | **Time Standing** | Duration in stance |
| 302 | **Time Crouching** | Duration crouched |
| 303 | **Time Prone** | Duration prone |
| 304 | **Stance Changes** | Transitions count |
| 305 | **Jump Count** | Total jumps |
| 306 | **Double Jumps** | Consecutive jumps |
| 307 | **Bunny Hop Chain** | Max consecutive |
| 308 | **Air Time** | Total time airborne |
| 309 | **Fall Deaths** | From fall damage |
| 310 | **Fall Damage Taken** | Total fall damage |
| 311 | **Ladder Mounts** | Ladder uses |
| 312 | **Ladder Time** | On ladders |
| 313 | **Lean Left Count** | Q-lean uses |
| 314 | **Lean Right Count** | E-lean uses |
| 315 | **Lean Time** | Total leaning |
| 316 | **Stationary Time** | Not moving |
| 317 | **Camping Time** | Same spot >10s |
| 318 | **Camping Deaths** | While stationary |
| 319 | **High Ground Time** | Above map average Z |
| 320 | **Low Ground Time** | Below map average Z |

### Position & Zones (10)
| # | Statistic | Description |
|---|-----------|-------------|
| 321 | **Time Near Teammates** | Within 500 units |
| 322 | **Time Isolated** | >1000 units from team |
| 323 | **Time in Choke Points** | In doorways/halls |
| 324 | **Time in Open** | >200 from cover |
| 325 | **Time on Objective** | In cap zone |
| 326 | **Time Flanking** | Side of map |
| 327 | **Time Defending** | Own side of map |
| 328 | **Time Attacking** | Enemy side |
| 329 | **Position Heatmap** | X,Y density |
| 330 | **Death Heatmap** | Where you die |

## D. Accuracy & Aiming (40 Stats)

### Hit Registration (20)
| # | Statistic | Description |
|---|-----------|-------------|
| 331 | **Overall Accuracy** | Hits / Shots |
| 332 | **Headshot Accuracy** | Head hits / Shots |
| 333 | **Torso Accuracy** | Torso hits / Shots |
| 334 | **Limb Accuracy** | Limb hits / Shots |
| 335 | **First Shot Accuracy** | First bullet hit % |
| 336 | **Follow-Up Accuracy** | Subsequent shots |
| 337 | **Spray Accuracy** | >5 shots burst |
| 338 | **ADS Accuracy** | While aiming |
| 339 | **Hip-Fire Accuracy** | Not aiming |
| 340 | **Moving Accuracy** | While moving |
| 341 | **Stationary Accuracy** | Not moving |
| 342 | **Crouching Accuracy** | In crouch |
| 343 | **Prone Accuracy** | Lying down |
| 344 | **Standing Accuracy** | Full height |
| 345 | **Close Range Accuracy** | <25m |
| 346 | **Long Range Accuracy** | >100m |
| 347 | **Tracking Accuracy** | Moving targets |
| 348 | **Flick Accuracy** | Fast aiming |
| 349 | **Head:Body Ratio** | Head vs body hits |
| 350 | **Miss Streaks** | Consecutive misses |

### Damage Output (20)
| # | Statistic | Description |
|---|-----------|-------------|
| 351 | **Total Damage Dealt** | Lifetime damage |
| 352 | **Damage Per Shot** | Avg per hit |
| 353 | **Damage Per Kill** | Overkill measurement |
| 354 | **Damage Per Life** | Before death |
| 355 | **Damage Per Round** | ADR |
| 356 | **Damage Per Match** | Full game avg |
| 357 | **Lethal Damage** | Killing blows only |
| 358 | **Assist Damage** | Without kills |
| 359 | **Wasted Damage** | Overflow on kills |
| 360 | **Headshot Damage** | To head only |
| 361 | **Torso Damage** | To torso |
| 362 | **Limb Damage** | To limbs |
| 363 | **Explosive Damage** | From explosives |
| 364 | **Bullet Damage** | From bullets |
| 365 | **Melee Damage** | From melee |
| 366 | **Self-Damage** | To yourself |
| 367 | **Team Damage** | To teammates |
| 368 | **Damage Taken** | Total received |
| 369 | **Damage Efficiency** | Dealt / Taken ratio |
| 370 | **DPS Peak** | Max damage/second |

## E. Session & Time (30 Stats)

### Playtime (15)
| # | Statistic | Description |
|---|-----------|-------------|
| 371 | **Total Playtime** | Hours lifetime |
| 372 | **Playtime Per Server** | By server |
| 373 | **Playtime Per Map** | By map |
| 374 | **Playtime Per Gametype** | By mode |
| 375 | **Matches Played** | Total games |
| 376 | **Rounds Played** | Total rounds |
| 377 | **Average Match Duration** | Time per game |
| 378 | **Average Life Duration** | Time per spawn |
| 379 | **Longest Life** | Max survival |
| 380 | **Shortest Life** | Min survival |
| 381 | **Peak Playing Hours** | Day of week |
| 382 | **Peak Playing Time** | Hour of day |
| 383 | **Consecutive Days** | Login streak |
| 384 | **First Seen** | Account created |
| 385 | **Last Seen** | Last activity |

### Win/Loss (15)
| # | Statistic | Description |
|---|-----------|-------------|
| 386 | **Wins** | Match wins |
| 387 | **Losses** | Match losses |
| 388 | **Win Rate** | Win % |
| 389 | **Win Streak** | Max consecutive |
| 390 | **Loss Streak** | Max consecutive |
| 391 | **Current Streak** | Active streak |
| 392 | **Round Wins** | Individual rounds |
| 393 | **Round Losses** | Lost rounds |
| 394 | **Allies Win Rate** | As Allies |
| 395 | **Axis Win Rate** | As Axis |
| 396 | **Map Win Rate** | Per map |
| 397 | **Gametype Win Rate** | Per mode |
| 398 | **Quit Rate** | Left early % |
| 399 | **Comeback Wins** | Down 3+ rounds |
| 400 | **Blowout Wins** | Win by 5+ |

## F. Clutch & Situational (50 Stats)

### Clutch Situations (20)
| # | Statistic | Description |
|---|-----------|-------------|
| 401 | **1v1 Wins** | Won alone vs 1 |
| 402 | **1v1 Win Rate** | % of 1v1s |
| 403 | **1v2 Wins** | Won alone vs 2 |
| 404 | **1v2 Win Rate** | % of 1v2s |
| 405 | **1v3 Wins** | Won alone vs 3 |
| 406 | **1v3 Win Rate** | % of 1v3s |
| 407 | **1v4 Wins** | Won alone vs 4 |
| 408 | **1v4 Win Rate** | % of 1v4s |
| 409 | **1v5+ Wins** | Won alone vs 5+ |
| 410 | **Ace Rounds** | Killed entire team |
| 411 | **Flawless Rounds** | Won with 0 deaths |
| 412 | **Last Man Standing** | Times left alone |
| 413 | **Last Man Wins** | Won when last |
| 414 | **Last Man Kills** | Kills when last |
| 415 | **Clutch Per Match** | Avg clutches |
| 416 | **Opening Duel Wins** | First fight of round |
| 417 | **Opening Duel Loss** | Lost first fight |
| 418 | **Man Advantage Wins** | Won when up players |
| 419 | **Man Disadvantage Wins** | Won when down |
| 420 | **Overtime Performance** | Final round stats |

### Kill Streaks (15)
| # | Statistic | Description |
|---|-----------|-------------|
| 421 | **Best Killstreak** | Max consecutive |
| 422 | **5 Kill Streaks** | Count of 5s |
| 423 | **10 Kill Streaks** | Count of 10s |
| 424 | **15 Kill Streaks** | Count of 15s |
| 425 | **20+ Kill Streaks** | Count of 20+ |
| 426 | **Average Streak** | Mean before death |
| 427 | **Streak Enders** | Ended others' streaks |
| 428 | **Aces** | 5+ rapid kills |
| 429 | **4Ks** | 4 rapid kills |
| 430 | **3Ks** | 3 rapid kills |
| 431 | **2Ks** | Double kills |
| 432 | **Shutdown Kills** | Killed 5+ streaker |
| 433 | **Denied Aces** | Killed last survivor |
| 434 | **Perfect Games** | 0 death games |
| 435 | **Near-Perfect** | 1-2 death games |

### Special Kills (15)
| # | Statistic | Description |
|---|-----------|-------------|
| 436 | **Buzzer Beater Kills** | In last 5s of round |
| 437 | **Overtime Kills** | In OT rounds |
| 438 | **Defuse Stop Kills** | Kill during defuse |
| 439 | **Plant Stop Kills** | Kill during plant |
| 440 | **Carrier Kills** | Kill flag/bomb holder |
| 441 | **Defensive Kills** | In your zone |
| 442 | **Offensive Kills** | In enemy zone |
| 443 | **Gatekeeper Kills** | In choke points |
| 444 | **Exit Kills** | Kill escaping enemy |
| 445 | **Entry Kills** | First into site |
| 446 | **Support Kills** | Helper kills |
| 447 | **Solo Entry Kills** | First in alone |
| 448 | **Eco Kills** | With worse weapons |
| 449 | **Anti-Eco Kills** | Against worse weapons |
| 450 | **Pistol Round Kills** | Round 1 only |

## G. Objective Statistics (40 Stats)

### Bomb/Objective (20)
| # | Statistic | Description |
|---|-----------|-------------|
| 451 | **Bombs Planted** | Plant count |
| 452 | **Bombs Defused** | Defuse count |
| 453 | **Bomb Explosions** | Successful plants |
| 454 | **Plant Kills** | While planting |
| 455 | **Defuse Kills** | While defusing |
| 456 | **Plant Deaths** | Died planting |
| 457 | **Defuse Deaths** | Died defusing |
| 458 | **Ninja Defuses** | 3+ enemies alive |
| 459 | **Buzzer Defuses** | <1s left |
| 460 | **Fast Plants** | <30s into round |
| 461 | **Site A Plants** | Site preference |
| 462 | **Site B Plants** | Site preference |
| 463 | **Objective Time** | In cap zone |
| 464 | **Objective Kills** | Near objective |
| 465 | **Objective Deaths** | Near objective |
| 466 | **Offensive Rounds** | On attacking team |
| 467 | **Defensive Rounds** | On defending team |
| 468 | **Attack Win Rate** | As attackers |
| 469 | **Defense Win Rate** | As defenders |
| 470 | **Objective Touches** | Started cap/plant |

### Flag/Capture (20)
| # | Statistic | Description |
|---|-----------|-------------|
| 471 | **Flag Captures** | Successful caps |
| 472 | **Flag Returns** | Returned own flag |
| 473 | **Flag Pickups** | Grabbed flag |
| 474 | **Flag Carry Time** | Held flag |
| 475 | **Flag Carry Distance** | Moved with flag |
| 476 | **Flag Drops** | Lost flag |
| 477 | **Flag Deaths** | Died with flag |
| 478 | **Flag Saves** | Returned in danger |
| 479 | **Carrier Defense Kills** | Protected carrier |
| 480 | **Flag Intercepts** | Killed enemy carrier |
| 481 | **Relay Passes** | Passed to teammate |
| 482 | **Relay Caps** | Cap from relay |
| 483 | **Solo Caps** | No help cap |
| 484 | **Cap Time** | Avg time to cap |
| 485 | **Zone Captures** | Point captures |
| 486 | **Zone Contests** | Contested caps |
| 487 | **Zone Defense** | Stopped cap |
| 488 | **Ticket Saves** | Reinforcement spawns |
| 489 | **Vehicle Kills** | In vehicles |
| 490 | **Vehicle Destroys** | Destroyed vehicles |

## H. Map-Specific Stats (50 Per Map × 20 Maps = 1000+ Combinations)

### Per-Map Statistics (For Each Map)
For maps: Stalingrad, V2 Rocket, Flughafen, Southern France, Bridge, Hunt, Omaha Beach, etc.

| # | Statistic Type | Count |
|---|---------------|-------|
| 491-500 | **Kills on [Map]** | Per map |
| 501-510 | **Deaths on [Map]** | Per map |
| 511-520 | **K/D on [Map]** | Per map |
| 521-530 | **Wins on [Map]** | Per map |
| 531-540 | **Playtime on [Map]** | Per map |
| 541-550 | **Favorite Weapon on [Map]** | Per map |
| 551-560 | **Best Killstreak on [Map]** | Per map |
| 561-570 | **Headshots on [Map]** | Per map |
| 571-580 | **Accuracy on [Map]** | Per map |
| 581-590 | **ADR on [Map]** | Per map |

---

# PART 3: SERVER STATISTICS (100+)

## Server Aggregate Stats
| # | Statistic | Description |
|---|-----------|-------------|
| 591 | **Total Players Ever** | Unique players |
| 592 | **Total Matches** | Games hosted |
| 593 | **Total Rounds** | All rounds |
| 594 | **Total Kills** | All kills |
| 595 | **Total Deaths** | All deaths |
| 596 | **Total Headshots** | All headshots |
| 597 | **Total Playtime** | Cumulative hours |
| 598 | **Peak Concurrent** | Max players at once |
| 599 | **Average Players** | Mean concurrency |
| 600 | **Active Players** | Last 7 days |

## Server Leaderboards
| # | Statistic | Description |
|---|-----------|-------------|
| 601 | **Most Kills on Server** | Player leaderboard |
| 602 | **Most Deaths on Server** | Player leaderboard |
| 603 | **Best K/D on Server** | Player leaderboard |
| 604 | **Most Headshots on Server** | Player leaderboard |
| 605 | **Most Playtime on Server** | Player leaderboard |
| 606 | **Most Wins on Server** | Player leaderboard |
| 607 | **Best Win Rate on Server** | Player leaderboard |
| 608 | **Most Accurate on Server** | Player leaderboard |
| 609 | **Highest ADR on Server** | Player leaderboard |
| 610 | **Most First Bloods on Server** | Player leaderboard |

## Server Map Stats
| # | Statistic | Description |
|---|-----------|-------------|
| 611 | **Most Played Map** | By playtime |
| 612 | **Least Played Map** | By playtime |
| 613 | **Map Rotation** | Current maps |
| 614 | **Allies Win Rate Per Map** | Map balance |
| 615 | **Axis Win Rate Per Map** | Map balance |
| 616 | **Average Match Duration Per Map** | Time per map |
| 617-630 | **[Map] Total Kills** | Per map |

## Server Weapon Stats
| # | Statistic | Description |
|---|-----------|-------------|
| 631 | **Most Used Weapon** | Server-wide |
| 632 | **Most Kills by Weapon** | Weapon ranking |
| 633 | **Most Headshots by Weapon** | Weapon ranking |
| 634-650 | **[Weapon] Total Kills on Server** | Per weapon |

---

# PART 4: ACHIEVEMENTS (500+)

## Tier 1: First Steps (20 Achievements)
| # | Achievement | Description | Unlock |
|---|------------|-------------|--------|
| 1 | **Boot Camp** | Complete first match | 1 match |
| 2 | **First Blood** | Get first kill | 1 kill |
| 3 | **First Casualty** | Die for first time | 1 death |
| 4 | **Sharpshooter** | First headshot | 1 headshot |
| 5 | **Social** | First chat message | 1 message |
| 6 | **Teamwork** | Join a team | 1 team join |
| 7 | **Tourist** | Play 3 maps | 3 maps |
| 8 | **Regular** | Play 10 matches | 10 matches |
| 9 | **Door Opener** | Use first door | 1 door |
| 10 | **Grenadier Initiate** | First grenade kill | 1 nade kill |
| 11 | **Weapon Swap** | Switch weapons | 1 swap |
| 12 | **Reload Pro** | First reload | 1 reload |
| 13 | **Distance Runner** | Travel 1km | 1km |
| 14 | **Jump Man** | Jump 10 times | 10 jumps |
| 15 | **Snake** | Go prone | 1 prone |
| 16 | **Climber** | Use ladder | 1 ladder |
| 17 | **Scavenger** | Pick up item | 1 pickup |
| 18 | **Multi Kill** | Double kill | 2K |
| 19 | **Survivor** | Survive 5 min in one life | 5 min |
| 20 | **Returning** | Log in 2 different days | 2 days |

## Tier 2: Combat Novice (50 Achievements)
| # | Achievement | Description | Unlock |
|---|------------|-------------|--------|
| 21 | **Centurion** | 100 kills | 100 |
| 22 | **Bicentennial** | 200 kills | 200 |
| 23 | **500 Club** | 500 kills | 500 |
| 24 | **Headhunter** | 100 headshots | 100 |
| 25 | **Surgeon** | 50% headshot rate | 50% |
| 26 | **Marathon** | Travel 10km | 10km |
| 27 | **Ultra Marathon** | Travel 42km | 42km |
| 28 | **Trigger Happy** | Fire 10,000 shots | 10,000 |
| 29 | **Lead Magnet** | Take 10,000 damage | 10,000 |
| 30 | **Wins 10** | 10 match wins | 10 |
| 31 | **Wins 50** | 50 match wins | 50 |
| 32 | **Win Streak 3** | 3 wins in a row | 3 |
| 33 | **Win Streak 5** | 5 wins in a row | 5 |
| 34 | **World Traveler** | Play 10 maps | 10 maps |
| 35 | **All Maps** | Play all maps | All |
| 36 | **Dedicated** | 24 hours playtime | 24h |
| 37 | **No Life** | 100 hours playtime | 100h |
| 38 | **Veteran Status** | 500 hours playtime | 500h |
| 39 | **Weapon Collector** | Kill with 10 weapons | 10 |
| 40 | **Armory Master** | Kill with all weapons | All |
| 41 | **Door Master** | Open 100 doors | 100 |
| 42 | **Bunny** | Jump 1,000 times | 1,000 |
| 43 | **Kangaroo** | Jump 10,000 times | 10,000 |
| 44 | **Grass Snake** | 100 prone kills | 100 |
| 45 | **Avenger 10** | 10 trade kills | 10 |
| 46 | **Avenger 50** | 50 trade kills | 50 |
| 47 | **First Blood 10** | 10 first bloods | 10 |
| 48 | **First Blood 50** | 50 first bloods | 50 |
| 49 | **Final Blow 10** | 10 final kills | 10 |
| 50 | **Killstreak 5** | 5 killstreak | 5 |
| 51 | **Killstreak 10** | 10 killstreak | 10 |
| 52 | **Double Kill 10** | 10 double kills | 10 |
| 53 | **Triple Kill 5** | 5 triple kills | 5 |
| 54 | **Quad Kill** | 4 kills rapid | 1 |
| 55 | **Ace** | Kill entire team | 5+ |
| 56 | **Longshot 10** | 10 100m+ kills | 10 |
| 57 | **Point Blank 10** | 10 <5m kills | 10 |
| 58 | **Backstabber 10** | 10 backstabs | 10 |
| 59 | **Wallbanger 5** | 5 wallbang kills | 5 |
| 60 | **Collateral** | 1 collateral kill | 1 |
| 61-70 | **Per-Weapon 100 Kills** | 100 kills with each weapon | 100 each |

## Tier 3: Weapon Specialist (50 Achievements)
| # | Achievement | Description |
|---|------------|-------------|
| 71-90 | **[Weapon] Master** | 500 kills with weapon |
| 91-110 | **[Weapon] Elite** | 1000 kills with weapon |
| 111-130 | **[Weapon] Legend** | 5000 kills with weapon |
| 131 | **Sniper Elite** | 500 sniper kills |
| 132 | **No-Scope Legend** | 50 no-scope kills |
| 133 | **Quick-Scope Master** | 100 quick-scope kills |
| 134 | **SMG Specialist** | 1000 SMG kills |
| 135 | **Rifle Expert** | 1000 rifle kills |
| 136 | **Explosive Expert** | 500 explosive kills |
| 137 | **Melee Master** | 100 melee kills |
| 138 | **Knife Only** | 10 knife-only match wins |
| 139 | **Pistol Pro** | 500 pistol kills |
| 140 | **One Tap** | 100 one-shot kills |

## Tier 4: Tactical Excellence (50 Achievements)
| # | Achievement | Description |
|---|------------|-------------|
| 141 | **1v1 Master** | 50 1v1 clutch wins |
| 142 | **1v2 Clutch** | 25 1v2 clutch wins |
| 143 | **1v3 Clutch** | 10 1v3 clutch wins |
| 144 | **1v4 Clutch** | 5 1v4 clutch wins |
| 145 | **1v5 Clutch** | 1 1v5+ clutch win |
| 146 | **Ace Machine** | 10 aces |
| 147 | **Flawless** | 10 flawless rounds |
| 148 | **Perfect Game** | 0 death match with 10+ kills |
| 149 | **Untouchable** | 3 perfect matches |
| 150 | **Opening Duelist** | 50% opening duel win rate |
| 151 | **Entry Fragger** | 100 entry kills |
| 152 | **Support Player** | 500 assists |
| 153 | **Trade King** | 100 trade kills |
| 154 | **Defuse Hero** | 50 defuses |
| 155 | **Plant Master** | 50 plants |
| 156 | **Ninja Defuse** | 10 ninja defuses |
| 157 | **Buzzer Beater** | 10 last-second defuses |
| 158 | **Flag Master** | 50 flag captures |
| 159 | **Flag Defender** | 50 flag returns |
| 160 | **Carrier Assassin** | 50 carrier kills |
| 161 | **Objective Player** | 100 hours on objective |
| 162-180 | **Map Master [Map]** | 500 kills per map |

## Tier 5: Humiliation (50 Achievements)
| # | Achievement | Description |
|---|------------|-------------|
| 181 | **Grave Dancer** | Teabag 50 victims |
| 182 | **Nutcracker** | 50 pelvis kills |
| 183 | **Ankle Biter** | 50 foot kills |
| 184 | **Disrespect** | Bash kill after gun kill |
| 185 | **Spawn Trapper** | 50 spawn kills |
| 186 | **Exit Camper** | Kill 25 in spawn exit |
| 187 | **Humiliation** | Kill with enemy's weapon |
| 188 | **Last Laugh** | Post-mortem grenade kill |
| 189 | **From the Grave** | Post-mortem kill |
| 190 | **Denied** | Kill during enemy's reload |
| 191 | **Too Slow** | Kill enemy who missed you |
| 192 | **360 Kill** | 360° before kill |
| 193 | **No-Scope Humiliation** | No-scope a sniper |
| 194 | **Air Mail** | Kill with thrown grenade direct hit |
| 195 | **Kobe** | 100m+ grenade kill |
| 196 | **Splash Zone** | Kill 3 with one grenade |
| 197 | **Rocket Man** | Direct hit rocket kill |
| 198 | **Air Jordan** | Kill while jumping |
| 199 | **Sky High** | Kill from max height |
| 200 | **Floor is Lava** | Win round without touching ground |
| 201-220 | **Nemesis [Player]** | Kill same player 10 times |

## Tier 6: Hall of Shame (50 Achievements)
| # | Achievement | Description |
|---|------------|-------------|
| 221 | **Kenny** | Die first 5 rounds in a row |
| 222 | **Oops** | Kill yourself with grenade |
| 223 | **Friendly Fire** | Teamkill |
| 224 | **Serial Teamkiller** | 10 teamkills |
| 225 | **Gravity** | 10 fall deaths |
| 226 | **Can't Swim** | 5 drown deaths |
| 227 | **Door Kill** | Crushed by door |
| 228 | **Tank Bait** | Killed by tank 10 times |
| 229 | **Suppressing Fire** | 0 kills with 100+ shots |
| 230 | **Reload Junkie** | 1000 reloads |
| 231 | **Empty Click** | Die with empty magazine |
| 232 | **Rage Quit** | Leave 10 matches early |
| 233 | **Target Practice** | Die 100 times without killing |
| 234 | **Pacifist** | Complete match with 0 kills |
| 235 | **Stormtrooper** | Miss 50 shots in a row |
| 236 | **Swiss Cheese** | Take damage from 5 enemies in one life |
| 237 | **Sorry** | Teamkill with grenade |
| 238 | **Blind Bat** | Flashbang yourself |
| 239 | **Camper Detected** | 10 min stationary |
| 240 | **Bush Wookie** | Prone for 30 min total |
| 241 | **Dominated** | Killed 10 times by same player |
| 242 | **Feeding** | Die 20 times in one match |
| 243 | **Bottom Frag** | Last on team 10 times |
| 244 | **Negative KD** | <0.5 KD in match |
| 245 | **The Bot** | 0 kills and 10+ deaths |
| 246-260 | **Reverse Challenge** | Funny subpar achievements |

## Tier 7: Dedication & Milestones (50 Achievements)
| # | Achievement | Description |
|---|------------|-------------|
| 261 | **Thousand Kills** | 1,000 kills |
| 262 | **Five Thousand** | 5,000 kills |
| 263 | **Ten Thousand** | 10,000 kills |
| 264 | **Fifty Thousand** | 50,000 kills |
| 265 | **Hundred Thousand** | 100,000 kills |
| 266 | **Ironman** | 100 hours playtime |
| 267 | **Grinder** | 500 hours playtime |
| 268 | **No Life** | 1,000 hours playtime |
| 269 | **Eternal** | 5,000 hours playtime |
| 270 | **Win 100** | 100 wins |
| 271 | **Win 500** | 500 wins |
| 272 | **Win 1000** | 1,000 wins |
| 273 | **Distance 100km** | 100km traveled |
| 274 | **Distance 500km** | 500km traveled |
| 275 | **Distance 1000km** | 1,000km traveled |
| 276 | **Accuracy God** | 50%+ accuracy |
| 277 | **Headshot God** | 40%+ headshot rate |
| 278 | **KD King** | 3.0+ K/D ratio |
| 279 | **KD Legend** | 5.0+ K/D ratio |
| 280 | **Win Rate Elite** | 70%+ win rate |
| 281 | **Login Streak 7** | 7 days in a row |
| 282 | **Login Streak 30** | 30 days in a row |
| 283 | **Login Streak 100** | 100 days in a row |
| 284 | **Login Streak 365** | Year of play |
| 285-300 | **Seasonal Champions** | Top player per season |

## Tier 8: Hidden & Secret (50 Achievements)
| # | Achievement | Description |
|---|------------|-------------|
| 301 | **Easter Egg** | Find hidden spot |
| 302 | **Midnight Warrior** | Play at 3 AM |
| 303 | **Early Bird** | Play at 6 AM |
| 304 | **Weekend Warrior** | Only play weekends |
| 305 | **Marathon Man** | 42.195km exact |
| 306 | **Door Destroyer** | Open 1000 doors |
| 307 | **Chat Warrior** | 1000 messages |
| 308 | **Ghost** | Win with 0 shots fired |
| 309 | **Sticky Fingers** | Pick up 500 items |
| 310 | **Ladder Specialist** | 1000 ladder climbs |
| 311 | **Sea Turtle** | 100 drowning near misses |
| 312 | **Lucky** | Survive with 1 HP |
| 313 | **Miracle** | Win when 1v5 |
| 314 | **Impossible** | Win when 1v10 |
| 315 | **Chosen One** | #1 on leaderboard |
| 316-350 | **Additional secrets** | Various hidden triggers |

## Tier 9: Tournament & Competitive (50 Achievements)
| # | Achievement | Description |
|---|------------|-------------|
| 351 | **Tournament Entry** | Join first tournament |
| 352 | **Tournament Win** | Win a tournament |
| 353 | **Champion** | Win 5 tournaments |
| 354 | **Dynasty** | Win 3 in a row |
| 355 | **Grand Slam** | Win all tournament types |
| 356 | **MVP** | Tournament MVP |
| 357 | **Bracket Buster** | Win from lower bracket |
| 358 | **3-0 Sweep** | Win finals 3-0 |
| 359 | **Clutch Finals** | Clutch in finals |
| 360 | **ELO 2000** | Reach 2000 ELO |
| 361 | **ELO 2500** | Reach 2500 ELO |
| 362 | **ELO 3000** | Reach 3000 ELO |
| 363 | **Top 100** | Global top 100 |
| 364 | **Top 10** | Global top 10 |
| 365 | **#1** | Global #1 |
| 366-400 | **Ranked Achievements** | Various rank tiers |

## Tier 10: Special & Community (100 Achievements)
| # | Achievement | Description |
|---|------------|-------------|
| 401 | **Founder** | Played in 2025 |
| 402 | **Beta Tester** | Pre-release player |
| 403 | **Server Admin** | Ran a server |
| 404 | **Map Creator** | Made a map |
| 405 | **Bug Hunter** | Reported valid bug |
| 406 | **Clip Master** | Submitted epic clip |
| 407 | **Community Hero** | 100+ forum posts |
| 408 | **Streamer** | Streamed game |
| 409 | **Content Creator** | Made video |
| 410 | **Team Member** | Part of official team |
| 411-500 | **Event-Specific** | Holiday events, special modes |

---

# PART 5: COMBINATION STATISTICS

## Cross-Dimensional Queries (∞ Possible)

The magic is in COMBINATIONS. Every stat above can be filtered by:

### Dimension Filters
- **Per Server** (591+ combinations)
- **Per Map** (590+ × 20 = 11,800 combinations)
- **Per Weapon** (450+ × 20 = 9,000 combinations)
- **Per Time Period** (All Time, Year, Month, Week, Day)
- **Per Opponent** (Personal rivalry stats)
- **Per Team** (Allies/Axis)
- **Per Gametype** (FFA, TDM, OBJ, etc.)

### Example Combination Stats
| # | Combined Statistic |
|---|-------------------|
| 1001 | Thompson kills on Stalingrad |
| 1002 | Kar98 headshots on V2 Rocket |
| 1003 | Prone kills with sniper on Hunt |
| 1004 | Backstab kills with MP40 |
| 1005 | 1v1 clutches on Flughafen |
| 1006 | First blood rate with Thompson |
| 1007 | Win rate as Allies on Bridge |
| 1008 | Accuracy with BAR on Omaha |
| 1009 | ADR per server |
| 1010 | KDR vs specific player |

**Total Theoretical Combinations: >50,000**

---

# PART 6: VISUALIZATION IDEAS

## For Every Stat
1. **Click = Drill Down** - Every number links to breakdown table
2. **Hover = Tooltip** - Shows formula, comparison to average
3. **Compare = VS Mode** - Overlay another player's stats
4. **Leaderboard = Sort** - See global rank for any stat
5. **Trend = Sparkline** - Show change over time
6. **Heatmap = Position** - Where on map (for spatial stats)
7. **Export = CSV** - Download any table

---

# Summary

| Category | Count |
|----------|-------|
| Engine Events | 31 |
| Player Combat Stats | 50 |
| Weapon Stats | 230 |
| Movement Stats | 50 |
| Accuracy Stats | 40 |
| Session Stats | 30 |
| Clutch/Situational | 50 |
| Objective Stats | 40 |
| Server Stats | 100+ |
| Map-Specific Stats | 100+ |
| Achievements | 500+ |
| Combination Stats | 50,000+ |
| **TOTAL TRACKED METRICS** | **>51,000** |

This is the foundation for the most comprehensive FPS statistics system ever built.


# THE GIANT LIST OF OPENMOHAA EVENTS, STATS, AND ACHIEVEMENTS

This document represents the master taxonomy of every possible trackable metric, event variation, and gamified achievement in OpenMoHAA. It is derived from the engine's atomic event system (`register_event`), weapon definitions (`item.cpp`), and hit location logic (`q_shared.h`).

**Total Estimated Permutations: >5,000+**

---

## 1. ATOMIC ENGINE EVENTS (The Source of Truth)

These 30 events are the building blocks. Every stat below is a filter or aggregation of these.

### Combat Layer
1. `player_kill` (Attacker, Victim, Mod, HitLoc, Weapon)
2. `player_death` (Victim, Inflictor, Mod)
3. `player_damage` (Attacker, Victim, Damage, Loc, Weapon)
4. `player_headshot` (Attacker, Victim, Weapon)
5. `weapon_fire` (Player, Weapon)
6. `weapon_hit` (Player, HitLoc, Surface)
7. `weapon_reload` (Player, Weapon)
8. `weapon_change` (Player, OldWeapon, NewWeapon)
9. `grenade_throw` (Player, Type)
10. `grenade_explode` (Player, DamageRadius)

### Movement Layer
11. `player_jump` (Player, Velocity)
12. `player_land` (Player, Height)
13. `player_crouch` (Player, Duration)
14. `player_prone` (Player, Duration)
15. `player_swim` (Player, Distance)
16. `player_sprint` (Player, Distance)
17. `player_walk` (Player, Distance)
18. `ladder_mount` (Player, LadderID)
19. `ladder_dismount` (Player, LadderID)
20. `wall_touch` (Player, WallNormal)

### Interaction Layer
21. `item_pickup` (Player, ItemName)
22. `item_drop` (Player, ItemName)
23. `player_use` (Player, TargetEntity)
24. `door_open` (Player, DoorID)
25. `turret_mount` (Player, TurretID)

### Session Layer
26. `client_connect` (PlayerIP, GUID)
27. `client_disconnect` (Reason)
28. `client_begin` (Spawn)
29. `team_join` (TeamID)
30. `player_chat` (Message, Team/All)

---

## 2. DERIVED PLAYER STATISTICS (The "Drill-Down")

This section permutes the 45+ weapons against the 30+ action types.

### A. Weapon Mastery (Per-Weapon Stats)
*For EVERY weapon below (Colt 45, P38, Webley, Nagant, M1 Garand, Kar98, Springfield, Enfield, SVT40, G43, Thompson, MP40, Sten, PPSH, BAR, MP44, Bazooka...)*

**Combat Efficiency**
*   `[Weapon]_Kills`: Total kills.
*   `[Weapon]_Deaths`: Deaths while holding this weapon.
*   `[Weapon]_Headshots`: Headshot count.
*   `[Weapon]_Headshot_Percentage`: (Headshots / Kills) %.
*   `[Weapon]_Accuracy`: (Shots Hit / Shots Fired) %.
*   `[Weapon]_Damage_Dealt`: Total HP damage inflicted.
*   `[Weapon]_Damage_Per_Shot`: Average damage per hit.
*   `[Weapon]_Time_Equipped`: Total duration held in hands.
*   `[Weapon]_Reloads`: Number of times reloaded.
*   `[Weapon]_Kills_Per_Mag`: Average kills before reloading.
*   `[Weapon]_Longest_Kill`: Max distance kill.
*   `[Weapon]_Point_Blank_Kills`: Kills < 2 meters.
*   `[Weapon]_Wallbang_Kills`: Kills through geometry.

**Comparison Metrics (Rivals)**
*   `Thompson_vs_MP40_WinRate`: % of duels won against MP40.
*   `Garand_vs_Kar98_WinRate`: % of duels won against Kar98.
*   `Sniper_vs_Sniper_WinRate`: % of duels won against other snipers.
*   `Bazooka_vs_Infantry_Ratio`: Kills vs non-explosive users.

### B. Anatomy & Hitbox Statistics
*Permuted for: Head, Helmet, Neck, Torso(Up/Mid/Low), Pelvis, Arms(L/R), Legs(L/R), Hands, Feet.*

*   `HitLoc_Damage_Received_[Part]`: Total damage taken to body part.
*   `HitLoc_Damage_Dealt_[Part]`: Total damage dealt to body part.
*   `HitLoc_Fatal_Shot_[Part]`: Count of kills where this part was the final hit.
*   `Limb_Amputation_Rate`: (Hypothetical if gore enabled) - Limb hits resulting in gibs.
*   `Groin_Shot_Count`: The "Nutcracker" stat.
*   `Helmet_Pop_Count`: Hits that removed helmet but didn't kill.
*   `Achilles_Heel_Deaths`: Deaths by foot shots.

### C. Movement & Stance Statistics
*   `Stance_Prone_Kills`: Kills while prone.
*   `Stance_Crouch_Kills`: Kills while crouching.
*   `Stance_Air_Kills`: Kills while Z-velocity > 0.
*   `Stance_Transition_Kills`: Kills during crouch/stand animation.
*   `Movement_Sprint_Kills`: Kills while velocity > walk speed.
*   `Movement_Stationary_Kills`: Kills while velocity == 0.
*   `Distance_Traveled_Walk`: Total KM walked.
*   `Distance_Traveled_Sprint`: Total KM sprinted.
*   `Distance_Traveled_Crouch`: Total KM crouched (The "Crab" stat).
*   `Distance_Traveled_Swim`: Total KM swam.
*   `Ladder_Time`: Total seconds on ladders.
*   `Jump_Count`: Total spacebar presses.
*   `Bunny_Hop_Chain_Max`: Max consecutive jumps.

### D. Map-Specific Statistics
*For every map (e.g., mohdm1, mohdm2, obj_team1...)*

*   `Map_[Name]_WinRate`: Win % on this map.
*   `Map_[Name]_KDR`: K/D Ratio specific to this map.
*   `Map_[Name]_Fav_Weapon`: Most used weapon on this map.
*   `Map_[Name]_Heatmap_Zone_A_Kills`: Kills in specific named zones.
*   `Map_[Name]_Spawn_Kills`: Kills near spawn points.
*   `Map_[Name]_Objective_Caps`: Flags/Bombs completed.
*   `Map_[Name]_Fall_Deaths`: Gravity victims on this map.

---

## 3. ACHIEVEMENTS & MEDALS (The 1,000 List)

### Tier 1: Weapon Training (Bronze/Silver/Gold/Onyx)
*Repeat for all 45 weapons.*
1.  **[Weapon] Marksman**: 100 Kills.
2.  **[Weapon] Expert**: 500 Kills.
3.  **[Weapon] Master**: 1,000 Kills.
4.  **[Weapon] God**: 10,000 Kills.
5.  **[Weapon] Surgeon**: 500 Headshots.
6.  **[Weapon] Spray & Pray**: Fire 10,000 rounds.
7.  **[Weapon] Consevator**: 50 Kills with >50% Accuracy.

*(Example subset for specific flavor)*
*   **Tommy Gun Tycoon**: 1,000 Thompson Kills.
*   **Kraut Mower**: 1,000 MP40 Kills.
*   **Garand Thumb**: Reload M1 Garand 500 times empty.
*   **Click-Click-Boom**: Kill with the last bullet in a Kar98 clip.
*   **Potato Masher**: 500 Stielhandgranate Kills.
*   **Pineapple Surprise**: 500 Mk2 Frag Kills.
*   **Bazooka Ace**: 100 Direct Impact Rocket Kills.
*   **Trench Sweeper**: 500 Shotgun Kills.
*   **Silent but Deadly**: 100 Hi-Standard/DeLisle Kills.

### Tier 2: Combat Situations
8.  **Death From Above**: Kill enemy while falling > 10ft.
9.  **Grave Digger**: Kill an enemy while you are under 10 HP.
10. **Post-Mortem**: Get a grenade kill after you have died.
11. **Trade Offer**: Kill an enemy who kills you (Simul-kill).
12. **Blind Fire**: Kill an enemy while 100% blind (Flashbang).
13. **Wall Hax**: Kill enemy through a door/wall.
14. **David vs Goliath**: Kill a Bazooka user with a Pistol.
15. **Knife to a Gunfight**: Bash kill vs MG42 user.
16. **Sniper Duelist**: Headshot a sniper who is scoping you.
17. **Collateral Damage**: Kill 2 enemies with 1 sniper bullet.
18. **Explosive Personality**: Kill 3 enemies with 1 grenade.
19. **Rocket Man**: Kill an enemy while mid-air from a rocket jump.
20. **Door Prize**: Kill someone by crushing them with a door (if physics allow).
21. **Telefrag**: Spawn inside someone and gib them.

### Tier 3: Streaks & Multi-Kills
22. **Double Kill**: 2 kills in 3 seconds.
23. **Triple Kill**: 3 kills in 5 seconds.
24. **Multi Kill**: 4 kills in 7 seconds.
25. **Mega Kill**: 5 kills in 10 seconds.
26. **Ultra Kill**: 6 kills in 12 seconds.
27. **Monster Kill**: 7 kills in 15 seconds.
28. **Ludicrous Kill**: 8+ kills in 20 seconds.
29. **Killing Spree**: 5 kills without dying.
30. **Rampage**: 10 kills without dying.
31. **Dominating**: 15 kills without dying.
32. **Unstoppable**: 20 kills without dying.
33. **Godlike**: 25 kills without dying.
34. **Wicked Sick**: 30 kills without dying.

### Tier 4: Objective & Teamwork
35. **Flag Runner**: Capture 3 flags in one match.
36. **Bomb Squad**: Defuse the bomb with < 1 second left.
37. **Planter**: Plant the bomb 100 times.
38. **Gatekeeper**: Kill 10 enemies near your flag.
39. **Defender**: Return 100 flags.
40. **Medic**: (If health packs drop) Heal 1000 HP.
41. **Ammo Mule**: Resupply teammates 100 times.
42. **Human Shield**: Take 500 damage in a round without dying.
43. **Last Man Standing**: Win a round as the sole survivor vs 3+.

### Tier 5: Movement & Parkour
44. **Marathon Man**: Run 42km total.
45. **Roof Camper**: Spend 50% of a match at highest Z-coords.
46. **Floor Mat**: Spend 50% of a match prone.
47. **Rabbit**: Jump 500 times in one match.
48. **Fish**: Swim 1km total.
49. **Ladder Goat**: Climb 1km vertical distance.
50. **Speed Demon**: Maintain top sprint speed for 60 seconds.

### Tier 6: The "Hall of Shame" (Fun/Negative Stats)
51. **Butterfingers**: Drop the objective flag 10 times.
52. **Kenny**: Die first in every round of a match.
53. **Suicide King**: 100 self-kills (rockets/nades).
54. **Friendly Fire**: Team kill 50 allies.
55. **Broken Legs**: Die from falling damage 50 times.
56. **Fish Food**: Drown 10 times.
57. **Pacifist**: Finish a match with 0 kills and >10 deaths.
58. **Swiss Cheese**: Die from 10 different weapons in one match.
59. **Bot**: Finish with score -5 or lower.
60. **Reload Addict**: Reload with >90% ammo left 1000 times.
61. **AFK**: Be kicked for inactivity 10 times.

### Tier 7: "Sabermetrics" (Advanced Analytics)
62. **The 1%**: Top 1% of Global Elo.
63. **Clutch King**: Highest 1vX win rate on server.
64. **First Blood Ratio**: Highest % of opening kills.
65. **Trade Efficiency**: Best Kill/Death trade ratio.
66. **Accuracy God**: Highest overall accuracy (>40%).
67. **Headshot Machine**: Highest HS% (>50%).
68. **Utility Master**: Most grenade damage per round.
69. **Survivor**: Lowest death rate per minute.
70. **Damage Dealer**: Highest ADR (Average Damage per Round).

---

## 4. SERVER STATISTICS & GLOBAL RECORDS

These are calculated across ALL players.

**Global Totals**
*   `Global_Bullets_Fired`: (e.g., 1,042,912,831)
*   `Global_Distance_Traveled`: Earth circumferences walked.
*   `Global_Kills_Map_[Map]`: Most violent map.
*   `Global_Weapon_Popularity`: Usage % of all weapons.

**Server-Specific Records**
*   `Server_Longest_Match`: Duration record.
*   `Server_Highest_Score`: Max score in one game.
*   `Server_Most_Kills_One_Game`: Tracking the kill record.
*   `Server_Most_Deaths_One_Game`: Tracking the feed record.
*   `Server_Chattiest_Player`: Most messages sent.
*   `Server_Bloodiest_Hour`: Time of day with most kills.

**Meta-Analysis**
*   `Faction_Win_Rate`: Axis vs Allies global win %.
*   `Map_Balance_Index`: How close rounds are on average per map.
*   `Weapon_Balance_Index`: Standard deviation of weapon K/D ratios.

---

## 5. EXTENDED 1,000+ GENERATOR PATTERN

To reach the requested 1,000+ figure practically, the system generates achievements programmatically using this matrix:

**[ACTION] + [CONDITION] + [THRESHOLD]**

**Actions:**
*   Kill, Headshot, Bash, Grenade Kill, Win, Cap, Defuse, Die...

**Conditions:**
*   While Prone
*   While Jumping
*   While Blind
*   While <10HP
*   From >100m
*   From <2m
*   With [Specific Weapon]
*   Against [Specific Weapon]
*   In [Specific Map]
*   Within [Time Limit]

**Thresholds:**
*   1, 10, 50, 100, 500, 1000, 10000

**Examples of Generated List (subset):**
*   ...
*   701. **Prone Master I**: 10 Kills while prone.
*   702. **Prone Master II**: 50 Kills while prone.
*   703. **Prone Master III**: 100 Kills while prone.
*   704. **Airborne I**: 10 Kills while jumping.
*   705. **Airborne II**: 50 Kills while jumping.
*   ...
*   850. **Stalingrad Veteran**: 100 Wins on Stalingrad.
*   851. **V2 Rocket Veteran**: 100 Wins on V2 Rocket.
*   852. **Omaha Beach Veteran**: 100 Wins on Omaha.
*   ...
*   920. **Kar98 Specialist**: 500 Headshots with Kar98.
*   921. **Springfield Specialist**: 500 Headshots with Springfield.
*   922. **Mosin Specialist**: 500 Headshots with Mosin.
*   ...
*   998. **Grandmaster of War**: 1,000,000 Total XP.
*   999. **The Completionist**: Unlock 500 other achievements.
*   1000. **OpenMoHAA Legend**: Play for 1,000 Hours.

*(Full database requires procedural generation in SQL/GameDB based on these patterns)*

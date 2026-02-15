# Task: Repair API Authentication & Event Callbacks

- [x] Fix 401 Unauthorized Issue <!-- id: 0 -->
    - [x] Locate server registration logic (`register.scr`).
    - [x] Verify how `level.server_token` is set in scripts.
    - [x] Check API database (`servers` table in Postgres) for the expected token.
    - [x] Ensure scripts properly load/save the token.
- [x] Fix Missing Callback Error <!-- id: 1 -->
    - [x] Check `tracker_common.scr` usage of `on_http_callback`.
    - [x] Verify `curl_post` syntax and expectation.
    - [x] Create/Fix `on_http_callback` in `tracker_common.scr`.
    - [x] Add 401 auto-recovery: clears stale credentials + triggers re-registration.
    - [x] Add 30s cooldown to prevent registration spam loops.
    - [x] Set `game.opm_registered = 1` on successful registration.
- [ ] Restore Leaderboard Data Flow <!-- id: 2 -->
    - [ ] Verify events are accepted (202 Accepted) after auth fix.
    - [ ] Confirm leaderboard population.

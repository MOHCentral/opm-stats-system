# Task: Repair API Authentication & Event Callbacks

- [ ] Fix 401 Unauthorized Issue <!-- id: 0 -->
    - [ ] Locate server registration logic (`register.scr`).
    - [ ] Verify how `level.server_token` is set in scripts.
    - [ ] Check API database (`servers` table in Postgres) for the expected token.
    - [ ] Ensure scripts properly load/save the token.
- [ ] Fix Missing Callback Error <!-- id: 1 -->
    - [ ] Check `tracker_common.scr` usage of `on_http_callback`.
    - [ ] Verify `curl_post` syntax and expectation.
    - [ ] Create/Fix `on_http_callback` in `tracker_common.scr`.
- [ ] Restore Leaderboard Data Flow <!-- id: 2 -->
    - [ ] Verify events are accepted (202 Accepted) after auth fix.
    - [ ] Confirm leaderboard population.

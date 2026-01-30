# Implementation Plan - Fix Auth & Callbacks

The game server is failing to authenticate (401) and failing to find the callback function.
We need to:
1.  Ensure `on_http_callback` is correctly exposed.
2.  Implement auto-re-registration when a 401 occurs.
3.  Clean up hardcoded tokens in simpler scripts if they override the registered one.

## Proposed Changes

### [opm-stats-game-scripts]

#### [MODIFY] [global/tracker_common.scr](file:///home/elgan/dev/opm-stats-system/opm-stats-game-scripts/global/register.scr)

-   Update `on_http_callback`:
    -   Add logic to detect 401 HTTP code.
    -   If 401, set `level.server_token = NIL` and execute `exec global/register.scr`.
    -   Maybe simplify the path string passed to `curl_post`.

#### [MODIFY] [global/register.scr](file:///home/elgan/dev/opm-stats-system/opm-stats-game-scripts/global/register.scr)

-   Review registration flow.
-   Make sure it saves the new token to a persistent cvar or file so it survives map restarts.

## Verification Plan

### Manual Verification
1.  Observe server logs.
2.  See if "Invalid server token" changes to "Registered successfully".
3.  Verify events start flowing.

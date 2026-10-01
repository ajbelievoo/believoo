# BMyDesk Agent

Desktop companion app for BMyDesk (bmydesk.believoo.com) — AnyDesk-style remote desktop with a session code.

## How it works

1. Agent launches → calls `POST /api/v1/bmydesk/agent/register` → gets an 8-char session code.
2. Agent connects to Reverb (`wss://believoo.com`, Pusher protocol) on `private-remote-agent.<CODE>` using its `agent_token` for channel auth.
3. A viewer enters the code at `https://bmydesk.believoo.com/remote/connect` and sends `client-join-request`.
4. Host accepts → viewer sends a WebRTC offer → agent answers and streams the screen (P2P, low latency).
5. Viewer mouse/keyboard events travel over the WebRTC `RTCDataChannel` (`input`) → renderer forwards them to the main process via IPC → `@nut-tree/nut-js` injects them into the host OS.

## Build & run (dev)

```bash
cd bmydesk-agent
npm install
npm start
```

macOS note: the app needs **Screen Recording** + **Accessibility** permissions (System Settings → Privacy & Security) for capture and input injection.

## Production build (installer)

```bash
npm run dist
```

Outputs `BMyDesk Agent Setup` (NSIS) on Windows, `.dmg` on macOS, `.AppImage` on Linux via electron-builder.

## Server endpoints (already in believoo repo)

| Endpoint | Purpose |
|---|---|
| `POST /api/v1/bmydesk/agent/register` | create waiting session + code + agent_token |
| `GET /api/v1/bmydesk/agent/{code}/status` | poll session state |
| `POST /api/v1/bmydesk/agent/{code}/end` | end session |
| `POST /api/v1/bmydesk/agent/broadcast-auth` | Reverb private-channel auth |

## Notes / calibration

- **TURN server**: P2P uses public STUN only. Strict symmetric NATs/corporate firewalls need a TURN server for guaranteed connectivity — add one to `pcConfig` in `renderer/renderer.js` and `agent-room.blade.php`/`host-room.blade.php`.
- **HiDPI**: input coords are fractions of the primary display's pixel size (`screen.getPrimaryDisplay().size`). If clicks land offset on scaled displays, multiply by `display.scaleFactor`.
- nut-js native builds are downloaded on `npm install`; on Linux ensure `libXtst` is present.

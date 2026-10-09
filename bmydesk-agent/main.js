const { app, BrowserWindow, ipcMain, desktopCapturer, screen, shell, session, dialog, clipboard, Tray, Menu } = require('electron');
const fs = require('fs');
const path = require('path');

// nut-js is a native module; load lazily so the app still starts even if the
// input-injection driver is unavailable on this OS (e.g. missing macOS perms).
let nut = null;
function loadNut() {
    if (nut) return nut;
    try {
        nut = require('@nut-tree-fork/nut-js');
        nut.mouse.config.autoDelayMs = 0;
        nut.mouse.config.mouseSpeed = 6000; // px/sec — near-instant moves
        nut.keyboard.config.autoDelayMs = 0;
        return nut;
    } catch (e) {
        console.error('[agent] nut-js unavailable:', e.message);
        return null;
    }
}

let win = null;

// Auto-update (electron-updater, generic feed on /downloads) — latest.yml is
// published next to the installer; the app checks on launch + every 4 h.
function wireAutoUpdate() {
    let autoUpdater;
    try { autoUpdater = require('electron-updater').autoUpdater; }
    catch (e) { console.warn('[agent] updater unavailable:', e.message); return; }
    autoUpdater.autoDownload = true;
    autoUpdater.autoInstallOnAppQuit = true;
    const send = (evt, data) => { try { win?.webContents.send('agent-update', { evt, ...data }); } catch (e) {} };
    autoUpdater.on('checking-for-update', () => send('checking'));
    autoUpdater.on('update-available', (i) => send('available', { version: i.version }));
    autoUpdater.on('update-not-available', () => send('none'));
    autoUpdater.on('download-progress', (p) => send('progress', { percent: Math.round(p.percent) }));
    autoUpdater.on('update-downloaded', (i) => send('downloaded', { version: i.version }));
    autoUpdater.on('error', (e) => send('error', { message: String(e.message || e) }));
    ipcMain.on('update-install', () => autoUpdater.quitAndInstall());
    autoUpdater.checkForUpdates().catch(() => {});
    setInterval(() => autoUpdater.checkForUpdates().catch(() => {}), 4 * 3600 * 1000);
}
app.whenReady().then(() => { if (gotLock) wireAutoUpdate(); });

// ── bmydesk:// deep links (Google sign-in token handoff, etc.) ────
function deliverDeepLink(url) {
    try {
        const u = new URL(url);
        if (u.hostname === 'auth') {
            const token = u.searchParams.get('token');
            const name = u.searchParams.get('name') || '';
            if (token && win) win.webContents.send('agent-auth', { token, name });
        }
    } catch (e) { /* malformed link — ignore */ }
}

const gotLock = app.requestSingleInstanceLock();
if (!gotLock) {
    app.quit();
} else {
    app.setAsDefaultProtocolClient('bmydesk');
    app.on('second-instance', (_e, argv) => {
        const url = argv.find((a) => typeof a === 'string' && a.startsWith('bmydesk://'));
        if (win) { if (win.isMinimized()) win.restore(); win.show(); win.focus(); }
        if (url) deliverDeepLink(url);
    });
    app.on('open-url', (e, url) => { e.preventDefault(); deliverDeepLink(url); });
}

function createWindow() {
    win = new BrowserWindow({
        width: 460,
        height: 640,
        resizable: false,
        autoHideMenuBar: true,
        backgroundColor: '#0b1220',
        title: 'BMyDesk Agent',
        icon: path.join(__dirname, 'assets', 'icon.png'),
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            contextIsolation: true,
            nodeIntegration: false,
        },
    });
    win.loadFile(path.join(__dirname, 'renderer', 'index.html'));
    win.on('close', (e) => {
        if (!isQuitting && tray) { e.preventDefault(); win.hide(); }
    });
}

app.whenReady().then(() => {
    if (!gotLock) return; // another instance owns the protocol — it got the argv
    createWindow();
    app.on('activate', () => { if (BrowserWindow.getAllWindows().length === 0) createWindow(); });
    // Cold start via bmydesk:// link — the URL arrives in argv on Windows/Linux.
    win.webContents.once('did-finish-load', () => {
        const url = process.argv.find((a) => typeof a === 'string' && a.startsWith('bmydesk://'));
        if (url) deliverDeepLink(url);
    });
});

app.on('window-all-closed', () => { if (process.platform !== 'darwin') app.quit(); });

// ── Screen enumeration for the renderer (screen picker) ─────
ipcMain.handle('get-screen-sources', async () => {
    const sources = await desktopCapturer.getSources({
        types: ['screen'],
        thumbnailSize: { width: 360, height: 220 },
    });
    return sources.map(s => ({ id: s.id, name: s.name, thumbnail: s.thumbnail.toDataURL() }));
});

// Renderer picks a source id, then getDisplayMedia() triggers this handler —
// the legacy chromeMediaSourceId getUserMedia path is flaky/black on modern
// Electron, so capture goes through the native display-media pipeline.
let pickedSourceId = null;
ipcMain.handle('select-screen-source', (_e, id) => { pickedSourceId = id; return true; });

app.whenReady().then(() => {
    session.defaultSession.setDisplayMediaRequestHandler(async (_req, callback) => {
        for (let i = 0; i < 2; i++) {
            try {
                const sources = await desktopCapturer.getSources({ types: ['screen'] });
                const src = sources.find(s => s.id === pickedSourceId) || sources[0];
                if (src) { callback({ video: src, audio: false }); return; }
            } catch (e) {
                console.error('[agent] getSources in displayMedia handler:', e.message);
            }
            await new Promise(r => setTimeout(r, 400));
        }
        callback({});
    });
});

ipcMain.handle('set-view-mode', (_e, on) => {
    if (!win) return;
    if (on) { win.setResizable(true); win.setSize(1100, 720); win.center(); }
    else { win.setResizable(false); win.setSize(460, 640); }
});

ipcMain.handle('open-external', async (_e, url) => {
    if (typeof url === 'string' && /^https:\/\/([a-z0-9-]+\.)*believoo\.com\//.test(url)) {
        await shell.openExternal(url);
    }
});

// ── System tray: app keeps running + reachable when the window is closed ──
let tray = null, isQuitting = false;
function wireTray() {
    try { tray = new Tray(path.join(__dirname, 'assets', 'icon.png')); } catch (e) { return; }
    const menu = Menu.buildFromTemplate([
        { label: 'Open BMyDesk', click: () => { win?.show(); win?.focus(); } },
        { label: 'Start with Windows', type: 'checkbox',
          checked: app.getLoginItemSettings().openAtLogin,
          click: (i) => app.setLoginItemSettings({ openAtLogin: i.checked }) },
        { type: 'separator' },
        { label: 'Quit', click: () => { isQuitting = true; app.quit(); } },
    ]);
    tray.setToolTip('BMyDesk Agent');
    tray.setContextMenu(menu);
    tray.on('click', () => { win?.show(); win?.focus(); });
}
app.whenReady().then(() => { if (gotLock) wireTray(); });
app.on('before-quit', () => { isQuitting = true; });

// ── Privacy screen: black out the host's own displays while a viewer works ──
// App-level (fullscreen topmost windows); blocking the host's keyboard/mouse
// needs a service/driver — tracked separately.
let privacyWins = [];
// Viewer knocked — flash taskbar + bring the window up so the host notices
ipcMain.on('attention', (_e, name) => {
    try {
        if (win && !win.isDestroyed()) { win.show(); win.flashFrame(true); win.focus(); }
        new (require('electron').Notification)({
            title: 'BMyDesk — connection request',
            body: (name || 'Someone') + ' wants to view your screen',
        }).show();
    } catch (e) {}
});
ipcMain.on('privacy-screen', (_e, on) => {
    if (on) {
        if (privacyWins.length) return;
        privacyWins = screen.getAllDisplays().map(d => {
            const w = new BrowserWindow({
                x: d.bounds.x, y: d.bounds.y, width: d.bounds.width, height: d.bounds.height,
                frame: false, fullscreen: true, alwaysOnTop: true, skipTaskbar: true,
                backgroundColor: '#000000', focusable: false,
                webPreferences: { offscreen: false },
            });
            w.loadURL('data:text/html,<body style="background:#000;margin:0;display:flex;align-items:center;justify-content:center;height:100vh"><div style="color:#334155;font:14px sans-serif;text-align:center">BMyDesk — remote session in progress<br><span style="font-size:11px">screen hidden for privacy</span></div></body>');
            w.setAlwaysOnTop(true, 'screen-saver');
            return w;
        });
    } else {
        privacyWins.forEach(w => { try { w.destroy(); } catch (e) {} });
        privacyWins = [];
    }
});

// ── File transfer + clipboard (ctl channel) ─────────────────
ipcMain.handle('pick-file', async () => {
    const r = await dialog.showOpenDialog(win, { properties: ['openFile'] });
    if (r.canceled || !r.filePaths[0]) return null;
    const p = r.filePaths[0];
    const buf = fs.readFileSync(p);
    if (buf.length > 30 * 1024 * 1024) throw new Error('file too large (30 MB max)');
    return { name: path.basename(p), data: buf.toString('base64'), size: buf.length };
});
ipcMain.handle('save-file', async (_e, name, b64) => {
    const r = await dialog.showSaveDialog(win, { defaultPath: name });
    if (r.canceled || !r.filePath) return null;
    fs.writeFileSync(r.filePath, Buffer.from(String(b64), 'base64'));
    return r.filePath;
});
ipcMain.handle('clipboard-get', () => clipboard.readText());
ipcMain.on('clipboard-set', (_e, t) => clipboard.writeText(String(t || '')));

ipcMain.handle('get-display-size', async () => {
    const d = screen.getPrimaryDisplay();
    return { width: d.size.width, height: d.size.height, scale: d.scaleFactor };
});

// ── Web `code` → nut-js Key mapping ─────────────────────────
function mapKey(code, key) {
    const { Key } = nut || {};
    if (!Key) return null;
    const m = {
        Enter: Key.Enter, Backspace: Key.Backspace, Tab: Key.Tab, Escape: Key.Escape,
        Space: Key.Space, ArrowLeft: Key.Left, ArrowRight: Key.Right, ArrowUp: Key.Up, ArrowDown: Key.Down,
        Delete: Key.Delete, Home: Key.Home, End: Key.End, PageUp: Key.PageUp, PageDown: Key.PageDown,
        CapsLock: Key.CapsLock, ShiftLeft: Key.LeftShift, ShiftRight: Key.RightShift,
        ControlLeft: Key.LeftControl, ControlRight: Key.RightControl,
        AltLeft: Key.LeftAlt, AltRight: Key.RightAlt, MetaLeft: Key.LeftSuper, MetaRight: Key.RightSuper,
        Minus: Key.Minus, Equal: Key.Equal, BracketLeft: Key.LeftBracket, BracketRight: Key.RightBracket,
        Semicolon: Key.Semicolon, Quote: Key.Quote, Backquote: Key.Grave, Backslash: Key.Backslash,
        Comma: Key.Comma, Period: Key.Period, Slash: Key.Slash,
        NumpadEnter: Key.NumLock, PrintScreen: Key.PrintScreen, Insert: Key.Insert,
    };
    for (let i = 1; i <= 12; i++) m['F' + i] = Key['F' + i];
    for (let i = 0; i <= 9; i++) m['Digit' + i] = Key['Num' + i];
    for (let c = 65; c <= 90; c++) m['Key' + String.fromCharCode(c)] = Key[String.fromCharCode(c)];
    return m[code] || null;
}

// ── Input injection (viewer → host) ─────────────────────────
ipcMain.on('input-event', async (_e, msg) => {
    const n = loadNut();
    if (!n) return;
    const { mouse, keyboard, Point, Button, Key } = n;
    const disp = screen.getPrimaryDisplay();
    const W = disp.size.width, H = disp.size.height;

    try {
        switch (msg.t) {
            case 'move':
                await mouse.setPosition(new Point(Math.round(msg.x * W), Math.round(msg.y * H)));
                break;
            case 'down':
            case 'up': {
                const btn = msg.b === 2 ? Button.RIGHT : msg.b === 1 ? Button.MIDDLE : Button.LEFT;
                if (msg.t === 'down') await mouse.pressButton(btn); else await mouse.releaseButton(btn);
                break;
            }
            case 'wheel':
                if (Math.abs(msg.dy) >= Math.abs(msg.dx)) {
                    await (msg.dy > 0 ? mouse.scrollDown(Math.max(1, Math.round(msg.dy / 100)))
                                     : mouse.scrollUp(Math.max(1, Math.round(-msg.dy / 100))));
                } else {
                    await (msg.dx > 0 ? mouse.scrollRight(Math.max(1, Math.round(msg.dx / 100)))
                                     : mouse.scrollLeft(Math.max(1, Math.round(-msg.dx / 100))));
                }
                break;
            case 'key': {
                // modifiers travel as flags on the key event — press them around
                // the key so Ctrl+C etc. actually arrives as a chord.
                const mods = [];
                if (msg.ctrl) mods.push(Key.LeftControl);
                if (msg.alt) mods.push(Key.LeftAlt);
                if (msg.shift) mods.push(Key.LeftShift);
                if (msg.meta) mods.push(Key.LeftSuper);
                if (msg.k && msg.k.length === 1) {
                    // printable char — type() applies layout/shift itself
                    if (msg.down) {
                        for (const mm of mods) await keyboard.pressKey(mm);
                        await keyboard.type(msg.k);
                        for (const mm of mods) await keyboard.releaseKey(mm);
                    }
                    break;
                }
                const k = mapKey(msg.code, msg.k);
                if (k === null) break;
                if (msg.down) {
                    for (const mm of mods) await keyboard.pressKey(mm);
                    await keyboard.pressKey(k);
                } else {
                    await keyboard.releaseKey(k);
                    for (const mm of mods) await keyboard.releaseKey(mm);
                }
                break;
            }
        }
    } catch (err) {
        console.warn('[agent] input inject failed:', err.message);
    }
});

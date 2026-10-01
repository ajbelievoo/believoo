const { app, BrowserWindow, ipcMain, desktopCapturer, screen, shell } = require('electron');
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

function createWindow() {
    win = new BrowserWindow({
        width: 460,
        height: 640,
        resizable: false,
        autoHideMenuBar: true,
        backgroundColor: '#0b1220',
        title: 'BMyDesk Agent',
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            contextIsolation: true,
            nodeIntegration: false,
        },
    });
    win.loadFile(path.join(__dirname, 'renderer', 'index.html'));
}

app.whenReady().then(() => {
    createWindow();
    app.on('activate', () => { if (BrowserWindow.getAllWindows().length === 0) createWindow(); });
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

ipcMain.handle('open-external', async (_e, url) => {
    if (typeof url === 'string' && /^https:\/\/([a-z0-9-]+\.)*believoo\.com\//.test(url)) {
        await shell.openExternal(url);
    }
});

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
    const { mouse, keyboard, Point, Button } = n;
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
                const k = mapKey(msg.code, msg.k);
                if (k === null) break;
                if (msg.down) await keyboard.pressKey(k); else await keyboard.releaseKey(k);
                break;
            }
        }
    } catch (err) {
        console.warn('[agent] input inject failed:', err.message);
    }
});

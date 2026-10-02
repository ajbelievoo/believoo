const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('agent', {
    getScreenSources: () => ipcRenderer.invoke('get-screen-sources'),
    getDisplaySize: () => ipcRenderer.invoke('get-display-size'),
    sendInput: (msg) => ipcRenderer.send('input-event', msg),
    openExternal: (url) => ipcRenderer.invoke('open-external', url),
    setViewMode: (on) => ipcRenderer.invoke('set-view-mode', on),
    onAuth: (cb) => ipcRenderer.on('agent-auth', (_e, d) => cb(d)),
    platform: process.platform,
});

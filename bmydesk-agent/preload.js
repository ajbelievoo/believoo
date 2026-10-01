const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('agent', {
    getScreenSources: () => ipcRenderer.invoke('get-screen-sources'),
    getDisplaySize: () => ipcRenderer.invoke('get-display-size'),
    sendInput: (msg) => ipcRenderer.send('input-event', msg),
    platform: process.platform,
});

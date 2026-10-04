const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('agent', {
    getScreenSources: () => ipcRenderer.invoke('get-screen-sources'),
    selectScreenSource: (id) => ipcRenderer.invoke('select-screen-source', id),
    getDisplaySize: () => ipcRenderer.invoke('get-display-size'),
    sendInput: (msg) => ipcRenderer.send('input-event', msg),
    openExternal: (url) => ipcRenderer.invoke('open-external', url),
    setViewMode: (on) => ipcRenderer.invoke('set-view-mode', on),
    pickFile: () => ipcRenderer.invoke('pick-file'),
    saveFile: (name, b64) => ipcRenderer.invoke('save-file', name, b64),
    clipboardGet: () => ipcRenderer.invoke('clipboard-get'),
    clipboardSet: (t) => ipcRenderer.send('clipboard-set', t),
    onUpdate: (cb) => ipcRenderer.on('agent-update', (_e, d) => cb(d)),
    installUpdate: () => ipcRenderer.send('update-install'),
    onAuth: (cb) => ipcRenderer.on('agent-auth', (_e, d) => cb(d)),
    platform: process.platform,
});

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// BelieVoo WebSocket Manager with Exponential Backoff & Polling Fallback
// Prevents connection failures and provides reliable real-time updates
const BelieVooEchoManager = {
    echo: null,
    maxReconnectAttempts: 5,
    baseReconnectDelay: 1000,
    maxReconnectDelay: 30000,
    reconnectAttempts: 0,
    reconnectTimer: null,
    isIntentionallyDisconnected: false,
    usePolling: false,
    pollingIntervals: {},

    init() {
        // Check if we should use polling instead of WebSocket
        const urlParams = new URLSearchParams(window.location.search);
        this.usePolling = urlParams.get('polling') === 'true' || localStorage.getItem('force_polling') === 'true';

        if (this.usePolling) {
            console.log('[BelieVoo Echo] Using polling mode (WebSocket disabled)');
            return this.createPollingEcho();
        }

        try {
            this.echo = new Echo({
                broadcaster: 'reverb',
                key: import.meta.env.VITE_REVERB_APP_KEY || 'believoo-key',
                wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
                wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
                wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
                forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            });

            // Set up connection monitoring
            this.setupConnectionMonitoring();

            return this.echo;
        } catch (error) {
            console.error('[BelieVoo Echo] Failed to initialize:', error);
            console.log('[BelieVoo Echo] Falling back to polling mode');
            return this.createPollingEcho();
        }
    },

    createPollingEcho() {
        // Create a mock Echo object that uses polling
        // Must implement ALL methods that Livewire/Alpine code calls
        this.usePolling = true;
        this.echo = {
            connector: {
                pusher: {
                    connection: {
                        state: 'connected',
                        bind: () => {}
                    },
                    connect: () => {},
                    disconnect: () => {}
                }
            },
            // Required by Livewire for CSRF/socket header
            socketId: () => null,
            // Required by Alpine notification listeners
            private: (channel) => ({
                listen: (event, callback) => {
                    if (!this.pollingIntervals[channel]) {
                        this.pollingIntervals[channel] = {};
                    }
                    this.pollingIntervals[channel][event] = callback;
                    console.log(`[BelieVoo Echo] Registered polling listener for ${channel}:${event}`);
                    return this; // chainable
                },
                notification: (callback) => {
                    // Polling fallback — no-op, notifications won't arrive in real-time
                    console.log(`[BelieVoo Echo] Notification listener registered (polling mode) for ${channel}`);
                    return this;
                },
                stopListening: (event) => {
                    if (this.pollingIntervals[channel] && this.pollingIntervals[channel][event]) {
                        delete this.pollingIntervals[channel][event];
                    }
                    return this;
                },
                subscribed: (callback) => { callback(); return this; },
                error: (callback) => { return this; },
            }),
            channel: (channel) => ({
                listen: (event, callback) => {
                    if (!this.pollingIntervals[channel]) {
                        this.pollingIntervals[channel] = {};
                    }
                    this.pollingIntervals[channel][event] = callback;
                    return this;
                },
                notification: (callback) => { return this; },
                stopListening: (event) => { return this; },
            }),
            join: (channel) => ({
                here: (callback) => { callback([]); return this; },
                joining: (callback) => { return this; },
                leaving: (callback) => { return this; },
                listen: (event, callback) => { return this; },
            }),
            leave: (channel) => {},
            leaveChannel: (channel) => {},
            disconnect: () => {
                Object.keys(this.pollingIntervals).forEach(channel => {
                    Object.keys(this.pollingIntervals[channel]).forEach(event => {
                        clearInterval(this.pollingIntervals[channel][event]);
                    });
                });
                this.pollingIntervals = {};
            }
        };
        return this.echo;
    },

    setupConnectionMonitoring() {
        if (this.usePolling) return;

        // Guard: if connector not available, fall back to polling
        if (!this.echo || !this.echo.connector || !this.echo.connector.pusher) {
            console.warn('[BelieVoo Echo] Connector not available, switching to polling');
            this.createPollingEcho();
            window.Echo = this.echo;
            return;
        }

        try {
            // Monitor connection state
            const originalConnect = this.echo.connector.pusher.connect.bind(this.echo.connector.pusher);
            
            this.echo.connector.pusher.connect = () => {
                console.log('[BelieVoo Echo] Connecting...');
                return originalConnect();
            };

            // Handle disconnection with exponential backoff
            this.echo.connector.pusher.connection.bind('disconnected', () => {
                console.log('[BelieVoo Echo] Disconnected');
                this.handleDisconnect();
            });

            this.echo.connector.pusher.connection.bind('connected', () => {
                console.log('[BelieVoo Echo] Connected');
                this.reconnectAttempts = 0;
                window.dispatchEvent(new CustomEvent('echo-connected'));
            });

            this.echo.connector.pusher.connection.bind('error', (error) => {
                console.error('[BelieVoo Echo] Connection error:', error);
                window.dispatchEvent(new CustomEvent('echo-error', { detail: error }));
            });

            // Connection timeout - fallback to polling if no connection after 10 seconds
            setTimeout(() => {
                if (this.getConnectionState() !== 'connected' && !this.usePolling) {
                    console.warn('[BelieVoo Echo] Connection timeout, switching to polling');
                    this.fallbackToPolling();
                }
            }, 10000);
        } catch (error) {
            console.error('[BelieVoo Echo] Failed to setup monitoring:', error);
            this.createPollingEcho();
            window.Echo = this.echo;
        }
    },

    fallbackToPolling() {
        if (this.usePolling) return; // Already in polling mode
        console.warn('[BelieVoo Echo] Falling back to polling mode (no page reload)');
        this.usePolling = true;
        this.createPollingEcho();
        window.Echo = this.echo;
        window.dispatchEvent(new CustomEvent('echo-connected')); // Let Livewire proceed
    },

    handleDisconnect() {
        if (this.isIntentionallyDisconnected || this.usePolling) {
            return;
        }

        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            const delay = Math.min(
                this.baseReconnectDelay * Math.pow(2, this.reconnectAttempts),
                this.maxReconnectDelay
            );
            
            this.reconnectAttempts++;
            console.log(`[BelieVoo Echo] Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);

            this.reconnectTimer = setTimeout(() => {
                if (this.echo && this.echo.connector && this.echo.connector.pusher) {
                    this.echo.connector.pusher.connect();
                }
            }, delay);
        } else {
            console.error('[BelieVoo Echo] Max reconnect attempts reached. Falling back to polling.');
            this.fallbackToPolling();
        }
    },

    disconnect() {
        this.isIntentionallyDisconnected = true;
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
        }
        if (this.echo && !this.usePolling) {
            this.echo.disconnect();
        }
    },

    getConnectionState() {
        if (this.usePolling) return 'connected';
        if (!this.echo || !this.echo.connector || !this.echo.connector.pusher) return 'disconnected';
        return this.echo.connector.pusher.connection.state;
    },

    // Manual polling for specific channels (used as fallback)
    pollChannel(channel, endpoint, interval = 3000) {
        if (!this.usePolling) return;
        
        const poll = async () => {
            try {
                const response = await fetch(endpoint, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                
                // Trigger callbacks
                if (this.pollingIntervals[channel]) {
                    Object.values(this.pollingIntervals[channel]).forEach(callback => {
                        callback(data);
                    });
                }
            } catch (error) {
                console.error(`[BelieVoo Echo] Polling error for ${channel}:`, error);
            }
        };

        // Start polling
        const intervalId = setInterval(poll, interval);
        
        // Store for cleanup
        if (!this.pollingIntervals[channel]) {
            this.pollingIntervals[channel] = {};
        }
        this.pollingIntervals[channel]['__interval__'] = intervalId;
        
        // Initial poll
        poll();
    },

    // Trigger event manually (for testing or fallback)
    trigger(channel, event, data) {
        if (this.pollingIntervals[channel] && this.pollingIntervals[channel][event]) {
            this.pollingIntervals[channel][event](data);
        }
    }
};

// Initialize Echo with connection management
window.Echo = BelieVooEchoManager.init();
window.BelieVooEchoManager = BelieVooEchoManager;

// Handle page visibility changes - reconnect when tab becomes active
let wasHidden = false;
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        wasHidden = true;
    } else if (wasHidden && BelieVooEchoManager.getConnectionState() !== 'connected') {
        console.log('[BelieVoo Echo] Page visible, checking connection...');
        BelieVooEchoManager.reconnectAttempts = 0;
        if (BelieVooEchoManager.echo && BelieVooEchoManager.echo.connector && BelieVooEchoManager.echo.connector.reverb) {
            BelieVooEchoManager.echo.connector.reverb.connect();
        }
        wasHidden = false;
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    BelieVooEchoManager.disconnect();
});

// Expose helper for migration progress
window.listenToMigration = (migrationId, onProgress, onComplete, onError) => {
    const userId = document.querySelector('meta[name="user-id"]')?.content;
    
    if (!userId) {
        console.error('[Migration] User ID not found');
        return;
    }

    // Try WebSocket first
    try {
        window.Echo.private(`user.${userId}`)
            .listen('.vm.migration.progress', (e) => {
                if (e.migration_id === migrationId && onProgress) {
                    onProgress(e.progress, e.message);
                }
            })
            .listen('.vm.migration.completed', (e) => {
                if (e.migration_id === migrationId && onComplete) {
                    onComplete(e);
                }
            });
        
        console.log('[Migration] WebSocket listener registered for migration', migrationId);
    } catch (error) {
        console.warn('[Migration] WebSocket failed, using polling fallback');
        
        // Fallback to polling
        const pollInterval = setInterval(async () => {
            try {
                const response = await fetch(`/api/vm-migrations/${migrationId}/progress`);
                const data = await response.json();
                
                if (data.success && data.migration) {
                    if (onProgress) {
                        onProgress(data.migration.progress, data.migration.status_message);
                    }
                    if (data.migration.status === 'completed' && onComplete) {
                        onComplete(data.migration);
                        clearInterval(pollInterval);
                    }
                }
            } catch (err) {
                if (onError) onError(err);
            }
        }, 3000);
        
        // Cleanup after 10 minutes max
        setTimeout(() => clearInterval(pollInterval), 600000);
    }
};

// Expose helper for server import progress with live log
window.listenToServerImport = (importId, onProgress, onComplete, onError) => {
    const userId = document.querySelector('meta[name="user-id"]')?.content;
    
    if (!userId) {
        console.error('[ServerImport] User ID not found');
        return;
    }

    // Try WebSocket first
    try {
        window.Echo.private(`user.${userId}`)
            .listen('.server.import.progress', (e) => {
                if (e.import_id === importId && onProgress) {
                    // Pass all sync data including live log
                    onProgress(e.progress, e.message, {
                        sync_log: e.sync_log,
                        transfer_speed: e.transfer_speed,
                        current_file: e.current_file,
                        files_transferred: e.files_transferred,
                        direction: e.direction
                    });
                }
            })
            .listen('.server.import.completed', (e) => {
                if (e.import_id === importId && onComplete) {
                    onComplete(e);
                }
            })
            .listen('.server.import.failed', (e) => {
                if (e.import_id === importId && onError) {
                    onError(e.error);
                }
            });
        
        console.log('[ServerImport] WebSocket listener registered for sync', importId);
    } catch (error) {
        console.warn('[ServerImport] WebSocket failed, using polling fallback');
        
        // Fallback to polling
        const pollInterval = setInterval(async () => {
            try {
                const response = await fetch(`/api/server-imports/${importId}/progress`);
                const data = await response.json();
                
                if (data.success && data.import) {
                    if (onProgress) {
                        onProgress(data.import.progress_percent, data.import.status_message, {
                            sync_log: data.import.sync_log,
                            transfer_speed: data.import.transfer_speed,
                            current_file: data.import.current_file,
                            files_transferred: data.import.files_transferred,
                            direction: data.import.sync_direction
                        });
                    }
                    if (data.import.status === 'completed' && onComplete) {
                        onComplete(data.import);
                        clearInterval(pollInterval);
                    }
                    if (data.import.status === 'failed' && onError) {
                        onError(data.import.error_log);
                        clearInterval(pollInterval);
                    }
                }
            } catch (err) {
                if (onError) onError(err);
            }
        }, 3000);
        
        // Cleanup after 1 hour max (imports can take longer than migrations)
        setTimeout(() => clearInterval(pollInterval), 3600000);
    }
};

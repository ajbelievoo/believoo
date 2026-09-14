/**
 * BelieVoo Dashboard — Event Log Global Store
 * Tracks every client action (DNS update, server reboot, firewall toggle, etc.)
 * Persists to sessionStorage (cleared when tab closes).
 * Requirements: 4.1–4.5
 */

const STORAGE_KEY = 'believoolog';
const MAX_ENTRIES = 200;

function loadFromStorage() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
}

function saveToStorage(entries) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(entries));
    } catch (e) {
        // sessionStorage unavailable — in-memory only
    }
}

export function registerEventLogStore(Alpine) {
    Alpine.store('eventLog', {
        entries: loadFromStorage(),

        /**
         * Add a new entry to the log.
         * @param {string} action       Short action key (e.g. 'server_start')
         * @param {string} description  Human-readable description
         * @param {string} outcome      'success' | 'failure'
         */
        add(action, description, outcome = 'success') {
            const entry = {
                action,
                description,
                outcome,
                ts: new Date().toISOString(),
            };
            this.entries.unshift(entry);
            // Cap at MAX_ENTRIES — evict oldest
            if (this.entries.length > MAX_ENTRIES) {
                this.entries.pop();
            }
            saveToStorage(this.entries);
        },

        /** Clear all entries from memory and sessionStorage. */
        clear() {
            this.entries = [];
            try {
                sessionStorage.removeItem(STORAGE_KEY);
            } catch (e) {}
        },

        /** Total count of entries. */
        get count() {
            return this.entries.length;
        },

        /** Entries with outcome === 'failure'. */
        get failures() {
            return this.entries.filter(e => e.outcome === 'failure');
        },
    });
}

/**
 * Global helper — called from Livewire $dispatch listener in app.js.
 * Usage: window.addEventListener('log-event', handleLogEvent)
 */
export function handleLogEvent(event) {
    const { action, description, outcome } = event.detail ?? {};
    if (action && window.Alpine) {
        window.Alpine.store('eventLog').add(action, description ?? action, outcome ?? 'success');
    }
}

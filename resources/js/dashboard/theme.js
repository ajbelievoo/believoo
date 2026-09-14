/**
 * BelieVoo Dashboard — Theme Engine
 * Alpine.js global store for three-theme system.
 * Persists selection to localStorage; applies data-theme on <html>.
 * Requirements: 7.1–7.7, 14.6
 */

const THEMES = ['midnight-onyx', 'frost-white', 'believoo-signature'];
const STORAGE_KEY = 'believoo_theme';

function readStoredTheme() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        return THEMES.includes(stored) ? stored : 'midnight-onyx';
    } catch (e) {
        return 'midnight-onyx';
    }
}

function applyTheme(name) {
    document.documentElement.setAttribute('data-theme', name);
}

// Apply theme immediately (before Alpine boots) to avoid flash
applyTheme(readStoredTheme());

export function registerThemeStore(Alpine) {
    Alpine.store('theme', {
        current: readStoredTheme(),

        /**
         * Switch to a named theme and persist the choice.
         * @param {string} name  One of: midnight-onyx | frost-white | believoo-signature
         */
        set(name) {
            if (!THEMES.includes(name)) return;
            this.current = name;
            applyTheme(name);
            try {
                localStorage.setItem(STORAGE_KEY, name);
            } catch (e) {
                // localStorage unavailable — theme applied for this session only
            }
        },

        /** Re-apply the stored theme (called on alpine:init). */
        init() {
            applyTheme(this.current);
        },

        /** Human-readable label for each theme. */
        label(name) {
            const labels = {
                'midnight-onyx':       'Midnight Onyx',
                'frost-white':         'Frost White',
                'believoo-signature':  'BelieVoo Signature',
            };
            return labels[name] ?? name;
        },

        /** Icon class for each theme. */
        icon(name) {
            const icons = {
                'midnight-onyx':       'fa-moon',
                'frost-white':         'fa-snowflake',
                'believoo-signature':  'fa-star',
            };
            return icons[name] ?? 'fa-circle';
        },

        themes: THEMES,
    });
}

import './bootstrap';
import { registerThemeStore } from './dashboard/theme.js';
import { registerEventLogStore } from './dashboard/event-log.js';

// Notification Sound Logic
window.addEventListener('play-notification-sound', () => {
    const audio = new Audio('/sounds/notification.mp3');
    audio.play().catch(e => console.log('Audio play failed:', e));
});

// Alpine plugins and directives — registered BEFORE Livewire starts Alpine
document.addEventListener('alpine:init', () => {
    // ── BelieVoo Luxury Dashboard Stores ──────────────────
    registerThemeStore(window.Alpine);
    registerEventLogStore(window.Alpine);
    // ──────────────────────────────────────────────────────

    // Lottie directive (lazy-load the library only when used)
    window.Alpine.directive('lottie', (el, { expression }, { evaluateLater, effect }) => {
        const getOptions = evaluateLater(expression);
        effect(() => {
            getOptions((options) => {
                import('lottie-web').then(({ default: lottie }) => {
                    lottie.loadAnimation({
                        container: el,
                        renderer: 'svg',
                        loop: options.loop ?? true,
                        autoplay: options.autoplay ?? true,
                        path: options.path,
                    });
                });
            });
        });
    });

    // DNS Manager Alpine component — registered globally so Livewire morph doesn't lose scope
    window.Alpine.data('dnsManager', () => ({
        showAddForm: false,
        editId: null,
        formType: 'A',
        formName: '',
        formValue: '',
        formPriority: '',
        formTtl: 3600,
        formError: '',

        openAdd() {
            this.editId = null;
            this.formType = 'A';
            this.formName = '';
            this.formValue = '';
            this.formPriority = '';
            this.formTtl = 3600;
            this.formError = '';
            this.showAddForm = true;
        },

        openEdit(record) {
            this.editId = record.id;
            this.formType = record.record_type;
            this.formName = record.name;
            this.formValue = record.value;
            this.formPriority = record.priority ?? '';
            this.formTtl = record.ttl ?? 3600;
            this.formError = '';
            this.showAddForm = true;
        },

        valueLabelFor(type) {
            const labels = { A: 'IPv4 Address', AAAA: 'IPv6 Address', MX: 'Mail Server', CNAME: 'Target Hostname', TXT: 'TXT Value', NS: 'Nameserver', SRV: 'Service Target', CAA: 'CAA Value' };
            return labels[type] || 'Value';
        },

        valuePlaceholderFor(type) {
            const ph = { A: '15.235.170.222', AAAA: '2001:db8::1', MX: 'mail.example.com', CNAME: 'target.example.com', TXT: 'v=spf1 include:believoo.com ~all', NS: 'ns1.believoo.com', SRV: '10 20 443 target.example.com', CAA: '0 issue "letsencrypt.org"' };
            return ph[type] || 'value';
        },

        needsPriority(type) {
            return ['MX', 'SRV', 'CAA'].includes(type);
        },

        async save(domainId) {
            this.formError = '';
            if (!this.formName.trim()) { this.formError = 'Hostname is required.'; return; }
            if (!this.formValue.trim()) { this.formError = 'Value is required.'; return; }
            if (this.formType === 'A' && !/^(\d{1,3}\.){3}\d{1,3}$/.test(this.formValue.trim())) {
                this.formError = 'Invalid IPv4 address.'; return;
            }
            const payload = {
                user_domain_id: domainId,
                record_type: this.formType,
                name: this.formName.trim(),
                value: this.formValue.trim(),
                ttl: parseInt(this.formTtl) || 3600,
                priority: this.formPriority ? parseInt(this.formPriority) : null,
            };
            try {
                const url = this.editId ? `/api/dns/records/${this.editId}` : '/api/dns/records';
                const method = this.editId ? 'PUT' : 'POST';
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (data.success) {
                    this.showAddForm = false;
                    // Refresh Livewire component to reload DNS records
                    window.Livewire?.dispatch('refresh') || window.livewire?.emit('refresh');
                    // Also call loadDnsRecords if available
                    const wire = document.querySelector('[wire\\:id]')?.__livewire;
                    if (wire) wire.call('loadDnsRecords');
                } else {
                    this.formError = data.message || 'Failed to save record.';
                }
            } catch (e) {
                this.formError = 'Network error: ' + e.message;
            }
        },
    }));
});

// Global DNS manager helpers (used by onclick to avoid Alpine scope issues)
window.dnsManagerEdit = function(record) {
    const el = document.querySelector('[x-data="dnsManager"]');
    if (el && el._x_dataStack) {
        const data = el._x_dataStack[0];
        if (data && data.openEdit) data.openEdit(record);
    }
};

window.dnsManagerDelete = async function(id) {
    if (!confirm('Delete this DNS record?')) return;
    try {
        const res = await fetch(`/api/dns/records/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success) {
            const wire = document.querySelector('[wire\\:id]')?.__livewire;
            if (wire) wire.call('loadDnsRecords');
            else window.location.reload();
        }
    } catch (e) {
        alert('Delete failed: ' + e.message);
    }
};

// ── BelieVoo Event Log — listen for Livewire dispatched events ──────────────
import { handleLogEvent } from './dashboard/event-log.js';
window.addEventListener('log-event', handleLogEvent);

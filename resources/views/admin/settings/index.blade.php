@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<div class="page-header">
    <h1 class="page-title">Settings</h1>
    <p class="page-subtitle">Manage all your application settings</p>
</div>

<!-- Tabs Navigation -->
<div class="settings-tabs mb-6">
        <button type="button" onclick="showTab('general')" class="settings-tab active" data-tab="general">
            <i class="fas fa-globe"></i> General
        </button>
        <button type="button" onclick="showTab('contact')" class="settings-tab" data-tab="contact">
            <i class="fas fa-address-book"></i> Contact
        </button>
        <button type="button" onclick="showTab('social')" class="settings-tab" data-tab="social">
            <i class="fas fa-share-alt"></i> Social Media
        </button>
        <button type="button" onclick="showTab('seo')" class="settings-tab" data-tab="seo">
            <i class="fas fa-search"></i> SEO
        </button>
        <button type="button" onclick="showTab('payment')" class="settings-tab" data-tab="payment">
            <i class="fas fa-credit-card"></i> Payment
        </button>
        <button type="button" onclick="showTab('agora')" class="settings-tab" data-tab="agora">
            <i class="fas fa-video"></i> Agora
        </button>
        <button type="button" onclick="showTab('push')" class="settings-tab" data-tab="push">
            <i class="fas fa-bell"></i> Push
        </button>
        <button type="button" onclick="showTab('bconnect')" class="settings-tab" data-tab="bconnect">
            <i class="fas fa-rocket"></i> Bmydesk
        </button>
        <button type="button" onclick="showTab('email')" class="settings-tab" data-tab="email">
            <i class="fas fa-envelope"></i> Email/SMTP
        </button>
        <button type="button" onclick="showTab('appearance')" class="settings-tab" data-tab="appearance">
            <i class="fas fa-paint-brush"></i> Appearance
        </button>
        <button type="button" onclick="showTab('maintenance')" class="settings-tab" data-tab="maintenance">
            <i class="fas fa-tools"></i> Maintenance
        </button>
        <button type="button" onclick="showTab('security')" class="settings-tab" data-tab="security">
            <i class="fas fa-shield-alt"></i> Security
        </button>
        <button type="button" onclick="showTab('notifications')" class="settings-tab" data-tab="notifications">
            <i class="fas fa-bell"></i> Notifications
        </button>
        <button type="button" onclick="showTab('backup')" class="settings-tab" data-tab="backup">
            <i class="fas fa-database"></i> Backup
        </button>
        <button type="button" onclick="showTab('server')" class="settings-tab" data-tab="server">
            <i class="fas fa-server"></i> Server Management
        </button>
        <button type="button" onclick="showTab('ghc')" class="settings-tab" data-tab="ghc">
            <i class="fas fa-server"></i> GHC Site
        </button>
        <button type="button" onclick="showTab('ai')" class="settings-tab" data-tab="ai">
            <i class="fas fa-robot"></i> AI Assistant
        </button>
        <button type="button" onclick="showTab('integrations')" class="settings-tab" data-tab="integrations">
            <i class="fas fa-plug"></i> Integrations
        </button>
</div>

<!-- Settings Content -->
<div class="card">
        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- General Settings -->
            <div id="general" class="settings-content">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">General Settings</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Basic site configuration</p>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Site Name *</label>
                            <input type="text" name="site_name" value="{{ $settings['site_name'] ?? config('app.name') }}" required
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Site Tagline</label>
                            <input type="text" name="site_tagline" value="{{ $settings['site_tagline'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Site Description</label>
                        <textarea name="site_description" rows="3"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; resize: vertical; color: var(--text-primary);">{{ $settings['site_description'] ?? '' }}</textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Logo</label>
                            <input type="file" name="logo" accept="image/*"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            @if(isset($settings['logo']))
                                <img src="{{ asset('storage/'.$settings['logo']) }}" loading="lazy" style="max-height: 40px; margin-top: 10px;">
                            @endif
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Favicon</label>
                            <input type="file" name="favicon" accept="image/*"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            @if(isset($settings['favicon']))
                                <img src="{{ asset('storage/'.$settings['favicon']) }}" loading="lazy" style="max-height: 32px; margin-top: 10px;">
                            @endif
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Dark Logo</label>
                        <input type="file" name="dark_logo" accept="image/*"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        @if(isset($settings['dark_logo']))
                            <img src="{{ asset('storage/'.$settings['dark_logo']) }}" loading="lazy" style="max-height: 40px; margin-top: 10px;">
                        @endif
                    </div>

                    <!-- Announcement Bar -->
                    <div style="margin-top: 28px; padding-top: 24px; border-top: 1px solid var(--border-color);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <div>
                                <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">
                                    <i class="fas fa-bullhorn" style="color: var(--accent); margin-right: 6px;"></i>Announcement Bar
                                </h4>
                                <p style="font-size: 0.8rem; color: var(--text-muted);">Website ke sabse top pe dikhne wala promo banner</p>
                            </div>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: var(--text-primary); white-space: nowrap;">
                                <input type="checkbox" name="announcement_enabled" value="1" {{ ($settings['announcement_enabled'] ?? '1') === '1' ? 'checked' : '' }}
                                    style="width: 16px; height: 16px; accent-color: var(--accent);">
                                Enabled
                            </label>
                        </div>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Announcement Text</label>
                                <input type="text" name="announcement_text" value="{{ $settings['announcement_text'] ?? '' }}" placeholder="e.g. Limited Offer: 50% off VPS hosting this week"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Link URL (optional)</label>
                                <input type="text" name="announcement_url" value="{{ $settings['announcement_url'] ?? '' }}" placeholder="/vps-hosting ya https://..."
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div style="margin-top: 16px;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Countdown Ends At <span style="font-weight: 400; color: var(--text-muted);">(optional)</span></label>
                            <input type="datetime-local" name="announcement_end_at"
                                value="{{ !empty($settings['announcement_end_at'] ?? '') ? \Illuminate\Support\Carbon::parse($settings['announcement_end_at'])->format('Y-m-d\TH:i') : '' }}"
                                style="width: 100%; max-width: 320px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">Set karne pe bar me live countdown dikhega ("2d 04:15:33 left"). Time khatam hote hi bar apne aap hide ho jayega.</p>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">Text khali rakhne pe ya Enabled hatane pe bar hide ho jayega. Link dene pe text clickable ban jayega.</p>
                    </div>
                </div>
            </div>

            <!-- GHC Site Settings -->
            <div id="ghc" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">
                        <i class="fas fa-server" style="color: var(--accent); margin-right: 8px;"></i>GHC Site Settings
                    </h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">ghc.believoo.com (Go Host Cloud) ki branding — yahan se control hogi</p>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Site Name</label>
                            <input type="text" name="ghc_site_name" value="{{ $settings['ghc_site_name'] ?? 'GHC' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Tagline</label>
                            <input type="text" name="ghc_tagline" value="{{ $settings['ghc_tagline'] ?? 'Go Host Cloud' }}" placeholder="e.g. Go Host Cloud"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Meta Title (SEO)</label>
                            <input type="text" name="ghc_meta_title" value="{{ $settings['ghc_meta_title'] ?? '' }}" placeholder="GHC - Enterprise Cloud Hosting"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Support Email</label>
                            <input type="email" name="ghc_support_email" value="{{ $settings['ghc_support_email'] ?? '' }}" placeholder="support@ghc.believoo.com"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Meta Description (SEO)</label>
                        <textarea name="ghc_meta_description" rows="2" placeholder="Fully automated VPS & Server infrastructure platform"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; resize: vertical; color: var(--text-primary);">{{ $settings['ghc_meta_description'] ?? '' }}</textarea>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Meta Keywords <span style="font-weight: 400; color: var(--text-muted);">(comma separated)</span></label>
                        <textarea name="ghc_meta_keywords" rows="2" placeholder="vps hosting india, dedicated servers, cloud hosting..."
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; resize: vertical; color: var(--text-primary);">{{ $settings['ghc_meta_keywords'] ?? '' }}</textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Brand Color</label>
                            <input type="color" name="ghc_primary_color" value="{{ $settings['ghc_primary_color'] ?? '#00f0ff' }}"
                                style="width: 100%; height: 48px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 4px; cursor: pointer;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Logo</label>
                            <input type="file" name="ghc_logo" accept="image/*"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            @if(isset($settings['ghc_logo']))
                                <img src="{{ asset('storage/'.$settings['ghc_logo']) }}" loading="lazy" style="max-height: 40px; margin-top: 10px;">
                            @endif
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Favicon</label>
                            <input type="file" name="ghc_favicon" accept="image/*"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            @if(isset($settings['ghc_favicon']))
                                <img src="{{ asset('storage/'.$settings['ghc_favicon']) }}" loading="lazy" style="max-height: 32px; margin-top: 10px;">
                            @endif
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Open Graph Image</label>
                            <input type="file" name="ghc_og_image" accept="image/*"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            @if(isset($settings['ghc_og_image']))
                                <img src="{{ asset('storage/'.$settings['ghc_og_image']) }}" loading="lazy" style="max-height: 60px; margin-top: 10px;">
                            @endif
                        </div>
                    </div>
                    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-bottom: 12px;"><i class="fas fa-heading" style="color: var(--accent); margin-right: 6px;"></i>Hero Section Text</h4>
                        <div style="display: grid; gap: 16px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Hero Badge</label>
                                <input type="text" name="ghc_hero_badge" value="{{ $settings['ghc_hero_badge'] ?? '' }}" placeholder="GHC-powered cloud marketplace"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Hero Heading</label>
                                <input type="text" name="ghc_hero_title" value="{{ $settings['ghc_hero_title'] ?? '' }}" placeholder="Cloud, hosting and servers configured like a real provider."
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Hero Subtitle</label>
                                <textarea name="ghc_hero_subtitle" rows="2" placeholder="Browse real synced plans by category..."
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; resize: vertical; color: var(--text-primary);">{{ $settings['ghc_hero_subtitle'] ?? '' }}</textarea>
                            </div>
                        </div>
                    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-bottom: 12px;"><i class="fas fa-bullhorn" style="color: var(--accent); margin-right: 6px;"></i>GHC Announcement Bar</h4>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Announcement Text</label>
                                <input type="text" name="ghc_announce_text" value="{{ $settings['ghc_announce_text'] ?? '' }}" placeholder="e.g. Launch Offer: Flat 40% off NVMe VPS"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Link URL (optional)</label>
                                <input type="text" name="ghc_announce_url" value="{{ $settings['ghc_announce_url'] ?? '' }}" placeholder="/vps ya https://..."
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">Text khali rakhne pe GHC pe bar nahi dikhega.</p>
                    </div>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 16px;">Ye settings GHC site pe live apply hoti hain — koi rebuild nahi chahiye.</p>
                    </div>
                </div>
            </div>

            <!-- Contact Settings -->
            <div id="contact" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Contact Information</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">How customers can reach you</p>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Contact Email *</label>
                            <input type="email" name="contact_email" value="{{ $settings['contact_email'] ?? '' }}" required
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Support Email</label>
                            <input type="email" name="support_email" value="{{ $settings['support_email'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Contact Phone</label>
                            <input type="text" name="contact_phone" value="{{ $settings['contact_phone'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">WhatsApp Number</label>
                            <input type="text" name="whatsapp" value="{{ $settings['whatsapp'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Office Address</label>
                        <textarea name="address" rows="2"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; resize: vertical; color: var(--text-primary);">{{ $settings['address'] ?? '' }}</textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">City</label>
                            <input type="text" name="business_city" value="{{ $settings['business_city'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">State</label>
                            <input type="text" name="business_state" value="{{ $settings['business_state'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Postal Code</label>
                            <input type="text" name="business_postal" value="{{ $settings['business_postal'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Country</label>
                            <input type="text" name="business_country" value="{{ $settings['business_country'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Latitude</label>
                            <input type="text" name="business_latitude" value="{{ $settings['business_latitude'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Longitude</label>
                            <input type="text" name="business_longitude" value="{{ $settings['business_longitude'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Google Maps Embed</label>
                        <textarea name="google_maps" rows="2"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; resize: vertical; color: var(--text-primary);">{{ $settings['google_maps'] ?? '' }}</textarea>
                    </div>

                    <!-- Company Legal / Registration Details -->
                    <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color);">
                        <div style="margin-bottom: 20px;">
                            <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);"><i class="fas fa-building" style="margin-right: 8px;"></i>Company Legal Details</h4>
                            <p style="font-size: 0.8rem; color: var(--text-muted);">Shown in the site footer, About page and on invoices</p>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Legal Company Name</label>
                                <input type="text" name="company_legal_name" value="{{ $settings['company_legal_name'] ?? '' }}" placeholder="BELIEVOO PRIVATE LIMITED"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">CIN (Corporate Identity Number)</label>
                                <input type="text" name="company_cin" value="{{ $settings['company_cin'] ?? '' }}" placeholder="U63119UP2026PTC252696"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">PAN</label>
                                <input type="text" name="company_pan" value="{{ $settings['company_pan'] ?? '' }}" placeholder="AAPCB1563P"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">TAN</label>
                                <input type="text" name="company_tan" value="{{ $settings['company_tan'] ?? '' }}" placeholder="KBNB14221E"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Date of Incorporation</label>
                                <input type="text" name="company_incorporation_date" value="{{ $settings['company_incorporation_date'] ?? '' }}" placeholder="16 September 2026"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">GST Number (optional)</label>
                                <input type="text" name="gst_number" value="{{ $settings['gst_number'] ?? '' }}" placeholder="Leave blank if not registered"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Registered Office Address</label>
                            <textarea name="company_registered_office" rows="2" placeholder="As per Certificate of Incorporation"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; resize: vertical; color: var(--text-primary);">{{ $settings['company_registered_office'] ?? '' }}</textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Invoice Email</label>
                                <input type="email" name="company_email" value="{{ $settings['company_email'] ?? '' }}" placeholder="billing@believoo.com"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Invoice Phone</label>
                                <input type="text" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}" placeholder="+91-XXXXXXXXXX"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Company Website</label>
                                <input type="text" name="company_website" value="{{ $settings['company_website'] ?? '' }}" placeholder="https://believoo.com"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media -->
            <div id="social" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Social Media Links</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Connect with your audience</p>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);"><i class="fab fa-facebook" style="margin-right: 8px;"></i>Facebook</label>
                            <input type="url" name="facebook" value="{{ $settings['facebook'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);"><i class="fab fa-twitter" style="margin-right: 8px;"></i>Twitter / X</label>
                            <input type="url" name="twitter" value="{{ $settings['twitter'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);"><i class="fab fa-instagram" style="margin-right: 8px;"></i>Instagram</label>
                            <input type="url" name="instagram" value="{{ $settings['instagram'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);"><i class="fab fa-linkedin" style="margin-right: 8px;"></i>LinkedIn</label>
                            <input type="url" name="linkedin" value="{{ $settings['linkedin'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);"><i class="fab fa-youtube" style="margin-right: 8px;"></i>YouTube</label>
                            <input type="url" name="youtube" value="{{ $settings['youtube'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);"><i class="fab fa-github" style="margin-right: 8px;"></i>GitHub</label>
                            <input type="url" name="github" value="{{ $settings['github'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>

                    <!-- Social Login OAuth Configuration -->
                    <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color);">
                        <div style="margin-bottom: 20px;">
                            <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 4px; color: var(--text-primary);"><i class="fas fa-key" style="margin-right: 8px;"></i>Social Login API Keys</h4>
                            <p style="font-size: 0.875rem; color: var(--text-muted);">OAuth credentials for social login</p>
                        </div>

                        <!-- Google OAuth -->
                        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                <h5 style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);"><i class="fab fa-google" style="margin-right: 8px; color: var(--accent);"></i>Google Login</h5>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="google_login_enabled" value="1" {{ ($settings['google_login_enabled'] ?? '1') == '1' ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--accent);">
                                    <span style="font-size: 0.85rem; color: var(--text-secondary);">Enable</span>
                                </label>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Client ID</label>
                                    <input type="text" name="google_client_id" value="{{ $settings['google_client_id'] ?? '' }}" placeholder="xxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com"
                                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; font-size: 0.85rem; color: var(--text-primary);">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Client Secret</label>
                                    <input type="password" name="google_client_secret" value="{{ $settings['google_client_secret'] ?? '' }}" placeholder="GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx"
                                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; font-size: 0.85rem; color: var(--text-primary);">
                                </div>
                            </div>
                        </div>

                        <!-- Facebook OAuth -->
                        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                <h5 style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);"><i class="fab fa-facebook" style="margin-right: 8px; color: var(--accent);"></i>Facebook Login</h5>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="facebook_login_enabled" value="1" {{ ($settings['facebook_login_enabled'] ?? '0') == '1' ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--accent);">
                                    <span style="font-size: 0.85rem; color: var(--text-secondary);">Enable</span>
                                </label>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">App ID</label>
                                    <input type="text" name="facebook_client_id" value="{{ $settings['facebook_client_id'] ?? '' }}" placeholder="xxxxxxxxxxxxxxx"
                                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; font-size: 0.85rem; color: var(--text-primary);">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">App Secret</label>
                                    <input type="password" name="facebook_client_secret" value="{{ $settings['facebook_client_secret'] ?? '' }}" placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; font-size: 0.85rem; color: var(--text-primary);">
                                </div>
                            </div>
                        </div>

                        <!-- Twitter OAuth -->
                        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                <h5 style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);"><i class="fab fa-twitter" style="margin-right: 8px; color: var(--accent);"></i>Twitter / X Login</h5>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="twitter_login_enabled" value="1" {{ ($settings['twitter_login_enabled'] ?? '0') == '1' ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--accent);">
                                    <span style="font-size: 0.85rem; color: var(--text-secondary);">Enable</span>
                                </label>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">API Key</label>
                                    <input type="text" name="twitter_client_id" value="{{ $settings['twitter_client_id'] ?? '' }}" placeholder="xxxxxxxxxxxxxxxxxxxxxxxxx"
                                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; font-size: 0.85rem; color: var(--text-primary);">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">API Secret</label>
                                    <input type="password" name="twitter_client_secret" value="{{ $settings['twitter_client_secret'] ?? '' }}" placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; font-size: 0.85rem; color: var(--text-primary);">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO Settings -->
            <div id="seo" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">SEO Settings</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Search engine optimization</p>
                </div>
                <div style="padding: 24px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ $settings['meta_title'] ?? '' }}"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Meta Description</label>
                        <textarea name="meta_description" rows="2"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; resize: vertical; color: var(--text-primary);">{{ $settings['meta_description'] ?? '' }}</textarea>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="{{ $settings['meta_keywords'] ?? '' }}"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Google Analytics ID (GA4)</label>
                        <input type="text" name="google_analytics" value="{{ $settings['google_analytics'] ?? '' }}"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Google Search Console Verification Code</label>
                        <input type="text" name="google_site_verification" value="{{ $settings['google_site_verification'] ?? '' }}"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Bing Webmaster Verification Code</label>
                        <input type="text" name="bing_site_verification" value="{{ $settings['bing_site_verification'] ?? '' }}"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                    </div>
                </div>
            </div>

            <!-- Payment Settings -->
            <div id="payment" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Payment Settings</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Configure payment gateways</p>
                </div>
                <div style="padding: 24px;">
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; background: var(--bg-tertiary);">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--accent);"><i class="fas fa-credit-card" style="margin-right: 8px;"></i>Razorpay</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Key ID</label>
                                <input type="text" name="razorpay_key_id" value="{{ $settings['razorpay_key_id'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Key Secret</label>
                                <input type="password" name="razorpay_key_secret" value="{{ $settings['razorpay_key_secret'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div style="margin-top: 16px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; align-items: end;">
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="razorpay_enabled" value="1" {{ ($settings['razorpay_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">Enable Razorpay</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Mode</label>
                                <select name="razorpay_mode" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                    <option value="test" {{ ($settings['razorpay_mode'] ?? 'test') == 'test' ? 'selected' : '' }}>Test (rzp_test_*)</option>
                                    <option value="live" {{ ($settings['razorpay_mode'] ?? 'test') == 'live' ? 'selected' : '' }}>Live (rzp_live_*)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-primary);"><i class="fas fa-money-bill" style="margin-right: 8px;"></i>Cashfree</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">App ID</label>
                                <input type="text" name="cashfree_app_id" value="{{ $settings['cashfree_app_id'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Secret Key</label>
                                <input type="password" name="cashfree_secret_key" value="{{ $settings['cashfree_secret_key'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div style="margin-top: 16px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="cashfree_enabled" value="1" {{ ($settings['cashfree_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">Enable Cashfree</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Mode</label>
                                <select name="cashfree_mode" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                    <option value="sandbox" {{ ($settings['cashfree_mode'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox (Test)</option>
                                    <option value="production" {{ ($settings['cashfree_mode'] ?? 'sandbox') == 'production' ? 'selected' : '' }}>Production (Live)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- PayPal Settings --}}
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; background: var(--bg-tertiary); margin-top: 20px;">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--accent);"><i class="fab fa-paypal" style="margin-right: 8px;"></i>PayPal</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Client ID</label>
                                <input type="text" name="paypal_client_id" value="{{ $settings['paypal_client_id'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Client Secret</label>
                                <input type="password" name="paypal_client_secret" value="{{ $settings['paypal_client_secret'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div style="margin-top: 16px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="paypal_enabled" value="1" {{ ($settings['paypal_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">Enable PayPal</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Mode</label>
                                <select name="paypal_mode" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                    <option value="sandbox" {{ ($settings['paypal_mode'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox (Test)</option>
                                    <option value="production" {{ ($settings['paypal_mode'] ?? 'sandbox') == 'production' ? 'selected' : '' }}>Production (Live)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Stripe Settings --}}
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; background: var(--bg-tertiary);">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--accent);"><i class="fab fa-stripe" style="margin-right: 8px;"></i>Stripe</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Publishable Key</label>
                                <input type="text" name="stripe_key" value="{{ $settings['stripe_key'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Secret Key</label>
                                <input type="password" name="stripe_secret" value="{{ $settings['stripe_secret'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div style="margin-top: 16px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="stripe_enabled" value="1" {{ ($settings['stripe_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                <span style="font-size: 0.875rem; color: var(--text-primary);">Enable Stripe</span>
                            </label>
                        </div>
                    </div>

                    {{-- PayU Settings --}}
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; background: var(--bg-tertiary);">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--warning);"><i class="fas fa-credit-card" style="margin-right: 8px;"></i>PayU</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Key</label>
                                <input type="text" name="payu_key" value="{{ $settings['payu_key'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Salt</label>
                                <input type="password" name="payu_salt" value="{{ $settings['payu_salt'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div style="margin-top: 16px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="payu_enabled" value="1" {{ ($settings['payu_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">Enable PayU</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Mode</label>
                                <select name="payu_mode" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                    <option value="sandbox" {{ ($settings['payu_mode'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox (Test)</option>
                                    <option value="production" {{ ($settings['payu_mode'] ?? 'sandbox') == 'production' ? 'selected' : '' }}>Production (Live)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Currency</label>
                            <input type="text" name="currency" value="{{ $settings['currency'] ?? 'INR' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Currency Symbol</label>
                            <input type="text" name="currency_symbol" value="{{ $settings['currency_symbol'] ?? '$' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">GST Rate (%)</label>
                            <input type="number" name="gst_rate" value="{{ $settings['gst_rate'] ?? '18' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agora Settings -->
            <div id="agora" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Agora Video/Voice Settings</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Configure Agora.io for Bmydesk video conferences and audio calls.</p>
                </div>
                <div style="padding: 24px;">
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; background: var(--bg-tertiary);">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--accent);"><i class="fas fa-video" style="margin-right: 8px;"></i>Agora</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">App ID</label>
                                <input type="text" name="agora_app_id" value="{{ $settings['agora_app_id'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">App Certificate</label>
                                <input type="password" name="agora_app_certificate" value="{{ $settings['agora_app_certificate'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div style="margin-top: 16px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="agora_enabled" value="1" {{ ($settings['agora_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                <span style="font-size: 0.875rem; color: var(--text-primary);">Enable Agora for Bmydesk</span>
                            </label>
                        </div>
                        <div style="margin-top: 16px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Customer ID</label>
                                <input type="text" name="agora_customer_id" value="{{ $settings['agora_customer_id'] ?? '' }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Customer Secret</label>
                                <input type="password" name="agora_customer_secret" value="{{ $settings['agora_customer_secret'] ?? '' }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Recording Bucket</label>
                                <input type="text" name="agora_recording_bucket" value="{{ $settings['agora_recording_bucket'] ?? '' }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Recording Region</label>
                                <input type="text" name="agora_recording_region" value="{{ $settings['agora_recording_region'] ?? '' }}" placeholder="ap-south-1" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Recording Access Key</label>
                                <input type="text" name="agora_recording_access_key" value="{{ $settings['agora_recording_access_key'] ?? '' }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Recording Secret Key</label>
                                <input type="password" name="agora_recording_secret_key" value="{{ $settings['agora_recording_secret_key'] ?? '' }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Push Notification Settings -->
            <div id="push" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Push Notification Settings</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">VAPID keys for browser push on Bmydesk</p>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">VAPID Public Key</label>
                            <input type="text" name="vapid_public_key" value="{{ $settings['vapid_public_key'] ?? '' }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">VAPID Private Key</label>
                            <input type="password" name="vapid_private_key" value="{{ $settings['vapid_private_key'] ?? '' }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bmydesk Brand & SEO -->
            <div id="bconnect" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Bmydesk Brand & SEO</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Logo, title, favicon, meta tags and Google verification for bc.believoo.com</p>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Product Name</label>
                            <input type="text" name="bconnect_name" value="{{ $settings['bconnect_name'] ?? 'Bmydesk' }}" placeholder="Bmydesk" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Brand Color</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="color" name="bconnect_brand_color" value="{{ $settings['bconnect_brand_color'] ?? '#7c3aed' }}" style="width: 52px; height: 44px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-tertiary); padding: 4px; cursor: pointer;">
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Accent color used across the Bmydesk site & workspace</span>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Bmydesk Logo</label>
                            @if(!empty($settings['bconnect_logo']))<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings['bconnect_logo']) }}" style="display: block; max-height: 48px; max-width: 220px; width: auto; margin-bottom: 10px; border-radius: 8px; background: rgba(148,163,184,0.08); padding: 4px;" alt="Bmydesk Logo">@endif
                            <input type="file" name="bconnect_logo" accept="image/*" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px; font-size: 0.85rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Bmydesk Favicon</label>
                            @if(!empty($settings['bconnect_favicon']))<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings['bconnect_favicon']) }}" style="display: block; max-height: 32px; max-width: 32px; width: auto; margin-bottom: 10px; border-radius: 6px; background: rgba(148,163,184,0.08); padding: 2px;" alt="Bmydesk Favicon">@endif
                            <input type="file" name="bconnect_favicon" accept="image/*" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px; font-size: 0.85rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Open Graph Image</label>
                            @if(!empty($settings['bconnect_og_image']))<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings['bconnect_og_image']) }}" style="display: block; max-height: 110px; max-width: 220px; width: auto; margin-bottom: 10px; border-radius: 8px; background: rgba(148,163,184,0.08); padding: 4px;" alt="OG Image">@endif
                            <input type="file" name="bconnect_og_image" accept="image/*" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px; font-size: 0.85rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Page Title</label>
                            <input type="text" name="bconnect_title" value="{{ $settings['bconnect_title'] ?? '' }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Google Analytics ID</label>
                            <input type="text" name="bconnect_google_analytics" value="{{ $settings['bconnect_google_analytics'] ?? '' }}" placeholder="G-XXXXXXXXXX" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Google Site Verification</label>
                            <input type="text" name="bconnect_google_site_verification" value="{{ $settings['bconnect_google_site_verification'] ?? '' }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Meta Description</label>
                        <textarea name="bconnect_description" rows="2" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary); resize: vertical;">{{ $settings['bconnect_description'] ?? '' }}</textarea>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Meta Keywords</label>
                        <textarea name="bconnect_keywords" rows="2" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary); resize: vertical;">{{ $settings['bconnect_keywords'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Footer Text</label>
                        <textarea name="bconnect_footer_text" rows="2" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary); resize: vertical;">{{ $settings['bconnect_footer_text'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Email/SMTP Settings -->
            <div id="email" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Email/SMTP Settings</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Configure email notifications</p>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">SMTP Host</label>
                            <input type="text" name="smtp_host" value="{{ $settings['smtp_host'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">SMTP Port</label>
                            <input type="number" name="smtp_port" value="{{ $settings['smtp_port'] ?? '587' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">SMTP Username</label>
                            <input type="text" name="smtp_username" value="{{ $settings['smtp_username'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">SMTP Password</label>
                            <input type="password" name="smtp_password" value="{{ $settings['smtp_password'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="smtp_encryption" value="tls" {{ ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'checked' : '' }}>
                            <span style="font-size: 0.875rem;">Use TLS Encryption</span>
                        </label>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">From Name</label>
                            <input type="text" name="mail_from_name" value="{{ $settings['mail_from_name'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">From Email</label>
                            <input type="email" name="mail_from_address" value="{{ $settings['mail_from_address'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appearance -->
            <div id="appearance" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Appearance</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Customize the look and feel</p>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Primary Color</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="color" name="primary_color" value="{{ $settings['primary_color'] ?? '#00b7ff' }}" style="width: 50px; height: 40px; border: none; border-radius: 8px; cursor: pointer;">
                                <input type="text" value="{{ $settings['primary_color'] ?? '#00b7ff' }}" style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);" readonly>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Default Theme</label>
                            <select name="default_theme" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <option value="dark" {{ ($settings['default_theme'] ?? 'dark') == 'dark' ? 'selected' : '' }}>Dark</option>
                                <option value="light" {{ ($settings['default_theme'] ?? 'dark') == 'light' ? 'selected' : '' }}>Light</option>
                                <option value="auto" {{ ($settings['default_theme'] ?? 'dark') == 'auto' ? 'selected' : '' }}>Auto (System)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Enable Theme Toggle</label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="theme_toggle_enabled" value="1" {{ ($settings['theme_toggle_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                <span style="font-size: 0.875rem; color: var(--text-primary);">Show theme toggle button</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Custom CSS</label>
                        <textarea name="custom_css" rows="4"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; resize: vertical; font-family: monospace; color: var(--text-primary);">{{ $settings['custom_css'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Maintenance -->
            <div id="maintenance" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Maintenance Mode</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Take site offline for maintenance</p>
                </div>
                <div style="padding: 24px;">
                    <div style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
                        <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-bottom: 16px;">
                            <input type="checkbox" name="maintenance_mode" value="1" {{ ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' }}>
                            <span style="font-size: 1rem; font-weight: 600; color: var(--danger);">Enable Maintenance Mode</span>
                        </label>
                        <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 16px;">When enabled, only admins can access the site. All other users will see a maintenance message.</p>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Maintenance Message</label>
                            <textarea name="maintenance_message" rows="2"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; resize: vertical; color: var(--text-primary);">{{ $settings['maintenance_message'] ?? 'We are currently performing maintenance. Please check back soon.' }}</textarea>
                        </div>
                    </div>
                </div>
                <div style="padding: 24px; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <input type="email" name="test_email" value="{{ auth()->user()->email ?? '' }}" placeholder="Test email address" style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        <a href="{{ route('admin.settings.test-mail') }}" class="btn btn-primary" style="padding: 12px 20px;" onclick="event.preventDefault(); fetch(this.href, {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:new URLSearchParams({email: document.querySelector('input[name=test_email]').value})}).then(r=>r.text()).then(t=>alert(t));"><i class="fas fa-paper-plane me-2"></i>Send Test Email</a>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div id="security" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Security Settings</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Protect your application</p>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="force_2fa" value="1" {{ ($settings['force_2fa'] ?? '0') == '1' ? 'checked' : '' }}>
                                <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">Force Two-Factor Authentication</span>
                            </label>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Require all users to enable 2FA</p>
                        </div>
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="password_strength" value="1" {{ ($settings['password_strength'] ?? '1') == '1' ? 'checked' : '' }}>
                                <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">Strong Password Required</span>
                            </label>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Minimum 8 chars with special characters</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Session Timeout (minutes)</label>
                            <input type="number" name="session_timeout" value="{{ $settings['session_timeout'] ?? '60' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Max Login Attempts</label>
                            <input type="number" name="max_login_attempts" value="{{ $settings['max_login_attempts'] ?? '5' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Allowed IP Addresses (comma separated)</label>
                            <input type="text" name="allowed_ips" value="{{ $settings['allowed_ips'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Blocked IP Addresses (comma separated)</label>
                            <input type="text" name="blocked_ips" value="{{ $settings['blocked_ips'] ?? '' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Settings -->
            <div id="notifications" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Notification Settings</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Configure system notifications</p>
                </div>
                <div style="padding: 24px;">
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-primary);">Email Notifications</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="notify_new_order" value="1" {{ ($settings['notify_new_order'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">New Order</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="notify_new_ticket" value="1" {{ ($settings['notify_new_ticket'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">New Support Ticket</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="notify_new_user" value="1" {{ ($settings['notify_new_user'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">New User Registration</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="notify_payment" value="1" {{ ($settings['notify_payment'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">Payment Received</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="notify_invoice" value="1" {{ ($settings['notify_invoice'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">Invoice Generated</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="notify_agreement" value="1" {{ ($settings['notify_agreement'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">Agreement Signed</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-primary);">SMS Notifications</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 16px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">SMS Provider</label>
                                <select name="sms_provider" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                    <option value="twilio" {{ ($settings['sms_provider'] ?? '') == 'twilio' ? 'selected' : '' }}>Twilio</option>
                                    <option value="msg91" {{ ($settings['sms_provider'] ?? '') == 'msg91' ? 'selected' : '' }}>MSG91</option>
                                    <option value="none" {{ ($settings['sms_provider'] ?? '') == 'none' ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">API Key</label>
                                <input type="password" name="sms_api_key" value="{{ $settings['sms_api_key'] ?? '' }}"
                                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="sms_new_order" value="1" {{ ($settings['sms_new_order'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">SMS on New Order</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="sms_new_ticket" value="1" {{ ($settings['sms_new_ticket'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; color: var(--text-primary);">SMS on New Ticket</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Settings -->
            <div id="backup" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);">Backup Settings</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Configure automatic backups</p>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="auto_backup" value="1" {{ ($settings['auto_backup'] ?? '0') == '1' ? 'checked' : '' }}>
                                <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">Enable Automatic Backup</span>
                            </label>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Backup Frequency</label>
                            <select name="backup_frequency" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <option value="daily" {{ ($settings['backup_frequency'] ?? 'daily') == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ ($settings['backup_frequency'] ?? 'daily') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ ($settings['backup_frequency'] ?? 'daily') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Backup Storage</label>
                            <select name="backup_storage" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <option value="local" {{ ($settings['backup_storage'] ?? 'local') == 'local' ? 'selected' : '' }}>Local Storage</option>
                                <option value="s3" {{ ($settings['backup_storage'] ?? 'local') == 's3' ? 'selected' : '' }}>AWS S3</option>
                                <option value="dropbox" {{ ($settings['backup_storage'] ?? 'local') == 'dropbox' ? 'selected' : '' }}>Dropbox</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Retention Days</label>
                            <input type="number" name="backup_retention" value="{{ $settings['backup_retention'] ?? '30' }}"
                                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-primary);">Manual Backup</h4>
                        <div style="display: flex; gap: 12px;">
                            <button type="button" onclick="alert('Backup initiated')" style="padding: 10px 20px; background: var(--accent); color: var(--text-primary); border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                <i class="fas fa-download"></i> Download Full Backup
                            </button>
                            <button type="button" onclick="alert('Database backup initiated')" style="padding: 10px 20px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: 8px; font-weight: 600; cursor: pointer;">
                                <i class="fas fa-database"></i> Database Only
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Server Management Settings -->
            <div id="server" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);"><i class="fas fa-server" style="margin-right: 8px;"></i>Server Management</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Configure WHMCS and Virtualizor API settings for VPS dashboard</p>
                </div>
                <div style="padding: 24px;">
                    <!-- WHMCS Configuration -->
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 24px; background: var(--bg-tertiary);">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--accent);"><i class="fas fa-credit-card" style="margin-right: 8px;"></i>WHMCS API Configuration</h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 16px;">Get these from WHMCS Admin > Setup > Staff Management > Manage API Credentials</p>
                        
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 16px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">WHMCS Base URL</label>
                                <input type="url" name="whmcs_base_url" value="{{ $settings['whmcs_base_url'] ?? '' }}" placeholder="https://billing.yourdomain.com"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">API Identifier</label>
                                <input type="text" name="whmcs_api_identifier" value="{{ $settings['whmcs_api_identifier'] ?? '' }}" placeholder="Your WHMCS API Identifier"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">API Secret</label>
                            <input type="password" name="whmcs_api_secret" value="{{ $settings['whmcs_api_secret'] ?? '' }}" placeholder="Your WHMCS API Secret"
                                style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>

                    <!-- Virtualizor Configuration -->
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 24px; background: var(--bg-tertiary);">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--accent);"><i class="fas fa-hdd" style="margin-right: 8px;"></i>Virtualizor API Configuration</h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 16px;">Get these from Virtualizor Admin > Configuration > API Credentials</p>
                        
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 16px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Virtualizor Base URL</label>
                                <input type="url" name="virtualizor_base_url" value="{{ $settings['virtualizor_base_url'] ?? '' }}" placeholder="https://virtualizor.yourdomain.com"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">API Port</label>
                                <input type="number" name="virtualizor_port" value="{{ $settings['virtualizor_port'] ?? '4085' }}" placeholder="4085"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">API Key</label>
                                <input type="text" name="virtualizor_api_key" value="{{ $settings['virtualizor_api_key'] ?? '' }}" placeholder="Your Virtualizor API Key"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">API Pass</label>
                                <input type="password" name="virtualizor_api_pass" value="{{ $settings['virtualizor_api_pass'] ?? '' }}" placeholder="Your Virtualizor API Pass"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                    </div>

                    <!-- Proxmox VE Configuration -->
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 24px; background: var(--bg-tertiary); margin-top: 24px;">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--success);"><i class="fas fa-cloud" style="margin-right: 8px;"></i>Proxmox VE API Configuration</h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 16px;">Configure Proxmox VE API for VM automation. Create API token from Proxmox: Datacenter > Permissions > API Tokens</p>
                        
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 16px;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Proxmox API URL</label>
                                <input type="url" name="proxmox_api_url" value="{{ $settings['proxmox_api_url'] ?? 'https://139.99.122.47:8006' }}" placeholder="https://your-proxmox-ip:8006"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <small style="color: var(--text-muted); font-size: 0.75rem;">Include https:// and port (default: 8006)</small>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Token ID</label>
                                <input type="text" name="proxmox_token_id" value="{{ $settings['proxmox_token_id'] ?? 'root@pam!believoo' }}" placeholder="root@pam!tokenname"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <small style="color: var(--text-muted); font-size: 0.75rem;">Format: user@realm!tokenname</small>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">API Secret</label>
                                <input type="password" name="proxmox_token_secret" value="{{ $settings['proxmox_token_secret'] ?? '' }}" placeholder="Your API Secret (UUID)"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <small style="color: var(--text-muted); font-size: 0.75rem;">The UUID token secret from Proxmox</small>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Node Name</label>
                                <input type="text" name="proxmox_node" value="{{ $settings['proxmox_node'] ?? 'ns548195' }}" placeholder="pve"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <small style="color: var(--text-muted); font-size: 0.75rem;">Proxmox node name (e.g., pve, ns548195)</small>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Default Bridge</label>
                                <input type="text" name="proxmox_bridge" value="{{ $settings['proxmox_bridge'] ?? 'vmbr0' }}" placeholder="vmbr0"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <small style="color: var(--text-muted); font-size: 0.75rem;">Network bridge (e.g., vmbr0, vmbr1)</small>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Default Storage</label>
                                <input type="text" name="proxmox_storage" value="{{ $settings['proxmox_storage'] ?? 'local-lvm' }}" placeholder="local-lvm"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <small style="color: var(--text-muted); font-size: 0.75rem;">Storage for VM disks (e.g., local-lvm, ceph)</small>
                            </div>
                        </div>
                        <div>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="proxmox_verify_ssl" value="1" {{ ($settings['proxmox_verify_ssl'] ?? '0') == '1' ? 'checked' : '' }}>
                                <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">Verify SSL Certificate (disable for self-signed)</span>
                            </label>
                            <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px; margin-left: 26px;">
                                Disable if using self-signed certificates
                            </small>
                        </div>
                    </div>

                    <!-- Dashboard Settings -->
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; background: var(--bg-tertiary);">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-primary);"><i class="fas fa-cog" style="margin-right: 8px;"></i>Dashboard Settings</h4>
                        
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 12px;">
                                    <input type="checkbox" name="server_dashboard_auto_refresh" value="1" {{ ($settings['server_dashboard_auto_refresh'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">Auto Refresh Dashboard</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Refresh Interval (seconds)</label>
                                <input type="number" name="server_dashboard_refresh_interval" value="{{ $settings['server_dashboard_refresh_interval'] ?? '30' }}" min="10" max="300"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">WHMCS Cache TTL (seconds)</label>
                                <input type="number" name="whmcs_cache_ttl" value="{{ $settings['whmcs_cache_ttl'] ?? '300' }}" min="60"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Virtualizor Cache TTL (seconds)</label>
                                <input type="number" name="virtualizor_cache_ttl" value="{{ $settings['virtualizor_cache_ttl'] ?? '60' }}" min="10"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Assistant Settings -->
            <div id="ai" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);"><i class="fas fa-robot" style="margin-right: 8px;"></i>AI Chat Assistant</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">Configure AI model and API keys for customer support widget</p>
                </div>
                <div style="padding: 24px;">
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-primary);">AI Configuration</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 16px;">
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 12px;">
                                    <input type="checkbox" name="ai_enabled" value="1" {{ ($settings['ai_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">Enable AI Chat</span>
                                </label>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Active AI Model</label>
                                <select name="ai_model" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                    <option value="local" {{ ($settings['ai_model'] ?? 'local') == 'local' ? 'selected' : '' }}>Local (no API)</option>
                                    <option value="openai" {{ ($settings['ai_model'] ?? 'local') == 'openai' ? 'selected' : '' }}>OpenAI / ChatGPT</option>
                                    <option value="gemini" {{ ($settings['ai_model'] ?? 'local') == 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                                    <option value="claude" {{ ($settings['ai_model'] ?? 'local') == 'claude' ? 'selected' : '' }}>Anthropic Claude</option>
                                    <option value="divine" {{ ($settings['ai_model'] ?? 'local') == 'divine' ? 'selected' : '' }}>Divine AI (OpenAI-compatible)</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Default API Key (fallback)</label>
                                <input type="password" name="ai_api_key" value="{{ $settings['ai_api_key'] ?? '' }}" placeholder="sk-... or AIza..."
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <small style="color: var(--text-muted); font-size: 0.75rem;">Used if a provider-specific key is empty</small>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">OpenAI Model</label>
                                <input type="text" name="ai_openai_model" value="{{ $settings['ai_openai_model'] ?? 'gpt-3.5-turbo' }}" placeholder="gpt-3.5-turbo"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">OpenAI API Key</label>
                                <input type="password" name="ai_openai_api_key" value="{{ $settings['ai_openai_api_key'] ?? '' }}" placeholder="sk-..."
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Gemini Model</label>
                                <input type="text" name="ai_gemini_model" value="{{ $settings['ai_gemini_model'] ?? 'gemini-pro' }}" placeholder="gemini-pro"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Gemini API Key</label>
                                <input type="password" name="ai_gemini_api_key" value="{{ $settings['ai_gemini_api_key'] ?? '' }}" placeholder="AIza..."
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Claude Model</label>
                                <input type="text" name="ai_claude_model" value="{{ $settings['ai_claude_model'] ?? 'claude-3-haiku-20240307' }}" placeholder="claude-3-haiku-20240307"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Claude API Key</label>
                                <input type="password" name="ai_claude_api_key" value="{{ $settings['ai_claude_api_key'] ?? '' }}" placeholder="sk-ant-..."
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Divine AI Base URL</label>
                                <input type="url" name="ai_divine_base_url" value="{{ $settings['ai_divine_base_url'] ?? '' }}" placeholder="https://api.divineai.com/v1"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                <small style="color: var(--text-muted); font-size: 0.75rem;">OpenAI-compatible /chat/completions endpoint base URL</small>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Divine AI Model</label>
                                <input type="text" name="ai_divine_model" value="{{ $settings['ai_divine_model'] ?? 'gpt-3.5-turbo' }}" placeholder="gpt-3.5-turbo"
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Divine AI API Key</label>
                                <input type="password" name="ai_divine_api_key" value="{{ $settings['ai_divine_api_key'] ?? '' }}" placeholder="..."
                                    style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">System Prompt</label>
                            <textarea name="ai_system_prompt" rows="3" placeholder="You are a helpful Believoo support assistant..." style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">{{ $settings['ai_system_prompt'] ?? 'You are a helpful support assistant for Believoo. Answer customer questions about domains, hosting, websites, invoices and services. Be concise and friendly. If unsure, ask them to open a support ticket.' }}</textarea>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Fallback Message</label>
                            <textarea name="ai_fallback_message" rows="2" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; color: var(--text-primary);">{{ $settings['ai_fallback_message'] ?? 'I am currently unable to answer that. Please create a support ticket or request a callback and our team will assist you shortly.' }}</textarea>
                        </div>

                        <!-- AI Personality -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">AI Name</label>
                                <input type="text" name="ai_name" value="{{ $settings['ai_name'] ?? 'Believoo AI' }}" placeholder="e.g. Priya, Support Bot" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">AI Tone</label>
                                <select name="ai_tone" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                                    <option value="friendly" {{ ($settings['ai_tone'] ?? '') == 'friendly' ? 'selected' : '' }}>Friendly</option>
                                    <option value="professional" {{ ($settings['ai_tone'] ?? '') == 'professional' ? 'selected' : '' }}>Professional</option>
                                    <option value="casual" {{ ($settings['ai_tone'] ?? '') == 'casual' ? 'selected' : '' }}>Casual</option>
                                    <option value="warm" {{ ($settings['ai_tone'] ?? '') == 'warm' ? 'selected' : '' }}>Warm</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════ External Integrations ══════ -->
            <div id="integrations" class="settings-content" style="display: none;">
                <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary);"><i class="fas fa-plug" style="color: var(--admin-purple); margin-right: 8px;"></i>External Integrations</h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Connect Telegram, WhatsApp, Facebook/Instagram, and email-to-chat</p>
                </div>
                <div style="padding: 24px; display: flex; flex-direction: column; gap: 20px;">

                    <!-- Telegram -->
                    <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color);">
                        <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--text-primary);"><i class="fab fa-telegram" style="color: var(--accent); margin-right: 6px;"></i>Telegram Bot</h4>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Bot Token</label>
                            <input type="password" name="telegram_bot_token" value="{{ $settings['telegram_bot_token'] ?? '' }}" placeholder="123456:ABC-DEF..." style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px;">Webhook URL: <code style="background:var(--bg-tertiary);padding:2px 6px;border-radius:4px;">{{ url('/webhook/telegram') }}</code></p>
                        </div>
                    </div>

                    <!-- Meta (FB/IG) -->
                    <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color);">
                        <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--text-primary);"><i class="fab fa-facebook" style="color: var(--accent); margin-right: 6px;"></i>Facebook / Instagram DM</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Verify Token</label>
                                <input type="text" name="meta_verify_token" value="{{ $settings['meta_verify_token'] ?? '' }}" placeholder="Any random string" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Page Access Token</label>
                                <input type="password" name="meta_page_access_token" value="{{ $settings['meta_page_access_token'] ?? '' }}" placeholder="EAA..." style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 8px;">Webhook URL: <code style="background:var(--bg-tertiary);padding:2px 6px;border-radius:4px;">{{ url('/webhook/meta') }}</code></p>
                    </div>

                    <!-- WhatsApp -->
                    <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color);">
                        <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--text-primary);"><i class="fab fa-whatsapp" style="color: var(--success); margin-right: 6px;"></i>WhatsApp</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">WhatsApp Number (no +)</label>
                                <input type="text" name="whatsapp_number" value="{{ $settings['whatsapp_number'] ?? '' }}" placeholder="917830014237" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Verify Token (for webhook)</label>
                                <input type="text" name="whatsapp_verify_token" value="{{ $settings['whatsapp_verify_token'] ?? '' }}" placeholder="believoo-wa-verify" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">API Token (Meta Cloud)</label>
                                <input type="password" name="whatsapp_api_token" value="{{ $settings['whatsapp_api_token'] ?? '' }}" placeholder="EAAG..." style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Phone Number ID</label>
                                <input type="text" name="whatsapp_phone_id" value="{{ $settings['whatsapp_phone_id'] ?? '' }}" placeholder="123456789" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 8px;">Webhook URL: <code style="background:var(--bg-tertiary);padding:2px 6px;border-radius:4px;">{{ url('/webhook/whatsapp') }}</code> — Set this in Meta Developer Console</p>
                    </div>

                    <!-- Cloudflare DNS -->
                    <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color); margin-top: 16px;">
                        <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--text-primary);"><i class="fab fa-cloudflare" style="color: var(--warning); margin-right: 6px;"></i>Cloudflare DNS</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">API Token</label>
                                <input type="password" name="cloudflare_api_token" value="{{ $settings['cloudflare_api_token'] ?? '' }}" placeholder="Bearer token with Zone:Edit permission" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Zone ID</label>
                                <input type="text" name="cloudflare_zone_id" value="{{ $settings['cloudflare_zone_id'] ?? '' }}" placeholder="Cloudflare zone ID for believoo.com" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                        </div>
                        <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 8px;">Used to programmatically update SPF, DMARC, DKIM and other DNS records.</p>
                    </div>

                    <!-- Email to Chat -->
                    <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color);">
                        <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--text-primary);"><i class="fas fa-envelope" style="color: var(--warning); margin-right: 6px;"></i>Email → Chat</h4>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Webhook Secret (optional)</label>
                            <input type="text" name="inbound_email_secret" value="{{ $settings['inbound_email_secret'] ?? '' }}" placeholder="Secret to validate inbound emails" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px;">Inbound email URL: <code style="background:var(--bg-tertiary);padding:2px 6px;border-radius:4px;">{{ url('/webhook/inbound-email') }}</code> | Auto-ticket URL: <code style="background:var(--bg-tertiary);padding:2px 6px;border-radius:4px;">{{ url('/webhook/email-ticket') }}</code></p>
                        </div>
                    </div>

                    <!-- SMS -->
                    <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color);">
                        <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--text-primary);"><i class="fas fa-sms" style="color: var(--warning); margin-right: 6px;"></i>SMS (Twilio)</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Account SID</label>
                                <input type="text" name="twilio_sid" value="{{ $settings['twilio_sid'] ?? '' }}" placeholder="ACxxxxxxxx" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Auth Token</label>
                                <input type="password" name="twilio_token" value="{{ $settings['twilio_token'] ?? '' }}" placeholder="Auth token" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">From Number</label>
                                <input type="text" name="twilio_from" value="{{ $settings['twilio_from'] ?? '' }}" placeholder="+1234567890" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Enable SMS</label>
                                <input type="checkbox" name="sms_enabled" value="1" {{ ($settings['sms_enabled'] ?? '') ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--warning);">
                            </div>
                        </div>
                    </div>

                    <!-- Slack / Discord -->
                    <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color);">
                        <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--text-primary);"><i class="fab fa-slack" style="color: var(--admin-purple); margin-right: 6px;"></i>Slack / Discord Alerts</h4>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Slack Webhook URL</label>
                            <input type="text" name="slack_webhook_url" value="{{ $settings['slack_webhook_url'] ?? '' }}" placeholder="https://hooks.slack.com/services/..." style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px;">Team notifications for new chats, tickets, leads will go here.</p>
                        </div>
                        <div style="margin-top: 12px;">
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Discord Webhook URL</label>
                            <input type="text" name="discord_webhook_url" value="{{ $settings['discord_webhook_url'] ?? '' }}" placeholder="https://discord.com/api/webhooks/..." style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                        </div>
                    </div>

                    <!-- Google Calendar -->
                    <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color);">
                        <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--text-primary);"><i class="fab fa-google" style="color: var(--accent); margin-right: 6px;"></i>Google Calendar</h4>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Calendar ID</label>
                            <input type="text" name="google_calendar_id" value="{{ $settings['google_calendar_id'] ?? '' }}" placeholder="primary or your-calendar-id@group.calendar.google.com" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: var(--text-primary);">
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px;">Appointments booked via chat will appear on this calendar.</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Save Button -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 24px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 16px; position: sticky; bottom: 20px;">
                <button type="reset" class="btn btn-secondary">Reset Changes</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>

<script>
function showTab(tabName) {
    // Hide all content sections
    document.querySelectorAll('.settings-content').forEach(section => {
        section.style.display = 'none';
    });

    // Remove active class from all tabs
    document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Show selected content
    document.getElementById(tabName).style.display = 'block';

    // Add active class to clicked tab
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

    // Update URL hash for deep-linking
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        history.replaceState(null, '', url);
    } catch (e) {}
}

// Open tab from URL param or hash
(function() {
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get('tab') || window.location.hash.replace('#', '');
    const validTabs = ['general', 'contact', 'social', 'seo', 'payment', 'agora', 'push', 'bconnect', 'email', 'appearance', 'maintenance', 'security', 'notifications', 'backup', 'server', 'ghc', 'ai', 'integrations'];
    if (tabParam && validTabs.includes(tabParam)) {
        document.querySelectorAll('.settings-content').forEach(s => s.style.display = 'none');
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        document.getElementById(tabParam).style.display = 'block';
        document.querySelector(`[data-tab="${tabParam}"]`).classList.add('active');
    }
})();
</script>
@endsection

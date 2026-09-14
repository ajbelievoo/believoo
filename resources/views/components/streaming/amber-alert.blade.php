@props([
    'percentage'  => 0,
    'projectName' => '',
    'upgradeUrl'  => '/services/streaming',
])

<div
    class="amber-alert-banner"
    style="border: 1px solid #ffb300; box-shadow: 0 0 16px rgba(255,179,0,0.4); border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; background: rgba(255,179,0,0.08);"
    x-data="{ dismissed: sessionStorage.getItem('amber_dismissed_{{ Str::slug($projectName) }}') === '1' }"
    x-show="!dismissed"
    x-transition
    role="alert"
    aria-live="polite"
>
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffb300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span style="color: #ffb300; font-weight: 600; font-size: 0.9rem;">
                ⚠️ Warning: You have used <strong>{{ $percentage }}%</strong> of your monthly bandwidth for <strong>{{ $projectName }}</strong>.
                <a href="{{ $upgradeUrl }}" style="text-decoration: underline; color: #ffb300;">Upgrade your plan</a> to avoid suspension.
            </span>
        </div>
        <button
            aria-label="Dismiss bandwidth warning for {{ $projectName }}"
            @click="dismissed = true; sessionStorage.setItem('amber_dismissed_{{ Str::slug($projectName) }}', '1')"
            style="color: #ffb300; background: none; border: none; cursor: pointer; font-size: 1.5rem; line-height: 1; padding: 0 0.25rem; flex-shrink: 0;"
        >&times;</button>
    </div>
</div>

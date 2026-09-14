{{--
    BelieVoo Dashboard — Reusable Status Pill Component
    Usage: <x-dashboard.status-pill :status="$hosting->status" />
           <x-dashboard.status-pill status="running" label="Online" />
    Requirements: 10.1–10.5, 13.1–13.5
--}}

@props(['status' => 'unknown', 'label' => null, 'size' => 'md'])

@php
$s = strtolower(trim($status));

$config = match(true) {
    in_array($s, ['running', 'active', 'online'])         => [
        'pill_class' => 'status-pill-running',
        'dot_class'  => 'status-dot-running bg-green-400',
        'text_class' => 'text-green-400',
        'label'      => $label ?? 'Running',
    ],
    in_array($s, ['stopped', 'inactive', 'offline'])      => [
        'pill_class' => 'status-pill-stopped',
        'dot_class'  => 'status-dot-stopped bg-red-400',
        'text_class' => 'text-red-400',
        'label'      => $label ?? 'Stopped',
    ],
    in_array($s, ['pending', 'provisioning', 'queued'])   => [
        'pill_class' => 'status-pill-pending',
        'dot_class'  => 'status-dot-pending bg-yellow-400',
        'text_class' => 'text-yellow-400',
        'label'      => $label ?? 'Pending',
    ],
    in_array($s, ['suspended', 'cancelled', 'expired'])   => [
        'pill_class' => 'status-pill-stopped',
        'dot_class'  => 'bg-red-500',
        'text_class' => 'text-red-500',
        'label'      => $label ?? ucfirst($s),
    ],
    in_array($s, ['migrating', 'processing'])             => [
        'pill_class' => 'status-pill-pending',
        'dot_class'  => 'status-dot-pending bg-blue-400',
        'text_class' => 'text-blue-400',
        'label'      => $label ?? ucfirst($s),
    ],
    default                                               => [
        'pill_class' => '',
        'dot_class'  => 'bg-gray-400',
        'text_class' => 'text-gray-400',
        'label'      => $label ?? ucfirst($s ?: 'Unknown'),
    ],
};

$sizeClass = match($size) {
    'sm'  => 'px-2 py-0.5 text-[10px] gap-1',
    'lg'  => 'px-4 py-1.5 text-sm gap-2',
    default => 'px-3 py-1 text-xs gap-1.5',
};
@endphp

<span
    class="inline-flex items-center rounded-full glass-card font-bold uppercase tracking-wider {{ $sizeClass }} {{ $config['pill_class'] }} {{ $config['text_class'] }}"
    role="status"
    aria-label="Status: {{ $config['label'] }}">
    <span class="rounded-full flex-shrink-0 {{ $config['dot_class'] }}"
          style="width: 7px; height: 7px;"
          aria-hidden="true"></span>
    {{ $config['label'] }}
</span>

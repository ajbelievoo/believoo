@extends('layouts.admin')
@section('title', 'HiTune — Admin')
@section('content')
@include('admin.brandpanels._style')
<div class="ghc-hero">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1><i class="fas fa-music" style="color:#f472b6;margin-right:10px;"></i>HiTune</h1>
            <p>hitune.in &amp; music.hitune.in — listeners, plays, subscriptions &amp; in-app distribution</p>
        </div>
        <a href="https://music.hitune.in/admin/" target="_blank" class="ghc-btn ghc-btn-primary" style="text-decoration:none;"><i class="fas fa-external-link-alt" style="margin-right:6px;"></i>Native Admin</a>
    </div>
</div>

@php $htabs = [
    'dashboard' => ['Dashboard','fa-chart-pie'],
    'users' => ['Users','fa-users'],
    'plays' => ['Plays','fa-play'],
    'subs' => ['Subscriptions','fa-crown'],
    'payments' => ['Payments','fa-credit-card'],
    'withdrawals' => ['Withdrawals','fa-money-bill'],
    'dist' => ['Dist Submissions','fa-cloud-upload-alt'],
    'stories' => ['Stories','fa-history'],
]; @endphp
<div class="ghc-tabs" style="margin-bottom:20px;">
    @foreach($htabs as $key => $item)
        <a href="{{ route('admin.brandpanels.hitune', ['tab' => $key]) }}" class="ghc-tab {{ $tab === $key ? 'active' : '' }}"><i class="fas {{ $item[1] }}"></i>{{ $item[0] }}</a>
    @endforeach
</div>

@if(session('success'))<div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#22c55e;padding:12px 16px;border-radius:12px;margin-bottom:18px;"><i class="fas fa-check-circle" style="margin-right:8px;"></i>{{ session('success') }}</div>@endif
@if(session('error'))<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#ef4444;padding:12px 16px;border-radius:12px;margin-bottom:18px;"><i class="fas fa-exclamation-circle" style="margin-right:8px;"></i>{{ session('error') }}</div>@endif
@if($dbError)<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#ef4444;padding:12px 16px;border-radius:12px;margin-bottom:18px;">DB error: {{ $dbError }}</div>@endif

@if($tab === 'dashboard')
<div class="ghc-grid-4" style="margin-bottom:24px;">
    <div class="ghc-stat"><div><div style="font-size:1rem;font-weight:800;color:#1e293b;">Site {{ $mainStatus['up'] ? 'Online' : 'Down' }}</div><div style="font-size:0.78rem;color:#64748b;">hitune.in</div></div></div>
    <div class="ghc-stat"><div><div style="font-size:1rem;font-weight:800;color:#1e293b;">App {{ $siteStatus['up'] ? 'Online' : 'Down' }}</div><div style="font-size:0.78rem;color:#64748b;">music.hitune.in {{ $siteStatus['ms'] ? '· '.$siteStatus['ms'].'ms' : '' }}</div></div></div>
    <div class="ghc-stat"><div><div style="font-size:1.4rem;font-weight:800;color:#1e293b;">{{ $stats['users'] ?? 0 }}</div><div style="font-size:0.78rem;color:#64748b;">Users</div></div></div>
    <div class="ghc-stat"><div><div style="font-size:1.4rem;font-weight:800;color:#1e293b;">{{ $stats['plays'] ?? 0 }}</div><div style="font-size:0.78rem;color:#64748b;">Plays · {{ $stats['plays_24h'] ?? 0 }} today</div></div></div>
</div>
<div class="ghc-grid-4" style="margin-bottom:24px;">
    <div class="ghc-stat"><div><div style="font-size:1.4rem;font-weight:800;color:#1e293b;">{{ $stats['subs'] ?? 0 }}</div><div style="font-size:0.78rem;color:#64748b;">Subscriptions</div></div></div>
    <div class="ghc-stat"><div><div style="font-size:1.4rem;font-weight:800;color:#1e293b;">{{ $stats['playlists'] ?? 0 }}</div><div style="font-size:0.78rem;color:#64748b;">Playlists</div></div></div>
    <div class="ghc-stat"><div><div style="font-size:1.4rem;font-weight:800;color:#f59e0b;">{{ $stats['sub_pending'] ?? 0 }}</div><div style="font-size:0.78rem;color:#64748b;">Pending dist submissions</div></div></div>
    <div class="ghc-stat"><div><div style="font-size:1.4rem;font-weight:800;color:#1e293b;">{{ $stats['payments'] ?? 0 }}</div><div style="font-size:0.78rem;color:#64748b;">Payments</div></div></div>
</div>
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-play" style="color:#f472b6;margin-right:8px;"></i>Latest Plays</h3></div>
    <div style="overflow-x:auto;"><table class="ghc-table"><thead><tr><th>Track</th><th>User</th><th>Platform</th><th>Region</th><th>Time</th></tr></thead><tbody>
        @forelse(array_slice($plays, 0, 15) as $p)
        <tr><td style="font-size:0.8rem;">#{{ $p['track_id'] }}</td><td style="font-size:0.8rem;">#{{ $p['user_id'] }}</td><td style="font-size:0.8rem;">{{ $p['platform'] ?: '—' }}</td><td style="font-size:0.78rem;color:#64748b;">{{ $p['city'] ?: $p['region'] ?: $p['cc'] ?: '—' }}</td><td style="font-size:0.75rem;color:#64748b;">{{ $p['time_add'] }}</td></tr>
        @empty
        <tr><td colspan="5" style="text-align:center;color:#64748b;padding:20px;">No plays.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endif

@if($tab === 'users')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-users" style="color:#f472b6;margin-right:8px;"></i>Users ({{ count($users) }})</h3></div>
    <div style="overflow-x:auto;"><table class="ghc-table"><thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Playlists</th><th>Subs</th><th>Joined</th><th>Last Online</th></tr></thead><tbody>
        @forelse($users as $u)
        <tr>
            <td style="font-size:0.78rem;color:#64748b;">{{ $u['ID'] }}</td>
            <td style="font-size:0.8rem;font-weight:600;">{{ $u['username'] }}</td>
            <td style="font-size:0.8rem;">{{ $u['name'] ?: '—' }}</td>
            <td style="font-size:0.78rem;">{{ $u['email'] ?: '—' }}</td>
            <td style="font-size:0.8rem;">{{ $u['s_playlists'] }}</td>
            <td style="font-size:0.8rem;">{{ $u['s_subscriptions'] }}</td>
            <td style="font-size:0.75rem;color:#64748b;">{{ $u['time_add'] }}</td>
            <td style="font-size:0.75rem;color:#64748b;">{{ $u['time_online'] }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:#64748b;padding:20px;">No users.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endif

@if($tab === 'plays')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-play" style="color:#f472b6;margin-right:8px;"></i>Play Analytics ({{ count($plays) }} recent)</h3></div>
    <div style="overflow-x:auto;"><table class="ghc-table"><thead><tr><th>ID</th><th>Track</th><th>Artist</th><th>User</th><th>Platform</th><th>Country</th><th>Region/City</th><th>Time</th></tr></thead><tbody>
        @forelse($plays as $p)
        <tr><td style="font-size:0.75rem;color:#64748b;">{{ $p['id'] }}</td><td>#{{ $p['track_id'] }}</td><td>#{{ $p['artist_id'] }}</td><td>#{{ $p['user_id'] }}</td><td style="font-size:0.78rem;">{{ $p['platform'] ?: '—' }}</td><td style="font-size:0.78rem;">{{ $p['cc'] ?: '—' }}</td><td style="font-size:0.78rem;color:#64748b;">{{ $p['region'] }} {{ $p['city'] }}</td><td style="font-size:0.75rem;color:#64748b;">{{ $p['time_add'] }}</td></tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:#64748b;padding:20px;">No plays.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endif

@if($tab === 'subs')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-crown" style="color:#f59e0b;margin-right:8px;"></i>Subscriptions</h3></div>
    <div style="overflow-x:auto;"><table class="ghc-table"><thead><tr><th>ID</th><th>User</th><th>Plan</th><th>Price</th><th>Gateway</th><th>Purchased</th><th>Expires</th></tr></thead><tbody>
        @forelse($subs as $s)
        <tr><td style="font-size:0.75rem;color:#64748b;">{{ $s['ID'] }}</td><td style="font-size:0.8rem;">{{ $s['username'] ?: '#'.$s['user_id'] }}</td><td style="font-size:0.8rem;">#{{ $s['subs_plan_id'] }}</td><td style="font-weight:700;color:#22c55e;">{{ $s['subs_plan_price'] }}</td><td style="font-size:0.78rem;">{{ $s['gateway_name'] ?: '—' }}</td><td style="font-size:0.75rem;color:#64748b;">{{ $s['time_purchased'] }}</td><td style="font-size:0.75rem;color:#64748b;">{{ $s['time_expire'] }}</td></tr>
        @empty
        <tr><td colspan="7" style="text-align:center;color:#64748b;padding:20px;">No subscriptions.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endif

@if($tab === 'payments')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-credit-card" style="color:#22c55e;margin-right:8px;"></i>Payments</h3></div>
    <div style="overflow-x:auto;"><table class="ghc-table"><thead><tr><th>ID</th><th>User</th><th>Details</th></tr></thead><tbody>
        @forelse($payments as $p)
        <tr><td style="font-size:0.75rem;color:#64748b;">{{ $p['ID'] ?? '—' }}</td><td style="font-size:0.8rem;">{{ $p['username'] ?? '—' }}</td><td><code style="font-size:0.72rem;">{{ \Illuminate\Support\Str::limit(json_encode($p), 200) }}</code></td></tr>
        @empty
        <tr><td colspan="3" style="text-align:center;color:#64748b;padding:20px;">No payments.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endif

@if($tab === 'withdrawals')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-money-bill" style="color:#ef4444;margin-right:8px;"></i>Withdrawal Requests</h3></div>
    <div style="overflow-x:auto;"><table class="ghc-table"><thead><tr><th>ID</th><th>User</th><th>Amount</th><th>Receiver</th><th>Status</th><th>Requested</th><th>Reviewed</th></tr></thead><tbody>
        @forelse($withdrawals as $w)
        <tr><td style="font-size:0.75rem;color:#64748b;">{{ $w['ID'] }}</td><td style="font-size:0.8rem;">{{ $w['username'] ?: '#'.$w['user_id'] }}</td><td style="font-weight:700;color:#22c55e;">{{ $w['amount'] }}</td><td style="font-size:0.78rem;">{{ $w['receiver'] ?: '—' }}</td><td><span class="ghc-badge ghc-badge-{{ $w['sta'] == 1 ? 'active' : 'pending' }}">sta {{ $w['sta'] }}</span></td><td style="font-size:0.75rem;color:#64748b;">{{ $w['time_add'] }}</td><td style="font-size:0.75rem;color:#64748b;">{{ $w['time_review'] ?: '—' }}</td></tr>
        @empty
        <tr><td colspan="7" style="text-align:center;color:#64748b;padding:20px;">No withdrawals.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endif

@if($tab === 'dist')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-cloud-upload-alt" style="color:#a855f7;margin-right:8px;"></i>Distribution Submissions (in-app)</h3></div>
    <div style="overflow-x:auto;"><table class="ghc-table"><thead><tr><th>ID</th><th>User</th><th>Title</th><th>Artist</th><th>Type</th><th>Status</th><th>ISRC</th><th>Platforms</th><th>Submitted</th><th>Actions</th></tr></thead><tbody>
        @forelse($submissions as $s)
        <tr>
            <form method="POST" action="{{ route('admin.brandpanels.hitune.save') }}">@csrf<input type="hidden" name="op" value="sub_update"><input type="hidden" name="tab" value="dist"><input type="hidden" name="id" value="{{ $s['id'] }}">
            <td style="font-size:0.75rem;color:#64748b;">{{ $s['id'] }}</td>
            <td style="font-size:0.8rem;">{{ $s['username'] ?: '#'.$s['user_id'] }}</td>
            <td style="font-size:0.8rem;font-weight:600;">{{ $s['title'] }}</td>
            <td style="font-size:0.8rem;">{{ $s['artist_name'] }}</td>
            <td style="font-size:0.75rem;">{{ $s['type'] }}</td>
            <td><select name="status" class="ghc-input" style="width:110px;">
                @foreach(['submitted','in_review','in_progress','approved','rejected','launched','taken_down'] as $st)<option value="{{ $st }}" {{ $s['status']===$st?'selected':'' }}>{{ $st }}</option>@endforeach</select></td>
            <td><input name="isrc" value="{{ $s['isrc'] }}" class="ghc-input" style="width:90px;">
                <input name="upc" type="hidden" value="{{ $s['upc'] }}"><input name="admin_notes" type="hidden" value="{{ $s['admin_notes'] }}"><input name="launch_date" type="hidden" value="{{ $s['launch_date'] }}"><input name="tunecore_url" type="hidden" value="{{ $s['tunecore_url'] }}"><input name="tunecore_status" type="hidden" value="{{ $s['tunecore_status'] }}"></td>
            <td style="font-size:0.72rem;color:#64748b;max-width:130px;overflow:hidden;text-overflow:ellipsis;">{{ $s['platforms'] }}</td>
            <td style="font-size:0.75rem;color:#64748b;">{{ $s['time_add'] }}</td>
            <td><button class="ghc-btn ghc-btn-primary" style="padding:5px 12px;font-size:0.72rem;">Save</button></td>
            </form>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;color:#64748b;padding:20px;">No submissions.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endif
@if($tab === 'stories')
<div class="ghc-card" style="margin-bottom:20px;">
    <div class="ghc-card-header"><h3><i class="fas fa-history" style="color:#a855f7;margin-right:8px;"></i>Post a Story</h3></div>
    <div style="padding:18px;">
        <p style="font-size:0.8rem;color:#64748b;margin:0 0 14px;">Shows at the top of the app <b>Stories rail</b> as <b>HiTune</b> <i class="fas fa-check-circle" style="color:#0ea5e9;"></i> and inside <b>IyolMe</b> as @blitzomnii. Expires after 24h unless <b>Repeat</b> is on.</p>
        <form method="POST" action="{{ route('admin.brandpanels.hitune.save') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="op" value="story_save"><input type="hidden" name="tab" value="stories">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                <div><label style="font-size:0.72rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">MEDIA FILE (image / video)</label>
                    <input type="file" name="media" accept="image/*,video/*" class="ghc-input" style="padding:8px;"></div>
                <div><label style="font-size:0.72rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">&hellip;OR MEDIA URL</label>
                    <input type="url" name="content" placeholder="https://&hellip;jpg / .mp4" class="ghc-input"></div>
                <div><label style="font-size:0.72rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">TYPE (URL mode)</label>
                    <select name="type" class="ghc-input"><option value="0">Image</option><option value="1">Video</option></select></div>
                <div><label style="font-size:0.72rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">CAPTION</label>
                    <input name="caption" maxlength="500" class="ghc-input" placeholder="Story text&hellip;"></div>
                <div><label style="font-size:0.72rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">LINK (optional)</label>
                    <input type="url" name="link_url" class="ghc-input" placeholder="https://hitune.in/&hellip;"></div>
                <div><label style="font-size:0.72rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">DURATION (sec)</label>
                    <input type="number" name="duration" value="5" min="1" max="60" class="ghc-input"></div>
                <div><label style="font-size:0.72rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">DISPLAY NAME</label>
                    <input name="display_name" value="HiTune" maxlength="100" class="ghc-input"></div>
            </div>
            <div style="display:flex;align-items:center;gap:14px;margin-top:14px;flex-wrap:wrap;">
                <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.85rem;font-weight:600;color:#334155;">
                    <input type="checkbox" name="repeat" value="1" style="width:17px;height:17px;accent-color:#7c3aed;"> Repeat after 24h (keep playing)
                </label>
                <button class="ghc-btn ghc-btn-primary"><i class="fas fa-paper-plane" style="margin-right:6px;"></i>Post Story</button>
            </div>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;">
    @forelse($stories as $s)
    @php
        $age = time() - strtotime($s['time_add'] . ' UTC');
        $live = $s['repeat_forever'] || $age < 86400;
    @endphp
    <div class="ghc-card" style="overflow:hidden;">
        <div style="height:220px;background:#0f172a;display:flex;align-items:center;justify-content:center;">
            @if($s['type'] == 1)
                <video src="{{ $s['content'] }}" muted playsinline controls style="width:100%;height:100%;object-fit:cover;"></video>
            @else
                <img src="{{ $s['content'] }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;" alt="">
            @endif
        </div>
        <div style="padding:12px 14px;">
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px;">
                @if($s['repeat_forever'])<span class="ghc-badge" style="background:#ede9fe;color:#6d28d9;">REPEAT</span>@endif
                <span class="ghc-badge {{ $live ? 'ghc-badge-active' : 'ghc-badge-pending' }}">{{ $live ? 'LIVE' : 'EXPIRED' }}</span>
                @if(!$s['active'])<span class="ghc-badge ghc-badge-pending">DISABLED</span>@endif
                @if($s['iyol_story_id'])<span class="ghc-badge" style="background:#e0f2fe;color:#0369a1;">IYOLME</span>@endif
            </div>
            <div style="font-size:0.8rem;color:#334155;min-height:18px;margin-bottom:6px;">{{ $s['caption'] }}</div>
            <div style="font-size:0.7rem;color:#94a3b8;margin-bottom:10px;">{{ $s['time_add'] }} &middot; {{ $s['s_views'] }} views &middot; {{ $s['duration'] }}s</div>
            <div style="display:flex;gap:6px;">
                <form method="POST" action="{{ route('admin.brandpanels.hitune.save') }}" style="flex:1;">@csrf
                    <input type="hidden" name="op" value="story_toggle"><input type="hidden" name="tab" value="stories">
                    <input type="hidden" name="id" value="{{ $s['ID'] }}"><input type="hidden" name="field" value="repeat_forever">
                    <button class="ghc-btn" style="width:100%;padding:6px;font-size:0.72rem;background:{{ $s['repeat_forever'] ? '#ede9fe' : '#f1f5f9' }};color:{{ $s['repeat_forever'] ? '#6d28d9' : '#475569' }};">Repeat {{ $s['repeat_forever'] ? 'ON' : 'OFF' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.brandpanels.hitune.save') }}" style="flex:1;">@csrf
                    <input type="hidden" name="op" value="story_toggle"><input type="hidden" name="tab" value="stories">
                    <input type="hidden" name="id" value="{{ $s['ID'] }}"><input type="hidden" name="field" value="active">
                    <button class="ghc-btn" style="width:100%;padding:6px;font-size:0.72rem;background:#f1f5f9;color:#475569;">{{ $s['active'] ? 'Disable' : 'Enable' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.brandpanels.hitune.save') }}" onsubmit="return confirm('Delete story? (removed from IyolMe too)');">@csrf
                    <input type="hidden" name="op" value="story_delete"><input type="hidden" name="tab" value="stories">
                    <input type="hidden" name="id" value="{{ $s['ID'] }}">
                    <button class="ghc-btn" style="padding:6px 10px;font-size:0.72rem;background:#fee2e2;color:#b91c1c;"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="ghc-card" style="padding:30px;text-align:center;color:#64748b;grid-column:1/-1;">No stories yet — post your first one above.</div>
    @endforelse
</div>
@endif
@endsection

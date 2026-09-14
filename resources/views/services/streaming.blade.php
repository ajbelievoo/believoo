<x-layouts.believoo
    title="Live Streaming Server & API in India - Believoo"
    description="Launch your own live streaming platform with WebRTC, RTMP and HLS. Buy Agora-style streaming API and server infrastructure from Believoo."
    keywords="live streaming API, RTMP server, WebRTC streaming, HLS streaming India, streaming infrastructure, Believoo">
    <div class="pt-24">
<div data-theme="midnight-onyx" style="min-height:100vh;background:linear-gradient(135deg,#0a0a0f 0%,#0d0d1a 50%,#0a0a0f 100%);padding-bottom:4rem;">

    {{-- Hero --}}
    <div style="max-width:1100px;margin:0 auto;text-align:center;padding:4rem 1rem 2.5rem;">
        <div style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.4rem 1.2rem;border-radius:999px;border:1px solid rgba(0,245,255,0.3);background:rgba(0,245,255,0.05);margin-bottom:1.5rem;">
            <span style="width:8px;height:8px;border-radius:50%;background:#00f5ff;display:inline-block;animation:pulse 2s infinite;"></span>
            <span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#00f5ff;">BelieVoo Live Streaming Engine</span>
        </div>
        <h1 style="font-size:clamp(2.2rem,5vw,3.8rem);font-weight:900;color:#fff;margin-bottom:1rem;line-height:1.1;">
            Stream at <span style="background:linear-gradient(90deg,#00f5ff,#7000ff);-webkit-background-clip:text;background-clip:text;color:transparent;">Global Scale</span>
        </h1>
        <p style="color:#94a3b8;font-size:1.1rem;max-width:620px;margin:0 auto 1rem;">
            Agora-style streaming infrastructure with WebRTC, RTMP, and real-time analytics. Get your AppID in seconds.
        </p>
        <div style="display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;margin-top:1.5rem;">
            <a href="{{ route('docs.streaming') }}" style="padding:0.6rem 1.4rem;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;font-weight:600;text-decoration:none;font-size:0.9rem;">
                📖 View Docs
            </a>
            @auth
            <a href="{{ route('client.streaming') }}" style="padding:0.6rem 1.4rem;border-radius:10px;background:rgba(0,245,255,0.1);border:1px solid rgba(0,245,255,0.3);color:#00f5ff;font-weight:600;text-decoration:none;font-size:0.9rem;">
                🚀 My Dashboard
            </a>
            @endauth
        </div>
    </div>

    {{-- Stats bar --}}
    <div style="max-width:1100px;margin:0 auto 3rem;padding:0 1rem;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;">
            @foreach([['<50ms','Latency'],['99.9%','Uptime'],['50+','Edge Nodes'],['WebRTC + RTMP','Protocols']] as $stat)
            <div style="text-align:center;padding:1rem;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);">
                <div style="font-size:1.4rem;font-weight:900;color:#00f5ff;">{{ $stat[0] }}</div>
                <div style="font-size:0.8rem;color:#64748b;margin-top:0.2rem;">{{ $stat[1] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Plans --}}
    <div style="max-width:1100px;margin:0 auto;padding:0 1rem;">
        @if($plans->isEmpty())
        <div style="text-align:center;padding:4rem 2rem;border-radius:16px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);">
            <div style="font-size:3rem;margin-bottom:1rem;">📡</div>
            <p style="color:#64748b;font-size:1.1rem;">No streaming plans available yet. Check back soon.</p>
        </div>
        @else
        <h2 style="text-align:center;color:#fff;font-size:1.5rem;font-weight:800;margin-bottom:2rem;">Choose Your Plan</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;">
            @foreach($plans as $index => $plan)
            @php $popular = $index === 1; @endphp
            <div style="border-radius:18px;padding:2rem;position:relative;overflow:hidden;
                background:{{ $popular ? 'linear-gradient(135deg,rgba(0,245,255,0.08),rgba(112,0,255,0.08))' : 'rgba(255,255,255,0.03)' }};
                border:{{ $popular ? '1px solid rgba(0,245,255,0.4)' : '1px solid rgba(255,255,255,0.08)' }};
                box-shadow:{{ $popular ? '0 0 40px rgba(0,245,255,0.1)' : 'none' }};
                transition:transform 0.2s,box-shadow 0.2s;"
                onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 40px rgba(0,245,255,0.15)'"
                onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='{{ $popular ? '0 0 40px rgba(0,245,255,0.1)' : 'none' }}'">

                {{-- Top accent --}}
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#00f5ff,#7000ff);"></div>

                @if($popular)
                <div style="position:absolute;top:1rem;right:1rem;padding:0.2rem 0.7rem;border-radius:999px;background:linear-gradient(90deg,#00f5ff,#7000ff);color:#000;font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;">
                    Most Popular
                </div>
                @endif

                {{-- Plan name & price --}}
                <div style="margin-bottom:1.5rem;">
                    <h3 style="color:#fff;font-size:1.25rem;font-weight:800;margin:0 0 0.5rem;">{{ $plan->name }}</h3>
                    <p style="color:#64748b;font-size:0.85rem;margin:0 0 1rem;line-height:1.5;">{{ $plan->description }}</p>
                    <div style="display:flex;align-items:baseline;gap:0.3rem;">
                        <span style="font-size:2.5rem;font-weight:900;color:#00f5ff;">${{ number_format($plan->price, 0) }}</span>
                        <span style="color:#64748b;font-size:0.9rem;">/month</span>
                    </div>
                </div>

                {{-- Features --}}
                <ul style="list-style:none;padding:0;margin:0 0 1.75rem;">
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span>
                        <strong style="color:#fff;">{{ number_format($plan->max_viewers) }}</strong>&nbsp;Concurrent Viewers
                    </li>
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span>
                        <strong style="color:#fff;">{{ $plan->bandwidth_gb }}GB</strong>&nbsp;Bandwidth / month
                    </li>
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span>
                        <strong style="color:#fff;">{{ $plan->storage_gb }}GB</strong>&nbsp;Cloud Storage
                    </li>
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span>
                        <strong style="color:#fff;">{{ $plan->stream_count }}</strong>&nbsp;Concurrent Stream{{ $plan->stream_count > 1 ? 's' : '' }}
                    </li>
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span>
                        Up to <strong style="color:#fff;">{{ $plan->max_projects ?? 5 }}</strong>&nbsp;Projects
                    </li>
                    @if($plan->rtmp_support)
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span> RTMP Ingest
                    </li>
                    @endif
                    @if($plan->webrtc_support)
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span> WebRTC Support
                    </li>
                    @endif
                    @if($plan->recording_enabled)
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span> Cloud Recording
                    </li>
                    @endif
                    @if($plan->low_latency)
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span> Low Latency Mode
                    </li>
                    @endif
                    @if($plan->transcoding_enabled)
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span> Live Transcoding
                    </li>
                    @endif
                    @if($plan->adaptive_bitrate)
                    <li style="display:flex;align-items:center;gap:0.6rem;color:#94a3b8;font-size:0.88rem;margin-bottom:0.6rem;">
                        <span style="color:#00f5ff;font-size:0.75rem;">✓</span> Adaptive Bitrate (ABR)
                    </li>
                    @endif
                </ul>

                {{-- CTA --}}
                @auth
                    <a href="{{ route('client.streaming') }}"
                       style="display:block;width:100%;padding:0.8rem;text-align:center;border-radius:12px;font-weight:700;text-decoration:none;font-size:0.95rem;transition:all 0.2s;
                       {{ $popular ? 'background:linear-gradient(135deg,#00f5ff,#7000ff);color:#000;' : 'background:rgba(0,245,255,0.1);border:1px solid rgba(0,245,255,0.3);color:#00f5ff;' }}"
                       onmouseover="this.style.opacity='0.85'"
                       onmouseout="this.style.opacity='1'">
                        Get Started →
                    </a>
                @else
                    <a href="{{ route('login') }}?intended={{ urlencode(route('services.streaming')) }}"
                       style="display:block;width:100%;padding:0.8rem;text-align:center;border-radius:12px;font-weight:700;text-decoration:none;font-size:0.95rem;
                       {{ $popular ? 'background:linear-gradient(135deg,#00f5ff,#7000ff);color:#000;' : 'background:rgba(0,245,255,0.1);border:1px solid rgba(0,245,255,0.3);color:#00f5ff;' }}">
                        Get Started →
                    </a>
                @endauth
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Bottom CTA --}}
    <div style="max-width:1100px;margin:3rem auto 0;padding:0 1rem;text-align:center;">
        <p style="color:#64748b;font-size:0.9rem;">
            Already have a plan?
            <a href="{{ route('docs.streaming') }}" style="color:#00f5ff;text-decoration:none;font-weight:600;">View Developer Docs →</a>
            &nbsp;·&nbsp;
            @auth
            <a href="{{ route('client.streaming') }}" style="color:#00f5ff;text-decoration:none;font-weight:600;">Go to Dashboard →</a>
            @else
            <a href="{{ route('login') }}" style="color:#00f5ff;text-decoration:none;font-weight:600;">Log in →</a>
            @endauth
        </p>
    </div>
    </div>

    <style>
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
    </style>
</div>
</x-layouts.believoo>

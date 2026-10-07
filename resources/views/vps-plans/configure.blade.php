@extends('layouts.app')

@php
    $ovhConfig = $vpsPlan->ovh_config ?? [];
    $isOvh = !empty($vpsPlan->ovh_plan_code);
    $ovhRegion = $ovhConfig['raw_offer']['region'] ?? $ovhConfig['raw_offer']['location'] ?? null;
    $datacenter = $ovhRegion ?? 'GHC Cloud European Network';
    $osList = $ovhConfig['os_list'] ?? ['Ubuntu 22.04', 'Ubuntu 24.04', 'Debian 12', 'Rocky Linux 9', 'Rocky Linux 8', 'AlmaLinux 9', 'AlmaLinux 8', 'Debian 11'];
    $selectedOs = session('vps_selected_os', $osList[0] ?? 'Ubuntu 22.04');
@endphp

@section('title', 'Configure ' . $vpsPlan->display_name . ' - Believoo Cloud')

@section('content')
<div style="background: linear-gradient(135deg, #0a0e1a 0%, #1a1f2e 100%); min-height: 100vh; padding: 60px 20px;">
    <div style="max-width: 900px; margin: 0 auto;">

        {{-- Back Link --}}
        <a href="{{ route('vps-plans.category', $vpsPlan->category) }}"
           style="display: inline-flex; align-items: center; gap: 8px; color: #00b7ff; text-decoration: none; font-size: 0.85rem; font-weight: 600; margin-bottom: 32px;">
            <i class="fas fa-arrow-left"></i> Back to Plans
        </a>

        <div style="display: grid; grid-template-columns: 1fr 360px; gap: 32px; align-items: start;">

            {{-- Left: Plan Details --}}
            <div>
                <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
                        <div style="width: 56px; height: 56px; background: rgba(0,183,255,0.15); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-server" style="color: #00b7ff; font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h1 style="font-size: 1.75rem; font-weight: 800; color: #fff; margin: 0;">
                                {{ $vpsPlan->display_name ?? $vpsPlan->name }}
                            </h1>
                            <p style="color: #8b9bb4; font-size: 0.9rem; margin: 4px 0 0;">
                                {{ ucfirst($vpsPlan->category) }} VPS • {{ $datacenter }}
                                @if($isOvh)
                                    <span style="color: #22c55e; margin-left: 8px;"><i class="fas fa-check-circle"></i> Cloud Powered</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Specs Grid --}}
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
                        <div style="background: rgba(0,183,255,0.08); border: 1px solid rgba(0,183,255,0.2); border-radius: 12px; padding: 16px;">
                            <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
                                <i class="fas fa-microchip" style="margin-right: 6px; color: #00b7ff;"></i>vCPU Cores
                            </div>
                            <div style="font-size: 1.75rem; font-weight: 800; color: #00b7ff;">{{ $vpsPlan->cpu_cores }}</div>
                        </div>
                        <div style="background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.2); border-radius: 12px; padding: 16px;">
                            <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
                                <i class="fas fa-memory" style="margin-right: 6px; color: #8b5cf6;"></i>RAM
                            </div>
                            <div style="font-size: 1.75rem; font-weight: 800; color: #8b5cf6;">{{ $vpsPlan->memory_gb }} GB</div>
                        </div>
                        <div style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2); border-radius: 12px; padding: 16px;">
                            <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
                                <i class="fas fa-hdd" style="margin-right: 6px; color: #f59e0b;"></i>Storage
                            </div>
                            <div style="font-size: 1.75rem; font-weight: 800; color: #f59e0b;">{{ $vpsPlan->disk_gb }} GB</div>
                            <div style="font-size: 0.7rem; color: #8b9bb4; margin-top: 2px;">{{ $vpsPlan->disk_type }}</div>
                        </div>
                        <div style="background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2); border-radius: 12px; padding: 16px;">
                            <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
                                <i class="fas fa-network-wired" style="margin-right: 6px; color: #22c55e;"></i>Bandwidth
                            </div>
                            <div style="font-size: 1.25rem; font-weight: 800; color: #22c55e;">
                                {{ $vpsPlan->unlimited_traffic ? 'Unlimited' : ($vpsPlan->bandwidth ?? '1 Gbps') }}
                            </div>
                        </div>
                    </div>

                    {{-- Features --}}
                    <div style="border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px;">
                        <h3 style="font-size: 0.85rem; font-weight: 700; color: #8b9bb4; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">Included Features</h3>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 8px; color: #d1d5db; font-size: 0.85rem;">
                                <i class="fas fa-check-circle" style="color: #22c55e;"></i> DDoS Protection
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; color: #d1d5db; font-size: 0.85rem;">
                                <i class="fas fa-check-circle" style="color: #22c55e;"></i> 99.9% Uptime SLA
                            </div>
                            @if($vpsPlan->daily_backup)
                            <div style="display: flex; align-items: center; gap: 8px; color: #d1d5db; font-size: 0.85rem;">
                                <i class="fas fa-check-circle" style="color: #22c55e;"></i> Daily Backups
                            </div>
                            @endif
                            @if($vpsPlan->installation_free)
                            <div style="display: flex; align-items: center; gap: 8px; color: #d1d5db; font-size: 0.85rem;">
                                <i class="fas fa-check-circle" style="color: #22c55e;"></i> Free Setup
                            </div>
                            @endif
                            <div style="display: flex; align-items: center; gap: 8px; color: #d1d5db; font-size: 0.85rem;">
                                <i class="fas fa-check-circle" style="color: #22c55e;"></i> Full Root Access
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; color: #d1d5db; font-size: 0.85rem;">
                                <i class="fas fa-check-circle" style="color: #22c55e;"></i> KVM Virtualization
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; color: #d1d5db; font-size: 0.85rem;">
                                <i class="fas fa-check-circle" style="color: #22c55e;"></i> {{ $datacenter }}
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; color: #d1d5db; font-size: 0.85rem;">
                                <i class="fas fa-check-circle" style="color: #22c55e;"></i> Expert Support
                            </div>
                        </div>
                    </div>
                </div>

                {{-- OS Selection --}}
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <h3 style="font-size: 0.85rem; font-weight: 700; color: #8b9bb4; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">
                        <i class="fas fa-compact-disc" style="margin-right: 8px; color: #00b7ff;"></i>Operating System
                    </h3>
                    <form id="os-form" action="{{ route('vps-plans.set-os', $vpsPlan->slug) }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        @foreach($osList as $os)
                        <button type="button"
                                onclick="selectOs('{{ $os }}')"
                                style="padding: 8px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
                                       background: {{ $selectedOs === $os ? 'rgba(0,183,255,0.2)' : 'rgba(0,183,255,0.05)' }};
                                       border: {{ $selectedOs === $os ? '1px solid #00b7ff' : '1px solid rgba(0,183,255,0.2)' }};
                                       color: {{ $selectedOs === $os ? '#00b7ff' : '#8b9bb4' }};">
                            {{ $os }}
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="os" id="selected-os" value="{{ $selectedOs }}">
                    <p style="font-size: 0.75rem; color: #6b7280; margin-top: 10px;">
                        <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                        Selected OS will be used during provisioning after checkout.
                    </p>
                </div>

                @if($isOvh)
                <div style="background: rgba(34,197,94,0.05); border: 1px solid rgba(34,197,94,0.15); border-radius: 12px; padding: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px; color: #22c55e; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">
                        <i class="fas fa-shield-alt"></i> GHC Cloud Powered
                    </div>
                    <p style="font-size: 0.75rem; color: #8b9bb4; margin: 0;">
                        This plan is provisioned directly on GHC Cloud infrastructure. Your service will be ordered from Cloud and managed through your Believoo dashboard.
                    </p>
                </div>
                @endif
            </div>

            {{-- Right: Order Summary & Checkout --}}
            <div style="position: sticky; top: 20px;">
                <div style="background: linear-gradient(145deg, rgba(30,41,59,0.9) 0%, rgba(15,23,42,0.95) 100%); border: 1px solid rgba(0,183,255,0.2); border-radius: 16px; padding: 28px;">
                    <h2 style="font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-shopping-cart" style="color: #00b7ff;"></i> Order Summary
                    </h2>

                    {{-- Plan Name --}}
                    <div style="padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <div style="font-size: 0.8rem; color: #8b9bb4;">Plan</div>
                        <div style="font-size: 1rem; font-weight: 700; color: #fff; margin-top: 2px;">
                            {{ $vpsPlan->display_name ?? $vpsPlan->name }}
                        </div>
                    </div>

                    {{-- Specs Summary --}}
                    <div style="padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <div style="font-size: 0.75rem; color: #8b9bb4; margin-bottom: 8px;">Configuration</div>
                        <div style="font-size: 0.85rem; color: #d1d5db; line-height: 1.8;">
                            {{ $vpsPlan->cpu_cores }} vCores • {{ $vpsPlan->memory_gb }} GB RAM<br>
                            {{ $vpsPlan->disk_gb }} GB {{ $vpsPlan->disk_type }} • {{ $datacenter }}
                        </div>
                    </div>

                    {{-- Price --}}
                    <div style="padding: 16px 0; border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <span style="font-size: 0.8rem; color: #8b9bb4;">Monthly Price</span>
                            <span style="font-size: 1.75rem; font-weight: 800; color: #00b7ff;">
                                ₹{{ number_format($vpsPlan->price_monthly, 0) }}
                            </span>
                        </div>
                        <div style="font-size: 0.75rem; color: #6b7280; text-align: right; margin-top: 2px;">
                            + 18% GST applicable
                        </div>
                        @if($vpsPlan->cost_price)
                        <div style="font-size: 0.7rem; color: #8b9bb4; text-align: right; margin-top: 4px;">
                            Cloud cost: ₹{{ number_format($vpsPlan->cost_price, 0) }} | Margin included
                        </div>
                        @endif
                        @if($vpsPlan->installation_free)
                        <div style="font-size: 0.75rem; color: #22c55e; text-align: right; margin-top: 4px;">
                            <i class="fas fa-check"></i> Setup fee: Free
                        </div>
                        @endif
                    </div>

                    {{-- Proceed to Checkout Button --}}
                    @auth
                        @php
                            $vpsService = \App\Models\Service::where('slug', 'vps')->first();
                        @endphp

                        @if($vpsService)
                            <a href="{{ route('checkout', ['service' => $vpsService->slug, 'tier' => $vpsPlan->name]) }}"
                               style="display: block; width: 100%; padding: 16px; margin-top: 20px;
                                      background: linear-gradient(90deg, #00b7ff, #0066cc);
                                      color: #fff; text-align: center; text-decoration: none;
                                      border-radius: 12px; font-size: 1rem; font-weight: 800;
                                      text-transform: uppercase; letter-spacing: 1px;
                                      transition: all 0.2s; box-shadow: 0 4px 20px rgba(0,183,255,0.3);">
                                <i class="fas fa-rocket" style="margin-right: 8px;"></i>Proceed to Checkout
                            </a>
                        @else
                            <div style="padding: 16px; margin-top: 20px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; color: #ef4444; font-size: 0.85rem; text-align: center;">
                                <i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i>
                                Checkout service not configured. Contact support.
                            </div>
                        @endif
                    @else
                        <a href="{{ route('login') }}?redirect={{ urlencode(request()->fullUrl()) }}"
                           style="display: block; width: 100%; padding: 16px; margin-top: 20px;
                                  background: linear-gradient(90deg, #00b7ff, #0066cc);
                                  color: #fff; text-align: center; text-decoration: none;
                                  border-radius: 12px; font-size: 1rem; font-weight: 800;
                                  text-transform: uppercase; letter-spacing: 1px;">
                            <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>Login to Order
                        </a>
                        <p style="font-size: 0.75rem; color: #6b7280; text-align: center; margin-top: 10px;">
                            Don't have an account?
                            <a href="{{ route('register') }}" style="color: #00b7ff;">Sign up free</a>
                        </p>
                    @endauth

                    {{-- Trust Badges --}}
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.06);">
                        <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                            <span style="font-size: 0.7rem; color: #6b7280; display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-shield-alt" style="color: #22c55e;"></i> Secure Payment
                            </span>
                            <span style="font-size: 0.7rem; color: #6b7280; display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-bolt" style="color: #f59e0b;"></i> Automatic Setup
                            </span>
                            <span style="font-size: 0.7rem; color: #6b7280; display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-headset" style="color: #00b7ff;"></i> Expert Support
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function selectOs(os) {
        document.getElementById('selected-os').value = os;
        // Update visual selection
        const buttons = document.querySelectorAll('#os-form + div button, #os-form + div + input + p button');
        // Actually buttons are siblings; simpler: reload page with query param or submit form via AJAX
        fetch('{{ route('vps-plans.set-os', $vpsPlan->slug) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ os: os })
        }).then(() => {
            window.location.reload();
        });
    }
</script>
@endsection

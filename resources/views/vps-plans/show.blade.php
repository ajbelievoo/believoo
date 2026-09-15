@extends('layouts.app')

@section('title', $vpsPlan->display_name . ' - ' . ucfirst($vpsPlan->category) . ' VPS in India | Believoo')

@section('meta_description', 'Buy ' . ($vpsPlan->display_name ?? $vpsPlan->name) . ' with ' . $vpsPlan->cpu_cores . ' vCPU, ' . $vpsPlan->memory_gb . ' GB RAM, ' . $vpsPlan->disk_gb . ' GB ' . $vpsPlan->disk_type . ' storage. Affordable ' . ucfirst($vpsPlan->category) . ' VPS hosting in India by Believoo.')

@section('meta_keywords', 'VPS hosting India, ' . $vpsPlan->name . ', ' . ucfirst($vpsPlan->category) . ' VPS, cloud VPS, cheap VPS India, Believoo VPS')

@section('content')
<div style="background: linear-gradient(135deg, #0a0e1a 0%, #1a1f2e 100%); min-height: 100vh; padding: 60px 20px;">
    <div style="max-width: 900px; margin: 0 auto;">

        <a href="{{ route('vps-plans.category', $vpsPlan->category) }}"
           style="display: inline-flex; align-items: center; gap: 8px; color: #00b7ff; text-decoration: none; font-size: 0.85rem; font-weight: 600; margin-bottom: 32px;">
            <i class="fas fa-arrow-left"></i> Back to Plans
        </a>

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
                        {{ ucfirst($vpsPlan->category) }} VPS
                        @if(!empty($vpsPlan->ovh_plan_code))
                            <span style="color: #22c55e; margin-left: 8px;"><i class="fas fa-check-circle"></i> GHC Cloud Powered</span>
                        @endif
                    </p>
                </div>
            </div>

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

            <div style="border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px;">
                <h3 style="font-size: 0.85rem; font-weight: 700; color: #8b9bb4; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">Included Features</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    @foreach($vpsPlan->getDisplayFeaturesAttribute() as $feature)
                    <div style="display: flex; align-items: center; gap: 8px; color: #d1d5db; font-size: 0.85rem;">
                        <i class="fas fa-check-circle" style="color: #22c55e;"></i> {{ $feature }}
                    </div>
                    @endforeach
                </div>
            </div>

            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.08);">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="font-size: 0.8rem; color: #8b9bb4;">Starting from</div>
                        <div style="font-size: 2rem; font-weight: 800; color: #00b7ff;">
                            ₹{{ number_format($vpsPlan->price_monthly, 0) }}<span style="font-size: 0.9rem; color: #8b9bb4;">/mo</span>
                        </div>
                    </div>
                    @if($vpsPlan->isAvailable())
                        <a href="{{ route('vps-plans.configure', $vpsPlan->slug) }}"
                           style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; background: linear-gradient(90deg, #00b7ff, #0066cc); color: #fff; text-decoration: none; border-radius: 12px; font-weight: 700;">
                            <i class="fas fa-shopping-cart"></i> Configure & Order
                        </a>
                    @else
                        <button disabled style="padding: 14px 28px; background: #4b5563; color: #9ca3af; border: none; border-radius: 12px; font-weight: 700; cursor: not-allowed;">
                            SOLD OUT
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if($relatedPlans->count())
        <div style="margin-top: 40px;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 16px;">Related Plans</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                @foreach($relatedPlans as $plan)
                <a href="{{ route('vps-plans.show', $plan->slug) }}" style="text-decoration: none;">
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; color: #fff;">
                        <div style="font-weight: 700; margin-bottom: 4px;">{{ $plan->display_name ?? $plan->name }}</div>
                        <div style="font-size: 0.85rem; color: #8b9bb4;">₹{{ number_format($plan->price_monthly, 0) }}/mo</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

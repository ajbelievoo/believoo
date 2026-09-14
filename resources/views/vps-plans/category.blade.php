@extends('layouts.app')

@section('title', $categoryInfo['name'] . ' VPS Hosting in India | Believoo Cloud')

@section('meta_description', 'Explore ' . $categoryInfo['name'] . ' VPS plans in India. ' . $categoryInfo['description'] . ' High-performance cloud VPS with 24/7 support from Believoo.')

@section('meta_keywords', $categoryInfo['name'] . ' VPS, India VPS, cloud VPS, ' . strtolower($category) . ' vps hosting, Believoo')

@section('content')
@php
    $userCurrency = session('currency', 'USD');
    $rate = \App\Models\ExchangeRate::getUsdToInrRate();
@endphp
<div style="background: linear-gradient(135deg, #0a0e1a 0%, #1a1f2e 100%); min-height: 100vh;">
    
    {{-- Header Section --}}
    <div style="background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%); padding: 60px 0;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                <div>
                    <h1 style="font-size: 2.5rem; font-weight: 700; color: #fff; margin-bottom: 12px;">
                        {{ $categoryInfo['name'] }}
                    </h1>
                    <p style="font-size: 1.1rem; color: rgba(255,255,255,0.9); max-width: 600px;">
                        {{ $categoryInfo['description'] }}
                    </p>
                </div>
                {{-- Currency Toggle --}}
                <div style="background: rgba(255,255,255,0.1); border-radius: 9999px; padding: 4px; display: flex;">
                    <a href="{{ request()->fullUrlWithQuery(['currency' => 'USD']) }}" 
                       style="padding: 8px 20px; border-radius: 9999px; font-size: 0.875rem; font-weight: 600; text-decoration: none; {{ $userCurrency === 'USD' ? 'background: #fff; color: #1976d2;' : 'color: #fff;' }}">
                        🇺🇸 USD ($)
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['currency' => 'INR']) }}" 
                       style="padding: 8px 20px; border-radius: 9999px; font-size: 0.875rem; font-weight: 600; text-decoration: none; {{ $userCurrency === 'INR' ? 'background: #fff; color: #1976d2;' : 'color: #fff;' }}">
                        🇮🇳 INR (₹)
                    </a>
                </div>
            </div>
            <p style="font-size: 0.75rem; color: rgba(255,255,255,0.7); margin-top: 10px;">
                <i class="fas fa-sync-alt"></i> 1 USD = ₹{{ number_format($rate, 2) }} • Updated hourly
            </p>
        </div>
    </div>

    {{-- Submenu Navigation --}}
    <div style="background: #fff; border-bottom: 1px solid #e0e0e0;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: flex; gap: 0; overflow-x: auto;">
                @foreach($allCategories as $catKey => $catInfo)
                    <a href="{{ route('vps-plans.category', array_merge(['category' => $catKey], request('currency') ? ['currency' => request('currency')] : [])) }}" 
                       style="padding: 20px 30px; color: {{ $catKey === $category ? '#1976d2' : '#666' }}; 
                              text-decoration: none; font-weight: {{ $catKey === $category ? '600' : '400' }}; 
                              border-bottom: 3px solid {{ $catKey === $category ? '#1976d2' : 'transparent' }}; 
                              white-space: nowrap; transition: all 0.2s;">
                        <i class="fas {{ $catInfo['icon'] }}" style="margin-right: 8px;"></i>
                        {{ $catInfo['name'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Plans Grid --}}
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 60px 20px;">
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
            @foreach($plans as $plan)
            <div style="background: #fff; border-radius: 8px; overflow: hidden; 
                        box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
                        position: relative; {{ $plan->is_sold_out ? 'opacity: 0.7;' : '' }}">
                
                {{-- Recommended Badge --}}
                @if($plan->is_recommended)
                <div style="position: absolute; top: 0; left: 0; right: 0; background: #00b7ff; color: #fff; 
                            text-align: center; padding: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                    Recommended
                </div>
                @endif

                <div style="padding: 30px; {{ $plan->is_recommended ? 'padding-top: 50px;' : '' }}">
                    {{-- Plan Name --}}
                    <h3 style="font-size: 1.3rem; font-weight: 700; color: #333; margin-bottom: 8px;">
                        {{ $plan->name }}
                    </h3>
                    
                    {{-- Price --}}
                    @php
                        // price_monthly is in INR (as shown in admin panel)
                        $priceInr = $plan->price_monthly ?? 0;
                        // Convert to USD using exchange rate
                        $priceUsd = $priceInr / $rate;
                        
                        if ($userCurrency === 'INR') {
                            $displayPrice = round($priceInr);
                            $priceSymbol = '₹';
                            $priceFormatted = number_format($displayPrice, 0);
                        } else {
                            $displayPrice = $priceUsd;
                            $priceSymbol = '$';
                            $priceFormatted = number_format($displayPrice, 2);
                        }
                    @endphp
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 0.8rem; color: #666; margin-bottom: 4px;">From</div>
                        <div style="font-size: 2rem; font-weight: 700; color: #1976d2;">
                            {{ $priceSymbol }}{{ $priceFormatted }}
                        </div>
                        <div style="font-size: 0.8rem; color: #666;">ex. GST/month</div>
                        @if($userCurrency === 'INR')
                            <div style="font-size: 0.75rem; color: #666; margin-top: 4px;">(${{ number_format($priceUsd, 2) }} USD)</div>
                        @else
                            <div style="font-size: 0.75rem; color: #666; margin-top: 4px;">(₹{{ number_format($priceInr, 0) }} INR)</div>
                        @endif
                        @if($plan->installation_free)
                        <div style="font-size: 0.75rem; color: #22c55e; margin-top: 4px;">
                            <i class="fas fa-check"></i> Installation fees: Free
                        </div>
                        @endif
                    </div>

                    {{-- Configure Button --}}
                    @if($plan->is_sold_out)
                        <button disabled 
                                style="width: 100%; padding: 14px; background: #ccc; color: #666; border: none; 
                                       border-radius: 4px; font-weight: 600; cursor: not-allowed;">
                            SOLD OUT
                        </button>
                    @else
                        <a href="{{ route('vps-plans.configure', $plan->slug) }}" 
                           style="display: block; width: 100%; padding: 14px; background: #e53935; color: #fff; 
                                  text-align: center; text-decoration: none; border-radius: 4px; 
                                  font-weight: 600; transition: background 0.2s;">
                            Configure
                        </a>
                    @endif

                    {{-- Specs --}}
                    <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #eee;">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="padding: 8px 0; color: #333; font-size: 0.9rem; display: flex; align-items: center;">
                                <i class="fas fa-check" style="color: #22c55e; margin-right: 12px; width: 16px;"></i>
                                {{ $plan->cpu_cores }} vCores
                            </li>
                            <li style="padding: 8px 0; color: #333; font-size: 0.9rem; display: flex; align-items: center;">
                                <i class="fas fa-check" style="color: #22c55e; margin-right: 12px; width: 16px;"></i>
                                {{ $plan->memory_gb }} GB RAM
                            </li>
                            <li style="padding: 8px 0; color: #333; font-size: 0.9rem; display: flex; align-items: center;">
                                <i class="fas fa-check" style="color: #22c55e; margin-right: 12px; width: 16px;"></i>
                                {{ $plan->disk_gb }} GB {{ $plan->disk_type }}
                                @if($plan->disk_type === 'NVMe')
                                    <span style="margin-left: 8px; color: #00b7ff; font-size: 0.75rem;">
                                        <i class="fas fa-bolt"></i>
                                    </span>
                                @endif
                            </li>
                            @if($plan->daily_backup)
                            <li style="padding: 8px 0; color: #333; font-size: 0.9rem; display: flex; align-items: center;">
                                <i class="fas fa-check" style="color: #22c55e; margin-right: 12px; width: 16px;"></i>
                                Daily backup of the previous 24 hours
                                <span style="margin-left: 8px; color: #00b7ff; cursor: pointer;">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </li>
                            @endif
                            @if($plan->unlimited_traffic)
                            <li style="padding: 8px 0; color: #333; font-size: 0.9rem; display: flex; align-items: center;">
                                <i class="fas fa-check" style="color: #22c55e; margin-right: 12px; width: 16px;"></i>
                                Unlimited traffic
                                <span style="margin-left: 8px; color: #00b7ff; cursor: pointer;">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </li>
                            @endif
                            <li style="padding: 8px 0; color: #333; font-size: 0.9rem; display: flex; align-items: center;">
                                <i class="fas fa-check" style="color: #22c55e; margin-right: 12px; width: 16px;"></i>
                                {{ $plan->bandwidth }} bandwidth
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($plans->isEmpty())
        <div style="text-align: center; padding: 80px 40px;">
            <i class="fas fa-server" style="font-size: 4rem; color: #ccc; margin-bottom: 24px;"></i>
            <h3 style="font-size: 1.5rem; color: #666; margin-bottom: 12px;">No Plans Available</h3>
            <p style="color: #999;">Check back soon for new VPS plans in this category.</p>
        </div>
        @endif
    </div>

    {{-- Info Section --}}
    <div style="background: #f5f5f5; padding: 60px 0;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px;">
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: 600; color: #333; margin-bottom: 16px;">
                        <i class="fas fa-shield-alt" style="color: #1976d2; margin-right: 8px;"></i>
                        Included DDoS Protection
                    </h4>
                    <p style="color: #666; font-size: 0.9rem; line-height: 1.6;">
                        All our VPS plans include free DDoS protection to keep your services online.
                    </p>
                </div>
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: 600; color: #333; margin-bottom: 16px;">
                        <i class="fas fa-hdd" style="color: #1976d2; margin-right: 8px;"></i>
                        NVMe SSD Storage
                    </h4>
                    <p style="color: #666; font-size: 0.9rem; line-height: 1.6;">
                        High-performance NVMe SSD storage for faster read/write operations.
                    </p>
                </div>
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: 600; color: #333; margin-bottom: 16px;">
                        <i class="fas fa-globe" style="color: #1976d2; margin-right: 8px;"></i>
                        Global Network
                    </h4>
                    <p style="color: #666; font-size: 0.9rem; line-height: 1.6;">
                        Low latency network with multiple locations worldwide.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

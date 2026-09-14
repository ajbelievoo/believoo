@extends('layouts.admin')

@section('title', 'Exchange Rates')

@section('content')
<div class="page-header" style="color: var(--text-primary);">
    <h1 class="page-title" style="color: var(--text-primary);">Exchange Rates</h1>
    <p class="page-subtitle" style="color: var(--text-muted);">Manage currency conversion rates (USD to INR)</p>
</div>

@if(session('success'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; color: #22c55e;">
    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>{{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; color: #ef4444;">
    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>{{ session('error') }}
</div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
    
    <!-- Current Rate Card -->
    <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-exchange-alt" style="font-size: 1.25rem; color: white;"></i>
            </div>
            <div>
                <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--text-primary); margin: 0;">USD to INR Rate</h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">Last updated: {{ $exchangeRate->fetched_at->diffForHumans() }}</p>
            </div>
        </div>

        <div style="background: var(--bg-tertiary); border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 20px;">
            <div style="font-size: 3rem; font-weight: 800; color: #22c55e; margin-bottom: 8px;">
                ₹{{ number_format($exchangeRate->rate, 2) }}
            </div>
            <div style="font-size: 1rem; color: var(--text-muted);">1 USD = {{ number_format($exchangeRate->rate, 2) }} INR</div>
        </div>

        <div style="background: rgba(0, 183, 255, 0.05); border: 1px solid rgba(0, 183, 255, 0.2); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;">
                <i class="fas fa-calculator" style="margin-right: 8px; color: #00b7ff;"></i>Example Conversions
            </h4>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; text-align: center;">
                <div style="background: var(--bg-primary); border-radius: 8px; padding: 12px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">$5 USD</div>
                    <div style="font-size: 1rem; font-weight: 600; color: var(--text-primary);">₹{{ number_format(5 * $exchangeRate->rate, 0) }}</div>
                </div>
                <div style="background: var(--bg-primary); border-radius: 8px; padding: 12px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">$10 USD</div>
                    <div style="font-size: 1rem; font-weight: 600; color: var(--text-primary);">₹{{ number_format(10 * $exchangeRate->rate, 0) }}</div>
                </div>
                <div style="background: var(--bg-primary); border-radius: 8px; padding: 12px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">$50 USD</div>
                    <div style="font-size: 1rem; font-weight: 600; color: var(--text-primary);">₹{{ number_format(50 * $exchangeRate->rate, 0) }}</div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.exchange-rates.fetch-live') }}" method="POST" style="margin-bottom: 12px;">
            @csrf
            <button type="submit" style="width: 100%; padding: 14px 24px; background: linear-gradient(135deg, #00b7ff 0%, #0099ff 100%); color: #000000; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-sync-alt"></i>
                Fetch Live Rate from API
            </button>
        </form>
        <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center; margin: 0;">
            <i class="fas fa-info-circle" style="margin-right: 4px;"></i>Updates rate from exchangerate-api.com
        </p>
    </div>

    <!-- Update Rate Card -->
    <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-edit" style="font-size: 1.25rem; color: white;"></i>
            </div>
            <div>
                <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--text-primary); margin: 0;">Manual Update</h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">Set custom exchange rate</p>
            </div>
        </div>

        <form action="{{ route('admin.exchange-rates.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">
                    <i class="fas fa-dollar-sign" style="margin-right: 6px; color: #22c55e;"></i>1 USD equals
                </label>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 1.5rem; font-weight: 700; color: #22c55e;">₹</span>
                    <input type="number" name="rate" value="{{ $exchangeRate->rate }}" step="0.01" min="1" max="500" required
                        style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 14px 16px; font-size: 1.25rem; font-weight: 600; color: var(--text-primary);">
                    <span style="font-size: 0.875rem; color: var(--text-muted); white-space: nowrap;">INR</span>
                </div>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">
                    Enter how many Indian Rupees equal 1 US Dollar
                </p>
            </div>

            <div style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;">
                    <i class="fas fa-lightbulb" style="margin-right: 8px; color: #f59e0b;"></i>Quick Presets
                </h4>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <button type="button" onclick="setRate(82.00)" style="padding: 8px 16px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem; color: var(--text-primary); cursor: pointer;">₹82.00</button>
                    <button type="button" onclick="setRate(83.00)" style="padding: 8px 16px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem; color: var(--text-primary); cursor: pointer;">₹83.00</button>
                    <button type="button" onclick="setRate(84.00)" style="padding: 8px 16px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem; color: var(--text-primary); cursor: pointer;">₹84.00</button>
                    <button type="button" onclick="setRate(85.00)" style="padding: 8px 16px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem; color: var(--text-primary); cursor: pointer;">₹85.00</button>
                </div>
            </div>

            <button type="submit" style="width: 100%; padding: 14px 24px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-save"></i>
                Save Exchange Rate
            </button>
        </form>
    </div>
</div>

<script>
function setRate(value) {
    document.querySelector('input[name="rate"]').value = value;
}
</script>
@endsection

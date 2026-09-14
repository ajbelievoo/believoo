@extends('layouts.app')

@section('title', 'Register Domain - ' . $domain)

@section('content')
<style>
.domain-register-wrapper {
    background: #050505;
    min-height: 100vh;
    padding: 120px 20px 60px;
    position: relative;
}
.domain-register-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(at 0% 0%, rgba(0, 183, 255, 0.08) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(112, 0, 255, 0.08) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(255, 0, 160, 0.05) 0px, transparent 50%);
    pointer-events: none;
}
.register-container {
    max-width: 900px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}
.register-header {
    text-align: center;
    margin-bottom: 40px;
}
.register-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 12px;
    background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.8) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.register-header p {
    font-size: 1.1rem;
    color: rgba(255,255,255,0.5);
}
.register-header strong {
    color: #00b7ff;
    font-weight: 600;
}
.glass-card {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.9) 0%, rgba(17, 17, 17, 0.95) 100%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    padding: 30px;
    backdrop-filter: blur(20px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 30px;
}
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
.section-title {
    font-size: 1.1rem;
    color: #fff;
    margin-bottom: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-title i {
    color: #00b7ff;
}
.year-btn {
    padding: 12px 20px;
    background: rgba(0,0,0,0.3);
    border: 2px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: rgba(255,255,255,0.5);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-block;
}
.year-btn:hover {
    border-color: rgba(0, 183, 255, 0.5);
    color: rgba(255,255,255,0.8);
}
.year-btn.active {
    border-color: #00b7ff;
    background: rgba(0, 183, 255, 0.15);
    color: #fff;
    box-shadow: 0 0 20px rgba(0, 183, 255, 0.2);
}
.form-input-dark {
    width: 100%;
    padding: 14px 16px;
    background: rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: #fff;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}
.form-input-dark:focus {
    outline: none;
    border-color: rgba(0, 183, 255, 0.5);
    box-shadow: 0 0 20px rgba(0, 183, 255, 0.1);
}
.form-label-dark {
    display: block;
    color: rgba(255,255,255,0.5);
    font-size: 0.85rem;
    margin-bottom: 8px;
    font-weight: 500;
}
.submit-btn {
    width: 100%;
    padding: 18px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.3);
}
.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(34, 197, 94, 0.4);
}
</style>

<div class="domain-register-wrapper">
    <div class="register-container">

    {{-- Header --}}
    <div class="register-header">
        <h1>Complete Your Registration</h1>
        <p>You're about to register <strong>{{ $domain }}</strong></p>
    </div>

    <div class="form-grid">

        {{-- Main Form --}}
        <div class="glass-card">
            {{-- Error Messages --}}
            @if($errors->any())
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: #ef4444; font-weight: 600;">
                        <i class="fas fa-exclamation-circle"></i>
                        Please fix the following errors:
                    </div>
                    <ul style="margin: 0; padding-left: 20px; color: #ef4444; font-size: 0.9rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Success/Error Messages from Session --}}
            @if(session('error'))
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 16px; margin-bottom: 24px; color: #ef4444;">
                    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>{{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; padding: 16px; margin-bottom: 24px; color: #22c55e;">
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>{{ session('success') }}
                </div>
            @endif

            <form id="registrationForm" action="{{ route('client.domains.register') }}" method="POST">
                @csrf
                <input type="hidden" name="domain" value="{{ $domain }}">

                {{-- Registration Period --}}
                <div style="margin-bottom: 30px;">
                    <label class="form-label-dark" style="font-size: 0.9rem; margin-bottom: 12px;">
                        Registration Period
                    </label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        @foreach([1, 2, 3, 5, 10] as $year)
                        <label style="cursor: pointer;">
                            <input type="radio" name="years" value="{{ $year }}" {{ $year == 1 ? 'checked' : '' }} style="display: none;">
                            <div class="year-btn year-option" data-years="{{ $year }}">
                                {{ $year }} Year{{ $year > 1 ? 's' : '' }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Contact Information --}}
                <h3 class="section-title">
                    <i class="fas fa-user"></i>Registrant Contact
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label class="form-label-dark">First Name *</label>
                        <input type="text" name="registrant[first_name]" required value="{{ auth()->user()->first_name ?? '' }}"
                               class="form-input-dark">
                    </div>
                    <div>
                        <label class="form-label-dark">Last Name *</label>
                        <input type="text" name="registrant[last_name]" required value="{{ auth()->user()->last_name ?? '' }}"
                               class="form-input-dark">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label class="form-label-dark">Email *</label>
                        <input type="email" name="registrant[email]" required value="{{ auth()->user()->email ?? '' }}"
                               class="form-input-dark">
                    </div>
                    <div>
                        <label class="form-label-dark">Phone *</label>
                        <input type="tel" name="registrant[phone]" required value="{{ auth()->user()->phone ?? '' }}" placeholder="+91 98765 43210"
                               class="form-input-dark">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="form-label-dark">Address *</label>
                    <input type="text" name="registrant[address1]" required
                           class="form-input-dark">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label class="form-label-dark">City *</label>
                        <input type="text" name="registrant[city]" required
                               class="form-input-dark">
                    </div>
                    <div>
                        <label class="form-label-dark">State *</label>
                        <input type="text" name="registrant[state]" required
                               class="form-input-dark">
                    </div>
                    <div>
                        <label class="form-label-dark">ZIP Code *</label>
                        <input type="text" name="registrant[zip]" required
                               class="form-input-dark">
                    </div>
                </div>

                <div style="margin-bottom: 30px;">
                    <label class="form-label-dark">Country *</label>
                    <select name="registrant[country]" required
                            class="form-input-dark" style="cursor: pointer;">
                        <option value="IN" selected>India (IN)</option>
                        <option value="US">United States (US)</option>
                        <option value="GB">United Kingdom (GB)</option>
                        <option value="CA">Canada (CA)</option>
                        <option value="AU">Australia (AU)</option>
                        <option value="DE">Germany (DE)</option>
                        <option value="FR">France (FR)</option>
                        <option value="SG">Singapore (SG)</option>
                        <option value="AE">UAE (AE)</option>
                    </select>
                </div>

                {{-- Options --}}
                <h3 class="section-title">
                    <i class="fas fa-cog"></i>Domain Options
                </h3>

                <div style="margin-bottom: 15px;">
                    <label style="display: flex; align-items: center; cursor: pointer; color: #fff;">
                        <input type="checkbox" name="whois_privacy" value="1" checked style="margin-right: 10px; width: 18px; height: 18px; accent-color: #00b7ff;">
                        <span>Enable WHOIS Privacy Protection</span>
                        <span style="margin-left: auto; color: #22c55e; font-size: 0.85rem; font-weight: 700;">Free</span>
                    </label>
                    <small style="display: block; margin-left: 28px; margin-top: 4px; color: rgba(255,255,255,0.3); font-size: 0.8rem;">Hide your contact information from public WHOIS lookups</small>
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="display: flex; align-items: center; cursor: pointer; color: #fff;">
                        <input type="checkbox" name="auto_renew" value="1" checked style="margin-right: 10px; width: 18px; height: 18px; accent-color: #00b7ff;">
                        <span>Auto-renew domain</span>
                    </label>
                    <small style="display: block; margin-left: 28px; margin-top: 4px; color: rgba(255,255,255,0.3); font-size: 0.8rem;">Automatically renew before expiration to prevent losing your domain</small>
                </div>

                {{-- Nameservers --}}
                <h3 class="section-title">
                    <i class="fas fa-server"></i>Nameservers
                </h3>

                <div style="margin-bottom: 20px;">
                    <label class="form-label-dark">Custom Nameservers (Optional)</label>
                    <textarea name="nameservers" placeholder="ns1.example.com, ns2.example.com" rows="2"
                              class="form-input-dark" style="resize: vertical; font-family: inherit;"></textarea>
                    <small style="display: block; margin-top: 6px; color: rgba(255,255,255,0.3); font-size: 0.8rem;">Leave empty to use default nameservers. Comma-separated if providing multiple.</small>
                </div>

                <button type="submit" id="submitBtn" class="submit-btn">
                    <i class="fas fa-lock" style="margin-right: 8px;"></i>Complete Registration
                </button>
            </form>
        </div>

        {{-- Sidebar Order Summary --}}
        <div class="glass-card" style="height: fit-content;">
            <h3 style="font-size: 1.1rem; color: #fff; margin-bottom: 20px; font-weight: 700;">Order Summary</h3>

            <div style="background: rgba(0,0,0,0.3); border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                    <div style="width: 44px; height: 44px; background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(22, 163, 74, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(34, 197, 94, 0.2);">
                        <i class="fas fa-globe" style="color: #22c55e; font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #fff; font-size: 0.95rem;">{{ $domain }}</div>
                        <div style="font-size: 0.8rem; color: rgba(255,255,255,0.4);">via {{ $searchResult['best_option']['provider'] ?? 'Cloudflare' }}</div>
                    </div>
                </div>

                <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: rgba(255,255,255,0.5); font-size: 0.9rem;">Registration</span>
                        <span style="color: #fff; font-size: 0.9rem; font-weight: 500;"><span id="yearCount">1</span> year</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: rgba(255,255,255,0.5); font-size: 0.9rem;">Price</span>
                        <span style="color: #fff; font-size: 0.9rem; font-weight: 500;">₹{{ number_format($price, 2) }}/yr</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: rgba(255,255,255,0.5); font-size: 0.9rem;">GST (18%)</span>
                        <span style="color: #fff; font-size: 0.9rem; font-weight: 500;" id="gstAmount">₹{{ number_format($price * 0.18, 2) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: rgba(255,255,255,0.5); font-size: 0.9rem;">WHOIS Privacy</span>
                        <span style="color: #22c55e; font-size: 0.9rem; font-weight: 700;">Free</span>
                    </div>
                </div>

                <div style="border-top: 2px solid rgba(255,255,255,0.1); padding-top: 15px; margin-top: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #fff; font-size: 1rem; font-weight: 700;">Total</span>
                        <span style="color: #22c55e; font-size: 1.6rem; font-weight: 800;" id="totalPrice">₹{{ number_format($price * 1.18, 2) }}</span>
                    </div>
                    <div style="text-align: right; margin-top: 5px;">
                        <span style="color: rgba(255,255,255,0.3); font-size: 0.75rem;">Includes 18% GST</span>
                    </div>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(0, 183, 255, 0.1) 0%, rgba(0, 153, 255, 0.05) 100%); border: 1px solid rgba(0, 183, 255, 0.2); border-radius: 12px; padding: 16px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <i class="fas fa-shield-alt" style="color: #00b7ff;"></i>
                    <span style="color: #fff; font-size: 0.9rem; font-weight: 600;">Secure Registration</span>
                </div>
                <p style="color: rgba(255,255,255,0.4); font-size: 0.8rem; margin: 0;">Your domain will be registered instantly with full DNS management.</p>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
    // Year selection styling
    document.querySelectorAll('input[name="years"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.year-btn').forEach(opt => {
                opt.classList.remove('active');
            });
            const selected = document.querySelector(`.year-btn[data-years="${this.value}"]`);
            selected.classList.add('active');

            // Update summary
            document.getElementById('yearCount').textContent = this.value;
            const basePrice = {{ $price }};
            const years = parseInt(this.value);
            const subtotal = basePrice * years;
            const gst = subtotal * 0.18;
            const total = subtotal + gst;
            document.getElementById('gstAmount').textContent = '₹' + gst.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('totalPrice').textContent = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        });
    });

    // Initialize first option
    document.querySelector('.year-btn[data-years="1"]').classList.add('active');

    // Form submission
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    });
</script>
@endsection

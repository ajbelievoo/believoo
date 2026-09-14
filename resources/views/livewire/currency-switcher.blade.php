<div class="currency-switcher" x-data="{ open: false }" @click.away="open = false">
    <style>
        .currency-switcher {
            position: relative;
            display: inline-block;
        }
        .currency-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: linear-gradient(135deg, rgba(0, 183, 255, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            border: 1px solid rgba(0, 183, 255, 0.3);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .currency-btn:hover {
            background: linear-gradient(135deg, rgba(0, 183, 255, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%);
            border-color: rgba(0, 183, 255, 0.5);
            transform: translateY(-1px);
        }
        .currency-flag {
            font-size: 1.1rem;
        }
        .currency-code {
            font-weight: 700;
            color: #00b7ff;
        }
        .currency-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 180px;
            background: linear-gradient(145deg, rgba(17, 17, 17, 0.98) 0%, rgba(10, 10, 10, 0.99) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        .currency-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .currency-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .currency-option:hover {
            background: rgba(0, 183, 255, 0.1);
        }
        .currency-option.active {
            background: rgba(0, 183, 255, 0.15);
            border: 1px solid rgba(0, 183, 255, 0.3);
        }
        .currency-option-flag {
            font-size: 1.3rem;
        }
        .currency-option-info {
            flex: 1;
        }
        .currency-option-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-primary);
        }
        .currency-option-code {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .currency-option-symbol {
            font-weight: 700;
            color: #00b7ff;
            font-size: 0.9rem;
        }
        .chevron {
            transition: transform 0.3s ease;
            font-size: 0.7rem;
            color: var(--text-muted);
        }
        [x-data] .chevron {
            transform: rotate(0deg);
        }
        [x-data] .chevron.rotate {
            transform: rotate(180deg);
        }
    </style>

    <button class="currency-btn" @click="open = !open">
        <span class="currency-flag">{{ $this->currencyData['flag'] }}</span>
        <span class="currency-code">{{ $this->currencyData['code'] }}</span>
        <i class="fas fa-chevron-down chevron" :class="{ 'rotate': open }"></i>
    </button>

    <div class="currency-dropdown" :class="{ 'show': open }">
        @foreach($availableCurrencies as $code => $data)
            <div 
                class="currency-option {{ $currentCurrency === $code ? 'active' : '' }}"
                wire:click="switchCurrency('{{ $code }}')"
                @click="open = false"
            >
                <span class="currency-option-flag">{{ $data['flag'] }}</span>
                <div class="currency-option-info">
                    <div class="currency-option-name">{{ $data['name'] }}</div>
                    <div class="currency-option-code">{{ $code }}</div>
                </div>
                <span class="currency-option-symbol">{{ $data['symbol'] }}</span>
            </div>
        @endforeach
        
        <div style="padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 4px;">
            <div style="font-size: 0.7rem; color: var(--text-muted); text-align: center;">
                <i class="fas fa-sync-alt" style="margin-right: 4px;"></i>
                Rates updated hourly
            </div>
        </div>
    </div>

    {{-- Mobile-friendly: Close on escape --}}
    <script>
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.currency-dropdown').forEach(el => {
                    el.classList.remove('show');
                });
            }
        });
    </script>
</div>

<x-layouts.believoo title="Domain Search - Find Your Perfect Domain Name">

@push('styles')
<style>
.domain-search-wrapper {
    background: #050505;
    min-height: 100vh;
    padding: 120px 20px 60px;
    position: relative;
}
.domain-search-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(at 0% 0%, rgba(0, 183, 255, 0.08) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(112, 0, 255, 0.08) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(255, 0, 160, 0.05) 0px, transparent 50%);
    pointer-events: none;
}
.search-container {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
    position: relative;
    z-index: 1;
}
.search-title {
    font-size: 3rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 16px;
    background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.8) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.search-subtitle {
    font-size: 1.1rem;
    color: rgba(255,255,255,0.5);
    margin-bottom: 40px;
    font-weight: 500;
}
.search-box-card {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.9) 0%, rgba(17, 17, 17, 0.95) 100%);
    border-radius: 20px;
    padding: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(0, 183, 255, 0.1);
    margin-bottom: 30px;
}
.search-input-wrapper {
    display: flex;
    gap: 8px;
}
.search-input {
    flex: 1;
    border: none;
    padding: 20px 24px;
    font-size: 1.1rem;
    color: #fff;
    outline: none;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
}
.search-input::placeholder {
    color: rgba(255,255,255,0.3);
}
.search-btn {
    background: linear-gradient(135deg, #00b7ff 0%, #0099ff 100%);
    color: #050505;
    border: none;
    padding: 16px 40px;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 20px rgba(0, 183, 255, 0.4);
}
.search-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 30px rgba(0, 183, 255, 0.6);
}
.search-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
.tld-cards {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 40px;
    position: relative;
    z-index: 1;
}
.tld-card {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.8) 0%, rgba(17, 17, 17, 0.9) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 14px;
    padding: 14px 22px;
    text-align: center;
    min-width: 85px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.tld-card:hover {
    background: linear-gradient(135deg, rgba(35, 35, 35, 0.9) 0%, rgba(25, 25, 25, 1) 100%);
    border-color: rgba(0, 183, 255, 0.3);
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 183, 255, 0.1);
}
.tld-name {
    font-size: 1.1rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.02em;
}
.tld-price {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.5);
    font-weight: 600;
}
.results-container {
    max-width: 900px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}
.result-card {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.9) 0%, rgba(17, 17, 17, 0.95) 100%);
    border-radius: 20px;
    padding: 24px 28px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.result-card:hover {
    transform: translateY(-3px);
    border-color: rgba(0, 183, 255, 0.3);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5), 0 0 30px rgba(0, 183, 255, 0.1);
}
.result-card.taken {
    opacity: 0.6;
    background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(15, 15, 15, 0.9) 100%);
}
.domain-info {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}
.tld-badge {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
}
.tld-badge.available {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}
.tld-badge.taken {
    background: #e0e0e0;
    color: #999;
}
.domain-details h3 {
    font-size: 1.4rem;
    color: #fff;
    margin: 0;
    font-weight: 700;
    letter-spacing: -0.01em;
}
.domain-details h3.taken {
    color: #999;
    text-decoration: line-through;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-top: 6px;
}
.status-badge.available {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(22, 163, 74, 0.1) 100%);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.2);
}
.status-badge.taken {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.1) 100%);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.2);
}
.status-badge.alternative {
    background: #E3F2FD;
    color: #2196F3;
}
.price-section {
    text-align: right;
    display: flex;
    align-items: center;
    gap: 20px;
}
.price-original {
    font-size: 0.9rem;
    color: #999;
    text-decoration: line-through;
}
.price-current {
    font-size: 1.6rem;
    font-weight: 800;
    color: #4ade80;
}
.price-current.taken {
    color: rgba(255,255,255,0.4);
}
.buy-btn {
    padding: 14px 32px;
    background: linear-gradient(135deg, #00b7ff 0%, #0099ff 100%);
    color: #050505;
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
    box-shadow: 0 4px 20px rgba(0, 183, 255, 0.3);
}
.buy-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 30px rgba(0, 183, 255, 0.5);
}
.buy-btn.taken {
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    color: rgba(255,255,255,0.5);
    box-shadow: none;
}
.buy-btn.taken:hover {
    transform: none;
    box-shadow: none;
    background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.08) 100%);
}
.great-alternative {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
    color: #fff;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.results-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    color: #fff;
}
.results-header h2 {
    font-size: 1.3rem;
    font-weight: 700;
}
.results-header .count {
    background: rgba(255,255,255,0.2);
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
}
.category-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.category-tab {
    padding: 10px 24px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 25px;
    color: rgba(255,255,255,0.6);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.category-tab:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(0, 183, 255, 0.3);
    color: rgba(255,255,255,0.9);
}
.category-tab.active {
    background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.9) 100%);
    color: #050505;
    border-color: #fff;
    font-weight: 700;
    box-shadow: 0 4px 20px rgba(255, 255, 255, 0.2);
}
.loading-state {
    text-align: center;
    padding: 60px 0;
    color: rgba(255,255,255,0.6);
}
.spinner {
    width: 50px;
    height: 50px;
    border: 3px solid rgba(0, 183, 255, 0.2);
    border-top-color: #00b7ff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
    box-shadow: 0 0 20px rgba(0, 183, 255, 0.3);
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: rgba(255,255,255,0.5);
}
.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.3;
    color: #00b7ff;
}
.empty-state h3 {
    color: #fff;
    font-weight: 700;
    margin-bottom: 8px;
}
</style>

<div class="domain-search-wrapper">
    <div class="search-container">
        <h1 class="search-title">Looking for available domains?</h1>
        <p class="search-subtitle">Use our domain checker to find your perfect web address</p>
        
        {{-- Search Box --}}
        <div class="search-box-card">
            <form id="searchForm" onsubmit="searchDomains(event)">
                <div class="search-input-wrapper">
                    <input type="text" id="domainInput" class="search-input"
                           placeholder="Enter your domain name (e.g., mybusiness)" required>
                    <button type="submit" id="searchBtn" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        {{-- Section Label --}}
        <div class="text-center mb-6">
            <span class="text-xs font-black uppercase tracking-[0.3em] text-electric-blue">Popular TLDs</span>
        </div>
        
        {{-- TLD Price Cards --}}
        <div class="tld-cards">
            <div class="tld-card">
                <div class="tld-name">.in</div>
                <div class="tld-price">₹199/yr</div>
            </div>
            <div class="tld-card">
                <div class="tld-name">.com</div>
                <div class="tld-price">₹899/yr</div>
            </div>
            <div class="tld-card">
                <div class="tld-name">.online</div>
                <div class="tld-price">₹299/yr</div>
            </div>
            <div class="tld-card">
                <div class="tld-name">.shop</div>
                <div class="tld-price">₹399/yr</div>
            </div>
            <div class="tld-card">
                <div class="tld-name">.xyz</div>
                <div class="tld-price">₹179/yr</div>
            </div>
            <div class="tld-card">
                <div class="tld-name">.net</div>
                <div class="tld-price">₹999/yr</div>
            </div>
        </div>
    </div>

    {{-- Loading State --}}
    <div id="loadingState" class="loading-state" style="display: none;">
        <div class="spinner"></div>
        <p>Searching for available domains...</p>
    </div>

    {{-- Results --}}
    <div id="results" class="results-container"></div>
</div>

<script>
// TLD pricing in INR
const tldPrices = {
    'in': { price: 199, original: 799, icon: '🇮🇳' },
    'com': { price: 899, original: 1299, icon: '🌐' },
    'online': { price: 299, original: 3999, icon: '💻' },
    'shop': { price: 399, original: 999, icon: '🛒' },
    'xyz': { price: 179, original: 1299, icon: '✨' },
    'net': { price: 999, original: 1499, icon: '🌐' },
    'org': { price: 899, original: 1299, icon: '🏛️' },
    'io': { price: 3499, original: 4499, icon: '⚡' },
    'co': { price: 1999, original: 2499, icon: '💼' },
    'info': { price: 799, original: 1199, icon: 'ℹ️' },
    'co.in': { price: 249, original: 899, icon: '🇮🇳' },
    'site': { price: 299, original: 1199, icon: '📍' },
};

function getTldPrice(tld) {
    const tldLower = tld.toLowerCase();
    return tldPrices[tldLower] || { price: 699, original: 999, icon: '🌐' };
}

function formatPriceINR(price) {
    return '₹' + price.toLocaleString('en-IN');
}

async function searchDomains(e) {
    e.preventDefault();
    
    const input = document.getElementById('domainInput');
    const btn = document.getElementById('searchBtn');
    const loading = document.getElementById('loadingState');
    const results = document.getElementById('results');
    
    const domains = input.value.trim();
    if (!domains) return;
    
    // UI Updates
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
    loading.style.display = 'block';
    results.innerHTML = '';
    
    try {
        const response = await fetch('{{ route('client.domains.search.post') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ domains }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayResults(data.results, domains);
        } else {
            results.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Oops!</h3>
                    <p>${data.message || 'Search failed. Please try again.'}</p>
                </div>
            `;
        }
    } catch (error) {
        results.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-wifi"></i>
                <h3>Connection Error</h3>
                <p>Please check your connection and try again.</p>
            </div>
        `;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search"></i> Search';
        loading.style.display = 'none';
    }
}

function displayResults(results, queryInput) {
    const container = document.getElementById('results');
    let html = '';

    // Count available and taken domains
    let availableCount = 0;
    let takenCount = 0;
    let firstAvailable = null;
    
    for (const [domain, data] of Object.entries(results)) {
        if (data.is_available && data.best_option) {
            availableCount++;
            if (!firstAvailable) firstAvailable = domain;
        } else {
            takenCount++;
        }
    }

    // Results Header with Category Tabs
    html += `
        <div class="results-header">
            <h2><i class="fas fa-check-circle" style="color: #22c55e;"></i> ${availableCount} domain${availableCount !== 1 ? 's' : ''} available</h2>
            ${takenCount > 0 ? `<span class="count"><i class="fas fa-times-circle"></i> ${takenCount} taken</span>` : ''}
        </div>
        <div class="category-tabs">
            <button class="category-tab active">All</button>
            <button class="category-tab">Popular</button>
            <button class="category-tab">Business</button>
            <button class="category-tab">Technology</button>
            <button class="category-tab">India</button>
        </div>
    `;

    // Sort results: available first, then by price
    const sortedEntries = Object.entries(results).sort((a, b) => {
        const aAvailable = a[1].is_available && a[1].best_option;
        const bAvailable = b[1].is_available && b[1].best_option;
        if (aAvailable && !bAvailable) return -1;
        if (!aAvailable && bAvailable) return 1;
        if (aAvailable && bAvailable) {
            return (a[1].best_option?.selling_price || 0) - (b[1].best_option?.selling_price || 0);
        }
        return 0;
    });

    // Domain Results
    let hasShownAlternative = false;
    
    for (const [domain, data] of sortedEntries) {
        const isAvailable = data.is_available;
        const bestOption = data.best_option;
        const domainParts = domain.split('.');
        const tld = domainParts.length > 1 ? domainParts.slice(1).join('.') : '';
        const pricing = getTldPrice(tld);
        
        // Check if this is the first alternative (available when main is taken)
        const isFirstAlternative = isAvailable && !hasShownAlternative && takenCount > 0 && !hasShownAlternative;
        if (isFirstAlternative) hasShownAlternative = true;

        if (isAvailable && bestOption) {
            const isAuth = {{ auth()->check() ? 'true' : 'false' }};
            
            html += `
                <div class="result-card">
                    <div class="domain-info">
                        <div class="tld-badge available">.${tld}</div>
                        <div class="domain-details">
                            ${isFirstAlternative ? `<div class="great-alternative"><i class="fas fa-star"></i> Great alternative</div>` : ''}
                            <h3>${domain}</h3>
                            <span class="status-badge available"><i class="fas fa-check"></i> Available</span>
                        </div>
                    </div>
                    <div class="price-section">
                        <div>
                            <div class="price-original">${formatPriceINR(pricing.original)}</div>
                            <div class="price-current">${formatPriceINR(pricing.price)}<span style="font-size: 0.8rem; color: #666;">/yr</span></div>
                        </div>
                        ${isAuth ? `
                            <a href="/domains/register?domain=${encodeURIComponent(domain)}&provider=${encodeURIComponent(bestOption.provider_code)}&price=${pricing.price}"
                               class="buy-btn">
                                <i class="fas fa-shopping-cart"></i> Buy Now
                            </a>
                        ` : `
                            <a href="/login?redirect=${encodeURIComponent('/domains/register?domain=' + domain + '&provider=' + bestOption.provider_code + '&price=' + pricing.price)}"
                               class="buy-btn" style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);">
                                <i class="fas fa-user"></i> Login to Buy
                            </a>
                        `}
                    </div>
                </div>
            `;
        } else {
            // Taken domain
            html += `
                <div class="result-card taken">
                    <div class="domain-info">
                        <div class="tld-badge taken">.${tld}</div>
                        <div class="domain-details">
                            <h3 class="taken">${domain}</h3>
                            <span class="status-badge taken"><i class="fas fa-times"></i> Already Taken</span>
                        </div>
                    </div>
                    <div class="price-section">
                        <div class="price-current taken">Unavailable</div>
                        <a href="https://who.is/whois/${domain}" target="_blank" class="buy-btn taken">
                            <i class="fas fa-search"></i> Check WHOIS
                        </a>
                    </div>
                </div>
            `;
        }
    }

    container.innerHTML = html;
}

// Tab switching functionality
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('category-tab')) {
        document.querySelectorAll('.category-tab').forEach(tab => tab.classList.remove('active'));
        e.target.classList.add('active');
    }
});
</script>
</style>
@endpush

</x-layouts.believoo>

@extends('layouts.app')

@section('title', 'DNS Scanner - Believoo')

@section('content')
<div class="container" style="max-width: 900px; margin: 40px auto; padding: 0 20px;">
    <h1 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 8px;">
        <i class="fas fa-search-location" style="margin-right: 12px; color: #00b7ff;"></i>DNS Scanner
    </h1>
    <p style="color: #8b9bb4; margin-bottom: 30px;">Scan and migrate DNS records from any domain.</p>

    <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; margin-bottom: 24px;">
        <form id="scanForm" onsubmit="event.preventDefault(); scanDomain();">
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <input type="text" id="scanDomain" placeholder="example.com" style="flex: 1; min-width: 240px; background: #1a1a1a; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 14px; border-radius: 10px;">
                <button type="submit" class="btn btn-primary" style="padding: 14px 24px; border-radius: 10px;">
                    <i class="fas fa-search" style="margin-right: 8px;"></i>Scan
                </button>
            </div>
            <div style="margin-top: 16px; color: #94a3b8; font-size: 0.85rem;">
                Record types: A, AAAA, CNAME, MX, TXT, NS, SOA
            </div>
        </form>
    </div>

    <div id="results" class="card" style="display: none; background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <h3 style="color: #fff; font-size: 1.1rem;">Scan Results</h3>
            <div>
                <button onclick="exportZone()" class="btn btn-secondary" style="padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; margin-right: 8px;">Export Zone</button>
                <button onclick="migrateRecords()" class="btn btn-primary" style="padding: 8px 16px; border-radius: 8px; font-size: 0.85rem;">Migrate Plan</button>
            </div>
        </div>
        <div id="recordsTable"></div>
        <p id="scanMsg" style="color: #22c55e; font-size: 0.85rem; margin-top: 12px; display: none;"></p>
    </div>
</div>

<script>
let scanResults = { records: [], domain: '' };
function scanDomain() {
    const domain = document.getElementById('scanDomain').value;
    fetch('{{ route('client.dns.scan') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ domain: domain, record_types: ['A','AAAA','CNAME','MX','TXT','NS','SOA'] })
    })
    .then(r => r.json())
    .then(data => {
        if(!data.success) { alert(data.message); return; }
        scanResults = data;
        const container = document.getElementById('recordsTable');
        container.innerHTML = '<table style="width:100%; color:#cbd5e1; border-collapse: collapse;"><thead><tr style="border-bottom:1px solid rgba(255,255,255,0.08)"><th style="text-align:left;padding:10px;">Type</th><th style="text-align:left;padding:10px;">Name</th><th style="text-align:left;padding:10px;">Value</th><th style="text-align:left;padding:10px;">TTL</th></tr></thead><tbody>' +
            data.records.map(r => `<tr style="border-bottom:1px solid rgba(255,255,255,0.05)"><td style="padding:10px;color:#fff;font-weight:600;">${r.type}</td><td style="padding:10px;">${r.name}</td><td style="padding:10px;word-break:break-all;">${r.value}</td><td style="padding:10px;">${r.ttl}</td></tr>`).join('') +
            '</tbody></table>';
        document.getElementById('results').style.display = 'block';
    })
    .catch(() => alert('Scan failed'));
}
function exportZone() {
    if(!scanResults.records.length) return;
    fetch('{{ route('client.dns.export') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'text/plain'
        },
        body: JSON.stringify({ domain: scanResults.domain, records: scanResults.records })
    })
    .then(r => r.text())
    .then(text => {
        const blob = new Blob([text], { type: 'text/plain' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = scanResults.domain + '.zone';
        a.click();
    });
}
function migrateRecords() {
    const targetIp = prompt('Enter target IP for A records:');
    if(!targetIp) return;
    fetch('{{ route('client.dns.migrate') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ domain: scanResults.domain, selected_records: scanResults.records, target_ip: targetIp })
    })
    .then(r => r.json())
    .then(data => {
        const msg = document.getElementById('scanMsg');
        msg.textContent = data.message || 'Migration plan generated.';
        msg.style.display = 'block';
        if(data.migrated_records) {
            msg.textContent += ' ' + data.migrated_records.length + ' records planned.';
        }
    })
    .catch(() => alert('Migration failed'));
}
</script>
@endsection

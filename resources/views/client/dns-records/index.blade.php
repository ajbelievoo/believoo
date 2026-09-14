@extends('layouts.app')

@section('title', 'DNS Records - ' . $domain->domain_name . ' - Believoo')

@section('content')
<div class="container" style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 8px;">
                <i class="fas fa-network-wired" style="margin-right: 12px; color: #00b7ff;"></i>{{ $domain->domain_name }}
            </h1>
            <p style="color: #8b9bb4;">Manage DNS records and nameservers.</p>
        </div>
        <a href="{{ route('client.domains.my-domains') }}" class="btn btn-secondary" style="padding: 12px 24px; border-radius: 12px;">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Domains
        </a>
    </div>

    <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; margin-bottom: 24px;">
        <h3 style="color: #fff; margin-bottom: 16px; font-size: 1.1rem;">Nameservers</h3>
        @if(!empty($domain->nameservers))
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                @foreach(is_array($domain->nameservers) ? $domain->nameservers : json_decode($domain->nameservers, true) ?? [] as $ns)
                    <span style="background: rgba(0,183,255,0.1); color: #00b7ff; padding: 8px 14px; border-radius: 8px; font-size: 0.85rem;">{{ $ns }}</span>
                @endforeach
            </div>
        @else
            <p style="color: #64748b;">No nameservers configured.</p>
        @endif
    </div>

    <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <h3 style="color: #fff; font-size: 1.1rem;">DNS Records</h3>
            <span style="color: #94a3b8; font-size: 0.85rem;">{{ count($records) }} records</span>
        </div>

        @if($records->isNotEmpty())
        <table style="width: 100%; color: #cbd5e1; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <th style="text-align: left; padding: 10px;">Type</th>
                    <th style="text-align: left; padding: 10px;">Name</th>
                    <th style="text-align: left; padding: 10px;">Value</th>
                    <th style="text-align: left; padding: 10px;">TTL</th>
                    <th style="text-align: left; padding: 10px;">Priority</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 12px 10px; color: #fff; font-weight: 600;">{{ $record->record_type }}</td>
                    <td style="padding: 12px 10px;">{{ $record->name }}</td>
                    <td style="padding: 12px 10px; word-break: break-all;">{{ $record->value }}</td>
                    <td style="padding: 12px 10px;">{{ $record->ttl }}</td>
                    <td style="padding: 12px 10px;">{{ $record->priority ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="text-align: center; padding: 40px 20px;">
            <i class="fas fa-network-wired" style="font-size: 2.5rem; color: #334155; margin-bottom: 12px;"></i>
            <p style="color: #64748b;">No DNS records found. Records will appear here once synced from the provider.</p>
        </div>
        @endif
    </div>
</div>
@endsection

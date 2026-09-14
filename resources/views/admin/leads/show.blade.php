@extends('layouts.admin')

@section('title', 'Lead Details')

@section('content')
<div class="page-header">
    <h1 class="page-title">Lead Details</h1>
    <p class="page-subtitle">{{ $lead->phone_number }}</p>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; max-width: 900px;">
    <div class="data-table">
        <div class="table-header"><h3 class="table-title">Information</h3></div>
        <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Phone Number</div>
                <div style="font-weight: 600; font-size: 1.1rem;">{{ $lead->phone_number }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Status</div>
                <span class="badge badge-{{ $lead->status === 'converted' ? 'success' : ($lead->status === 'lost' ? 'danger' : 'info') }}">
                    {{ ucfirst($lead->status) }}
                </span>
            </div>
            @if($lead->notes)
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Notes</div>
                <p style="color: var(--text-secondary);">{{ $lead->notes }}</p>
            </div>
            @endif
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Received</div>
                <div>{{ $lead->created_at->format('M d, Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="data-table">
        <div class="table-header"><h3 class="table-title">Update Status</h3></div>
        <div style="padding: 24px;">
            <form action="{{ route('admin.leads.update-status', $lead) }}" method="POST">
                @csrf
                <select name="status" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-bottom: 12px;">
                    @foreach(['new','contacted','qualified','converted','lost'] as $s)
                    <option value="{{ $s }}" {{ $lead->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
            </form>
        </div>
    </div>
</div>
@endsection

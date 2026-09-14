@extends('layouts.admin')

@section('title', 'Project Request - ' . $agreementRequest->request_number)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $agreementRequest->request_number }}</h1>
        <p class="page-subtitle">{{ $agreementRequest->project_name }}</p>
    </div>
    <a href="{{ route('admin.agreement-requests.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Requests
    </a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div>
        <!-- Client Details -->
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Client Information</h3></div>
            <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Client Name</div>
                    <div style="font-weight: 600;">{{ $agreementRequest->client->name ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Email</div>
                    <div>{{ $agreementRequest->client->email ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Phone</div>
                    <div>{{ $agreementRequest->client->phone ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Submitted At</div>
                    <div>{{ $agreementRequest->submitted_at?->format('M d, Y h:i A') ?? '—' }}</div>
                </div>
            </div>
        </div>

        <!-- Project Details -->
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Project Details</h3></div>
            <div style="padding: 24px;">
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Project Name</div>
                    <div style="font-weight: 600;">{{ $agreementRequest->project_name }}</div>
                </div>
                @if($agreementRequest->project_description)
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Description</div>
                    <div style="line-height: 1.6;">{!! nl2br(e($agreementRequest->project_description)) !!}</div>
                </div>
                @endif
                @if($agreementRequest->requirements)
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Requirements</div>
                    <div style="line-height: 1.6;">{!! nl2br(e($agreementRequest->requirements)) !!}</div>
                </div>
                @endif
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Budget Range</div>
                        <div style="font-weight: 600; color: #00b7ff;">{{ $agreementRequest->budget_range ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Timeline Expectation</div>
                        <div style="font-weight: 600;">{{ $agreementRequest->timeline_expectation ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Preferred Technology</div>
                        <div>{{ $agreementRequest->preferred_technology ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($agreementRequest->admin_notes)
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Admin Notes</h3></div>
            <div style="padding: 24px;">
                <div style="line-height: 1.6; color: var(--text-secondary);">{!! nl2br(e($agreementRequest->admin_notes)) !!}</div>
            </div>
        </div>
        @endif
    </div>

    <div>
        <!-- Status Update -->
        <div class="data-table" style="margin-bottom: 24px; position: sticky; top: 24px;">
            <div class="table-header"><h3 class="table-title">Update Status</h3></div>
            <div style="padding: 24px;">
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Current Status</div>
                    <span class="badge badge-{{ $agreementRequest->status === 'approved' ? 'success' : ($agreementRequest->status === 'rejected' ? 'danger' : ($agreementRequest->status === 'under_review' ? 'info' : ($agreementRequest->status === 'converted' ? 'primary' : 'warning'))) }}">
                        {{ $agreementRequest->status_label }}
                    </span>
                </div>

                @if($agreementRequest->agreement)
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Linked Agreement</div>
                    <a href="{{ route('admin.agreements.show', $agreementRequest->agreement) }}" style="color: #00b7ff; text-decoration: none;">
                        {{ $agreementRequest->agreement->agreement_number }}
                    </a>
                </div>
                @endif

                <form action="{{ route('admin.agreement-requests.update-status', $agreementRequest) }}" method="POST">
                    @csrf
                    <select name="status" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-bottom: 12px; font-size: 0.9rem;">
                        @foreach(['pending' => 'Pending Review', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'converted' => 'Converted to Agreement'] as $value => $label)
                        <option value="{{ $value }}" {{ $agreementRequest->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
                </form>
            </div>
        </div>

        @if($agreementRequest->reviewed_at)
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Review Timeline</h3></div>
            <div style="padding: 24px;">
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Reviewed At</div>
                    <div>{{ $agreementRequest->reviewed_at->format('M d, Y h:i A') }}</div>
                </div>
                @if($agreementRequest->converted_at)
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Converted At</div>
                    <div>{{ $agreementRequest->converted_at->format('M d, Y h:i A') }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

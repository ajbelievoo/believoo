@extends('layouts.admin')

@section('title', 'Create Agreement')

@section('content')
<div class="page-header">
    <h1 class="page-title">Create Agreement</h1>
    <p class="page-subtitle">Create a new client agreement</p>
</div>

<form action="{{ route('admin.agreements.store') }}" method="POST" id="agreementForm">
    @csrf

    @if(session('error'))
    <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 24px;">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 24px;">
        <ul style="margin: 0; padding-left: 18px;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <div>
            <!-- Agreement Information -->
            <div class="data-table" style="margin-bottom: 24px;">
                <div class="table-header"><h3 class="table-title">Agreement Information</h3></div>
                <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Title *</label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Client *</label>
                        <select name="client_id" id="clientSelect" required
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;"
                            onchange="document.getElementById('clientName').value = this.options[this.selectedIndex].text">
                            <option value="">Select Client</option>
                            @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Client Name *</label>
                        <input type="text" name="client_name" id="clientName" value="{{ old('client_name') }}" required
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Service Provider *</label>
                        <input type="text" name="service_provider_name" value="{{ old('service_provider_name', 'Believoo') }}" required
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Lead Developer</label>
                        <input type="text" name="lead_developer" value="{{ old('lead_developer', 'AJ (Founder, Believoo)') }}"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Status *</label>
                        <select name="status" required
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                            <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ old('status') == 'sent' ? 'selected' : '' }}>Sent to Client</option>
                            <option value="viewed" {{ old('status') == 'viewed' ? 'selected' : '' }}>Viewed by Client</option>
                            <option value="signed" {{ old('status') == 'signed' ? 'selected' : '' }}>Signed & Active</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Project Details -->
            <div class="data-table" style="margin-bottom: 24px;">
                <div class="table-header"><h3 class="table-title">Project Details</h3></div>
                <div style="padding: 24px;">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Project Name *</label>
                        <input type="text" name="project_name" value="{{ old('project_name') }}" required
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Project Overview</label>
                        <textarea name="project_overview" rows="3"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('project_overview') }}</textarea>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Technical Specifications</label>
                        <textarea name="technical_specs" rows="3"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('technical_specs') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Financial Details -->
            <div class="data-table" style="margin-bottom: 24px;">
                <div class="table-header"><h3 class="table-title">Financial Details</h3></div>
                <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Total Amount *</label>
                        <input type="number" name="total_amount" value="{{ old('total_amount') }}" step="0.01" min="0" required
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Currency *</label>
                        <input type="text" name="currency" value="{{ old('currency', 'USD') }}" required
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Upfront Amount</label>
                        <input type="number" name="upfront_amount" value="{{ old('upfront_amount', 0) }}" step="0.01" min="0"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Timeline (Months) *</label>
                        <input type="number" name="timeline_months" value="{{ old('timeline_months', 4) }}" min="1" required
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Start Date</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">End Date</label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                </div>
            </div>

            <!-- Terms & Deliverables -->
            <div class="data-table" style="margin-bottom: 24px;">
                <div class="table-header"><h3 class="table-title">Terms & Deliverables</h3></div>
                <div style="padding: 24px;">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Payment Terms</label>
                        <textarea name="payment_terms" rows="3"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('payment_terms') }}</textarea>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Deliverables</label>
                        <textarea name="deliverables" rows="3"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('deliverables') }}</textarea>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Support Terms</label>
                        <textarea name="support_terms" rows="3"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('support_terms') }}</textarea>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Additional Terms</label>
                        <textarea name="additional_terms" rows="3"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('additional_terms') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Work Items -->
            <div class="data-table" style="margin-bottom: 24px;">
                <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="table-title">Work Items</h3>
                    <button type="button" class="btn btn-secondary" onclick="addWorkItem()" style="padding: 6px 14px; font-size: 0.8rem;">
                        <i class="fas fa-plus"></i> Add Work Item
                    </button>
                </div>
                <div style="padding: 24px;" id="workItemsContainer">
                    <p style="color: var(--text-muted); text-align: center; padding: 20px; margin: 0;" id="noWorkItemsMsg">No work items added yet</p>
                </div>
            </div>

            <!-- Milestones -->
            <div class="data-table" style="margin-bottom: 24px;">
                <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="table-title">Milestones & Payments</h3>
                    <button type="button" class="btn btn-secondary" onclick="addMilestone()" style="padding: 6px 14px; font-size: 0.8rem;">
                        <i class="fas fa-plus"></i> Add Milestone
                    </button>
                </div>
                <div style="padding: 24px;" id="milestonesContainer">
                    <p style="color: var(--text-muted); text-align: center; padding: 20px; margin: 0;" id="noMilestonesMsg">No milestones added yet</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <div class="data-table" style="position: sticky; top: 24px;">
                <div class="table-header"><h3 class="table-title">Actions</h3></div>
                <div style="padding: 24px;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 12px;">
                        <i class="fas fa-save"></i> Create Agreement
                    </button>
                    <a href="{{ route('admin.agreements.index') }}" class="btn btn-secondary" style="width: 100%;">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    let workItemIndex = 0;
    let milestoneIndex = 0;

    function addWorkItem() {
        const container = document.getElementById('workItemsContainer');
        const noMsg = document.getElementById('noWorkItemsMsg');
        if (noMsg) noMsg.style.display = 'none';

        const row = document.createElement('div');
        row.className = 'work-item-row';
        row.style.cssText = 'border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; margin-bottom: 12px;';
        row.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-weight: 600; font-size: 0.85rem;">Work Item #${workItemIndex + 1}</span>
                <button type="button" onclick="this.closest('.work-item-row').remove(); checkWorkItems();" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.85rem;">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Item Name *</label>
                    <input type="text" name="work_items[${workItemIndex}][item_name]" required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.85rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Amount</label>
                    <input type="number" name="work_items[${workItemIndex}][amount]" step="0.01" min="0"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.85rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Timeline (Days)</label>
                    <input type="number" name="work_items[${workItemIndex}][timeline_days]" min="1"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.85rem;">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Description</label>
                    <textarea name="work_items[${workItemIndex}][description]" rows="2"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.85rem; resize: vertical;"></textarea>
                </div>
            </div>
        `;
        container.appendChild(row);
        workItemIndex++;
    }

    function checkWorkItems() {
        const container = document.getElementById('workItemsContainer');
        const noMsg = document.getElementById('noWorkItemsMsg');
        const rows = container.querySelectorAll('.work-item-row');
        if (rows.length === 0 && noMsg) noMsg.style.display = 'block';
        // Renumber
        rows.forEach((row, idx) => {
            row.querySelector('span').textContent = 'Work Item #' + (idx + 1);
        });
        workItemIndex = rows.length;
    }

    function addMilestone() {
        const container = document.getElementById('milestonesContainer');
        const noMsg = document.getElementById('noMilestonesMsg');
        if (noMsg) noMsg.style.display = 'none';

        const row = document.createElement('div');
        row.className = 'milestone-row';
        row.style.cssText = 'border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; margin-bottom: 12px;';
        row.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-weight: 600; font-size: 0.85rem;">Milestone #${milestoneIndex + 1}</span>
                <button type="button" onclick="this.closest('.milestone-row').remove(); checkMilestones();" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.85rem;">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Phase Name *</label>
                    <input type="text" name="milestones[${milestoneIndex}][phase_name]" required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.85rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Payment Amount *</label>
                    <input type="number" name="milestones[${milestoneIndex}][payment_amount]" step="0.01" min="0" required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.85rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Timeline (Month)</label>
                    <input type="number" name="milestones[${milestoneIndex}][timeline_month]" min="1"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.85rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Due Date</label>
                    <input type="date" name="milestones[${milestoneIndex}][due_date]"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.85rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Status</label>
                    <select name="milestones[${milestoneIndex}][status]"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.85rem;">
                        <option value="pending" selected>Pending</option>
                        <option value="completed">Completed</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Description</label>
                    <textarea name="milestones[${milestoneIndex}][description]" rows="2"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.85rem; resize: vertical;"></textarea>
                </div>
            </div>
        `;
        container.appendChild(row);
        milestoneIndex++;
    }

    function checkMilestones() {
        const container = document.getElementById('milestonesContainer');
        const noMsg = document.getElementById('noMilestonesMsg');
        const rows = container.querySelectorAll('.milestone-row');
        if (rows.length === 0 && noMsg) noMsg.style.display = 'block';
        rows.forEach((row, idx) => {
            row.querySelector('span').textContent = 'Milestone #' + (idx + 1);
        });
        milestoneIndex = rows.length;
    }
</script>
@endsection

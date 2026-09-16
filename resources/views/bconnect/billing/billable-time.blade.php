@extends('bconnect.layout')
@section('title', 'Billable Time')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="font-bold text-xl">Billable Time</h3>
        <p class="text-sm text-slate-400">Select unbilled time entries to generate an invoice.</p>
    </div>
    <a href="{{ route('bconnect.billing') }}" class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-arrow-left mr-1"></i>Invoices</a>
</div>

<form method="POST" action="{{ route('bconnect.billing.invoice_from_time') }}">@csrf
    @forelse($entries as $projectId => $projectEntries)
    @php
    $project = $projectEntries->first()->project;
    $projectTotal = $projectEntries->sum('billed_amount');
    @endphp
    <div class="bc-card p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h4 class="font-bold text-lg text-cyan-400">{{ $project?->name ?? 'Unknown project' }}</h4>
                <p class="text-sm text-slate-400">Client: {{ $project?->client?->user?->name ?? '—' }} &bull; Unbilled total: <span class="text-amber-400 font-bold">₹{{ number_format($projectTotal, 2) }}</span></p>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer select-all-toggle">
                <input type="checkbox" class="project-toggle rounded bg-slate-800 border-slate-600 text-cyan-500 focus:ring-cyan-500"> Select all
            </label>
        </div>
        <table class="bc-table">
            <thead>
                <tr><th class="w-8"><input type="checkbox" class="project-header-toggle rounded bg-slate-800 border-slate-600 text-cyan-500 focus:ring-cyan-500"></th><th>Member</th><th>Description</th><th>Ticket</th><th>Date</th><th>Hours</th><th>Rate</th><th>Amount</th></tr>
            </thead>
            <tbody>
                @foreach($projectEntries as $entry)
                <tr>
                    <td><input type="checkbox" name="time_entry_ids[]" value="{{ $entry->id }}" class="entry-checkbox rounded bg-slate-800 border-slate-600 text-cyan-500 focus:ring-cyan-500"></td>
                    <td class="text-slate-400 text-sm">{{ $entry->member?->user?->name ?? '—' }}</td>
                    <td>{{ $entry->description ?? '—' }}</td>
                    <td class="text-slate-400 text-sm">{{ $entry->ticket?->title ?? '—' }}</td>
                    <td class="text-slate-400 text-sm">{{ $entry->started_at->format('M d, Y') }}</td>
                    <td class="font-mono">{{ $entry->duration_hours }}h</td>
                    <td class="text-slate-400 text-sm">₹{{ number_format($entry->hourly_rate, 2) }}</td>
                    <td class="font-bold">₹{{ number_format($entry->billed_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @empty
    <div class="bc-card p-6 text-center text-slate-400">No unbilled time entries found.</div>
    @endforelse

    @if($entries->count())
    <div class="fixed bottom-6 right-6 bg-slate-900 border border-slate-700 rounded-xl p-4 shadow-xl flex items-center gap-4">
        <div>
            <p class="text-sm text-slate-400">Selected total</p>
            <p class="text-xl font-bold text-cyan-400" id="selectedTotal">₹0.00</p>
        </div>
        <button type="submit" class="bc-btn bc-btn-primary"><i class="fas fa-file-invoice mr-1"></i>Create Invoice</button>
    </div>
    @endif
</form>

<script>
(function() {
    const checkboxes = document.querySelectorAll('.entry-checkbox');
    const selectedTotalEl = document.getElementById('selectedTotal');
    const entries = @json($entries->flatten(1)->keyBy('id')->map(fn($e) => ['amount' => (float)$e->billed_amount]));

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.entry-checkbox:checked').forEach(cb => {
            total += entries[cb.value]?.amount || 0;
        });
        selectedTotalEl.textContent = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateTotal));

    document.querySelectorAll('.project-header-toggle').forEach(header => {
        header.addEventListener('change', () => {
            const card = header.closest('.bc-card');
            card.querySelectorAll('.entry-checkbox').forEach(cb => cb.checked = header.checked);
            updateTotal();
        });
    });

    document.querySelectorAll('.project-toggle').forEach(toggle => {
        toggle.addEventListener('change', () => {
            const card = toggle.closest('.bc-card');
            const header = card.querySelector('.project-header-toggle');
            header.checked = toggle.checked;
            card.querySelectorAll('.entry-checkbox').forEach(cb => cb.checked = toggle.checked);
            updateTotal();
        });
    });

    updateTotal();
})();
</script>
@endsection

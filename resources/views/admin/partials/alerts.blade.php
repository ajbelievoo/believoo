@if (session('error'))
    <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.35); color: #ef4444; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;">
        <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
        {{ session('error') }}
    </div>
@endif

@if (session('warning'))
    <div style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.35); color: #f59e0b; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;">
        <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
        {{ session('warning') }}
    </div>
@endif

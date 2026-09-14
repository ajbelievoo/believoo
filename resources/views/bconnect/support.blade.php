@extends('bconnect.layout')
@section('title', 'Help & Support')
@section('content')
<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold text-white mb-2">Help & Support</h2>
    <p class="text-slate-400 mb-8">B-CONNECT workspace ke baare mein common questions aur guides.</p>

    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <a href="https://bc.believoo.com/login" class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-cyan-500/30 transition">
            <i class="fas fa-sign-in-alt text-cyan-400 text-2xl mb-3"></i>
            <h3 class="font-bold text-white mb-1">Login Issues</h3>
            <p class="text-slate-400 text-sm">Password reset ya Google login help.</p>
        </a>
        <a href="https://believoo.com/help" target="_blank" class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-cyan-500/30 transition">
            <i class="fas fa-book text-cyan-400 text-2xl mb-3"></i>
            <h3 class="font-bold text-white mb-1">Knowledge Base</h3>
            <p class="text-slate-400 text-sm">Detailed guides aur tutorials.</p>
        </a>
        <a href="https://believoo.com/status" target="_blank" class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-cyan-500/30 transition">
            <i class="fas fa-chart-line text-cyan-400 text-2xl mb-3"></i>
            <h3 class="font-bold text-white mb-1">System Status</h3>
            <p class="text-slate-400 text-sm">Live service status aur uptime.</p>
        </a>
        <a href="https://believoo.com/contact" target="_blank" class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-cyan-500/30 transition">
            <i class="fas fa-headset text-cyan-400 text-2xl mb-3"></i>
            <h3 class="font-bold text-white mb-1">Contact Support</h3>
            <p class="text-slate-400 text-sm">24/7 support team se baat karein.</p>
        </a>
    </div>

    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6">
        <h3 class="font-bold text-white mb-4">Quick Guides</h3>
        <div class="space-y-4">
            <details class="group bg-slate-800 rounded-xl p-4">
                <summary class="font-semibold text-white cursor-pointer list-none flex justify-between items-center"><span>How do I start a video meeting?</span><i class="fas fa-chevron-down text-cyan-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-400 text-sm mt-3">Dashboard se <strong>Meetings</strong> par jao, <strong>New Meeting</strong> click karo, room link copy karo aur team ko invite karo.</p>
            </details>
            <details class="group bg-slate-800 rounded-xl p-4">
                <summary class="font-semibold text-white cursor-pointer list-none flex justify-between items-center"><span>How do I create a ticket?</span><i class="fas fa-chevron-down text-cyan-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-400 text-sm mt-3"><strong>Tickets</strong> menu se <strong>New Ticket</strong> banao. Title, description aur priority set karo.</p>
            </details>
            <details class="group bg-slate-800 rounded-xl p-4">
                <summary class="font-semibold text-white cursor-pointer list-none flex justify-between items-center"><span>How does billing work?</span><i class="fas fa-chevron-down text-cyan-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-400 text-sm mt-3"><strong>Billing</strong> se invoice create karo. Client pay kar sakta hai online gateway se ya admin manually mark paid kar sakta hai.</p>
            </details>
            <details class="group bg-slate-800 rounded-xl p-4">
                <summary class="font-semibold text-white cursor-pointer list-none flex justify-between items-center"><span>Can I use a custom domain?</span><i class="fas fa-chevron-down text-cyan-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-400 text-sm mt-3">Yes. <strong>Company Settings</strong> mein custom domain apply karo. Admin SSL configure karega.</p>
            </details>
        </div>
    </div>
</div>
@endsection

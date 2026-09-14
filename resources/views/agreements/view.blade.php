<x-app-layout>
    <div class="min-h-screen bg-dark pt-32 pb-20 px-6 relative z-0">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="mb-12">
                <a href="{{ route('client.dashboard') }}?tab=agreements" class="text-[10px] font-black text-electric-blue uppercase tracking-widest flex items-center hover:translate-x-[-4px] transition-all mb-6">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-3 mb-2">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $agreement->status == 'signed' ? 'bg-green-500/20 text-green-500' : ($agreement->status == 'sent' ? 'bg-blue-500/20 text-blue-500' : 'bg-gray-500/20 text-gray-400') }}">
                                {{ $agreement->status_label }}
                            </span>
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">#{{ $agreement->agreement_number }}</span>
                        </div>
                        <h1 class="text-4xl font-black text-white uppercase tracking-tight">{{ $agreement->title }}</h1>
                        <p class="text-gray-400 mt-2">{{ $agreement->project_name }}</p>
                    </div>
                    <a href="{{ route('client.agreement.download', $agreement) }}" class="px-6 py-3 rounded-xl bg-electric-blue/10 text-electric-blue font-black uppercase tracking-widest text-xs hover:bg-electric-blue hover:text-dark transition-all">
                        <i class="fas fa-download mr-2"></i>Download PDF
                    </a>
                </div>
            </div>

            <!-- Agreement Document -->
            <div class="glass rounded-[3rem] border border-white/10 overflow-hidden p-12 space-y-12">
                <!-- Title -->
                <div class="text-center border-b border-white/10 pb-8">
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight mb-4">{{ $agreement->title }}</h2>
                    <p class="text-gray-500">Agreement #{{ $agreement->agreement_number }}</p>
                </div>

                <!-- Parties -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="p-6 bg-white/5 rounded-2xl">
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">Service Provider</p>
                        <p class="text-xl font-bold text-white">{{ $agreement->service_provider_name }}</p>
                        <p class="text-sm text-electric-blue">{{ $agreement->lead_developer }}</p>
                    </div>
                    <div class="p-6 bg-white/5 rounded-2xl">
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">Client</p>
                        <p class="text-xl font-bold text-white">{{ $agreement->client_name }}</p>
                        <p class="text-sm text-gray-400">{{ $agreement->client->email }}</p>
                    </div>
                </div>

                <!-- Project Overview -->
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-widest mb-4 border-b border-white/10 pb-2">1. Project Overview</h3>
                    <div class="prose prose-invert max-w-none text-gray-300">
                        {!! $agreement->project_overview !!}
                    </div>
                </div>

                <!-- Work Items -->
                @if($agreement->workItems->count() > 0)
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-widest mb-4 border-b border-white/10 pb-2">2. Itemized Costing</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-left py-4 text-[10px] font-black text-gray-500 uppercase tracking-widest">Work Item</th>
                                    <th class="text-left py-4 text-[10px] font-black text-gray-500 uppercase tracking-widest">Description</th>
                                    <th class="text-right py-4 text-[10px] font-black text-gray-500 uppercase tracking-widest">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($agreement->workItems as $item)
                                <tr class="border-b border-white/5">
                                    <td class="py-4 text-sm text-white font-bold">{{ $item->item_name }}</td>
                                    <td class="py-4 text-sm text-gray-400">{{ $item->description }}</td>
                                    <td class="py-4 text-sm text-white font-bold text-right">${{ number_format($item->amount, 2) }}</td>
                                </tr>
                                @endforeach
                                <tr class="bg-electric-blue/10">
                                    <td class="py-4 text-sm font-black text-electric-blue uppercase" colspan="2">Total Project Value</td>
                                    <td class="py-4 text-lg font-black text-electric-blue text-right">${{ number_format($agreement->total_amount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Technical Specs -->
                @if($agreement->technical_specs)
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-widest mb-4 border-b border-white/10 pb-2">3. Technical Specifications</h3>
                    <div class="prose prose-invert max-w-none text-gray-300">
                        {!! $agreement->technical_specs !!}
                    </div>
                </div>
                @endif

                <!-- Timeline -->
                @if($agreement->milestones->count() > 0)
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-widest mb-4 border-b border-white/10 pb-2">4. Timeline & Milestones ({{ $agreement->timeline_months }} Months)</h3>
                    <div class="space-y-4">
                        @foreach($agreement->milestones as $milestone)
                        <div class="p-4 bg-white/5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="w-8 h-8 rounded-full bg-electric-blue/20 flex items-center justify-center text-electric-blue text-xs font-black">{{ $milestone->timeline_month }}</span>
                                    <h4 class="font-bold text-white">{{ $milestone->phase_name }}</h4>
                                </div>
                                <p class="text-sm text-gray-400 ml-11">{{ $milestone->description }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-black text-white">${{ number_format($milestone->payment_amount, 2) }}</p>
                                <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $milestone->status == 'paid' ? 'bg-green-500/20 text-green-500' : 'bg-gray-500/20 text-gray-400' }}">
                                    {{ $milestone->status }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Payment Terms -->
                @if($agreement->payment_terms)
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-widest mb-4 border-b border-white/10 pb-2">5. Payment Terms</h3>
                    <div class="prose prose-invert max-w-none text-gray-300">
                        {!! $agreement->payment_terms !!}
                    </div>
                    @if($agreement->upfront_amount > 0)
                    <p class="mt-4 text-sm text-electric-blue">
                        <strong>Upfront Deposit Required:</strong> ${{ number_format($agreement->upfront_amount, 2) }}
                    </p>
                    @endif
                </div>
                @endif

                <!-- Deliverables -->
                @if($agreement->deliverables)
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-widest mb-4 border-b border-white/10 pb-2">6. Deliverables</h3>
                    <div class="prose prose-invert max-w-none text-gray-300">
                        {!! $agreement->deliverables !!}
                    </div>
                </div>
                @endif

                <!-- Support -->
                @if($agreement->support_terms)
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-widest mb-4 border-b border-white/10 pb-2">7. Support & Maintenance</h3>
                    <div class="prose prose-invert max-w-none text-gray-300">
                        {!! $agreement->support_terms !!}
                    </div>
                </div>
                @endif

                <!-- Additional Terms -->
                @if($agreement->additional_terms)
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-widest mb-4 border-b border-white/10 pb-2">8. Additional Terms</h3>
                    <div class="prose prose-invert max-w-none text-gray-300">
                        {!! $agreement->additional_terms !!}
                    </div>
                </div>
                @endif

                <!-- Signatures -->
                <div class="border-t border-white/10 pt-8">
                    <h3 class="text-lg font-black text-white uppercase tracking-widest mb-6">Signatures</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="p-6 bg-white/5 rounded-2xl">
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-4">For Service Provider</p>
                            <p class="text-lg font-bold text-electric-blue">{{ $agreement->service_provider_name }}</p>
                            @if($agreement->admin_signature_data)
                                <p class="mt-4 pt-4 border-t border-white/10 text-white font-bold">{{ $agreement->admin_signature_data }}</p>
                            @else
                                <p class="mt-4 pt-4 border-t border-white/10 text-gray-500 italic">Pending signature</p>
                            @endif
                        </div>
                        <div class="p-6 bg-white/5 rounded-2xl">
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-4">For Client</p>
                            <p class="text-lg font-bold text-white">{{ $agreement->client_name }}</p>
                            @if($agreement->client_signature_data)
                                <p class="mt-4 pt-4 border-t border-white/10 text-electric-blue font-bold">{{ $agreement->client_signature_data }}</p>
                                <p class="text-sm text-gray-500 mt-2">Signed on {{ $agreement->client_signed_at?->format('M d, Y • H:i') }}</p>
                            @else
                                <p class="mt-4 pt-4 border-t border-white/10 text-yellow-500 italic">
                                    <i class="fas fa-clock mr-2"></i>Waiting for your signature
                                </p>
                                <a href="{{ route('client.dashboard') }}?tab=agreements" class="mt-4 inline-block px-6 py-3 rounded-xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                                    Sign Now
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

{{-- Kanban Board --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    @php
        $statusColors = [
            'Planning'   => 'bg-gray-400',
            'Developing' => 'bg-electric-blue',
            'Testing'    => 'bg-yellow-400',
            'Delivered'  => 'bg-green-400',
        ];
    @endphp
    @foreach(['Planning', 'Developing', 'Testing', 'Delivered'] as $status)
        <div class="glass rounded-3xl p-5 border border-white/5">
            {{-- Column Header --}}
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full {{ $statusColors[$status] }}"></div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $status }}</h3>
                </div>
                <span class="badge-gray text-[9px]">{{ count($kanban[$status]) }}</span>
            </div>

            {{-- Project Cards --}}
            <div class="space-y-3 min-h-[200px]">
                @forelse($kanban[$status] as $project)
                    <div class="bg-dark-200 rounded-2xl p-5 border border-white/5 hover:border-electric-blue/20 transition-colors">
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="font-black text-sm uppercase leading-tight flex-1 pr-2">{{ $project->name }}</h4>
                            <span class="badge-gray text-[9px] flex-shrink-0">#{{ $project->id }}</span>
                        </div>
                        @if($project->description)
                            <p class="text-gray-500 text-xs leading-relaxed line-clamp-2 mb-4">{{ strip_tags($project->description) }}</p>
                        @endif
                        {{-- Progress Bar --}}
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-gray-600 font-black uppercase tracking-widest">Progress</span>
                                <span class="text-[10px] font-black text-electric-blue">{{ $project->progress ?? 0 }}%</span>
                            </div>
                            <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                                <div class="progress-bar h-full rounded-full transition-all duration-500"
                                     style="width: {{ $project->progress ?? 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-inbox text-gray-600"></i>
                        </div>
                        <p class="text-gray-600 text-xs font-black uppercase tracking-widest">No projects</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

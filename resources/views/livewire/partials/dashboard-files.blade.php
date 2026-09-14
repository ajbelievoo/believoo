{{-- File Vault Tab --}}
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-black text-white uppercase tracking-tight">Project Assets & Deliverables</h2>
        <button wire:click="openUploadModal" class="btn-primary text-xs px-5 py-3">
            <i class="fas fa-upload mr-2"></i>Upload Asset
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($vaultAssets as $asset)
            <div class="glass rounded-3xl p-6 border border-white/5 group hover:border-electric-blue/20 transition-all">
                <div class="flex items-start justify-between mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-electric-blue/10 flex items-center justify-center text-xl text-electric-blue">
                        @if($asset->type == 'invoice')
                            <i class="fas fa-file-invoice-dollar"></i>
                        @elseif($asset->type == 'deliverable')
                            <i class="fas fa-box-open"></i>
                        @else
                            <i class="fas fa-file-alt"></i>
                        @endif
                    </div>
                    <button wire:click="downloadAsset({{ $asset->id }})" aria-label="Download {{ $asset->name }}"
                            class="w-9 h-9 rounded-xl bg-white/5 hover:bg-electric-blue hover:text-dark transition-all text-gray-400 flex items-center justify-center">
                        <i class="fas fa-download text-xs"></i>
                    </button>
                </div>
                <h4 class="font-black text-white uppercase tracking-tight mb-1 truncate text-sm">{{ $asset->name }}</h4>
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-4">{{ $asset->project->name }}</p>
                <div class="flex items-center justify-between pt-4 border-t border-white/5">
                    <span class="badge-gray text-[9px]">{{ $asset->type }}</span>
                    <span class="text-[10px] font-bold text-white/40">{{ $asset->created_at->format('M d, Y') }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center glass rounded-[3rem]">
                <div class="w-16 h-16 rounded-3xl bg-white/5 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-vault text-2xl text-gray-600"></i>
                </div>
                <h3 class="text-lg font-black text-white uppercase mb-2">Vault is Empty</h3>
                <p class="text-gray-500 text-sm">No assets or deliverables have been uploaded yet.</p>
            </div>
        @endforelse
    </div>
</div>

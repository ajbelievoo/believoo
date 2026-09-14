<div>
    @if (session()->has('feedback_success'))
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Thank You!</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">{{ session('feedback_success') }}</p>
        </div>
    @else
        <form wire:submit="submit" class="space-y-4 text-left">
            <input type="text" wire:model="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Your Name <span class="text-amber-500">*</span></label>
                    <input type="text" wire:model="name" placeholder="e.g. Rahul Sharma"
                           class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Company</label>
                    <input type="text" wire:model="company" placeholder="e.g. FashionHub"
                           class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('company') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Designation / Title</label>
                <input type="text" wire:model="title" placeholder="e.g. CEO, Founder, Manager"
                       class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Your Rating <span class="text-amber-500">*</span></label>
                <div class="flex items-center gap-1.5">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" wire:click="setRating({{ $i }})" aria-label="Rate {{ $i }} star{{ $i > 1 ? 's' : '' }}"
                                class="text-2xl transition-transform hover:scale-125 {{ $rating >= $i ? 'text-amber-500' : 'text-slate-300 dark:text-slate-600' }}">
                            <i class="fas fa-star"></i>
                        </button>
                    @endfor
                    <span class="ml-2 text-sm text-slate-500 dark:text-slate-400">{{ $rating }}/5</span>
                </div>
                @error('rating') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Your Feedback <span class="text-amber-500">*</span></label>
                <textarea wire:model="content" rows="4" placeholder="Share your experience working with Believoo..."
                          class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                @error('content') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition shadow-lg shadow-amber-500/25 disabled:opacity-60"
                    wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit"><i class="fas fa-paper-plane text-sm"></i> Submit Feedback</span>
                <span wire:loading wire:target="submit"><i class="fas fa-circle-notch fa-spin"></i> Submitting...</span>
            </button>
            <p class="text-[11px] text-slate-400 text-center">Feedback is reviewed before appearing on the site.</p>
        </form>
    @endif
</div>

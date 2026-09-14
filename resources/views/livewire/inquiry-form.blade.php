<div class="bg-white dark:bg-slate-800 p-8 rounded-2xl relative overflow-hidden transition-colors"
     x-data="{
        init() {
            window.addEventListener('validation-failed', () => {
                const firstError = document.querySelector('.text-red-500, .text-red-500');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.classList.add('animate-shake');
                    setTimeout(() => firstError.classList.remove('animate-shake'), 500);
                }
            });
            window.addEventListener('inquiry-submitted', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            window.addEventListener('scroll-to-top', () => {
                $el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
     }">
    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-shake { animation: shake 0.2s ease-in-out 0s 2; }
    </style>
    <!-- Progress Bar -->
    <div class="absolute top-0 left-0 w-full h-2 bg-slate-100 dark:bg-slate-700 transition-colors">
        <div class="h-full bg-gradient-to-r from-amber-500 to-amber-600 transition-all duration-500" style="width: {{ (max((int) $totalSteps, 1) > 0 ? ((int) $step / max((int) $totalSteps, 1)) * 100 : 0) }}%"></div>
    </div>

    @if (session()->has('inquiry_success'))
        <div class="text-center py-10">
            <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-3xl font-black mb-4 uppercase tracking-tight text-slate-900 dark:text-white">SUCCESS!</h3>
            <p class="text-slate-500 dark:text-slate-300 text-lg">{{ session('inquiry_success') }}</p>
            <button wire:click="$set('step', 1)" class="mt-8 px-8 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors font-bold uppercase tracking-wider text-sm text-slate-700 dark:text-slate-200">Send Another</button>
        </div>
    @else
        <div class="mb-10 flex justify-between items-center">
            <span class="text-sm font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Step {{ (int) $step }} of {{ (int) $totalSteps }}</span>
            <span class="text-xl font-black text-amber-600 uppercase tracking-tight">
                @if($step == 1) Contact Info @elseif($step == 2) Project Details @else Final Requirements @endif
            </span>
        </div>

        @if ($errors->any())
            <div class="p-6 mb-8 rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 text-red-500">
                <div class="flex items-center gap-3 font-black uppercase tracking-widest text-xs mb-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    Validation Error
                </div>
                <ul class="text-sm font-medium space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form wire:submit.prevent="submit" class="space-y-8">
            @if($step == 1)
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label for="inq-name" class="text-xs font-black text-slate-500 dark:text-slate-300 uppercase tracking-widest ml-1">Full Name</label>
                        <input id="inq-name" type="text" wire:model.blur="full_name" placeholder="John Doe" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 focus:border-amber-500 outline-none transition-all text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500">
                        @error('full_name') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-2">
                        <label for="inq-email" class="text-xs font-black text-slate-500 dark:text-slate-300 uppercase tracking-widest ml-1">Email Address</label>
                        <input id="inq-email" type="email" wire:model.blur="email" placeholder="john@example.com" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 focus:border-amber-500 outline-none transition-all text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500">
                        @error('email') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-2">
                        <label for="inq-phone" class="text-xs font-black text-slate-500 dark:text-slate-300 uppercase tracking-widest ml-1">Phone Number</label>
                        <input id="inq-phone" type="tel" wire:model.blur="phone" placeholder="+1 234 567 890" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 focus:border-amber-500 outline-none transition-all text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500">
                        @error('phone') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-2">
                        <label for="inq-company" class="text-xs font-black text-slate-500 dark:text-slate-300 uppercase tracking-widest ml-1">Company (Optional)</label>
                        <input id="inq-company" type="text" wire:model.blur="company" placeholder="Acme Inc." class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 focus:border-amber-500 outline-none transition-all text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500">
                        @error('company') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>
                </div>
            @elseif($step == 2)
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 dark:text-slate-300 uppercase tracking-widest ml-1">Project Type</label>
                        <select wire:model.blur="project_type" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 focus:border-amber-500 outline-none transition-all appearance-none text-slate-900 dark:text-white">
                            <option value="">Select Service</option>
                            <option value="infrastructure">Infrastructure & VPS</option>
                            <option value="app_dev">App Development</option>
                            <option value="seo">SEO & Growth</option>
                            <option value="other">Other</option>
                        </select>
                        @error('project_type') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 dark:text-slate-300 uppercase tracking-widest ml-1">Budget Range</label>
                        <select wire:model.blur="budget" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 focus:border-amber-500 outline-none transition-all appearance-none text-slate-900 dark:text-white">
                            <option value="">Select Budget</option>
                            <option value="<1000">Less than $1,000</option>
                            <option value="1000-5000">$1,000 - $5,000</option>
                            <option value="5000-10000">$5,000 - $10,000</option>
                            <option value="10000+">$10,000+</option>
                        </select>
                        @error('budget') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>
                </div>
            @else
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 dark:text-slate-300 uppercase tracking-widest ml-1">Project Requirements</label>
                    <textarea wire:model.blur="requirements" rows="6" placeholder="Describe your project in detail..." class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 focus:border-amber-500 outline-none transition-all text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"></textarea>
                    @error('requirements') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="pt-8 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center">
                @if($step > 1)
                    <button type="button" wire:click="prevStep" class="px-8 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors font-bold uppercase tracking-wider text-sm text-slate-700 dark:text-slate-200">Back</button>
                @else
                    <div></div>
                @endif

                @if($step < $totalSteps)
                    <button type="button"
                            wire:click.prevent="nextStep"
                            wire:loading.attr="disabled"
                            wire:target="nextStep"
                            class="px-10 py-4 rounded-xl bg-amber-500 text-white font-black uppercase tracking-widest shadow-[0_0_20px_rgba(245,158,11,0.2)] hover:scale-105 transition-transform disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="nextStep">Next Step</span>
                        <span wire:loading wire:target="nextStep"><i class="fas fa-spinner animate-spin"></i> Processing...</span>
                    </button>
                @else
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="submit"
                            class="px-10 py-4 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-white font-black uppercase tracking-widest shadow-[0_0_20px_rgba(245,158,11,0.2)] hover:scale-105 transition-transform disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="submit">Submit Request</span>
                        <span wire:loading wire:target="submit"><i class="fas fa-spinner animate-spin"></i> Submitting...</span>
                    </button>
                @endif
            </div>
        </form>
    @endif
</div>

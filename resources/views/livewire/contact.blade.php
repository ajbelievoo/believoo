<div class="py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-slate-900 mb-4">Contact Believoo</h1>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Get in touch for VPS hosting, web hosting, live streaming and custom software solutions.</p>
        </div>
        <div class="grid lg:grid-cols-2 gap-8 items-start">
            {{-- Contact Form --}}
            <div class="bg-white rounded-2xl p-8 md:p-12 shadow-sm border border-slate-100" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-6" x-transition:enter-end="opacity-100 translate-y-0">
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Leave A Reply</h2>
                <p class="text-slate-500 text-sm mb-8">Fill out the contact form below to submit your inquiry.</p>
                <livewire:inquiry-form />
            </div>

            {{-- Office Information --}}
            <div class="bg-white rounded-2xl p-8 md:p-12 shadow-sm border border-slate-100" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 200)" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-6" x-transition:enter-end="opacity-100 translate-y-0">
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Office Information</h2>
                <p class="text-slate-500 text-sm mb-8">We're available to take your inquiry.</p>

                <div class="space-y-6">
                    <div class="pb-6 border-b border-slate-100">
                        <h3 class="font-bold text-slate-900 mb-3">Contact Information</h3>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                                <i class="fas fa-phone-alt text-sm"></i>
                            </div>
                            <div>
                                <p class="text-slate-900 font-medium">{{ $settings['contact_phone'] ?? '+1 (555) 000-0000' }}</p>
                                <p class="text-slate-500 text-sm">{{ $settings['contact_email'] ?? 'hello@believoo.com' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="pb-6 border-b border-slate-100">
                        <h3 class="font-bold text-slate-900 mb-3">Office Address</h3>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-slate-600 text-sm leading-relaxed">{{ $settings['address'] ?? 'London, United Kingdom' }}</p>
                                <a href="https://maps.google.com/?q={{ urlencode($settings['address'] ?? 'London, UK') }}" target="_blank" class="inline-flex items-center gap-2 mt-3 px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">
                                    View on Map
                                </a>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-bold text-slate-900 mb-3">Hours of Operation</h3>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                                <i class="fas fa-clock text-sm"></i>
                            </div>
                            <div>
                                <p class="text-slate-600 text-sm">Monday - Friday: 09:00 - 20:00</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

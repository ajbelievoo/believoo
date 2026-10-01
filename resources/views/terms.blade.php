<x-layouts.believoo
    title="Terms of Service - Believoo"
    description="Read the terms of service for Believoo VPS hosting, web hosting, live streaming and software solutions."
    keywords="Terms of Service, Believoo terms, hosting terms">
    <main class="pt-32 pb-20 px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-5xl font-black mb-12 uppercase tracking-tighter">Terms of <span class="text-electric-blue">Service</span></h1>
            
            <div class="glass p-10 rounded-[2.5rem] space-y-12 text-gray-400 leading-relaxed text-lg">
                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">1. Acceptance of Terms</h2>
                    <p>These Terms of Service govern your use of services provided by {{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }} ("Believoo", "we", "us"), a company incorporated under the Companies Act, 2013, Government of India @if($settings['company_cin'] ?? false)(CIN: {{ $settings['company_cin'] }})@endif. By accessing and using Believoo's services, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">2. Service Description</h2>
                    <p>{{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }} provides digital infrastructure, software development, and cloud architecture services. The specific scope of work for each project will be defined in a separate Service Agreement.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">3. Intellectual Property</h2>
                    <p>Unless otherwise agreed in writing, all custom software developed for clients remains the property of Believoo until full payment is received, at which point ownership is transferred to the client, subject to any third-party licenses.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">4. Payment Terms</h2>
                    <p>Payment terms are 50% upfront and 50% upon completion, unless otherwise specified in your project agreement. Late payments may result in suspension of services or infrastructure access.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">5. Liability</h2>
                    <p>Believoo shall not be liable for any indirect, incidental, special, or consequential damages resulting from the use or inability to use our services, including but not limited to loss of profits or data.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">6. Governing Law</h2>
                    <p>These terms shall be governed by and construed in accordance with the laws of India, without regard to its conflict of law provisions.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">7. Contact</h2>
                    <p>For any questions regarding these terms, please contact us at legal@believoo.com</p>
                    @if($settings['company_registered_office'] ?? $settings['address'] ?? false)
                        <p class="mt-4 text-sm text-gray-500">Registered Office: {{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }}, {{ $settings['company_registered_office'] ?? $settings['address'] }}</p>
                    @endif
                </section>
            </div>
        </div>
    </main>
</x-layouts.believoo>

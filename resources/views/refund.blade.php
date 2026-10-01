<x-layouts.believoo
    title="Cancellation & Refund Policy - Believoo"
    description="Believoo cancellation and refund policy for VPS hosting, web hosting, streaming and software services."
    keywords="Refund Policy, Cancellation Policy, Believoo refund">
    <main class="pt-32 pb-20 px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-5xl font-black mb-12 uppercase tracking-tighter">Cancellation & <span class="text-electric-violet">Refund Policy</span></h1>

            <div class="glass p-10 rounded-[2.5rem] space-y-12 text-gray-400 leading-relaxed text-lg">
                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">1. Overview</h2>
                    <p>This Cancellation &amp; Refund Policy applies to all products and services offered by {{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }} ("Believoo", "we", "us"), including VPS hosting, web hosting, streaming services, domains, and custom software development. By purchasing our services, you agree to the terms outlined below. This policy is governed by the laws of India.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">2. Cancellation Policy</h2>
                    <p class="mb-4">You may cancel any recurring service at any time through your client portal or by submitting a request via our support desk:</p>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Cancellation takes effect at the end of the current billing cycle; services remain active until then.</li>
                        <li>Immediate cancellation (same-day termination) can be requested via a support ticket.</li>
                        <li>Upon cancellation, associated data (containers, files, backups) may be permanently deleted after a grace period of up to 7 days. Please take backups before cancelling.</li>
                        <li>Cancelling a service does not automatically entitle you to a refund — refund eligibility is defined in Section 3.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">3. Refund Eligibility</h2>
                    <p class="mb-4">Refunds are issued at our discretion and are generally limited to the following cases:</p>
                    <ul class="list-disc ml-6 space-y-2">
                        <li><strong>Duplicate or incorrect charges:</strong> Accidental double payments or billing errors are refunded in full.</li>
                        <li><strong>Service not delivered:</strong> If a paid service cannot be provisioned or activated by us within a reasonable time, the payment for that service is refunded.</li>
                        <li><strong>First-time hosting orders:</strong> New shared/VPS hosting orders may be eligible for a refund if cancelled within 7 days of activation and the service was materially not as described.</li>
                        <li><strong>SLA breaches:</strong> Extended downtime beyond our committed uptime may be compensated as service credits applied to your account.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">4. Non-Refundable Items</h2>
                    <p class="mb-4">The following are strictly non-refundable:</p>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Domain name registrations, transfers, and renewals (once registered with the registry).</li>
                        <li>SSL certificates, software licenses, and third-party products purchased on your behalf.</li>
                        <li>Setup fees, migration fees, and one-time configuration charges.</li>
                        <li>Custom software development, design, or consulting work after work has commenced or been delivered.</li>
                        <li>Renewal payments for hosting or subscription services.</li>
                        <li>Accounts suspended or terminated for violation of our Terms of Service or Acceptable Use Policy.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">5. Refund Process & Timeline</h2>
                    <p class="mb-4">To request a refund, open a support ticket or email us with your invoice number and reason for the request.</p>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Requests are reviewed within 5–7 business days.</li>
                        <li>Approved refunds are issued to the original payment method within 7–10 business days, subject to your bank or payment gateway's processing time.</li>
                        <li>Refunds are processed in INR unless the original transaction was in another currency.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">6. Contact Us</h2>
                    <p class="mb-4">For any questions about cancellations or refunds, contact us:</p>
                    <ul class="list-none space-y-2">
                        <li><i class="fas fa-envelope text-electric-violet mr-2"></i> <strong>Email:</strong> billing@believoo.com</li>
                        <li><i class="fas fa-headset text-electric-violet mr-2"></i> <strong>Support:</strong> <a href="https://support.believoo.com" class="text-electric-violet hover:underline">support.believoo.com</a></li>
                        <li><i class="fas fa-map-marker-alt text-electric-violet mr-2"></i> <strong>Registered Office:</strong> {{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }}, {{ $settings['company_registered_office'] ?? $settings['address'] ?? 'Bisauli, Rajpur, Budaun, Uttar Pradesh' }}</li>
                        @if($settings['company_cin'] ?? false)
                        <li><i class="fas fa-certificate text-electric-violet mr-2"></i> <strong>CIN:</strong> {{ $settings['company_cin'] }}</li>
                        @endif
                    </ul>
                </section>
            </div>
        </div>
    </main>
</x-layouts.believoo>

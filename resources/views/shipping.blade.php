<x-layouts.believoo
    title="Shipping & Exchange Policy - Believoo"
    description="Believoo shipping, delivery and exchange policy for VPS hosting, web hosting, domains, streaming and software services."
    keywords="Shipping Policy, Delivery Policy, Exchange Policy, Believoo">
    <main class="pt-32 pb-20 px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-5xl font-black mb-12 uppercase tracking-tighter">Shipping &amp; <span class="text-electric-violet">Exchange Policy</span></h1>

            <div class="glass p-10 rounded-[2.5rem] space-y-12 text-gray-400 leading-relaxed text-lg">
                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">1. Overview</h2>
                    <p>This Shipping &amp; Exchange Policy applies to all products and services offered by {{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }} ("Believoo", "we", "us"). All Believoo offerings are digital products and services — no physical goods are sold or shipped. This policy explains how and when your services are delivered and how plan changes (exchanges) are handled. This policy is governed by the laws of India.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">2. Digital Delivery</h2>
                    <p class="mb-4">Since we provide only digital services, "shipping" refers to the provisioning and delivery of your service credentials:</p>
                    <ul class="list-disc ml-6 space-y-2">
                        <li><strong>VPS &amp; cloud servers:</strong> Typically provisioned automatically within minutes of successful payment. In rare cases requiring manual review, activation may take up to 24–48 hours.</li>
                        <li><strong>Web hosting:</strong> Activated shortly after payment confirmation; login details are sent to your registered email address.</li>
                        <li><strong>Domain registration:</strong> Usually completed within minutes; registry propagation may take up to 24–48 hours globally.</li>
                        <li><strong>Custom development services:</strong> Delivered digitally according to the milestones and timelines defined in your project agreement.</li>
                        <li>All credentials and access details are delivered to the email address registered on your account and are also visible in your client portal.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">3. Delivery Delays</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Occasional delays may occur due to payment verification, upstream provider issues, or manual fraud review.</li>
                        <li>If your service is not activated within 48 hours of a successful payment, contact our support desk with your order/invoice number and we will prioritise it.</li>
                        <li>If we are unable to deliver a paid service at all, you are entitled to a refund as per our <a href="{{ route('refund') }}" class="text-cyan-400 hover:underline">Cancellation &amp; Refund Policy</a>.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">4. Exchange &amp; Plan Changes</h2>
                    <p class="mb-4">Because our products are digital services, an "exchange" means changing or upgrading your existing service plan:</p>
                    <ul class="list-disc ml-6 space-y-2">
                        <li><strong>Upgrades:</strong> You may upgrade your hosting or VPS plan at any time from the client portal. The price difference is prorated for the remaining billing period.</li>
                        <li><strong>Downgrades:</strong> Downgrade requests take effect at the start of the next billing cycle and are subject to resource limits of the lower plan.</li>
                        <li><strong>Billing cycle changes:</strong> You may switch between monthly, quarterly, semi-annual, and annual billing at renewal time.</li>
                        <li><strong>Domain names:</strong> Once registered, a domain name cannot be exchanged or renamed. Please double-check spelling before ordering.</li>
                        <li><strong>Custom development:</strong> Scope changes are handled through change requests under your project agreement.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">5. Contact Us</h2>
                    <p>For any questions about service delivery, activation status, or plan changes, reach us through your client portal, our support desk, or email us at {{ $settings['contact_email'] ?? 'info@believoo.com' }}. Our team is happy to help.</p>
                </section>
            </div>
        </div>
    </main>
</x-layouts.believoo>

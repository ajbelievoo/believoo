<x-layouts.believoo
    title="Service Level Agreement (SLA) - Believoo"
    description="Believoo uptime commitment and service level agreement for VPS hosting, web hosting and cloud services."
    keywords="SLA, uptime guarantee, Believoo hosting SLA">
    <main class="pt-32 pb-20 px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-5xl font-black mb-12 uppercase tracking-tighter">Service Level <span class="text-electric-violet">Agreement</span></h1>

            <div class="glass p-10 rounded-[2.5rem] space-y-12 text-gray-400 leading-relaxed text-lg">
                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">1. Uptime Commitment</h2>
                    <p>{{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }} ("Believoo") commits to the following monthly availability targets for hosting services:</p>
                    <div class="mt-6 overflow-hidden rounded-2xl border border-white/10">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-white">
                                <tr><th class="px-6 py-4">Service</th><th class="px-6 py-4">Monthly Uptime Target</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr><td class="px-6 py-4">VPS Hosting (KVM/NVMe)</td><td class="px-6 py-4 font-bold text-emerald-400">99.9%</td></tr>
                                <tr><td class="px-6 py-4">Web / Shared Hosting</td><td class="px-6 py-4 font-bold text-emerald-400">99.9%</td></tr>
                                <tr><td class="px-6 py-4">Dedicated Servers</td><td class="px-6 py-4 font-bold text-emerald-400">99.9% network uptime</td></tr>
                                <tr><td class="px-6 py-4">Control Panel &amp; Client Portal</td><td class="px-6 py-4 font-bold text-emerald-400">99.5%</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">2. Definitions</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li><strong>"Downtime"</strong> means the service is unreachable or non-functional from the public internet, measured from our monitoring systems.</li>
                        <li><strong>"Monthly Uptime Percentage"</strong> = (total minutes in month − downtime minutes) ÷ total minutes in month × 100.</li>
                        <li><strong>"Service Credit"</strong> means a credit applied to your account balance — it is not a cash refund.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">3. Service Credits</h2>
                    <p class="mb-4">If monthly uptime falls below the committed target, you may claim a credit:</p>
                    <div class="overflow-hidden rounded-2xl border border-white/10">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-white">
                                <tr><th class="px-6 py-4">Monthly Uptime</th><th class="px-6 py-4">Credit</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr><td class="px-6 py-4">99.0% – 99.89%</td><td class="px-6 py-4">10% of monthly fee</td></tr>
                                <tr><td class="px-6 py-4">95.0% – 98.99%</td><td class="px-6 py-4">25% of monthly fee</td></tr>
                                <tr><td class="px-6 py-4">Below 95.0%</td><td class="px-6 py-4">50% of monthly fee</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <ul class="list-disc ml-6 space-y-2 mt-6">
                        <li>Credits must be claimed by opening a support ticket within 15 days of the end of the affected month.</li>
                        <li>Maximum credit per month: 100% of that month's fee for the affected service.</li>
                        <li>Credits apply only to the specific affected service, not the entire account.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">4. Exclusions</h2>
                    <p class="mb-4">Downtime caused by the following is excluded from uptime calculations:</p>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Scheduled maintenance announced at least 24 hours in advance (performed in low-traffic windows wherever possible).</li>
                        <li>Emergency maintenance required to protect service integrity or security.</li>
                        <li>Customer-caused issues: misconfiguration, resource overuse, suspended accounts, unpaid invoices.</li>
                        <li>DDoS attacks or upstream network failures outside our direct control.</li>
                        <li>Third-party software or services (payment gateways, external APIs, license providers).</li>
                        <li>Force majeure events (natural disasters, war, government actions, utility failures).</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">5. Support Response Targets</h2>
                    <div class="overflow-hidden rounded-2xl border border-white/10">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-white">
                                <tr><th class="px-6 py-4">Severity</th><th class="px-6 py-4">First Response</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr><td class="px-6 py-4">Critical — service down</td><td class="px-6 py-4">Within 4 hours</td></tr>
                                <tr><td class="px-6 py-4">High — degraded service</td><td class="px-6 py-4">Within 8 hours</td></tr>
                                <tr><td class="px-6 py-4">Normal — general queries</td><td class="px-6 py-4">Within 24 hours</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4">Support is provided via <a href="https://support.believoo.com" class="text-cyan-400 hover:underline">support.believoo.com</a> tickets and <a href="mailto:{{ $settings['support_email'] ?? 'support@believoo.com' }}" class="text-cyan-400 hover:underline">{{ $settings['support_email'] ?? 'support@believoo.com' }}</a>.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">6. Service Provisioning</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>VPS and web hosting: automatic provisioning after confirmed payment, typically within 15 minutes.</li>
                        <li>Dedicated servers: up to 72 hours depending on stock and configuration.</li>
                        <li>If provisioning fails, you are notified and entitled to a full refund for the undelivered service.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">7. Backups</h2>
                    <p>We maintain infrastructure-level backups for disaster recovery. Customer-level backups depend on your plan — unless your plan explicitly includes backups, you are responsible for maintaining your own copies. Believoo is not liable for data loss except where a paid backup add-on was active.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">8. Changes to this SLA</h2>
                    <p>This SLA may be updated periodically; the current version is always published on this page. Materially reduced commitments will be announced on our status channels before taking effect.</p>
                    <p class="mt-4 text-sm text-gray-500">Last updated: October 2026</p>
                </section>
            </div>
        </div>
    </main>
</x-layouts.believoo>

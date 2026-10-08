<x-layouts.believoo
    title="Acceptable Use Policy - Believoo"
    description="Believoo acceptable use policy for VPS hosting, dedicated servers, web hosting, streaming and related services."
    keywords="Acceptable Use Policy, AUP, Believoo hosting rules">
    <main class="pt-32 pb-20 px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-5xl font-black mb-12 uppercase tracking-tighter">Acceptable <span class="text-electric-violet">Use Policy</span></h1>

            <div class="glass p-10 rounded-[2.5rem] space-y-12 text-gray-400 leading-relaxed text-lg">
                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">1. Lawful Use</h2>
                    <p>All services provided by {{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }} ("Believoo", "we", "us") may only be used for lawful purposes under the laws of India and the jurisdiction where our servers are located. Use of our services for any illegal activity, or to support illegal activity, is strictly prohibited and will result in immediate suspension or termination without refund.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">2. Prohibited Activities</h2>
                    <p class="mb-4">You may not use Believoo services to:</p>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Host, store, or distribute malware, phishing pages, viruses, ransomware, or botnet command-and-control systems.</li>
                        <li>Send unsolicited bulk email (spam), operate open mail relays, or engage in email bombing.</li>
                        <li>Perform port scanning, brute-force attacks, DDoS attacks, or unauthorized probing of external networks.</li>
                        <li>Mine cryptocurrency on shared or web hosting plans (permitted only on dedicated/VPS plans where it does not breach provider terms).</li>
                        <li>Host or distribute child sexual abuse material (CSAM) or any content that exploits minors — reported to law enforcement immediately.</li>
                        <li>Infringe intellectual property rights, including hosting pirated software, warez, or unlicensed streaming content.</li>
                        <li>Operate open proxies, TOR exit nodes, or services designed to anonymize abuse.</li>
                        <li>Resell, sublet, or share services with third parties unless your plan explicitly allows reselling.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">3. Email Use</h2>
                    <p class="mb-4">Email sent through our infrastructure must comply with anti-spam laws:</p>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Marketing email requires verifiable opt-in consent from recipients.</li>
                        <li>Every marketing message must include a working unsubscribe mechanism.</li>
                        <li>Excessive bounce rates, spam complaints, or blacklistings may result in email service restriction or account review.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">4. Resource Use</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Services must not consume resources in a way that degrades performance for other customers on shared infrastructure.</li>
                        <li>"Unlimited" resources, where offered, are subject to fair use — sustained abnormal consumption may lead to throttling, plan upgrade requests, or suspension.</li>
                        <li>Processes must not bypass resource limits, container isolation, or fair-usage controls.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">5. Content Responsibility</h2>
                    <p>You are solely responsible for all content hosted on your services. Believoo does not actively monitor customer content but will act on credible abuse reports, court orders, or notices from rights holders. We may remove or disable access to content that violates this policy.</p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">6. Security Obligations</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Keep your software, CMS, plugins, and credentials updated and secure.</li>
                        <li>You are responsible for activity originating from your account, including activity caused by compromised credentials.</li>
                        <li>Compromised services that attack third parties may be suspended without prior notice to protect the network.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">7. Enforcement</h2>
                    <p class="mb-4">Violations may result in one or more of the following, at our discretion:</p>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Warning and a request for corrective action within a defined period.</li>
                        <li>Temporary suspension of the affected service.</li>
                        <li>Permanent termination without refund for severe or repeated violations.</li>
                        <li>Reporting to relevant law-enforcement authorities where required by law.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">8. Reporting Abuse</h2>
                    <p>To report abuse of Believoo services (spam, phishing, malware, copyright infringement, or other violations), contact our abuse desk:</p>
                    <p class="mt-4">
                        <strong class="text-white">Abuse:</strong>
                        <a href="mailto:abuse@believoo.com" class="text-cyan-400 hover:underline">abuse@believoo.com</a><br>
                        <strong class="text-white">Support:</strong>
                        <a href="mailto:{{ $settings['support_email'] ?? 'support@believoo.com' }}" class="text-cyan-400 hover:underline">{{ $settings['support_email'] ?? 'support@believoo.com' }}</a>
                    </p>
                </section>

                <section>
                    <h2 class="text-3xl font-black text-white mb-6 uppercase tracking-tight">9. Policy Updates</h2>
                    <p>We may update this Acceptable Use Policy from time to time. The current version is always available on this page. Continued use of our services after changes take effect constitutes acceptance of the updated policy.</p>
                    <p class="mt-4 text-sm text-gray-500">Last updated: October 2026</p>
                </section>
            </div>
        </div>
    </main>
</x-layouts.believoo>

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'title' => 'Web Application Development',
                'slug' => 'web-application-development',
                'category' => 'Development',
                'description' => 'Custom web apps, dashboards, SaaS platforms and portals built with Laravel, React and Vue.',
                'content' => 'From concept to deployment, our engineering team builds secure, scalable and high-performance web applications tailored to your business. We use modern stacks including Laravel, React, Vue, Livewire and Tailwind.',
                'icon' => 'fas fa-laptop-code',
                'price' => 499.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'Mobile App Development',
                'slug' => 'mobile-app-development',
                'category' => 'Development',
                'description' => 'Native and cross-platform mobile apps for iOS and Android with modern frameworks.',
                'content' => 'We build native and cross-platform mobile applications that deliver smooth user experiences. From Flutter to React Native, we choose the right technology for your product.',
                'icon' => 'fas fa-mobile-alt',
                'price' => 999.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'AI & Automation Solutions',
                'slug' => 'ai-automation-solutions',
                'category' => 'AI & Automation',
                'description' => 'AI chatbots, workflow automation, predictive analytics and smart integrations.',
                'content' => 'Boost efficiency with AI-powered chatbots, document processing, workflow automation and custom machine-learning integrations designed for real business impact.',
                'icon' => 'fas fa-robot',
                'price' => 299.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'Cloud & DevOps Services',
                'slug' => 'cloud-devops-services',
                'category' => 'Infrastructure',
                'description' => 'Cloud setup, CI/CD, server monitoring, containerization and infrastructure scaling.',
                'content' => 'We design, deploy and maintain cloud infrastructure on AWS, Azure and private Proxmox clusters. Includes CI/CD pipelines, Docker/Kubernetes orchestration and 24/7 monitoring.',
                'icon' => 'fas fa-cloud',
                'price' => 149.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'Web Hosting & VPS',
                'slug' => 'web-hosting-vps',
                'category' => 'Infrastructure',
                'description' => 'High-performance web hosting, VPS and dedicated servers with 99.9% uptime.',
                'content' => 'Reliable web hosting and virtual private servers backed by premium network infrastructure. Fully managed with daily backups, DDoS protection and expert support.',
                'icon' => 'fas fa-server',
                'price' => 9.99,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'Domain Registration & DNS',
                'slug' => 'domain-registration-dns',
                'category' => 'Domains',
                'description' => 'Domain search, registration, transfer and managed DNS configuration.',
                'content' => 'Search and register domains across all major TLDs. We handle DNS setup, domain transfers, privacy protection and ongoing DNS management.',
                'icon' => 'fas fa-globe-americas',
                'price' => 12.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'SEO & Digital Growth',
                'slug' => 'seo-digital-growth',
                'category' => 'Growth',
                'description' => 'Search engine optimization, content strategy and organic growth campaigns.',
                'content' => 'Improve rankings, drive organic traffic and convert visitors into customers with data-driven SEO, technical audits, content strategy and backlink building.',
                'icon' => 'fas fa-chart-line',
                'price' => 199.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'AdSense & Ad Management',
                'slug' => 'adsense-ad-management',
                'category' => 'Growth',
                'description' => 'AdSense approval, ad placement optimization and revenue management.',
                'content' => 'Get AdSense approved and maximize ad revenue with strategic placement, A/B testing and policy-compliant optimization across web and mobile.',
                'icon' => 'fas fa-ad',
                'price' => 149.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'Play Store & App Store Publishing',
                'slug' => 'play-store-app-store-publishing',
                'category' => 'Publishing',
                'description' => 'App submission, ASO, account setup and ongoing store management.',
                'content' => 'End-to-end publishing for Google Play and Apple App Store. We handle screenshots, descriptions, ASO, policy compliance and update management.',
                'icon' => 'fab fa-google-play',
                'price' => 79.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'UI/UX Design',
                'slug' => 'ui-ux-design',
                'category' => 'Development',
                'description' => 'User research, wireframes, prototypes and polished interface design.',
                'content' => 'Create intuitive and visually stunning interfaces. We deliver wireframes, interactive prototypes and production-ready designs that improve conversion.',
                'icon' => 'fas fa-paint-brush',
                'price' => 349.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'API Development & Integration',
                'slug' => 'api-development-integration',
                'category' => 'Development',
                'description' => 'REST/GraphQL APIs and third-party integrations for your applications.',
                'content' => 'We build secure REST and GraphQL APIs and integrate third-party services such as payment gateways, CRMs, ERPs and marketing tools.',
                'icon' => 'fas fa-plug',
                'price' => 399.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'Cybersecurity & Hardening',
                'slug' => 'cybersecurity-hardening',
                'category' => 'Infrastructure',
                'description' => 'Security audits, SSL, firewalls, malware cleanup and compliance guidance.',
                'content' => 'Protect your applications and data with vulnerability scans, WAF configuration, SSL setup, server hardening and incident response.',
                'icon' => 'fas fa-shield-alt',
                'price' => 249.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
            [
                'title' => 'Website Maintenance & Support',
                'slug' => 'website-maintenance-support',
                'category' => 'Support',
                'description' => 'Monthly updates, backups, performance tuning and priority support.',
                'content' => 'Keep your website secure and up-to-date with our monthly maintenance plans. Includes core/plugin updates, backups, monitoring and fast support.',
                'icon' => 'fas fa-headset',
                'price' => 49.00,
                'price_label' => 'Starting From',
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['slug' => $service['slug']], $service);
        }

        // Update existing services to realistic USD pricing if still active
        $existing = [
            'app-development' => ['price' => 999.00, 'category' => 'Development', 'price_label' => 'Starting From'],
            'seo-digital-growth' => ['price' => 199.00, 'category' => 'Growth', 'price_label' => 'Starting From'],
            'play-store-app-store-publishing' => ['price' => 79.00, 'category' => 'Publishing', 'price_label' => 'Starting From'],
            'adsense-approval-service' => ['price' => 149.00, 'category' => 'Growth', 'price_label' => 'Starting From'],
            'managed-vps-cloud' => ['price' => 49.00, 'category' => 'Infrastructure', 'price_label' => 'Starting From'],
            'streaming-addon' => ['price' => 29.00, 'category' => 'Infrastructure', 'price_label' => 'Starting From'],
        ];

        foreach ($existing as $slug => $data) {
            $service = Service::where('slug', $slug)->first();
            if ($service) {
                $service->update($data);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Service;
use App\Models\Portfolio;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Services
        $services = [
            [
                'title' => 'Managed VPS & Cloud',
                'slug' => Str::slug('Managed VPS & Cloud'),
                'description' => 'High-performance, enterprise-grade VPS and cloud infrastructure. Optimized for speed, security, and scalability.',
                'icon' => 'fas fa-server',
                'price' => 49.00,
                'price_label' => 'Starting From',
            ],
            [
                'title' => 'App Development',
                'slug' => Str::slug('App Development'),
                'description' => 'Custom mobile and web applications built with modern frameworks like Laravel, Flutter, and React. From idea to deployment.',
                'icon' => 'fas fa-mobile-screen-button',
                'price' => 1999.00,
                'price_label' => 'Starting From',
            ],
            [
                'title' => 'SEO & Digital Growth',
                'slug' => Str::slug('SEO & Digital Growth'),
                'description' => 'Strategic search engine optimization and growth hacking to dominate your industry and maximize organic reach.',
                'icon' => 'fas fa-chart-line',
                'price' => 299.00,
                'price_label' => 'Starting From',
            ],
            [
                'title' => 'AdSense Approval Service',
                'slug' => Str::slug('AdSense Approval Service'),
                'description' => 'Expert guidance and optimization to get your website approved for Google AdSense monetization quickly and efficiently.',
                'icon' => 'fas fa-money-bill-trend-up',
                'price' => 99.00,
                'price_label' => 'Fixed Price',
            ],
            [
                'title' => 'Play Store/App Store Publishing',
                'slug' => Str::slug('Play Store/App Store Publishing'),
                'description' => 'Professional app store submission and management. We handle all the technical requirements for a successful launch.',
                'icon' => 'fab fa-google-play',
                'price' => 149.00,
                'price_label' => 'Per App',
            ],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(['title' => $service['title']], $service);
        }

        // VPS Hosting Services - INR Pricing (matches VpsPlanSeeder)
        $vpsServices = [
            [
                'title' => 'VPS-1',
                'slug' => 'vps-1',
                'category' => 'Hosting',
                'description' => 'Entry level VPS for small projects and testing environments.',
                'icon' => 'fas fa-server',
                'price' => 1400.00,
                'price_label' => 'per month',
                'features' => [
                    ['feature' => '4 vCores'],
                    ['feature' => '8 GB RAM'],
                    ['feature' => '75 GB SSD'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '400 Mbps Bandwidth'],
                ],
                'is_active' => true,
            ],
            [
                'title' => 'VPS-2',
                'slug' => 'vps-2',
                'category' => 'Hosting',
                'description' => 'Best for applications and medium traffic websites.',
                'icon' => 'fas fa-server',
                'price' => 2153.00,
                'price_label' => 'per month',
                'features' => [
                    ['feature' => '6 vCores'],
                    ['feature' => '12 GB RAM'],
                    ['feature' => '100 GB NVMe'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '1 Gbps Bandwidth'],
                ],
                'is_active' => true,
            ],
            [
                'title' => 'VPS-3',
                'slug' => 'vps-3',
                'category' => 'Hosting',
                'description' => 'Best value for performance and resources.',
                'icon' => 'fas fa-server',
                'price' => 4308.00,
                'price_label' => 'per month',
                'features' => [
                    ['feature' => '8 vCores'],
                    ['feature' => '24 GB RAM'],
                    ['feature' => '200 GB NVMe'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '1.5 Gbps Bandwidth'],
                ],
                'is_active' => true,
            ],
            [
                'title' => 'VPS-4',
                'slug' => 'vps-4',
                'category' => 'Hosting',
                'description' => 'Powerful VPS for high-traffic applications.',
                'icon' => 'fas fa-server',
                'price' => 7970.00,
                'price_label' => 'per month',
                'features' => [
                    ['feature' => '12 vCores'],
                    ['feature' => '48 GB RAM'],
                    ['feature' => '300 GB NVMe'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '2 Gbps Bandwidth'],
                ],
                'is_active' => true,
            ],
            [
                'title' => 'VPS-5',
                'slug' => 'vps-5',
                'category' => 'Hosting',
                'description' => 'Enterprise VPS for business-critical applications.',
                'icon' => 'fas fa-server',
                'price' => 11850.00,
                'price_label' => 'per month',
                'features' => [
                    ['feature' => '16 vCores'],
                    ['feature' => '64 GB RAM'],
                    ['feature' => '350 GB NVMe'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '2.5 Gbps Bandwidth'],
                ],
                'is_active' => true,
            ],
            [
                'title' => 'VPS-6',
                'slug' => 'vps-6',
                'category' => 'Hosting',
                'description' => 'Ultimate VPS for maximum performance and scale.',
                'icon' => 'fas fa-server',
                'price' => 15730.00,
                'price_label' => 'per month',
                'features' => [
                    ['feature' => '24 vCores'],
                    ['feature' => '96 GB RAM'],
                    ['feature' => '400 GB NVMe'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '3 Gbps Bandwidth'],
                ],
                'is_active' => true,
            ],
        ];

        foreach ($vpsServices as $vpsService) {
            Service::firstOrCreate(['slug' => $vpsService['slug']], $vpsService);
        }

        // Portfolios
        $portfolios = [
            [
                'title' => 'iYol Analytics',
                'slug' => Str::slug('iYol Analytics'),
                'description' => 'A comprehensive social media analytics platform providing real-time insights and data-driven growth strategies for influencers.',
                'problem' => 'The client required a high-availability infrastructure capable of handling millions of real-time social media data points with zero latency issues while maintaining strict security compliance.',
                'solution' => 'We architected a distributed microservices environment using Kubernetes and AWS, implementing custom load balancing and end-to-end encryption protocols for data processing.',
                'result' => 'The platform achieved 99.99% uptime during peak traffic, with a 40% reduction in server response times and enhanced security posture, now serving over 50,000 active influencers.',
                'tech_stack' => [
                    ['name' => 'Laravel', 'icon' => 'fab fa-laravel'],
                    ['name' => 'AWS', 'icon' => 'fab fa-aws'],
                    ['name' => 'Kubernetes', 'icon' => 'fas fa-cubes'],
                    ['name' => 'Redis', 'icon' => 'fas fa-database'],
                    ['name' => 'PostgreSQL', 'icon' => 'fas fa-database'],
                    ['name' => 'Tailwind', 'icon' => 'fab fa-css3-alt'],
                ],
                'image' => 'portfolio/iyol.jpg',
                'url' => 'https://iyol.com',
                'is_visible' => true,
            ],
            [
                'title' => 'Vidmite API',
                'slug' => Str::slug('Vidmite API'),
                'description' => 'High-speed video processing and transcoding API designed for high-traffic platforms requiring instant media transformations.',
                'problem' => 'Legacy video processing systems were slow and expensive to scale, causing significant delays in media transformation for a rapidly growing user base.',
                'solution' => 'Developed a serverless video transcoding engine using AWS Lambda and FFmpeg, integrated with a global CDN for instant delivery of processed media.',
                'result' => 'Transcoding speed increased by 300% while reducing infrastructure costs by 60%. The API now handles over 1 million transformations daily without any performance degradation.',
                'tech_stack' => [
                    ['name' => 'Node.js', 'icon' => 'fab fa-node-js'],
                    ['name' => 'AWS Lambda', 'icon' => 'fab fa-aws'],
                    ['name' => 'FFmpeg', 'icon' => 'fas fa-video'],
                    ['name' => 'S3', 'icon' => 'fas fa-hdd'],
                    ['name' => 'CloudFront', 'icon' => 'fas fa-network-wired'],
                    ['name' => 'Redis', 'icon' => 'fas fa-database'],
                ],
                'image' => 'portfolio/vidmite.jpg',
                'url' => 'https://vidmite.dev',
                'is_visible' => true,
            ],
            [
                'title' => 'Hitune Music',
                'slug' => Str::slug('Hitune Music'),
                'description' => 'Scalable infrastructure and backend architecture for a global music streaming service, handling millions of requests concurrently.',
                'problem' => 'The streaming platform struggled with erratic traffic spikes during major releases, leading to frequent downtime and poor user experience in high-latency regions.',
                'solution' => 'Implemented an edge-computing architecture with multi-region database synchronization and an intelligent caching layer for frequently accessed music tracks.',
                'result' => 'Zero downtime recorded during 10+ major global music launches. Global latency reduced by 50%, resulting in a 25% increase in user retention and average listening time.',
                'tech_stack' => [
                    ['name' => 'Go', 'icon' => 'fab fa-golang'],
                    ['name' => 'Docker', 'icon' => 'fab fa-docker'],
                    ['name' => 'GCP', 'icon' => 'fab fa-google'],
                    ['name' => 'MongoDB', 'icon' => 'fas fa-database'],
                    ['name' => 'Redis', 'icon' => 'fas fa-database'],
                    ['name' => 'WebRTC', 'icon' => 'fas fa-video'],
                ],
                'image' => 'portfolio/hitune.jpg',
                'url' => 'https://hitune.io',
                'is_visible' => true,
            ],
        ];

        foreach ($portfolios as $portfolio) {
            Portfolio::firstOrCreate(['title' => $portfolio['title']], $portfolio);
        }
    }
}

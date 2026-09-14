<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Portfolio;
use App\Models\Testimonial;

class PortfolioAndTestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        Portfolio::truncate();
        Testimonial::truncate();

        // Create Portfolio Items
        $portfolios = [
            [
                'title' => 'E-Commerce Mobile App',
                'slug' => 'e-commerce-mobile-app',
                'description' => 'Full-stack React Native app with payment integration for a fashion retailer.',
                'icon' => 'fa-mobile-alt',
                'status' => 'completed',
                'gradient_from' => 'from-electric-blue/20',
                'gradient_to' => 'to-electric-violet/20',
                'tech_stack' => [
                    ['name' => 'React Native', 'icon' => 'fa-brands fa-react'],
                    ['name' => 'Node.js', 'icon' => 'fa-brands fa-node'],
                    ['name' => 'Stripe', 'icon' => 'fa-brands fa-stripe'],
                ],
                'is_visible' => true,
            ],
            [
                'title' => 'Multi-Vendor Marketplace',
                'slug' => 'multi-vendor-marketplace',
                'description' => 'Laravel-based marketplace with vendor dashboards and real-time chat.',
                'icon' => 'fa-shopping-cart',
                'status' => 'completed',
                'gradient_from' => 'from-green-500/20',
                'gradient_to' => 'to-electric-blue/20',
                'tech_stack' => [
                    ['name' => 'Laravel', 'icon' => 'fa-brands fa-laravel'],
                    ['name' => 'Vue.js', 'icon' => 'fa-brands fa-vuejs'],
                    ['name' => 'WebSockets', 'icon' => 'fa-plug'],
                ],
                'is_visible' => true,
            ],
            [
                'title' => 'Healthcare Dashboard',
                'slug' => 'healthcare-dashboard',
                'description' => 'HIPAA-compliant patient management system with appointment scheduling.',
                'icon' => 'fa-heartbeat',
                'status' => 'completed',
                'gradient_from' => 'from-purple-500/20',
                'gradient_to' => 'to-pink-500/20',
                'tech_stack' => [
                    ['name' => 'React', 'icon' => 'fa-brands fa-react'],
                    ['name' => 'Python', 'icon' => 'fa-brands fa-python'],
                    ['name' => 'AWS', 'icon' => 'fa-brands fa-aws'],
                ],
                'is_visible' => true,
            ],
        ];

        foreach ($portfolios as $portfolio) {
            Portfolio::create($portfolio);
        }

        // Create Testimonials
        $testimonials = [
            [
                'name' => 'Sarah Johnson',
                'title' => 'CEO',
                'company' => 'FashionHub',
                'content' => 'Believoo delivered our e-commerce app ahead of schedule. The team\'s professionalism and technical expertise exceeded our expectations. Highly recommended!',
                'rating' => 5,
                'is_visible' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Michael Chen',
                'title' => 'Founder',
                'company' => 'TechStart',
                'content' => 'Working with Believoo was a game-changer for our startup. They understood our vision and built a scalable solution that helped us secure Series A funding.',
                'rating' => 5,
                'is_visible' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }
    }
}

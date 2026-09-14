<?php

namespace Database\Seeders;

use App\Models\PageContent;
use Illuminate\Database\Seeder;

class PageContentSeeder extends Seeder
{
    public function run(): void
    {
        $contents = [
            // Home Page
            [
                'page_name' => 'home',
                'section_name' => 'hero',
                'key' => 'hero_title',
                'value' => 'Architecting The <br/><span class="text-electric-blue">Digital Frontier</span>',
                'type' => 'text',
            ],
            [
                'page_name' => 'home',
                'section_name' => 'hero',
                'key' => 'hero_description',
                'value' => 'We deploy elite infrastructure and software solutions for high-performance enterprises. From VPS dominance to custom blockchain ecosystems, we build for the billion-dollar future.',
                'type' => 'text',
            ],
            [
                'page_name' => 'home',
                'section_name' => 'why_choose_us',
                'key' => 'bento_1_title',
                'value' => '99.9% Uptime SLA',
                'type' => 'text',
            ],
            [
                'page_name' => 'home',
                'section_name' => 'why_choose_us',
                'key' => 'bento_1_text',
                'value' => 'Our infrastructure is built for maximum reliability and global scale.',
                'type' => 'text',
            ],
            [
                'page_name' => 'home',
                'section_name' => 'why_choose_us',
                'key' => 'bento_2_title',
                'value' => 'Elite Security',
                'type' => 'text',
            ],
            [
                'page_name' => 'home',
                'section_name' => 'why_choose_us',
                'key' => 'bento_2_text',
                'value' => 'Military-grade encryption and real-time threat monitoring as standard.',
                'type' => 'text',
            ],
            [
                'page_name' => 'home',
                'section_name' => 'why_choose_us',
                'key' => 'bento_3_title',
                'value' => '24/7 Support',
                'type' => 'text',
            ],
            [
                'page_name' => 'home',
                'section_name' => 'why_choose_us',
                'key' => 'bento_3_text',
                'value' => 'Dedicated engineering support for all enterprise clients.',
                'type' => 'text',
            ],
        ];

        foreach ($contents as $content) {
            // Use firstOrCreate to avoid overwriting existing values
            PageContent::firstOrCreate(
                ['key' => $content['key']],
                $content
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\StreamingPlan;

class StreamingHybridPlansSeeder extends Seeder
{
    public function run(): void
    {
        // VPS-Embedded Streaming Add-on Plans
        $vpsEmbeddedPlans = [
            [
                'name' => 'VPS Streaming Basic',
                'slug' => 'vps-streaming-basic',
                'description' => 'Basic streaming add-on for VPS hosting. Unlimited viewers, limited only by your VPS resources.',
                'price' => 19.99,
                'addon_price' => 19.99,
                'billing_cycle' => 'monthly',
                'max_viewers' => 999999, // Unlimited - VPS dependent
                'max_concurrent_viewers' => 999999,
                'max_bitrate' => 8000,
                'max_resolution' => 1080,
                'bandwidth_gb' => 999999, // Unlimited - VPS dependent
                'bandwidth_limit_gb' => 999999,
                'storage_gb' => 999999, // Unlimited - VPS dependent
                'storage_limit_gb' => 999999,
                'stream_count' => 3,
                'max_projects' => 5,
                'rtmp_support' => true,
                'webrtc_support' => true,
                'hls_support' => true,
                'dash_support' => true,
                'recording_enabled' => true,
                'transcoding_enabled' => true,
                'adaptive_bitrate' => true,
                'low_latency' => true,
                'features' => json_encode([
                    'Unlimited Viewers (VPS Dependent)',
                    'Self-Hosted on Your VPS',
                    'Full API Access',
                    'RTMP & WebRTC Support',
                    'HLS & DASH Playback',
                    'Cloud Recording',
                    'Live Transcoding',
                    'Adaptive Bitrate',
                    'Low Latency Streaming'
                ]),
                'is_active' => true,
                'is_addon' => true,
                'delivery_method' => 'vps_embedded',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'VPS Streaming Pro',
                'slug' => 'vps-streaming-pro',
                'description' => 'Professional streaming add-on for VPS hosting with advanced features and priority support.',
                'price' => 39.99,
                'addon_price' => 39.99,
                'billing_cycle' => 'monthly',
                'max_viewers' => 999999, // Unlimited - VPS dependent
                'max_concurrent_viewers' => 999999,
                'max_bitrate' => 12000,
                'max_resolution' => 2160,
                'bandwidth_gb' => 999999, // Unlimited - VPS dependent
                'bandwidth_limit_gb' => 999999,
                'storage_gb' => 999999, // Unlimited - VPS dependent
                'storage_limit_gb' => 999999,
                'stream_count' => 5,
                'max_projects' => 10,
                'rtmp_support' => true,
                'webrtc_support' => true,
                'hls_support' => true,
                'dash_support' => true,
                'recording_enabled' => true,
                'transcoding_enabled' => true,
                'adaptive_bitrate' => true,
                'low_latency' => true,
                'features' => json_encode([
                    'Unlimited Viewers (VPS Dependent)',
                    'Self-Hosted on Your VPS',
                    'Full API Access',
                    'RTMP & WebRTC Support',
                    'HLS & DASH Playback',
                    'Cloud Recording',
                    'Live Transcoding',
                    'Adaptive Bitrate',
                    'Low Latency Streaming',
                    'Priority Support',
                    '4K Streaming Support'
                ]),
                'is_active' => true,
                'is_addon' => true,
                'delivery_method' => 'vps_embedded',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Dedicated Streaming API (Standalone) Plans
        $standalonePlans = [
            [
                'name' => 'Starter Streaming',
                'slug' => 'starter-streaming',
                'description' => 'Perfect for beginners and small projects. Hosted on BelieVoo\'s streaming cluster.',
                'price' => 29.99,
                'addon_price' => 0,
                'billing_cycle' => 'monthly',
                'max_viewers' => 500,
                'max_concurrent_viewers' => 500,
                'max_bitrate' => 4000,
                'max_resolution' => 720,
                'bandwidth_gb' => 1000,
                'bandwidth_limit_gb' => 1000,
                'storage_gb' => 50,
                'storage_limit_gb' => 50,
                'stream_count' => 2,
                'max_projects' => 3,
                'rtmp_support' => true,
                'webrtc_support' => true,
                'hls_support' => true,
                'dash_support' => false,
                'recording_enabled' => true,
                'transcoding_enabled' => false,
                'adaptive_bitrate' => false,
                'low_latency' => true,
                'features' => json_encode([
                    '500 Concurrent Viewers',
                    'Cloud-Hosted on BelieVoo Cluster',
                    'Full API Access',
                    'RTMP & WebRTC Support',
                    'HLS Playback',
                    'Cloud Recording (50GB)',
                    'Low Latency Streaming',
                    'Email Support'
                ]),
                'is_active' => true,
                'is_addon' => false,
                'delivery_method' => 'cloud_hosted',
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Professional Streaming',
                'slug' => 'professional-streaming',
                'description' => 'Ideal for professional content creators and businesses. Hosted on BelieVoo\'s streaming cluster.',
                'price' => 79.99,
                'addon_price' => 0,
                'billing_cycle' => 'monthly',
                'max_viewers' => 2000,
                'max_concurrent_viewers' => 2000,
                'max_bitrate' => 6000,
                'max_resolution' => 1080,
                'bandwidth_gb' => 5000,
                'bandwidth_limit_gb' => 5000,
                'storage_gb' => 200,
                'storage_limit_gb' => 200,
                'stream_count' => 5,
                'max_projects' => 10,
                'rtmp_support' => true,
                'webrtc_support' => true,
                'hls_support' => true,
                'dash_support' => true,
                'recording_enabled' => true,
                'transcoding_enabled' => true,
                'adaptive_bitrate' => true,
                'low_latency' => true,
                'features' => json_encode([
                    '2,000 Concurrent Viewers',
                    'Cloud-Hosted on BelieVoo Cluster',
                    'Full API Access',
                    'RTMP & WebRTC Support',
                    'HLS & DASH Playback',
                    'Cloud Recording (200GB)',
                    'Live Transcoding',
                    'Adaptive Bitrate',
                    'Low Latency Streaming',
                    'Priority Support'
                ]),
                'is_active' => true,
                'is_addon' => false,
                'delivery_method' => 'cloud_hosted',
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Business Streaming',
                'slug' => 'business-streaming',
                'description' => 'Perfect for businesses and enterprises with high streaming demands.',
                'price' => 199.99,
                'addon_price' => 0,
                'billing_cycle' => 'monthly',
                'max_viewers' => 5000,
                'max_concurrent_viewers' => 5000,
                'max_bitrate' => 8000,
                'max_resolution' => 1440,
                'bandwidth_gb' => 15000,
                'bandwidth_limit_gb' => 15000,
                'storage_gb' => 500,
                'storage_limit_gb' => 500,
                'stream_count' => 10,
                'max_projects' => 25,
                'rtmp_support' => true,
                'webrtc_support' => true,
                'hls_support' => true,
                'dash_support' => true,
                'recording_enabled' => true,
                'transcoding_enabled' => true,
                'adaptive_bitrate' => true,
                'low_latency' => true,
                'features' => json_encode([
                    '5,000 Concurrent Viewers',
                    'Cloud-Hosted on BelieVoo Cluster',
                    'Full API Access',
                    'RTMP & WebRTC Support',
                    'HLS & DASH Playback',
                    'Cloud Recording (500GB)',
                    'Live Transcoding',
                    'Adaptive Bitrate',
                    'Low Latency Streaming',
                    '24/7 Priority Support',
                    '2K Streaming Support'
                ]),
                'is_active' => true,
                'is_addon' => false,
                'delivery_method' => 'cloud_hosted',
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Enterprise Streaming',
                'slug' => 'enterprise-streaming',
                'description' => 'Unlimited streaming for large enterprises with dedicated resources.',
                'price' => 499.99,
                'addon_price' => 0,
                'billing_cycle' => 'monthly',
                'max_viewers' => 999999, // Unlimited
                'max_concurrent_viewers' => 999999,
                'max_bitrate' => 12000,
                'max_resolution' => 2160,
                'bandwidth_gb' => 999999, // Unlimited
                'bandwidth_limit_gb' => 999999,
                'storage_gb' => 999999, // Unlimited
                'storage_limit_gb' => 999999,
                'stream_count' => 20,
                'max_projects' => 50,
                'rtmp_support' => true,
                'webrtc_support' => true,
                'hls_support' => true,
                'dash_support' => true,
                'recording_enabled' => true,
                'transcoding_enabled' => true,
                'adaptive_bitrate' => true,
                'low_latency' => true,
                'features' => json_encode([
                    'Unlimited Concurrent Viewers',
                    'Cloud-Hosted on BelieVoo Cluster',
                    'Full API Access',
                    'RTMP & WebRTC Support',
                    'HLS & DASH Playback',
                    'Unlimited Cloud Recording',
                    'Live Transcoding',
                    'Adaptive Bitrate',
                    'Low Latency Streaming',
                    '24/7 Dedicated Support',
                    '4K Streaming Support',
                    'Custom Integrations'
                ]),
                'is_active' => true,
                'is_addon' => false,
                'delivery_method' => 'cloud_hosted',
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert VPS Embedded plans
        foreach ($vpsEmbeddedPlans as $plan) {
            StreamingPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        // Insert Standalone plans
        foreach ($standalonePlans as $plan) {
            StreamingPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Streaming hybrid plans seeded successfully!');
    }
}

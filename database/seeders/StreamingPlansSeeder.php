<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StreamingPlansSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if plans already exist
        if (DB::table('streaming_plans')->where('is_addon', 0)->count() > 0) {
            $this->command->info('Streaming plans already exist, skipping.');
            return;
        }

        $now = now();

        DB::table('streaming_plans')->insert([
            [
                'name'                => 'Starter Stream',
                'slug'                => 'starter-stream',
                'description'         => 'Perfect for indie creators and small communities. Get your AppID in seconds.',
                'price'               => 9.99,
                'billing_cycle'       => 'monthly',
                'max_viewers'         => 100,
                'max_bitrate'         => 3000,
                'max_resolution'      => 720,
                'bandwidth_gb'        => 100,
                'storage_gb'          => 10,
                'stream_count'        => 1,
                'max_projects'        => 3,
                'rtmp_support'        => 1,
                'webrtc_support'      => 1,
                'hls_support'         => 1,
                'dash_support'        => 0,
                'recording_enabled'   => 0,
                'transcoding_enabled' => 0,
                'adaptive_bitrate'    => 0,
                'low_latency'         => 0,
                'is_active'           => 1,
                'is_addon'            => 0,
                'addon_price'         => 0,
                'sort_order'          => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'name'                => 'Pro Stream',
                'slug'                => 'pro-stream',
                'description'         => 'For growing channels and professional broadcasters. Low latency, cloud recording included.',
                'price'               => 29.99,
                'billing_cycle'       => 'monthly',
                'max_viewers'         => 1000,
                'max_bitrate'         => 6000,
                'max_resolution'      => 1080,
                'bandwidth_gb'        => 500,
                'storage_gb'          => 50,
                'stream_count'        => 3,
                'max_projects'        => 10,
                'rtmp_support'        => 1,
                'webrtc_support'      => 1,
                'hls_support'         => 1,
                'dash_support'        => 1,
                'recording_enabled'   => 1,
                'transcoding_enabled' => 0,
                'adaptive_bitrate'    => 0,
                'low_latency'         => 1,
                'is_active'           => 1,
                'is_addon'            => 0,
                'addon_price'         => 0,
                'sort_order'          => 2,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'name'                => 'Enterprise Stream',
                'slug'                => 'enterprise-stream',
                'description'         => 'Unlimited scale for large events, enterprises, and OTT platforms.',
                'price'               => 99.99,
                'billing_cycle'       => 'monthly',
                'max_viewers'         => 10000,
                'max_bitrate'         => 12000,
                'max_resolution'      => 2160,
                'bandwidth_gb'        => 2000,
                'storage_gb'          => 200,
                'stream_count'        => 10,
                'max_projects'        => 25,
                'rtmp_support'        => 1,
                'webrtc_support'      => 1,
                'hls_support'         => 1,
                'dash_support'        => 1,
                'recording_enabled'   => 1,
                'transcoding_enabled' => 1,
                'adaptive_bitrate'    => 1,
                'low_latency'         => 1,
                'is_active'           => 1,
                'is_addon'            => 0,
                'addon_price'         => 0,
                'sort_order'          => 3,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'name'                => 'Stream Add-on',
                'slug'                => 'stream-addon',
                'description'         => 'Add live streaming capability to any VPS plan.',
                'price'               => 14.99,
                'billing_cycle'       => 'monthly',
                'max_viewers'         => 500,
                'max_bitrate'         => 4000,
                'max_resolution'      => 1080,
                'bandwidth_gb'        => 200,
                'storage_gb'          => 20,
                'stream_count'        => 2,
                'max_projects'        => 5,
                'rtmp_support'        => 1,
                'webrtc_support'      => 1,
                'hls_support'         => 1,
                'dash_support'        => 0,
                'recording_enabled'   => 1,
                'transcoding_enabled' => 0,
                'adaptive_bitrate'    => 0,
                'low_latency'         => 0,
                'is_active'           => 1,
                'is_addon'            => 1,
                'addon_price'         => 14.99,
                'sort_order'          => 4,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ]);

        $this->command->info('✅ 4 streaming plans seeded (3 standalone + 1 VPS add-on).');
    }
}

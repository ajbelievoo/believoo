<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Streaming Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your streaming server connection for metrics collection.
    | Supports: srs, mediamtx, nginx
    |
    */

    'server_type' => env('STREAMING_SERVER_TYPE', 'mediamtx'),
    'api_base_url' => env('STREAMING_API_BASE_URL', 'http://localhost:40001'),
    'api_secret' => env('STREAMING_API_SECRET', null),

    // Base URLs for stream endpoints
    'ingest_base_url' => env('STREAMING_INGEST_BASE_URL', 'rtmp://139.99.43.203:1935'),
    'playback_base_url' => env('STREAMING_PLAYBACK_BASE_URL', 'http://139.99.43.203:8888'),
    'webrtc_base_url' => env('STREAMING_WEBRTC_BASE_URL', 'ws://139.99.43.203:8889'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    |
    | How often to collect metrics (in seconds).
    | This should match your cron job frequency.
    |
    */
    'metrics_collection_interval' => env('STREAMING_METRICS_INTERVAL', 60),

    /*
    |--------------------------------------------------------------------------
    | Recording Settings
    |--------------------------------------------------------------------------
    |
    */
    'recording_storage_path' => env('STREAMING_RECORDING_PATH', storage_path('app/streaming/recordings')),
    'recording_retention_days' => env('STREAMING_RECORDING_RETENTION', 30),
];

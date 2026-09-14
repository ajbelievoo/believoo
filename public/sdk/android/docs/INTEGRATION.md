# BelieVoo Live SDK Integration Guide

## Overview
BelieVoo Live SDK is a WebRTC-based streaming engine for Android apps. It provides low-latency live streaming capabilities with support for broadcasting and viewing streams.

## Requirements
- Android SDK 21+ (Android 5.0)
- Java 8 or Kotlin
- Camera and Microphone permissions

## Installation

### 1. Add AAR to your project

Copy `believoo-live-sdk-2.0.0.aar` to your app's `libs/` folder.

### 2. Update build.gradle

```gradle
repositories {
    flatDir {
        dirs 'libs'
    }
}

dependencies {
    implementation(name: 'believoo-live-sdk-2.0.0', ext: 'aar')
    
    // WebRTC (required)
    implementation 'org.webrtc:google-webrtc:1.0.32006'
    
    // WebSocket (required)
    implementation 'org.java-websocket:Java-WebSocket:1.5.3'
    implementation 'org.json:json:20210307'
    
    // Other dependencies
    implementation 'com.google.code.gson:gson:2.9.1'
}
```

### 3. Add permissions to AndroidManifest.xml

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.RECORD_AUDIO" />
<uses-permission android:name="android.permission.MODIFY_AUDIO_SETTINGS" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
```

## Quick Start

### Initialize SDK

```kotlin
import com.believoo.live.BelieVooLive

class MyApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        // Initialize with your BelieVoo host
        BelieVooLive.initWithHost(this, "wss://live.yourdomain.com")
    }
}
```

### Start Broadcasting

```kotlin
import com.believoo.live.BelieVooLive
import com.believoo.live.render.BelieVooSurfaceView

class BroadcastActivity : AppCompatActivity() {
    private lateinit var liveClient: BelieVooLive
    private lateinit var localView: BelieVooSurfaceView
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_broadcast)
        
        localView = findViewById(R.id.local_video_view)
        
        // Initialize with your BelieVoo credentials
        liveClient = BelieVooLive(
            this,
            "bel_your_app_id",
            "your_app_certificate"
        )
        
        // Start broadcasting
        liveClient.joinBroadcastChannel(
            channel = "my-live-channel",
            token = "your_token",
            uid = 12345,
            view = localView
        )
    }
    
    override fun onDestroy() {
        super.onDestroy()
        liveClient.leaveChannel()
        liveClient.release()
    }
}
```

### Watch Stream (Audience)

```kotlin
class WatchActivity : AppCompatActivity() {
    private lateinit var liveClient: BelieVooLive
    private lateinit var remoteView: BelieVooSurfaceView
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_watch)
        
        remoteView = findViewById(R.id.remote_video_view)
        
        liveClient = BelieVooLive(this, "bel_your_app_id", "your_cert")
        
        // Join as audience
        liveClient.joinAudienceChannel(
            channel = "my-live-channel",
            token = "your_token",
            uid = 67890,
            view = remoteView
        )
    }
}
```

## API Reference

### BelieVooLive Class

| Method | Description |
|--------|-------------|
| `initWithHost(context, host)` | Initialize SDK with your BelieVoo server |
| `joinBroadcastChannel(channel, token, uid, view)` | Start broadcasting |
| `joinAudienceChannel(channel, token, uid, view)` | Watch a stream |
| `leaveChannel()` | Leave current channel |
| `release()` | Release all resources |

### XML Layout

```xml
<com.believoo.live.render.BelieVooSurfaceView
    android:id="@+id/local_video_view"
    android:layout_width="match_parent"
    android:layout_height="match_parent" />
```

## BelieVoo Server Setup

Your clients need a BelieVoo streaming server. Contact your hosting provider or set up your own:

```bash
# Docker deployment
docker run -d \
  -p 1935:1935 \
  -p 8080:8080 \
  -p 8443:8443 \
  -e BELIEVOO_APP_ID=bel_your_id \
  believoo/streaming-server:2.0.0
```

## Support

- Documentation: https://believoo.com/docs/streaming
- Email: support@believoo.com
- App ID: bel_06ad61f458468c57b1d48a29

## License

Copyright 2026 BelieVoo Technologies. All rights reserved.

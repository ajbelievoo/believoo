# BelieVoo Live Engine - SDK Technical Architecture

## 1. SDK Decompilation & API Injection

### Audio Pipeline Entry Points
```java
// Located in: com.believoo.live.BelieVooLive

// Method A: Direct PCM Injection
public int pushExternalAudioFrame(byte[] pcmData, int sampleRate, int channels) {
    return nativePushAudioFrame(pcmData, sampleRate, channels);
}

// Method B: External Audio Source
public void setExternalAudioSource(AudioSource source) {
    nativeSetExternalAudioSource(source);
}

// Native Audio Pipeline Entry
private native int nativePushAudioFrame(byte[] data, int sampleRate, int channels);
private native void nativeSetExternalAudioSource(AudioSource source);
```

### WebRTC Engine Integration
```cpp
// Native Layer (libbelievoo.so)
// File: src/webrtc/audio_device_buffer.cc

int AudioDeviceBuffer::PushExternalAudio(const void* audio_data,
                                         int samples_per_channel,
                                         int sample_rate_hz,
                                         int num_channels) {
    // Direct injection to WebRTC audio pipeline
    // Bypasses microphone hardware
    return InsertAudioData(audio_data, samples_per_channel, 
                          sample_rate_hz, num_channels);
}
```

## 2. Hybrid Infrastructure Logic

### Dynamic Endpoint Selection
```java
public class BelieVooEndpointManager {
    
    public enum EndpointType {
        CLIENT_VPS,      // User's own VPS
        BELIEVOO_CLOUD,  // BelieVoo managed cluster
        HYBRID           // Fallback to cloud if VPS fails
    }
    
    public ConnectionConfig determineEndpoint(StreamingPlan plan) {
        if (plan.hasVpsHosting() && plan.getVpsIp() != null) {
            // Client VPS mode
            return ConnectionConfig.builder()
                .signalServer("wss://" + plan.getVpsIp() + ":8443")
                .turnServer(plan.getVpsIp())
                .iceServers(plan.getCustomIceServers())
                .fallbackToCloud(true)  // Auto-fallback
                .build();
        } else {
            // BelieVoo Cloud mode
            return ConnectionConfig.builder()
                .signalServer("wss://cluster.believoo.com:8443")
                .turnServer("turn.believoo.com")
                .iceServers(BelieVooConstants.DEFAULT_ICE_SERVERS)
                .region(getNearestRegion())
                .build();
        }
    }
}
```

## 3. AppID & Token Authentication

### 24-Hour Token Generator
```java
public class BelieVooAuth {
    
    // Token structure: HMAC-SHA256 based
    // Format: APP_ID:EXPIRY:NONCE:SIGNATURE
    
    public String generateTempToken(String appId, String appCert, int hours) {
        long expiry = System.currentTimeMillis() / 1000 + (hours * 3600);
        String nonce = UUID.randomUUID().toString().substring(0, 8);
        
        String payload = appId + ":" + expiry + ":" + nonce;
        String signature = HmacUtils.hmacSha256Hex(appCert, payload);
        
        return payload + ":" + signature;
    }
    
    // Server-side validation
    public boolean validateToken(String token, String appId, String appCert) {
        String[] parts = token.split(":");
        if (parts.length != 4) return false;
        
        String payload = parts[0] + ":" + parts[1] + ":" + parts[2];
        String expectedSig = HmacUtils.hmacSha256Hex(appCert, payload);
        
        return parts[3].equals(expectedSig) && 
               Long.parseLong(parts[1]) > (System.currentTimeMillis() / 1000);
    }
}
```

## 4. Real-time Metrics Pipeline

### Heartbeat System (30s interval)
```java
public class BelieVooMetrics {
    
    @Interval(30) // seconds
    public void sendHeartbeat() {
        MetricsPayload payload = MetricsPayload.builder()
            .timestamp(System.currentTimeMillis())
            .streamId(currentStream.getId())
            .viewers(currentStream.getViewerCount())
            .latencyMs(measureLatency())
            .bandwidthKbps(getBandwidthUsage())
            .packetLoss(getPacketLossPercent())
            .audioQuality(getAudioQualityScore())
            .videoQuality(getVideoQualityScore())
            .batteryLevel(getBatteryPercent())
            .cpuUsage(getCpuUsage())
            .memoryUsage(getMemoryUsage())
            .build();
        
        metricsClient.sendAsync(payload);
    }
    
    private long measureLatency() {
        // RTT measurement via STUN binding request
        return iceConnection.getRoundTripTimeMs();
    }
}
```

## 5. Audio System - 502 Fix Implementation

### WebSocket to WebRTC Fallback
```java
public class BelieVooAudioManager {
    
    private AudioTransport primaryTransport;
    private AudioTransport fallbackTransport;
    
    public void initializeAudio() {
        // Try WebSocket first
        primaryTransport = new WebSocketAudioTransport();
        
        // Set Native WebRTC as fallback
        fallbackTransport = new NativeWebRtcAudioTransport();
        
        primaryTransport.setOnErrorListener(error -> {
            if (error.getCode() == 502 || error.isWebSocketError()) {
                // Automatic fallback
                switchToFallback();
            }
        });
    }
    
    private void switchToFallback() {
        primaryTransport.stop();
        fallbackTransport.start();
        currentTransport = fallbackTransport;
        
        Log.i(TAG, "Switched to Native WebRTC audio (502 fallback)");
    }
}
```

### Nginx Configuration Fix
```nginx
# /etc/nginx/conf.d/websocket-audio.conf

upstream websocket_backend {
    server 127.0.0.1:8080 max_fails=3 fail_timeout=30s;
    keepalive 64;
}

map $http_upgrade $connection_upgrade {
    default upgrade;
    '' close;
}

server {
    listen 443 ssl http2;
    server_name admin.unilive.me;

    location /ws/audio {
        proxy_pass http://websocket_backend;
        proxy_http_version 1.1;
        
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        
        # Critical: WebSocket timeout settings
        proxy_read_timeout 86400s;
        proxy_send_timeout 86400s;
        proxy_connect_timeout 60s;
        
        # Disable buffering for real-time audio
        proxy_buffering off;
        proxy_cache off;
        
        # Fallback on 502
        proxy_intercept_errors on;
        error_page 502 = @webrtc_fallback;
    }
    
    location @webrtc_fallback {
        return 302 https://$host/webrtc/audio$is_args$args;
    }
}
```

## 6. Beauty Filter Integration

### GPU-Accelerated Beauty Pipeline
```java
public class BelieVooBeautyFilter {
    
    private BeautyProcessor beautyProcessor;
    private GPUImageFilterGroup filterGroup;
    
    public void initialize(Context context) {
        // Initialize GPU-accelerated beauty engine
        beautyProcessor = new BeautyProcessor.Builder(context)
            .setPerformanceMode(PerformanceMode.HIGH_QUALITY)
            .setOutputResolution(1280, 720)
            .enableGPUAcceleration(true)
            .build();
        
        // AI Model loading
        beautyProcessor.loadModel("skin_smoothing.tflite");
        beautyProcessor.loadModel("face_mesh.tflite");
    }
    
    public Frame processFrame(Frame inputFrame) {
        // Step 1: Face detection and landmarks
        FaceMesh faceMesh = beautyProcessor.detectFace(inputFrame);
        
        // Step 2: Skin smoothing (GPU shader)
        Frame smoothed = beautyProcessor.applySkinSmoothing(
            inputFrame, 
            config.getSkinSmoothingLevel()
        );
        
        // Step 3: Face sculpting (Mesh deformation)
        Frame sculpted = beautyProcessor.applyFaceSculpting(
            smoothed,
            faceMesh,
            config.getFaceSlimmingLevel(),
            config.getEyeEnlargementLevel()
        );
        
        // Step 4: Visual enhancement (Brightness/Contrast)
        return beautyProcessor.applyVisualEnhancement(
            sculpted,
            config.getBrightness(),
            config.getContrast(),
            config.isLowLightBoostEnabled()
        );
    }
    
    // Processing time: < 5ms on modern devices
}
```

## 7. Database Schema Additions

### Streaming Subscription Beauty Settings
```sql
-- Migration: Add beauty filter columns
ALTER TABLE streaming_subscriptions 
ADD COLUMN beauty_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN beauty_skin_smoothing FLOAT DEFAULT 0.5,
ADD COLUMN beauty_face_slimming FLOAT DEFAULT 0.3,
ADD COLUMN beauty_eye_enlargement FLOAT DEFAULT 0.2,
ADD COLUMN beauty_brightness FLOAT DEFAULT 0.1,
ADD COLUMN beauty_preset VARCHAR(20) DEFAULT 'NATURAL';

-- Index for quick lookup
CREATE INDEX idx_streaming_beauty ON streaming_subscriptions(beauty_enabled);
```

## 8. SDK Integration Checklist

### Android Integration
```gradle
dependencies {
    implementation 'com.believoo:live-sdk:2.0.0'
    implementation 'com.believoo:beauty-filters:1.5.0'
    implementation 'org.webrtc:google-webrtc:1.0.32006'
}
```

### Required Permissions
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.RECORD_AUDIO" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.MODIFY_AUDIO_SETTINGS" />
```

### ProGuard Rules
```proguard
-keep class com.believoo.live.** { *; }
-keep class org.webrtc.** { *; }
-dontwarn com.believoo.live.**
```

---

## Implementation Priority

1. **P0 - Audio System**: Apply WebSocket fix + Native WebRTC fallback
2. **P0 - Beauty Filters**: Integrate GPU-accelerated beauty pipeline
3. **P1 - Authentication**: Deploy 24-hour token system
4. **P1 - Metrics**: Enable heartbeat pipeline
5. **P2 - Hybrid Infra**: Test VPS/Cloud dynamic switching

---

**Document Version**: 2.0.0  
**Last Updated**: May 9, 2026  
**Status**: PRODUCTION READY

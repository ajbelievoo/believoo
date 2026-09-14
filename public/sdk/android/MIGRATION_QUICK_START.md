# 🎵 BelieVoo SDK Migration - Quick Start (2-3 Weeks)

## Files Created for You:

| File | Purpose |
|------|---------|
| `MIGRATION_GUIDE.md` | Complete 3-week migration plan |
| `MIGRATION_QUICK_START.md` | This file - quick reference |
| `build.gradle.example` | Dependencies to add |
| `src/main/java/.../HostLiveAudioActivity.java` | Drop-in replacement activity |
| `res/layout/activity_host_live_audio.xml` | UI layout |

---

## Week 1: SDK Integration (Days 1-5)

### Day 1: Download SDK
```bash
# Download AAR from BelieVoo
wget https://believoo.com/sdk/android/believoo-live-sdk-2.0.0.aar

# Copy to your project
cp believoo-live-sdk-2.0.0.aar YourApp/app/libs/
```

### Day 2: Update build.gradle
```gradle
dependencies {
    // NEW - BelieVoo SDK
    implementation files('libs/believoo-live-sdk-2.0.0.aar')
    implementation 'org.webrtc:google-webrtc:1.0.32006'
    
    // OLD - REMOVE THESE
    // implementation 'org.java-websocket:Java-WebSocket:1.5.3' // Keep only for signaling
}
```

### Day 3: Copy Files
```bash
# Copy HostLiveAudioActivity to your project
cp HostLiveAudioActivity.java YourApp/app/src/main/java/com/yourapp/live/

# Copy layout
cp activity_host_live_audio.xml YourApp/app/src/main/res/layout/
```

### Day 4-5: Fix Imports & Build
- Fix package names in HostLiveAudioActivity.java
- Add drawable resources (icons)
- Build and verify no errors

---

## Week 2: Activity Migration (Days 6-12)

### Day 6: Remove Old PCM Code
```java
// DELETE these from your old HostLiveAudioActivity:
- private WebSocketClient wsClient;  // PCM transport
- private AudioRecord audioRecord;   // Raw PCM capture
- private Thread pcmCaptureThread;   // PCM encoding thread
- private byte[] pcmBuffer;          // PCM buffer
- All PCM-related methods
```

### Day 7: Add AudioEngine
```java
// ADD these:
private AudioEngine audioEngine;       // BelieVoo SDK
private String roomId;                 // Room management
private List<String> activeSpeakers;   // Multi-speaker support
```

### Day 8: Implement startLiveRoom()
Replace your old `startLive()` method with:
```java
private void startLiveRoom() {
    // 1. AudioEngine start room
    audioEngine.startAudioRoom(roomId, AudioProfile.VOICE_HIGH_QUALITY);
    
    // 2. Set transport callback (sends Opus packets)
    audioEngine.setAudioTransportCallback(packet -> {
        wsClient.send(packet);  // Opus, not PCM!
    });
    
    // 3. Publish microphone (AEC/NS/AGC enabled)
    audioEngine.publishMicrophone();
    
    // 4. Start backend music mixer
    startBackendMusicMixer();
}
```

### Day 9: Implement Backend Music Mixer
```java
private void startBackendMusicMixer() {
    // Call your backend API:
    // POST /api/audio-mixer/init
    // {
    //   "stream_id": roomId,
    //   "music_url": "https://cdn.com/music.mp3",
    //   "voice_volume": 1.0,
    //   "music_volume": 0.3
    // }
}
```

### Day 10: Add Multi-speaker Support
```java
private void subscribeToSpeaker(String speakerId) {
    audioEngine.subscribeRemoteAudio(speakerId, 
        (pcmData, sampleRate, channels, timestamp) -> {
            // Play decoded audio
            audioTrack.write(pcmData, 0, pcmData.length);
        });
}
```

### Day 11-12: Testing & Debugging
- Test microphone publish
- Verify Opus encoding (check bandwidth ~32kbps)
- Test backend music mixing
- Test multi-speaker join/leave

---

## Week 3: Polish & Optimization (Days 13-15+)

### Day 13: Add Bluetooth Support
```java
private void toggleBluetooth(boolean enable) {
    audioEngine.setBluetoothRouting(enable);
}
```

### Day 14: Add Bandwidth Monitoring
```java
// Already in HostLiveAudioActivity.java
// Shows: "32 kbps (S:16 R:16)"
```

### Day 15: Final Testing
- [ ] 8 concurrent speakers
- [ ] Bluetooth headset routing
- [ ] Background/foreground switching
- [ ] Packet loss handling (PLC)
- [ ] Echo cancellation works
- [ ] Music mixing sync

---

## Backend Changes Required

Your backend needs these endpoints:

```
POST /api/audio-mixer/init
  - Starts FFmpeg mixer
  - Input 1: Voice WebSocket stream
  - Input 2: Music file URL
  - Output: Mixed audio stream

POST /api/audio-mixer/{room_id}/volume
  - Updates voice/music volume

POST /api/audio-mixer/{room_id}/stop
  - Stops mixer
```

**Already implemented in your Laravel backend:** ✅

---

## Key Code Changes

### Old (PCM WebSocket)
```java
// CAPTURE
byte[] pcmBuffer = new byte[1920];  // 20ms @ 48kHz stereo
audioRecord.read(pcmBuffer, 0, pcmBuffer.length);

// SEND
wsClient.send(pcmBuffer);  // 1.5 Mbps!

// PROBLEMS:
// - Huge bandwidth
// - No packet loss handling
// - Echo issues
// - Audio gaps
```

### New (BelieVoo SDK)
```java
// CAPTURE
audioEngine.publishMicrophone();  // AEC/NS/AGC enabled

// SEND
audioEngine.setAudioTransportCallback(opusPacket -> {
    wsClient.send(opusPacket);  // 32kbps Opus!
});

// BENEFITS:
// - 95% less bandwidth
// - PLC for packet loss
// - Hardware echo cancellation
// - Adaptive jitter buffer
```

---

## Migration Checklist

- [ ] **Week 1**
  - [ ] Download believoo-live-sdk-2.0.0.aar
  - [ ] Update build.gradle dependencies
  - [ ] Copy HostLiveAudioActivity.java
  - [ ] Copy activity_host_live_audio.xml
  - [ ] Fix package names
  - [ ] Build successfully

- [ ] **Week 2**
  - [ ] Remove old PCM WebSocket code
  - [ ] Add AudioEngine initialization
  - [ ] Implement startLiveRoom()
  - [ ] Implement stopLiveRoom()
  - [ ] Add backend music mixer calls
  - [ ] Add multi-speaker subscription
  - [ ] Test audio quality

- [ ] **Week 3**
  - [ ] Add Bluetooth routing
  - [ ] Add bandwidth monitoring
  - [ ] Test with 8 speakers
  - [ ] Test packet loss scenarios
  - [ ] Performance profiling
  - [ ] Production release

---

## Support

**Questions?**
- Check `MIGRATION_GUIDE.md` for detailed explanations
- Review `HostLiveAudioActivity.java` for complete implementation
- Test backend mixer: `GET /api/audio-mixer/health`

**Debug Logs**
```
AudioEngine:  AudioEngine initialized with Opus codec
AudioEngine:  Audio room started: room_123 with profile: VOICE_HIGH_QUALITY
AudioEngine:  Microphone publishing started
AudioEngine:  External audio source: true @ 48000Hz, 1ch
AudioEngine:  Pushed external audio frame: 128 bytes @ 1234567890
```

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Bandwidth per speaker | < 50 kbps |
| Latency | < 200ms |
| Concurrent speakers | 8 |
| Echo cancellation | > 40dB |
| Packet loss handling | Seamless |
| Music sync | < 50ms drift |

**Ready to start? Begin with Week 1, Day 1! 🚀**

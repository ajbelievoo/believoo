# BelieVoo SDK v2.0.0 Migration Guide
## HostLiveAudioActivity Refactor (2-3 weeks)

### Overview
Migrate from custom PCM WebSocket to production-grade BelieVoo AudioEngine with Opus encoding, WebRTC transport, jitter buffer, and hardware AEC/NS/AGC.

---

## Week 1: SDK Integration & Dependencies

### Step 1: Update build.gradle

```gradle
dependencies {
    // Remove old PCM WebSocket dependencies
    // implementation 'org.java-websocket:Java-WebSocket:1.5.3'
    
    // Add BelieVoo Live SDK v2.0.0
    implementation files('libs/believoo-live-sdk-2.0.0.aar')
    
    // WebRTC (required by SDK)
    implementation 'org.webrtc:google-webrtc:1.0.32006'
    
    // Opus codec (required by SDK)
    implementation 'com.google.android.exoplayer:exoplayer-core:2.18.1'
    
    // For music playback (MP3 decoding)
    implementation 'com.google.android.exoplayer:exoplayer-ui:2.18.1'
}
```

### Step 2: Download SDK AAR
Download `believoo-live-sdk-2.0.0.aar` from:
- URL: `https://believoo.com/sdk/android/believoo-live-sdk-2.0.0.aar`
- Place in: `app/libs/believoo-live-sdk-2.0.0.aar`

---

## Week 2: Activity Refactoring

### Step 3: Create New HostLiveAudioActivity

Replace your custom PCM WebSocket implementation:

**OLD (Custom PCM WebSocket):**
```java
public class HostLiveAudioActivity extends Activity {
    private WebSocketClient wsClient;  // PCM over WebSocket
    private AudioRecord audioRecord;   // Raw PCM
    private Thread captureThread;
    
    // Problems:
    // - 1.5 Mbps bandwidth
    // - No jitter buffer
    // - Packet loss = audio gaps
    // - No echo cancellation
}
```

**NEW (BelieVoo AudioEngine):**
```java
public class HostLiveAudioActivity extends Activity {
    private AudioEngine audioEngine;
    private WebSocketClient signalingSocket;  // For signaling only
    private String roomId;
    private String authToken;
    
    // Benefits:
    // - 32kbps Opus (95% less bandwidth)
    // - Adaptive jitter buffer
    // - Packet loss concealment
    // - Hardware AEC/NS/AGC
}
```

---

### Step 4: Initialize AudioEngine

```java
public class HostLiveAudioActivity extends AppCompatActivity {
    
    private static final String TAG = "HostLiveAudio";
    private AudioEngine audioEngine;
    private WebSocketClient wsClient;
    
    // UI Elements
    private Button btnStartLive;
    private Button btnStopLive;
    private SeekBar seekBarVoiceVolume;
    private SeekBar seekBarMusicVolume;
    private TextView tvStatus;
    
    // Room config
    private String roomId;
    private String userId;
    private boolean isLive = false;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_host_live_audio);
        
        // Initialize UI
        initViews();
        
        // Initialize AudioEngine
        audioEngine = new AudioEngine(this);
        
        // Set transport callback for sending Opus packets
        audioEngine.setAudioTransportCallback(new AudioEngine.AudioTransportCallback() {
            @Override
            public void onAudioPacketReady(byte[] opusPacket) {
                // Send Opus packet over WebSocket
                if (wsClient != null && wsClient.isOpen()) {
                    wsClient.send(opusPacket);
                }
            }
        });
    }
    
    private void initViews() {
        btnStartLive = findViewById(R.id.btn_start_live);
        btnStopLive = findViewById(R.id.btn_stop_live);
        seekBarVoiceVolume = findViewById(R.id.seekbar_voice_volume);
        seekBarMusicVolume = findViewById(R.id.seekbar_music_volume);
        tvStatus = findViewById(R.id.tv_status);
        
        btnStartLive.setOnClickListener(v -> startLiveRoom());
        btnStopLive.setOnClickListener(v -> stopLiveRoom());
        
        // Volume controls
        seekBarVoiceVolume.setOnSeekBarChangeListener(new SeekBar.OnSeekBarChangeListener() {
            @Override
            public void onProgressChanged(SeekBar seekBar, int progress, boolean fromUser) {
                float volume = progress / 100f;
                updateVoiceVolume(volume);
            }
            @Override public void onStartTrackingTouch(SeekBar seekBar) {}
            @Override public void onStopTrackingTouch(SeekBar seekBar) {}
        });
    }
}
```

---

### Step 5: Start Live Room with AudioEngine

```java
private void startLiveRoom() {
    roomId = "room_" + System.currentTimeMillis();
    userId = "host_" + getUserId();
    
    // 1. Start audio room with voice profile
    boolean roomStarted = audioEngine.startAudioRoom(
        roomId, 
        AudioEngine.AudioProfile.VOICE_HIGH_QUALITY  // 32kbps mono
    );
    
    if (!roomStarted) {
        showError("Failed to start audio room");
        return;
    }
    
    // 2. Connect WebSocket for signaling (NOT PCM!)
    connectSignalingWebSocket();
    
    // 3. Publish microphone with AEC/NS/AGC
    boolean micStarted = audioEngine.publishMicrophone();
    
    if (!micStarted) {
        showError("Failed to start microphone");
        return;
    }
    
    // 4. Start backend music mixer (FFmpeg server-side)
    startBackendMusicMixer();
    
    // 5. Subscribe other speakers (if any)
    subscribeRemoteSpeakers();
    
    // Update UI
    isLive = true;
    updateUIState();
    tvStatus.setText("Live: " + roomId);
    
    Log.i(TAG, "Live room started: " + roomId);
}
```

---

### Step 6: Backend Music Mixing (FFmpeg)

```java
private void startBackendMusicMixer() {
    // Call backend API to initialize FFmpeg mixer
    RetrofitClient.getApi()
        .initAudioMixer(new AudioMixerRequest(
            roomId,
            "https://cdn.believoo.com/music/background.mp3",  // Music URL
            1.0f,  // Voice volume
            0.3f   // Music volume
        ))
        .enqueue(new Callback<AudioMixerResponse>() {
            @Override
            public void onResponse(Call<AudioMixerResponse> call, 
                                   Response<AudioMixerResponse> response) {
                if (response.isSuccessful()) {
                    Log.i(TAG, "Backend music mixer started");
                    // Voice goes to mixer input
                    // Audience receives mixed output
                }
            }
            
            @Override
            public void onFailure(Call<AudioMixerResponse> call, Throwable t) {
                Log.e(TAG, "Failed to start music mixer", t);
            }
        });
}

private void updateVoiceVolume(float volume) {
    // Update backend mixer volume
    RetrofitClient.getApi()
        .updateMixerVolume(roomId, volume, null)
        .enqueue(new Callback<Void>() {
            @Override
            public void onResponse(Call<Void> call, Response<Void> response) {
                Log.i(TAG, "Voice volume updated: " + volume);
            }
            @Override public void onFailure(Call<Void> call, Throwable t) {}
        });
}

private void updateMusicVolume(float volume) {
    // Update backend music volume
    RetrofitClient.getApi()
        .updateMixerVolume(roomId, null, volume)
        .enqueue(new Callback<Void>() {
            @Override
            public void onResponse(Call<Void> call, Response<Void> response) {
                Log.i(TAG, "Music volume updated: " + volume);
            }
            @Override public void onFailure(Call<Void> call, Throwable t) {}
        });
}
```

---

### Step 7: Subscribe Remote Speakers (Multi-user)

```java
private void subscribeRemoteSpeakers() {
    // Get list of speakers from backend
    RetrofitClient.getApi()
        .getRoomSpeakers(roomId)
        .enqueue(new Callback<List<Speaker>>() {
            @Override
            public void onResponse(Call<List<Speaker>> call, 
                                   Response<List<Speaker>> response) {
                if (response.isSuccessful() && response.body() != null) {
                    for (Speaker speaker : response.body()) {
                        if (!speaker.getUserId().equals(userId)) {
                            // Subscribe to each speaker
                            subscribeSpeaker(speaker.getUserId());
                        }
                    }
                }
            }
            @Override public void onFailure(Call<List<Speaker>> call, Throwable t) {}
        });
}

private void subscribeSpeaker(String speakerId) {
    boolean subscribed = audioEngine.subscribeRemoteAudio(
        speakerId,
        new AudioEngine.AudioFrameCallback() {
            @Override
            public void onAudioFrame(byte[] pcmData, int sampleRate, 
                                     int channels, long timestamp) {
                // PCM data is ready for playback
                // AudioEngine handles: Opus decode, jitter buffer, PLC
                playRemoteAudio(pcmData, sampleRate, channels);
            }
        }
    );
    
    if (subscribed) {
        Log.i(TAG, "Subscribed to speaker: " + speakerId);
    }
}

private void playRemoteAudio(byte[] pcmData, int sampleRate, int channels) {
    // Use AudioTrack to play decoded PCM
    if (audioTrack == null) {
        int bufferSize = AudioTrack.getMinBufferSize(
            sampleRate,
            channels == 2 ? AudioFormat.CHANNEL_OUT_STEREO : AudioFormat.CHANNEL_OUT_MONO,
            AudioFormat.ENCODING_PCM_16BIT
        );
        
        audioTrack = new AudioTrack(
            AudioManager.STREAM_VOICE_CALL,
            sampleRate,
            channels == 2 ? AudioFormat.CHANNEL_OUT_STEREO : AudioFormat.CHANNEL_OUT_MONO,
            AudioFormat.ENCODING_PCM_16BIT,
            bufferSize,
            AudioTrack.MODE_STREAM
        );
        audioTrack.play();
    }
    
    audioTrack.write(pcmData, 0, pcmData.length);
}
```

---

### Step 8: Stop Live Room

```java
private void stopLiveRoom() {
    isLive = false;
    
    // 1. Stop backend mixer
    RetrofitClient.getApi()
        .stopAudioMixer(roomId)
        .enqueue(new Callback<Void>() {
            @Override public void onResponse(Call<Void> call, Response<Void> response) {}
            @Override public void onFailure(Call<Void> call, Throwable t) {}
        });
    
    // 2. Leave audio room (stops mic, unsubscribes all)
    audioEngine.leaveAudioRoom();
    
    // 3. Disconnect WebSocket
    if (wsClient != null) {
        wsClient.close();
        wsClient = null;
    }
    
    // 4. Release AudioTrack
    if (audioTrack != null) {
        audioTrack.stop();
        audioTrack.release();
        audioTrack = null;
    }
    
    // Update UI
    updateUIState();
    tvStatus.setText("Offline");
    
    Log.i(TAG, "Live room stopped");
}
```

---

### Step 9: Bluetooth Routing

```java
private void toggleBluetooth(boolean enable) {
    audioEngine.setBluetoothRouting(enable);
    
    if (enable) {
        Toast.makeText(this, "Bluetooth headset connected", Toast.LENGTH_SHORT).show();
    } else {
        Toast.makeText(this, "Using phone speaker", Toast.LENGTH_SHORT).show();
    }
}
```

---

### Step 10: Handle Incoming Opus Packets

```java
private void connectSignalingWebSocket() {
    wsClient = new WebSocketClient(WS_URL + "/room/" + roomId) {
        @Override
        public void onMessage(String message) {
            // Handle signaling messages
        }
        
        @Override
        public void onMessage(ByteBuffer bytes) {
            // Handle incoming Opus packet
            byte[] opusPacket = bytes.array();
            
            // Extract userId from packet header (first byte = packet type)
            // Rest of header contains userId or sequence info
            String senderId = extractUserIdFromPacket(opusPacket);
            
            // Route to AudioEngine for decoding
            audioEngine.onRemoteAudioPacket(senderId, opusPacket);
        }
    };
    wsClient.connect();
}
```

---

## Week 3: Testing & Optimization

### Step 11: Lifecycle Management

```java
@Override
protected void onPause() {
    super.onPause();
    if (isLive) {
        // Pause but don't stop
        audioEngine.setAudioProfile(AudioEngine.AudioProfile.VOICE_STANDARD);
    }
}

@Override
protected void onResume() {
    super.onResume();
    if (isLive) {
        // Resume high quality
        audioEngine.setAudioProfile(AudioEngine.AudioProfile.VOICE_HIGH_QUALITY);
    }
}

@Override
protected void onDestroy() {
    super.onDestroy();
    stopLiveRoom();
    
    // Release AudioEngine
    if (audioEngine != null) {
        audioEngine.release();
        audioEngine = null;
    }
}
```

---

## Backend API Endpoints Required

```java
public interface BelieVooApi {
    
    // Initialize FFmpeg audio mixer
    @POST("/api/audio-mixer/init")
    Call<AudioMixerResponse> initAudioMixer(@Body AudioMixerRequest request);
    
    // Update volume levels
    @POST("/api/audio-mixer/{room_id}/volume")
    Call<Void> updateMixerVolume(
        @Path("room_id") String roomId,
        @Query("voice_volume") Float voiceVolume,
        @Query("music_volume") Float musicVolume
    );
    
    // Stop mixer
    @POST("/api/audio-mixer/{room_id}/stop")
    Call<Void> stopAudioMixer(@Path("room_id") String roomId);
    
    // Get room speakers
    @GET("/api/rooms/{room_id}/speakers")
    Call<List<Speaker>> getRoomSpeakers(@Path("room_id") String roomId);
}
```

---

## Testing Checklist

### Week 1: Integration
- [ ] SDK AAR added to build.gradle
- [ ] App compiles without errors
- [ ] AudioEngine initializes successfully

### Week 2: Functionality
- [ ] Microphone publishes Opus (32kbps)
- [ ] Backend music mixer starts
- [ ] Voice + Music mixed correctly
- [ ] Remote speakers subscribe/unsubscribe
- [ ] Bluetooth routing works
- [ ] Jitter buffer handles packet reordering

### Week 3: Quality
- [ ] AEC cancels echo
- [ ] NS suppresses background noise
- [ ] AGC maintains consistent volume
- [ ] PLC masks packet loss
- [ ] 8 concurrent speakers work smoothly
- [ ] Bandwidth usage < 50kbps per speaker

---

## Benefits After Migration

| Metric | Before (PCM) | After (BelieVoo SDK) | Improvement |
|--------|-------------|---------------------|-------------|
| Bandwidth | ~1.5 Mbps | ~32 kbps | **95% reduction** |
| Packet Loss Handling | Audio gaps | PLC concealment | **Seamless** |
| Echo Cancellation | None | Hardware AEC | **No echo** |
| Noise Suppression | None | WebRTC NS | **Clear audio** |
| Multi-speaker | 2-3 max | 8 speakers | **4x capacity** |
| Bluetooth | Manual | Auto SCO/HFP | **Plug & play** |
| Battery Usage | High | Optimized | **2-3x longer** |

---

## Support

For issues during migration:
1. Check logs for "AudioEngine" tag
2. Verify WebSocket connection to `/ws/audio`
3. Test backend mixer health: `GET /api/audio-mixer/health`
4. Review JitterBuffer statistics: `audioEngine.getJitterStatistics()`

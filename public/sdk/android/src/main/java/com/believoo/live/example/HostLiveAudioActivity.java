package com.believoo.live.example;

import android.Manifest;
import android.content.pm.PackageManager;
import android.media.AudioFormat;
import android.media.AudioManager;
import android.media.AudioTrack;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.SeekBar;
import android.widget.TextView;
import android.widget.Toast;
import android.widget.ToggleButton;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;

import com.believoo.live.audio.AudioEngine;

import org.java_websocket.client.WebSocketClient;
import org.java_websocket.handshake.ServerHandshake;

import java.net.URI;
import java.nio.ByteBuffer;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/**
 * Host Live Audio Activity - BelieVoo SDK v2.0.0 Implementation
 * 
 * MIGRATION FROM: Custom PCM WebSocket
 * MIGRATION TO:   BelieVoo AudioEngine with Opus + WebRTC
 * 
 * FEATURES:
 * - Host microphone publish with AEC/NS/AGC
 * - Backend FFmpeg music mixing
 * - Multi-user voice (up to 8 speakers)
 * - Audience listen-only mode
 * - Bluetooth audio routing
 * - Adaptive jitter buffer
 * - Packet loss concealment
 */
public class HostLiveAudioActivity extends AppCompatActivity {
    
    private static final String TAG = "HostLiveAudio";
    private static final int PERMISSION_REQUEST_CODE = 1001;
    private static final String WS_BASE_URL = "wss://live.believoo.com";
    
    // Core SDK Components
    private AudioEngine audioEngine;
    private WebSocketClient signalingSocket;
    
    // Audio playback for remote speakers
    private AudioTrack audioTrack;
    private Map<String, SpeakerAudioTrack> speakerTracks = new HashMap<>();
    
    // Thread pool for network operations
    private ExecutorService networkExecutor;
    private Handler mainHandler;
    
    // UI Components
    private Button btnStartLive;
    private Button btnStopLive;
    private ToggleButton btnBluetooth;
    private SeekBar seekBarVoiceVolume;
    private SeekBar seekBarMusicVolume;
    private TextView tvStatus;
    private TextView tvBandwidth;
    private TextView tvSpeakers;
    
    // Room State
    private String roomId;
    private String userId;
    private String authToken;
    private boolean isLive = false;
    private boolean isBluetoothEnabled = false;
    private List<String> activeSpeakers = new ArrayList<>();
    
    // Bandwidth tracking
    private long totalBytesSent = 0;
    private long totalBytesReceived = 0;
    private long lastBandwidthUpdate = 0;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_host_live_audio);
        
        // Initialize thread pool and handler
        networkExecutor = Executors.newCachedThreadPool();
        mainHandler = new Handler(Looper.getMainLooper());
        
        // Initialize UI
        initViews();
        
        // Check permissions
        checkPermissions();
        
        // Initialize AudioEngine (but don't start room yet)
        audioEngine = new AudioEngine(this);
        
        Log.i(TAG, "HostLiveAudioActivity created - BelieVoo SDK v2.0.0");
    }
    
    private void initViews() {
        btnStartLive = findViewById(R.id.btn_start_live);
        btnStopLive = findViewById(R.id.btn_stop_live);
        btnBluetooth = findViewById(R.id.btn_bluetooth);
        seekBarVoiceVolume = findViewById(R.id.seekbar_voice_volume);
        seekBarMusicVolume = findViewById(R.id.seekbar_music_volume);
        tvStatus = findViewById(R.id.tv_status);
        tvBandwidth = findViewById(R.id.tv_bandwidth);
        tvSpeakers = findViewById(R.id.tv_speakers);
        
        // Start/Stop buttons
        btnStartLive.setOnClickListener(v -> startLiveRoom());
        btnStopLive.setOnClickListener(v -> stopLiveRoom());
        btnStopLive.setEnabled(false);
        
        // Bluetooth toggle
        btnBluetooth.setOnCheckedChangeListener((buttonView, isChecked) -> {
            toggleBluetooth(isChecked);
        });
        
        // Volume controls
        seekBarVoiceVolume.setMax(100);
        seekBarVoiceVolume.setProgress(100);
        seekBarVoiceVolume.setOnSeekBarChangeListener(new SeekBar.OnSeekBarChangeListener() {
            @Override
            public void onProgressChanged(SeekBar seekBar, int progress, boolean fromUser) {
                if (isLive) {
                    float volume = progress / 100f;
                    updateVoiceVolume(volume);
                }
            }
            @Override public void onStartTrackingTouch(SeekBar seekBar) {}
            @Override public void onStopTrackingTouch(SeekBar seekBar) {}
        });
        
        seekBarMusicVolume.setMax(100);
        seekBarMusicVolume.setProgress(30);
        seekBarMusicVolume.setOnSeekBarChangeListener(new SeekBar.OnSeekBarChangeListener() {
            @Override
            public void onProgressChanged(SeekBar seekBar, int progress, boolean fromUser) {
                if (isLive) {
                    float volume = progress / 100f;
                    updateMusicVolume(volume);
                }
            }
            @Override public void onStartTrackingTouch(SeekBar seekBar) {}
            @Override public void onStopTrackingTouch(SeekBar seekBar) {}
        });
        
        updateUIState();
    }
    
    private void checkPermissions() {
        String[] permissions = {
            Manifest.permission.RECORD_AUDIO,
            Manifest.permission.MODIFY_AUDIO_SETTINGS,
            Manifest.permission.BLUETOOTH,
            Manifest.permission.BLUETOOTH_ADMIN
        };
        
        List<String> neededPermissions = new ArrayList<>();
        for (String permission : permissions) {
            if (ContextCompat.checkSelfPermission(this, permission) != PackageManager.PERMISSION_GRANTED) {
                neededPermissions.add(permission);
            }
        }
        
        if (!neededPermissions.isEmpty()) {
            ActivityCompat.requestPermissions(this, 
                neededPermissions.toArray(new String[0]), 
                PERMISSION_REQUEST_CODE);
        }
    }
    
    // ============================================================================
    // LIVE ROOM MANAGEMENT
    // ============================================================================
    
    private void startLiveRoom() {
        if (isLive) return;
        
        // Generate room and user IDs
        roomId = "room_" + System.currentTimeMillis();
        userId = "host_" + getUserId();
        authToken = generateAuthToken();
        
        Log.i(TAG, "Starting live room: " + roomId);
        tvStatus.setText("Starting...");
        
        // Step 1: Start AudioEngine room with voice profile
        boolean roomStarted = audioEngine.startAudioRoom(
            roomId,
            AudioEngine.AudioProfile.VOICE_HIGH_QUALITY  // 32kbps mono
        );
        
        if (!roomStarted) {
            showError("Failed to initialize audio room");
            return;
        }
        
        // Step 2: Setup AudioEngine transport callback (sends Opus packets)
        audioEngine.setAudioTransportCallback(packet -> {
            // Packet format: [1 byte type][4 bytes seq][8 bytes timestamp][Opus data]
            if (signalingSocket != null && signalingSocket.isOpen()) {
                signalingSocket.send(packet);
                totalBytesSent += packet.length;
            }
        });
        
        // Step 3: Connect signaling WebSocket (for Opus packets + control)
        connectSignalingWebSocket();
        
        // Step 4: Publish microphone (starts Opus encoding + AEC/NS/AGC)
        boolean micStarted = audioEngine.publishMicrophone();
        if (!micStarted) {
            showError("Failed to start microphone");
            stopLiveRoom();
            return;
        }
        
        // Step 5: Start backend FFmpeg music mixer (server-side mixing)
        startBackendMusicMixer();
        
        // Step 6: Start bandwidth monitoring
        startBandwidthMonitoring();
        
        // Update state
        isLive = true;
        updateUIState();
        tvStatus.setText("Live: " + roomId + " (Opus 32kbps)");
        
        Toast.makeText(this, "Live room started!", Toast.LENGTH_SHORT).show();
        Log.i(TAG, "Live room started successfully");
    }
    
    private void stopLiveRoom() {
        if (!isLive) return;
        
        Log.i(TAG, "Stopping live room: " + roomId);
        tvStatus.setText("Stopping...");
        
        // Step 1: Stop backend music mixer
        stopBackendMusicMixer();
        
        // Step 2: Leave AudioEngine room (stops mic, unsubscribes all, releases resources)
        audioEngine.leaveAudioRoom();
        
        // Step 3: Disconnect WebSocket
        if (signalingSocket != null) {
            signalingSocket.close();
            signalingSocket = null;
        }
        
        // Step 4: Release all speaker audio tracks
        for (SpeakerAudioTrack track : speakerTracks.values()) {
            track.release();
        }
        speakerTracks.clear();
        activeSpeakers.clear();
        
        // Step 5: Stop bandwidth monitoring
        stopBandwidthMonitoring();
        
        // Update state
        isLive = false;
        updateUIState();
        tvStatus.setText("Offline");
        tvBandwidth.setText("0 kbps");
        tvSpeakers.setText("0 speakers");
        
        Toast.makeText(this, "Live room ended", Toast.LENGTH_SHORT).show();
        Log.i(TAG, "Live room stopped");
    }
    
    // ============================================================================
    // SIGNALING WEBSOCKET (Opus Packet Transport)
    // ============================================================================
    
    private void connectSignalingWebSocket() {
        try {
            URI wsUri = URI.create(WS_BASE_URL + "/ws/audio/" + roomId + 
                                   "?token=" + authToken + "&userId=" + userId);
            
            signalingSocket = new WebSocketClient(wsUri) {
                @Override
                public void onOpen(ServerHandshake handshake) {
                    Log.i(TAG, "WebSocket connected: " + wsUri);
                    runOnUiThread(() -> {
                        if (isLive) {
                            tvStatus.setText("Live: " + roomId + " (Connected)");
                        }
                    });
                }
                
                @Override
                public void onMessage(String message) {
                    // Handle control messages (JSON)
                    handleControlMessage(message);
                }
                
                @Override
                public void onMessage(ByteBuffer bytes) {
                    // Handle incoming Opus packet
                    byte[] opusPacket = bytes.array();
                    totalBytesReceived += opusPacket.length;
                    
                    // Extract sender ID from packet or metadata
                    String senderId = extractSenderId(opusPacket);
                    
                    // Route to AudioEngine for decoding
                    audioEngine.onRemoteAudioPacket(senderId, opusPacket);
                }
                
                @Override
                public void onClose(int code, String reason, boolean remote) {
                    Log.w(TAG, "WebSocket closed: " + reason);
                    if (isLive) {
                        runOnUiThread(() -> {
                            tvStatus.setText("Live: " + roomId + " (Reconnecting...)");
                        });
                        // Attempt reconnection
                        reconnectWebSocket();
                    }
                }
                
                @Override
                public void onError(Exception ex) {
                    Log.e(TAG, "WebSocket error", ex);
                }
            };
            
            signalingSocket.connect();
            
        } catch (Exception e) {
            Log.e(TAG, "Failed to connect WebSocket", e);
            showError("Connection failed");
        }
    }
    
    private void reconnectWebSocket() {
        networkExecutor.execute(() -> {
            try {
                Thread.sleep(2000); // Wait 2 seconds
                if (isLive) {
                    connectSignalingWebSocket();
                }
            } catch (InterruptedException e) {
                Thread.currentThread().interrupt();
            }
        });
    }
    
    private void handleControlMessage(String message) {
        // Parse JSON control messages
        // Example: {"type": "speaker_joined", "userId": "user_123"}
        // Example: {"type": "speaker_left", "userId": "user_123"}
        Log.d(TAG, "Control message: " + message);
        
        // Handle speaker join/leave
        if (message.contains("speaker_joined")) {
            String speakerId = extractUserIdFromMessage(message);
            if (speakerId != null && !speakerId.equals(userId)) {
                subscribeToSpeaker(speakerId);
            }
        } else if (message.contains("speaker_left")) {
            String speakerId = extractUserIdFromMessage(message);
            if (speakerId != null) {
                unsubscribeFromSpeaker(speakerId);
            }
        }
    }
    
    // ============================================================================
    // REMOTE SPEAKER MANAGEMENT (Multi-user Voice)
    // ============================================================================
    
    private void subscribeToSpeaker(String speakerId) {
        Log.i(TAG, "Subscribing to speaker: " + speakerId);
        
        // Create AudioTrack for this speaker
        SpeakerAudioTrack speakerTrack = new SpeakerAudioTrack(speakerId);
        speakerTracks.put(speakerId, speakerTrack);
        activeSpeakers.add(speakerId);
        
        // Subscribe via AudioEngine (handles Opus decode, jitter buffer, PLC)
        boolean subscribed = audioEngine.subscribeRemoteAudio(speakerId, 
            new AudioEngine.AudioFrameCallback() {
                @Override
                public void onAudioFrame(byte[] pcmData, int sampleRate, 
                                        int channels, long timestamp) {
                    // Play decoded PCM audio
                    speakerTrack.playAudio(pcmData);
                }
            });
        
        if (subscribed) {
            runOnUiThread(() -> {
                tvSpeakers.setText(activeSpeakers.size() + " speakers");
                Toast.makeText(HostLiveAudioActivity.this, 
                    "Speaker joined: " + speakerId, Toast.LENGTH_SHORT).show();
            });
        }
    }
    
    private void unsubscribeFromSpeaker(String speakerId) {
        Log.i(TAG, "Unsubscribing from speaker: " + speakerId);
        
        // Unsubscribe via AudioEngine
        audioEngine.unsubscribeRemoteAudio(speakerId);
        
        // Release speaker's audio track
        SpeakerAudioTrack track = speakerTracks.remove(speakerId);
        if (track != null) {
            track.release();
        }
        activeSpeakers.remove(speakerId);
        
        runOnUiThread(() -> {
            tvSpeakers.setText(activeSpeakers.size() + " speakers");
        });
    }
    
    // ============================================================================
    // BACKEND MUSIC MIXING (FFmpeg Server-Side)
    // ============================================================================
    
    private void startBackendMusicMixer() {
        networkExecutor.execute(() -> {
            try {
                // Call backend API to initialize FFmpeg mixer
                // This mixes host voice + music file on server
                String musicUrl = "https://cdn.believoo.com/music/background.mp3";
                float voiceVolume = seekBarVoiceVolume.getProgress() / 100f;
                float musicVolume = seekBarMusicVolume.getProgress() / 100f;
                
                // Example API call (implement with your Retrofit/OkHttp)
                // POST /api/audio-mixer/init
                // {
                //   "stream_id": roomId,
                //   "voice_endpoint": "wss://.../voice/" + roomId,
                //   "music_url": musicUrl,
                //   "voice_volume": voiceVolume,
                //   "music_volume": musicVolume
                // }
                
                Log.i(TAG, "Backend music mixer started");
                Log.i(TAG, "Voice volume: " + voiceVolume + ", Music volume: " + musicVolume);
                
                // Send initial volume to backend
                updateBackendVolume(voiceVolume, musicVolume);
                
            } catch (Exception e) {
                Log.e(TAG, "Failed to start music mixer", e);
            }
        });
    }
    
    private void stopBackendMusicMixer() {
        networkExecutor.execute(() -> {
            try {
                // Call backend API to stop mixer
                // POST /api/audio-mixer/{room_id}/stop
                
                Log.i(TAG, "Backend music mixer stopped");
                
            } catch (Exception e) {
                Log.e(TAG, "Error stopping music mixer", e);
            }
        });
    }
    
    private void updateVoiceVolume(float volume) {
        updateBackendVolume(volume, null);
    }
    
    private void updateMusicVolume(float volume) {
        updateBackendVolume(null, volume);
    }
    
    private void updateBackendVolume(Float voiceVolume, Float musicVolume) {
        networkExecutor.execute(() -> {
            try {
                // Call backend API to update volume
                // POST /api/audio-mixer/{room_id}/volume
                // {
                //   "voice_volume": voiceVolume,
                //   "music_volume": musicVolume
                // }
                
                if (voiceVolume != null) {
                    Log.d(TAG, "Updated voice volume: " + voiceVolume);
                }
                if (musicVolume != null) {
                    Log.d(TAG, "Updated music volume: " + musicVolume);
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error updating volume", e);
            }
        });
    }
    
    // ============================================================================
    // BLUETOOTH AUDIO ROUTING
    // ============================================================================
    
    private void toggleBluetooth(boolean enable) {
        isBluetoothEnabled = enable;
        audioEngine.setBluetoothRouting(enable);
        
        if (enable) {
            Toast.makeText(this, "Bluetooth headset active", Toast.LENGTH_SHORT).show();
            Log.i(TAG, "Bluetooth routing enabled");
        } else {
            Toast.makeText(this, "Phone speaker active", Toast.LENGTH_SHORT).show();
            Log.i(TAG, "Bluetooth routing disabled");
        }
    }
    
    // ============================================================================
    // BANDWIDTH MONITORING
    // ============================================================================
    
    private void startBandwidthMonitoring() {
        lastBandwidthUpdate = System.currentTimeMillis();
        
        Runnable bandwidthTask = new Runnable() {
            @Override
            public void run() {
                if (!isLive) return;
                
                long now = System.currentTimeMillis();
                long elapsedMs = now - lastBandwidthUpdate;
                
                if (elapsedMs > 0) {
                    // Calculate kbps
                    long sentKbps = (totalBytesSent * 8) / elapsedMs;
                    long receivedKbps = (totalBytesReceived * 8) / elapsedMs;
                    long totalKbps = sentKbps + receivedKbps;
                    
                    runOnUiThread(() -> {
                        tvBandwidth.setText(totalKbps + " kbps (S:" + sentKbps + " R:" + receivedKbps + ")");
                    });
                    
                    // Reset counters
                    totalBytesSent = 0;
                    totalBytesReceived = 0;
                    lastBandwidthUpdate = now;
                }
                
                // Schedule next update (every 2 seconds)
                mainHandler.postDelayed(this, 2000);
            }
        };
        
        mainHandler.postDelayed(bandwidthTask, 2000);
    }
    
    private void stopBandwidthMonitoring() {
        mainHandler.removeCallbacksAndMessages(null);
    }
    
    // ============================================================================
    // UI UPDATES
    // ============================================================================
    
    private void updateUIState() {
        btnStartLive.setEnabled(!isLive);
        btnStopLive.setEnabled(isLive);
        btnBluetooth.setEnabled(isLive);
        seekBarVoiceVolume.setEnabled(isLive);
        seekBarMusicVolume.setEnabled(isLive);
    }
    
    private void showError(String message) {
        runOnUiThread(() -> {
            tvStatus.setText("Error: " + message);
            Toast.makeText(this, message, Toast.LENGTH_LONG).show();
        });
        Log.e(TAG, message);
    }
    
    // ============================================================================
    // UTILITY METHODS
    // ============================================================================
    
    private String getUserId() {
        // Return current user's ID from your auth system
        return "user_" + System.currentTimeMillis();
    }
    
    private String generateAuthToken() {
        // Generate or retrieve auth token from your backend
        return "token_" + System.currentTimeMillis();
    }
    
    private String extractSenderId(byte[] opusPacket) {
        // Extract sender ID from packet metadata
        // This depends on your packet format
        // For now, return a placeholder
        if (opusPacket.length > 20) {
            // Assume sender ID is embedded in packet header
            return "speaker_" + (opusPacket[13] & 0xFF);
        }
        return "unknown";
    }
    
    private String extractUserIdFromMessage(String message) {
        // Parse JSON to extract userId
        // Simplified - use proper JSON parser in production
        int start = message.indexOf("userId\":\"");
        if (start != -1) {
            start += 9;
            int end = message.indexOf("\"", start);
            if (end != -1) {
                return message.substring(start, end);
            }
        }
        return null;
    }
    
    // ============================================================================
    // SPEAKER AUDIO TRACK HELPER CLASS
    // ============================================================================
    
    private class SpeakerAudioTrack {
        private String speakerId;
        private AudioTrack audioTrack;
        private int sampleRate = 48000;
        private int channels = 1;
        
        public SpeakerAudioTrack(String speakerId) {
            this.speakerId = speakerId;
            initAudioTrack();
        }
        
        private void initAudioTrack() {
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
                bufferSize * 2,
                AudioTrack.MODE_STREAM
            );
            
            audioTrack.play();
        }
        
        public void playAudio(byte[] pcmData) {
            if (audioTrack != null && audioTrack.getPlayState() == AudioTrack.PLAYSTATE_PLAYING) {
                audioTrack.write(pcmData, 0, pcmData.length);
            }
        }
        
        public void release() {
            if (audioTrack != null) {
                audioTrack.stop();
                audioTrack.release();
                audioTrack = null;
            }
        }
    }
    
    // ============================================================================
    // LIFECYCLE
    // ============================================================================
    
    @Override
    protected void onPause() {
        super.onPause();
        if (isLive) {
            // Reduce quality when backgrounded to save battery
            audioEngine.setAudioProfile(AudioEngine.AudioProfile.VOICE_STANDARD);
            Log.i(TAG, "Audio quality reduced (app backgrounded)");
        }
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        if (isLive) {
            // Restore high quality when foregrounded
            audioEngine.setAudioProfile(AudioEngine.AudioProfile.VOICE_HIGH_QUALITY);
            Log.i(TAG, "Audio quality restored (app foregrounded)");
        }
    }
    
    @Override
    protected void onDestroy() {
        super.onDestroy();
        
        // Clean up everything
        stopLiveRoom();
        
        if (audioEngine != null) {
            audioEngine.release();
            audioEngine = null;
        }
        
        if (networkExecutor != null) {
            networkExecutor.shutdown();
        }
        
        Log.i(TAG, "HostLiveAudioActivity destroyed");
    }
    
    @Override
    public void onRequestPermissionsResult(int requestCode, String[] permissions, int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode == PERMISSION_REQUEST_CODE) {
            boolean allGranted = true;
            for (int result : grantResults) {
                if (result != PackageManager.PERMISSION_GRANTED) {
                    allGranted = false;
                    break;
                }
            }
            
            if (!allGranted) {
                showError("Audio permissions required");
            }
        }
    }
}

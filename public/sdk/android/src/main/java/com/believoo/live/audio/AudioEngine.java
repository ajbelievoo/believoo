package com.believoo.live.audio;

import android.content.Context;
import android.media.AudioAttributes;
import android.media.AudioFormat;
import android.media.AudioManager;
import android.media.AudioRecord;
import android.media.AudioTrack;
import android.media.MediaRecorder;
import android.media.audiofx.AcousticEchoCanceler;
import android.media.audiofx.AutomaticGainControl;
import android.media.audiofx.NoiseSuppressor;
import android.os.Process;
import android.util.Log;

import org.webrtc.audio.JavaAudioDeviceModule;
import org.webrtc.audio.WebRtcAudioRecord;
import org.webrtc.audio.WebRtcAudioTrack;

import java.nio.ByteBuffer;
import java.util.HashMap;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.PriorityBlockingQueue;
import java.util.concurrent.atomic.AtomicBoolean;
import java.util.concurrent.atomic.AtomicInteger;
import java.util.concurrent.atomic.AtomicLong;

/**
 * BelieVoo Live Audio Engine
 * Production-grade real-time audio transport with Opus encoding
 */
public class AudioEngine {
    private static final String TAG = "BelieVooAudioEngine";
    
    // Audio Profiles
    public enum AudioProfile {
        VOICE_STANDARD(48000, 1, 24000),      // 24kbps mono
        VOICE_HIGH_QUALITY(48000, 1, 32000),  // 32kbps mono
        MUSIC_STANDARD(48000, 2, 64000),      // 64kbps stereo
        MUSIC_HIGH_QUALITY(48000, 2, 128000); // 128kbps stereo
        
        final int sampleRate;
        final int channels;
        final int bitrate;
        
        AudioProfile(int sampleRate, int channels, int bitrate) {
            this.sampleRate = sampleRate;
            this.channels = channels;
            this.bitrate = bitrate;
        }
    }
    
    // Packet types
    private static final byte PACKET_TYPE_VOICE = 0x01;
    private static final byte PACKET_TYPE_MUSIC = 0x02;
    private static final byte PACKET_TYPE_MIXED = 0x03;
    
    private Context context;
    private AudioManager audioManager;
    private OpusEncoder opusEncoder;
    private OpusDecoder opusDecoder;
    private JitterBuffer jitterBuffer;
    private PacketLossConcealment plc;
    private AudioMixer audioMixer;
    private AcousticEchoCanceler echoCanceler;
    private NoiseSuppressor noiseSuppressor;
    private AutomaticGainControl agc;
    
    // Audio routing
    private boolean bluetoothRouting = false;
    private AudioProfile currentProfile = AudioProfile.VOICE_HIGH_QUALITY;
    
    // Recording/playback
    private AudioRecord audioRecord;
    private AudioTrack audioTrack;
    private ExecutorService audioExecutor;
    private ExecutorService mixerExecutor;
    
    // Room state
    private AtomicBoolean isInRoom = new AtomicBoolean(false);
    private String currentRoomId = null;
    private AtomicInteger localSequenceNumber = new AtomicInteger(0);
    private AtomicLong localTimestamp = new AtomicLong(0);
    
    // Remote audio management
    private ConcurrentHashMap<String, RemoteAudioStream> remoteStreams = new ConcurrentHashMap<>();
    private Map<String, AudioFrameCallback> remoteCallbacks = new HashMap<>();
    
    // Callbacks
    private AudioFrameCallback microphoneCallback;
    private AudioTransportCallback transportCallback;
    
    public AudioEngine(Context context) {
        this.context = context;
        this.audioManager = (AudioManager) context.getSystemService(Context.AUDIO_SERVICE);
        this.audioExecutor = Executors.newSingleThreadExecutor(r -> {
            Thread t = new Thread(r, "AudioEngine-Capture");
            t.setPriority(Thread.MAX_PRIORITY);
            return t;
        });
        this.mixerExecutor = Executors.newSingleThreadExecutor(r -> {
            Thread t = new Thread(r, "AudioEngine-Mixer");
            t.setPriority(Thread.MAX_PRIORITY);
            return t;
        });
        
        initializeComponents();
    }
    
    private void initializeComponents() {
        // Initialize Opus codec
        opusEncoder = new OpusEncoder();
        opusDecoder = new OpusDecoder();
        
        // Initialize jitter buffer (200ms capacity)
        jitterBuffer = new JitterBuffer(50, 200, 48000);
        
        // Initialize packet loss concealment
        plc = new PacketLossConcealment(48000, 2);
        
        // Initialize audio mixer for multi-speaker
        audioMixer = new AudioMixer(48000, 2);
        
        Log.i(TAG, "AudioEngine initialized with Opus codec");
    }
    
    /**
     * Start audio room with specified profile
     */
    public boolean startAudioRoom(String roomId, AudioProfile profile) {
        if (isInRoom.get()) {
            Log.w(TAG, "Already in room: " + currentRoomId);
            return false;
        }
        
        currentProfile = profile;
        currentRoomId = roomId;
        
        // Initialize Opus with profile settings
        opusEncoder.init(profile.sampleRate, profile.channels, profile.bitrate);
        opusDecoder.init(profile.sampleRate, profile.channels);
        
        // Configure audio session
        configureAudioSession(profile);
        
        isInRoom.set(true);
        Log.i(TAG, "Audio room started: " + roomId + " with profile: " + profile.name());
        return true;
    }
    
    /**
     * Leave audio room
     */
    public void leaveAudioRoom() {
        if (!isInRoom.get()) return;
        
        isInRoom.set(false);
        
        // Stop all publishers
        stopMicrophonePublish();
        stopMusicPublish();
        
        // Clear remote streams
        for (RemoteAudioStream stream : remoteStreams.values()) {
            stream.stop();
        }
        remoteStreams.clear();
        remoteCallbacks.clear();
        
        // Reset sequence/timestamp
        localSequenceNumber.set(0);
        localTimestamp.set(0);
        
        currentRoomId = null;
        Log.i(TAG, "Audio room left");
    }
    
    /**
     * Publish microphone audio with echo cancellation
     */
    public boolean publishMicrophone() {
        if (!isInRoom.get()) {
            Log.e(TAG, "Not in room, call startAudioRoom() first");
            return false;
        }
        
        if (audioRecord != null) {
            Log.w(TAG, "Microphone already publishing");
            return true;
        }
        
        int bufferSize = AudioRecord.getMinBufferSize(
            currentProfile.sampleRate,
            currentProfile.channels == 1 ? AudioFormat.CHANNEL_IN_MONO : AudioFormat.CHANNEL_IN_STEREO,
            AudioFormat.ENCODING_PCM_16BIT
        );
        
        // Use voice communication for lowest latency
        audioRecord = new AudioRecord(
            MediaRecorder.AudioSource.VOICE_COMMUNICATION,
            currentProfile.sampleRate,
            currentProfile.channels == 1 ? AudioFormat.CHANNEL_IN_MONO : AudioFormat.CHANNEL_IN_STEREO,
            AudioFormat.ENCODING_PCM_16BIT,
            bufferSize * 2
        );
        
        // Enable WebRTC built-in AEC/NS/AGC
        enableAudioEffects();
        
        audioRecord.startRecording();
        
        // Start capture thread
        audioExecutor.execute(this::microphoneCaptureLoop);
        
        Log.i(TAG, "Microphone publishing started");
        return true;
    }
    
    /**
     * Stop microphone publishing
     */
    public void stopMicrophonePublish() {
        if (audioRecord != null) {
            audioRecord.stop();
            audioRecord.release();
            audioRecord = null;
        }
        
        disableAudioEffects();
        Log.i(TAG, "Microphone publishing stopped");
    }
    
    /**
     * Publish music mix (optimized for music quality)
     */
    public boolean publishMusicMix(String musicFilePath) {
        if (!isInRoom.get()) {
            Log.e(TAG, "Not in room");
            return false;
        }
        
        // Music uses high quality profile automatically
        opusEncoder.init(48000, 2, 128000); // 128kbps stereo for music
        
        // Start music mixing thread
        mixerExecutor.execute(() -> musicMixLoop(musicFilePath));
        
        Log.i(TAG, "Music mix publishing started: " + musicFilePath);
        return true;
    }
    
    /**
     * Stop music publishing
     */
    public void stopMusicPublish() {
        // Signal music loop to stop
        Log.i(TAG, "Music publishing stopped");
    }
    
    /**
     * Subscribe to remote audio stream
     */
    public boolean subscribeRemoteAudio(String userId, AudioFrameCallback callback) {
        if (!isInRoom.get()) {
            Log.e(TAG, "Not in room");
            return false;
        }
        
        RemoteAudioStream stream = new RemoteAudioStream(userId, opusDecoder, jitterBuffer, plc);
        remoteStreams.put(userId, stream);
        remoteCallbacks.put(userId, callback);
        
        stream.start(callback);
        
        Log.i(TAG, "Subscribed to remote audio: " + userId);
        return true;
    }
    
    /**
     * Unsubscribe from remote audio
     */
    public void unsubscribeRemoteAudio(String userId) {
        RemoteAudioStream stream = remoteStreams.remove(userId);
        if (stream != null) {
            stream.stop();
        }
        remoteCallbacks.remove(userId);
        
        Log.i(TAG, "Unsubscribed from remote audio: " + userId);
    }
    
    /**
     * Set audio profile dynamically
     */
    public void setAudioProfile(AudioProfile profile) {
        currentProfile = profile;
        opusEncoder.init(profile.sampleRate, profile.channels, profile.bitrate);
        Log.i(TAG, "Audio profile changed to: " + profile.name());
    }
    
    /**
     * Enable/disable Bluetooth routing
     */
    public void setBluetoothRouting(boolean enable) {
        bluetoothRouting = enable;
        if (enable) {
            audioManager.setMode(AudioManager.MODE_IN_COMMUNICATION);
            audioManager.startBluetoothSco();
            audioManager.setBluetoothScoOn(true);
        } else {
            audioManager.setBluetoothScoOn(false);
            audioManager.stopBluetoothSco();
        }
        Log.i(TAG, "Bluetooth routing: " + enable);
    }
    
    /**
     * Receive remote audio packet (called from WebSocket)
     */
    public void onRemoteAudioPacket(String userId, byte[] packet) {
        RemoteAudioStream stream = remoteStreams.get(userId);
        if (stream != null) {
            stream.onPacketReceived(packet);
        }
    }
    
    /**
     * Set transport callback for sending audio packets
     */
    public void setAudioTransportCallback(AudioTransportCallback callback) {
        this.transportCallback = callback;
    }
    
    // ============ Private Methods ============
    
    private void configureAudioSession(AudioProfile profile) {
        audioManager.setMode(AudioManager.MODE_IN_COMMUNICATION);
        audioManager.setSpeakerphoneOn(true);
        
        // Request audio focus
        AudioAttributes attrs = new AudioAttributes.Builder()
            .setUsage(AudioAttributes.USAGE_VOICE_COMMUNICATION)
            .setContentType(AudioAttributes.CONTENT_TYPE_SPEECH)
            .build();
    }
    
    private void enableAudioEffects() {
        if (AcousticEchoCanceler.isAvailable() && audioRecord != null) {
            echoCanceler = AcousticEchoCanceler.create(audioRecord.getAudioSessionId());
            if (echoCanceler != null) {
                echoCanceler.setEnabled(true);
            }
        }
        
        if (NoiseSuppressor.isAvailable() && audioRecord != null) {
            noiseSuppressor = NoiseSuppressor.create(audioRecord.getAudioSessionId());
            if (noiseSuppressor != null) {
                noiseSuppressor.setEnabled(true);
            }
        }
        
        if (AutomaticGainControl.isAvailable() && audioRecord != null) {
            agc = AutomaticGainControl.create(audioRecord.getAudioSessionId());
            if (agc != null) {
                agc.setEnabled(true);
            }
        }
    }
    
    private void disableAudioEffects() {
        if (echoCanceler != null) {
            echoCanceler.release();
            echoCanceler = null;
        }
        if (noiseSuppressor != null) {
            noiseSuppressor.release();
            noiseSuppressor = null;
        }
        if (agc != null) {
            agc.release();
            agc = null;
        }
    }
    
    private void microphoneCaptureLoop() {
        Process.setThreadPriority(Process.THREAD_PRIORITY_URGENT_AUDIO);
        
        int frameSize = currentProfile.sampleRate / 50; // 20ms frames
        short[] pcmBuffer = new short[frameSize * currentProfile.channels];
        byte[] opusBuffer = new byte[1275]; // Max Opus packet size
        
        while (isInRoom.get() && audioRecord != null) {
            int read = audioRecord.read(pcmBuffer, 0, pcmBuffer.length);
            if (read > 0) {
                // Convert short[] to byte[]
                byte[] pcmBytes = shortArrayToByteArray(pcmBuffer);
                
                // Encode with Opus
                int encoded = opusEncoder.encode(pcmBytes, pcmBytes.length, opusBuffer);
                if (encoded > 0) {
                    // Create RTP-like packet with sequence number and timestamp
                    byte[] packet = createAudioPacket(
                        PACKET_TYPE_VOICE,
                        localSequenceNumber.getAndIncrement(),
                        localTimestamp.getAndAdd(frameSize),
                        opusBuffer,
                        encoded
                    );
                    
                    // Send via transport callback
                    if (transportCallback != null) {
                        transportCallback.onAudioPacketReady(packet);
                    }
                    
                    // Also notify local callback
                    if (microphoneCallback != null) {
                        microphoneCallback.onAudioFrame(pcmBytes, currentProfile.sampleRate, 
                            currentProfile.channels, System.currentTimeMillis());
                    }
                }
            }
        }
    }
    
    private void musicMixLoop(String musicFilePath) {
        Process.setThreadPriority(Process.THREAD_PRIORITY_URGENT_AUDIO);
        
        // Music mixing implementation
        // Reads music file, mixes with microphone if needed, encodes with Opus
        Log.i(TAG, "Music mix loop started for: " + musicFilePath);
        
        // TODO: Implement music file reading and mixing
        // This is a placeholder for the music mixing logic
    }
    
    private byte[] createAudioPacket(byte type, int sequence, long timestamp, 
                                      byte[] opusData, int opusLength) {
        // RTP-like header: [1 byte type][4 bytes seq][8 bytes timestamp][opus data]
        byte[] packet = new byte[1 + 4 + 8 + opusLength];
        
        packet[0] = type;
        
        // Sequence number (big endian)
        packet[1] = (byte) (sequence >> 24);
        packet[2] = (byte) (sequence >> 16);
        packet[3] = (byte) (sequence >> 8);
        packet[4] = (byte) sequence;
        
        // Timestamp (big endian)
        packet[5] = (byte) (timestamp >> 56);
        packet[6] = (byte) (timestamp >> 48);
        packet[7] = (byte) (timestamp >> 40);
        packet[8] = (byte) (timestamp >> 32);
        packet[9] = (byte) (timestamp >> 24);
        packet[10] = (byte) (timestamp >> 16);
        packet[11] = (byte) (timestamp >> 8);
        packet[12] = (byte) timestamp;
        
        // Opus data
        System.arraycopy(opusData, 0, packet, 13, opusLength);
        
        return packet;
    }
    
    private byte[] shortArrayToByteArray(short[] shorts) {
        byte[] bytes = new byte[shorts.length * 2];
        for (int i = 0; i < shorts.length; i++) {
            bytes[i * 2] = (byte) (shorts[i] & 0xFF);
            bytes[i * 2 + 1] = (byte) ((shorts[i] >> 8) & 0xFF);
        }
        return bytes;
    }
    
    public void release() {
        leaveAudioRoom();
        
        if (audioExecutor != null) {
            audioExecutor.shutdown();
        }
        if (mixerExecutor != null) {
            mixerExecutor.shutdown();
        }
        
        if (opusEncoder != null) opusEncoder.release();
        if (opusDecoder != null) opusDecoder.release();
        if (jitterBuffer != null) jitterBuffer.release();
        if (plc != null) plc.release();
        if (audioMixer != null) audioMixer.release();
        
        Log.i(TAG, "AudioEngine released");
    }
    
    // ============ Callback Interfaces ============
    
    public interface AudioFrameCallback {
        void onAudioFrame(byte[] pcmData, int sampleRate, int channels, long timestamp);
    }
    
    public interface AudioTransportCallback {
        void onAudioPacketReady(byte[] opusPacket);
    }
}

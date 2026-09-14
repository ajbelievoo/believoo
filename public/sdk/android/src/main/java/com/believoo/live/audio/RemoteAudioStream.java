package com.believoo.live.audio;

import android.os.Process;
import android.util.Log;

import com.believoo.live.audio.AudioEngine.AudioFrameCallback;

import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.atomic.AtomicBoolean;

/**
 * Remote Audio Stream Handler
 * Decodes and plays audio from a remote participant
 */
public class RemoteAudioStream {
    private static final String TAG = "RemoteAudioStream";
    
    private String userId;
    private OpusDecoder opusDecoder;
    private JitterBuffer jitterBuffer;
    private PacketLossConcealment plc;
    
    private ExecutorService decodeExecutor;
    private AtomicBoolean isRunning = new AtomicBoolean(false);
    
    private AudioFrameCallback playbackCallback;
    
    // RTP header parsing
    private static final int RTP_HEADER_SIZE = 13; // 1 type + 4 seq + 8 timestamp
    
    public RemoteAudioStream(String userId, OpusDecoder decoder, JitterBuffer jitterBuffer, 
                             PacketLossConcealment plc) {
        this.userId = userId;
        this.opusDecoder = decoder;
        this.jitterBuffer = jitterBuffer;
        this.plc = plc;
        
        this.decodeExecutor = Executors.newSingleThreadExecutor(r -> {
            Thread t = new Thread(r, "RemoteAudio-" + userId);
            t.setPriority(Thread.MAX_PRIORITY);
            return t;
        });
    }
    
    /**
     * Start processing remote audio
     */
    public void start(AudioFrameCallback callback) {
        this.playbackCallback = callback;
        isRunning.set(true);
        
        decodeExecutor.execute(this::decodeLoop);
        Log.i(TAG, "Started remote audio stream for: " + userId);
    }
    
    /**
     * Stop processing
     */
    public void stop() {
        isRunning.set(false);
        if (decodeExecutor != null) {
            decodeExecutor.shutdown();
        }
        Log.i(TAG, "Stopped remote audio stream for: " + userId);
    }
    
    /**
     * Handle incoming audio packet from network
     */
    public void onPacketReceived(byte[] packet) {
        if (packet.length < RTP_HEADER_SIZE) {
            Log.w(TAG, "Packet too small: " + packet.length);
            return;
        }
        
        // Parse RTP-like header
        byte packetType = packet[0];
        int sequenceNumber = ((packet[1] & 0xFF) << 24) | ((packet[2] & 0xFF) << 16) |
                            ((packet[3] & 0xFF) << 8) | (packet[4] & 0xFF);
        long timestamp = ((packet[5] & 0xFFL) << 56) | ((packet[6] & 0xFFL) << 48) |
                       ((packet[7] & 0xFFL) << 40) | ((packet[8] & 0xFFL) << 32) |
                       ((packet[9] & 0xFFL) << 24) | ((packet[10] & 0xFFL) << 16) |
                       ((packet[11] & 0xFFL) << 8) | (packet[12] & 0xFFL);
        
        // Extract Opus data
        int opusLength = packet.length - RTP_HEADER_SIZE;
        byte[] opusData = new byte[opusLength];
        System.arraycopy(packet, RTP_HEADER_SIZE, opusData, 0, opusLength);
        
        // Add to jitter buffer
        jitterBuffer.addPacket(sequenceNumber, timestamp, opusData);
    }
    
    /**
     * Main decode loop
     */
    private void decodeLoop() {
        Process.setThreadPriority(Process.THREAD_PRIORITY_URGENT_AUDIO);
        
        // PCM buffer for decoded audio
        byte[] pcmBuffer = new byte[opusDecoder.getMaxFrameSize() * opusDecoder.getChannels() * 2];
        
        while (isRunning.get()) {
            try {
                // Get packet from jitter buffer (with adaptive wait)
                JitterBuffer.JitterPacket packet = jitterBuffer.getPacket(20);
                
                int samplesDecoded = 0;
                
                if (packet != null) {
                    // Decode Opus to PCM
                    samplesDecoded = opusDecoder.decode(packet.data, packet.data.length, pcmBuffer);
                    
                    if (samplesDecoded > 0) {
                        // Add to PLC history
                        plc.addToHistory(pcmBuffer, samplesDecoded);
                        
                        // Send to callback for playback
                        if (playbackCallback != null) {
                            playbackCallback.onAudioFrame(
                                pcmBuffer.clone(),
                                opusDecoder.getSampleRate(),
                                opusDecoder.getChannels(),
                                System.currentTimeMillis()
                            );
                        }
                    }
                } else {
                    // No packet available - generate PLC
                    int plcSamples = opusDecoder.getSampleRate() / 50; // 20ms
                    samplesDecoded = plc.generateConcealment(pcmBuffer, plcSamples);
                    
                    if (samplesDecoded > 0 && playbackCallback != null) {
                        playbackCallback.onAudioFrame(
                            pcmBuffer.clone(),
                            opusDecoder.getSampleRate(),
                            opusDecoder.getChannels(),
                            System.currentTimeMillis()
                        );
                    }
                }
                
                // Sleep to maintain real-time pace
                Thread.sleep(20);
                
            } catch (InterruptedException e) {
                Thread.currentThread().interrupt();
                break;
            } catch (Exception e) {
                Log.e(TAG, "Error in decode loop", e);
            }
        }
    }
    
    /**
     * Get user ID
     */
    public String getUserId() {
        return userId;
    }
    
    /**
     * Check if stream is running
     */
    public boolean isRunning() {
        return isRunning.get();
    }
}

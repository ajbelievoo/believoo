package com.believoo.live.audio;

import android.util.Log;

import java.nio.ByteBuffer;
import java.util.HashMap;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;

/**
 * Audio Mixer for Multi-Speaker Conferences
 * Mixes multiple remote audio streams with automatic gain control
 */
public class AudioMixer {
    private static final String TAG = "AudioMixer";
    
    private int sampleRate;
    private int channels;
    private int frameSize;
    
    // Individual speaker buffers
    private ConcurrentHashMap<String, SpeakerBuffer> speakerBuffers = new ConcurrentHashMap<>();
    
    // Mixing parameters
    private static final int MAX_MIXED_SPEAKERS = 8;
    private static final float DEFAULT_SPEAKER_GAIN = 1.0f;
    private static final float MIX_HEADROOM_DB = -3.0f; // Prevent clipping
    
    // Output buffer (reused)
    private float[] mixBuffer;
    private byte[] outputBuffer;
    
    public AudioMixer(int sampleRate, int channels) {
        this.sampleRate = sampleRate;
        this.channels = channels;
        this.frameSize = sampleRate / 50; // 20ms frames
        
        // Allocate mixing buffers
        int maxSamples = frameSize * channels * MAX_MIXED_SPEAKERS;
        this.mixBuffer = new float[maxSamples];
        this.outputBuffer = new byte[frameSize * channels * 2]; // 16-bit
    }
    
    /**
     * Add speaker to mixer
     */
    public void addSpeaker(String userId) {
        SpeakerBuffer buffer = new SpeakerBuffer(frameSize * channels, DEFAULT_SPEAKER_GAIN);
        speakerBuffers.put(userId, buffer);
        Log.i(TAG, "Added speaker: " + userId);
    }
    
    /**
     * Remove speaker from mixer
     */
    public void removeSpeaker(String userId) {
        speakerBuffers.remove(userId);
        Log.i(TAG, "Removed speaker: " + userId);
    }
    
    /**
     * Set individual speaker volume
     * @param userId Speaker ID
     * @param gain Volume gain (0.0 to 2.0, 1.0 = 100%)
     */
    public void setSpeakerVolume(String userId, float gain) {
        SpeakerBuffer buffer = speakerBuffers.get(userId);
        if (buffer != null) {
            buffer.setGain(Math.max(0.0f, Math.min(2.0f, gain)));
        }
    }
    
    /**
     * Push audio frame from speaker
     */
    public void pushSpeakerAudio(String userId, byte[] pcmData) {
        SpeakerBuffer buffer = speakerBuffers.get(userId);
        if (buffer != null) {
            buffer.pushAudio(pcmData);
        }
    }
    
    /**
     * Mix all speakers and return mixed audio
     * @return Mixed PCM audio ready for playback
     */
    public byte[] mixAudio() {
        // Clear mix buffer
        for (int i = 0; i < mixBuffer.length; i++) {
            mixBuffer[i] = 0.0f;
        }
        
        int activeSpeakers = 0;
        
        // Sum all speaker audio
        for (SpeakerBuffer speaker : speakerBuffers.values()) {
            short[] speakerFrame = speaker.popFrame();
            if (speakerFrame != null) {
                activeSpeakers++;
                float gain = speaker.getGain();
                
                for (int i = 0; i < speakerFrame.length && i < mixBuffer.length; i++) {
                    mixBuffer[i] += speakerFrame[i] * gain;
                }
            }
        }
        
        if (activeSpeakers == 0) {
            // No active speakers, return silence
            return new byte[frameSize * channels * 2];
        }
        
        // Apply automatic gain control to prevent clipping
        float maxAmplitude = 0.0f;
        for (int i = 0; i < frameSize * channels; i++) {
            maxAmplitude = Math.max(maxAmplitude, Math.abs(mixBuffer[i]));
        }
        
        // Calculate attenuation to prevent clipping
        float targetMax = 32767.0f * dbToLinear(MIX_HEADROOM_DB);
        float attenuation = 1.0f;
        if (maxAmplitude > targetMax) {
            attenuation = targetMax / maxAmplitude;
        }
        
        // Convert to 16-bit PCM with attenuation
        for (int i = 0; i < frameSize * channels; i++) {
            float sample = mixBuffer[i] * attenuation;
            // Soft clipping
            sample = softClip(sample);
            
            short shortSample = (short) Math.max(-32768, Math.min(32767, (int) sample));
            outputBuffer[i * 2] = (byte) (shortSample & 0xFF);
            outputBuffer[i * 2 + 1] = (byte) ((shortSample >> 8) & 0xFF);
        }
        
        // Return mixed audio
        byte[] result = new byte[frameSize * channels * 2];
        System.arraycopy(outputBuffer, 0, result, 0, result.length);
        return result;
    }
    
    /**
     * Mix with automatic active speaker detection (focus on dominant speaker)
     */
    public byte[] mixAudioWithFocus() {
        // Find dominant speaker
        String dominantSpeaker = null;
        float maxEnergy = 0.0f;
        
        for (Map.Entry<String, SpeakerBuffer> entry : speakerBuffers.entrySet()) {
            float energy = entry.getValue().getCurrentEnergy();
            if (energy > maxEnergy) {
                maxEnergy = energy;
                dominantSpeaker = entry.getKey();
            }
        }
        
        // Adjust gains - boost dominant, reduce others
        for (Map.Entry<String, SpeakerBuffer> entry : speakerBuffers.entrySet()) {
            if (entry.getKey().equals(dominantSpeaker)) {
                entry.getValue().setTargetGain(1.2f); // Boost dominant
            } else {
                entry.getValue().setTargetGain(0.6f); // Reduce others
            }
        }
        
        return mixAudio();
    }
    
    /**
     * Get number of active speakers
     */
    public int getActiveSpeakerCount() {
        return speakerBuffers.size();
    }
    
    private float dbToLinear(float db) {
        return (float) Math.pow(10.0, db / 20.0);
    }
    
    private float softClip(float sample) {
        // Soft clipping using sigmoid-like function
        if (sample > 32767.0f) {
            return 32767.0f + (sample - 32767.0f) * 0.1f;
        } else if (sample < -32768.0f) {
            return -32768.0f + (sample + 32768.0f) * 0.1f;
        }
        return sample;
    }
    
    public void release() {
        speakerBuffers.clear();
    }
    
    /**
     * Inner class to buffer audio from individual speakers
     */
    private static class SpeakerBuffer {
        private short[] buffer;
        private int writeIndex = 0;
        private int readIndex = 0;
        private int capacity;
        private float gain = 1.0f;
        private float targetGain = 1.0f;
        private float currentEnergy = 0.0f;
        
        public SpeakerBuffer(int capacity, float initialGain) {
            this.capacity = capacity;
            this.buffer = new short[capacity * 2]; // 2x for jitter
            this.gain = initialGain;
        }
        
        public void pushAudio(byte[] pcmData) {
            // Convert bytes to shorts and add to buffer
            short[] shorts = byteArrayToShortArray(pcmData);
            
            // Calculate energy
            float energy = 0.0f;
            for (short sample : shorts) {
                energy += sample * sample;
            }
            currentEnergy = (currentEnergy * 0.9f) + ((energy / shorts.length) * 0.1f);
            
            // Add to circular buffer
            for (short sample : shorts) {
                buffer[writeIndex++] = sample;
                if (writeIndex >= buffer.length) {
                    writeIndex = 0;
                }
            }
        }
        
        public short[] popFrame() {
            // Check if enough data
            int available = (writeIndex - readIndex + buffer.length) % buffer.length;
            if (available < capacity / 2) {
                return null; // Not enough data
            }
            
            short[] frame = new short[capacity];
            for (int i = 0; i < capacity; i++) {
                frame[i] = buffer[readIndex++];
                if (readIndex >= buffer.length) {
                    readIndex = 0;
                }
            }
            
            // Smooth gain transition
            gain += (targetGain - gain) * 0.1f;
            
            return frame;
        }
        
        public void setGain(float gain) {
            this.targetGain = gain;
        }
        
        public float getGain() {
            return gain;
        }
        
        public float getCurrentEnergy() {
            return currentEnergy;
        }
        
        public void setTargetGain(float target) {
            this.targetGain = target;
        }
        
        private short[] byteArrayToShortArray(byte[] bytes) {
            short[] shorts = new short[bytes.length / 2];
            for (int i = 0; i < shorts.length; i++) {
                shorts[i] = (short) ((bytes[i * 2] & 0xFF) | ((bytes[i * 2 + 1] & 0xFF) << 8));
            }
            return shorts;
        }
    }
}

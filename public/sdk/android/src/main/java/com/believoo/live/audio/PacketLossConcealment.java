package com.believoo.live.audio;

import android.util.Log;

/**
 * Packet Loss Concealment (PLC)
 * Generates synthetic audio to mask lost packets
 */
public class PacketLossConcealment {
    private static final String TAG = "PacketLossConcealment";
    
    private int sampleRate;
    private int channels;
    
    // History buffer for waveform extrapolation
    private short[] historyBuffer;
    private int historySize;
    private int historyIndex = 0;
    
    // PLC state
    private boolean hasHistory = false;
    private int consecutiveLosses = 0;
    private static final int MAX_HISTORY_SAMPLES = 48000; // 1 second at 48kHz
    
    public PacketLossConcealment(int sampleRate, int channels) {
        this.sampleRate = sampleRate;
        this.channels = channels;
        this.historySize = Math.min(MAX_HISTORY_SAMPLES, sampleRate / 10); // 100ms history
        this.historyBuffer = new short[historySize * channels];
    }
    
    /**
     * Process audio frame - add to history
     * @param pcmData PCM audio data
     * @param samples Number of samples (per channel)
     */
    public void addToHistory(byte[] pcmData, int samples) {
        // Convert bytes to shorts
        short[] shorts = byteArrayToShortArray(pcmData);
        
        // Add to circular history buffer
        for (int i = 0; i < shorts.length && historyIndex < historyBuffer.length; i++) {
            historyBuffer[historyIndex++] = shorts[i];
            if (historyIndex >= historyBuffer.length) {
                historyIndex = 0;
                hasHistory = true;
            }
        }
        
        // Reset loss counter on successful packet
        consecutiveLosses = 0;
    }
    
    /**
     * Generate concealment audio for lost packet
     * @param outputBuffer Output buffer to fill with synthetic audio
     * @param samples Number of samples to generate
     * @return Number of samples generated
     */
    public int generateConcealment(byte[] outputBuffer, int samples) {
        if (!hasHistory) {
            // No history available, generate silence
            fillSilence(outputBuffer, samples);
            return samples;
        }
        
        consecutiveLosses++;
        
        // Generate synthetic audio using simple techniques:
        // 1. Pattern repetition for short losses
        // 2. Attenuated noise for longer losses
        // 3. Fade to silence after extended losses
        
        short[] output = new short[samples * channels];
        
        if (consecutiveLosses <= 3) {
            // Pattern repetition from history
            generatePatternRepetition(output, samples);
        } else if (consecutiveLosses <= 10) {
            // Attenuated noise with exponential decay
            generateAttenuatedNoise(output, samples);
        } else {
            // Fade to silence
            generateFadingSilence(output, samples);
        }
        
        // Convert shorts to bytes
        byte[] bytes = shortArrayToByteArray(output);
        System.arraycopy(bytes, 0, outputBuffer, 0, Math.min(bytes.length, outputBuffer.length));
        
        return samples;
    }
    
    private void generatePatternRepetition(short[] output, int samples) {
        // Repeat last known good audio pattern
        int patternLength = Math.min(samples, historySize / 2);
        int historyStart = (historyIndex - patternLength + historyBuffer.length) % historyBuffer.length;
        
        // Apply slight pitch shift for variety
        double pitchShift = 1.0 + (consecutiveLosses * 0.02); // Slight pitch up
        
        for (int i = 0; i < samples * channels; i++) {
            int historyIdx = (int) ((historyStart + (i / pitchShift)) % historyBuffer.length);
            output[i] = (short) (historyBuffer[historyIdx] * getAttenuationFactor());
        }
    }
    
    private void generateAttenuatedNoise(short[] output, int samples) {
        // Generate comfort noise based on history statistics
        double mean = calculateHistoryMean();
        double variance = calculateHistoryVariance(mean);
        
        float attenuation = getAttenuationFactor();
        
        for (int i = 0; i < samples * channels; i++) {
            // Pseudo-random comfort noise
            double noise = (Math.random() - 0.5) * 2 * Math.sqrt(variance);
            output[i] = (short) ((mean + noise) * attenuation);
        }
    }
    
    private void generateFadingSilence(short[] output, int samples) {
        // Fade to complete silence
        float attenuation = Math.max(0, getAttenuationFactor());
        
        for (int i = 0; i < samples * channels; i++) {
            float sampleAttenuation = attenuation * (1.0f - (i / (float) (samples * channels)));
            output[i] = (short) (0 * sampleAttenuation);
        }
    }
    
    private void fillSilence(byte[] buffer, int samples) {
        // Fill with zeros
        for (int i = 0; i < buffer.length && i < samples * channels * 2; i++) {
            buffer[i] = 0;
        }
    }
    
    private float getAttenuationFactor() {
        // Exponential attenuation based on consecutive losses
        return (float) Math.pow(0.8, consecutiveLosses);
    }
    
    private double calculateHistoryMean() {
        double sum = 0;
        for (short sample : historyBuffer) {
            sum += sample;
        }
        return sum / historyBuffer.length;
    }
    
    private double calculateHistoryVariance(double mean) {
        double sumSquaredDiff = 0;
        for (short sample : historyBuffer) {
            double diff = sample - mean;
            sumSquaredDiff += diff * diff;
        }
        return sumSquaredDiff / historyBuffer.length;
    }
    
    private short[] byteArrayToShortArray(byte[] bytes) {
        short[] shorts = new short[bytes.length / 2];
        for (int i = 0; i < shorts.length; i++) {
            shorts[i] = (short) ((bytes[i * 2] & 0xFF) | ((bytes[i * 2 + 1] & 0xFF) << 8));
        }
        return shorts;
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
        historyBuffer = null;
        hasHistory = false;
    }
}

package com.believoo.live.audio;

import android.util.Log;

/**
 * Opus Audio Decoder
 * Wraps native Opus codec for real-time audio decoding
 */
public class OpusDecoder {
    private static final String TAG = "OpusDecoder";
    
    private long nativeDecoder = 0;
    private int sampleRate;
    private int channels;
    private int maxFrameSize;
    
    static {
        System.loadLibrary("opus");
    }
    
    public OpusDecoder() {}
    
    /**
     * Initialize Opus decoder
     * @param sampleRate Sample rate in Hz
     * @param channels Number of channels (1 or 2)
     * @return true if successful
     */
    public boolean init(int sampleRate, int channels) {
        this.sampleRate = sampleRate;
        this.channels = channels;
        this.maxFrameSize = sampleRate / 25; // Max 40ms frames
        
        nativeDecoder = nativeInit(sampleRate, channels);
        if (nativeDecoder == 0) {
            Log.e(TAG, "Failed to initialize Opus decoder");
            return false;
        }
        
        Log.i(TAG, "Opus decoder initialized: " + sampleRate + "Hz, " + channels + "ch");
        return true;
    }
    
    /**
     * Decode Opus packet to PCM
     * @param opusData Encoded Opus data
     * @param opusLength Length of Opus data
     * @param pcmBuffer Output PCM buffer (16-bit signed)
     * @return Number of PCM samples decoded, or -1 on error
     */
    public int decode(byte[] opusData, int opusLength, byte[] pcmBuffer) {
        if (nativeDecoder == 0) {
            Log.e(TAG, "Decoder not initialized");
            return -1;
        }
        
        return nativeDecode(nativeDecoder, opusData, opusLength, pcmBuffer, pcmBuffer.length / 2);
    }
    
    /**
     * Decode with packet loss concealment (for lost packets)
     * @param pcmBuffer Output PCM buffer
     * @return Number of PCM samples decoded
     */
    public int decodeLost(byte[] pcmBuffer) {
        if (nativeDecoder == 0) {
            return -1;
        }
        
        // Pass null to indicate packet loss
        return nativeDecode(nativeDecoder, null, 0, pcmBuffer, pcmBuffer.length / 2);
    }
    
    public void release() {
        if (nativeDecoder != 0) {
            nativeRelease(nativeDecoder);
            nativeDecoder = 0;
        }
    }
    
    public int getSampleRate() {
        return sampleRate;
    }
    
    public int getChannels() {
        return channels;
    }
    
    public int getMaxFrameSize() {
        return maxFrameSize;
    }
    
    // Native methods
    private native long nativeInit(int sampleRate, int channels);
    private native int nativeDecode(long decoder, byte[] opus, int opusLen, byte[] pcm, int maxSamples);
    private native void nativeRelease(long decoder);
}

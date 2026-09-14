package com.believoo.live.audio;

import android.util.Log;

/**
 * Opus Audio Encoder
 * Wraps native Opus codec for real-time audio encoding
 */
public class OpusEncoder {
    private static final String TAG = "OpusEncoder";
    
    private long nativeEncoder = 0;
    private int sampleRate;
    private int channels;
    private int bitrate;
    private int frameSize;
    
    static {
        System.loadLibrary("opus");
    }
    
    public OpusEncoder() {}
    
    /**
     * Initialize Opus encoder
     * @param sampleRate Sample rate in Hz (8000, 12000, 16000, 24000, 48000)
     * @param channels Number of channels (1 or 2)
     * @param bitrate Bitrate in bits per second
     * @return true if successful
     */
    public boolean init(int sampleRate, int channels, int bitrate) {
        this.sampleRate = sampleRate;
        this.channels = channels;
        this.bitrate = bitrate;
        this.frameSize = sampleRate / 50; // 20ms frames
        
        nativeEncoder = nativeInit(sampleRate, channels, bitrate);
        if (nativeEncoder == 0) {
            Log.e(TAG, "Failed to initialize Opus encoder");
            return false;
        }
        
        Log.i(TAG, "Opus encoder initialized: " + sampleRate + "Hz, " + 
              channels + "ch, " + bitrate + "bps");
        return true;
    }
    
    /**
     * Encode PCM audio to Opus
     * @param pcmData PCM audio data (16-bit signed)
     * @param pcmLength Length of PCM data in bytes
     * @param opusBuffer Output buffer for encoded Opus data
     * @return Number of bytes written to opusBuffer, or -1 on error
     */
    public int encode(byte[] pcmData, int pcmLength, byte[] opusBuffer) {
        if (nativeEncoder == 0) {
            Log.e(TAG, "Encoder not initialized");
            return -1;
        }
        
        return nativeEncode(nativeEncoder, pcmData, pcmLength, opusBuffer, opusBuffer.length);
    }
    
    /**
     * Set encoder bitrate dynamically
     */
    public void setBitrate(int bitrate) {
        if (nativeEncoder != 0) {
            nativeSetBitrate(nativeEncoder, bitrate);
        }
    }
    
    /**
     * Set encoder complexity (0-10, higher = better quality, more CPU)
     */
    public void setComplexity(int complexity) {
        if (nativeEncoder != 0) {
            nativeSetComplexity(nativeEncoder, complexity);
        }
    }
    
    public void release() {
        if (nativeEncoder != 0) {
            nativeRelease(nativeEncoder);
            nativeEncoder = 0;
        }
    }
    
    // Native methods
    private native long nativeInit(int sampleRate, int channels, int bitrate);
    private native int nativeEncode(long encoder, byte[] pcm, int pcmLen, byte[] opus, int opusLen);
    private native void nativeSetBitrate(long encoder, int bitrate);
    private native void nativeSetComplexity(long encoder, int complexity);
    private native void nativeRelease(long encoder);
}

package com.believoo.live.audio;

import android.util.Log;

import java.util.Comparator;
import java.util.concurrent.PriorityBlockingQueue;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.atomic.AtomicBoolean;

/**
 * Jitter Buffer for Real-time Audio
 * Manages packet ordering, buffering, and adaptive jitter control
 */
public class JitterBuffer {
    private static final String TAG = "JitterBuffer";
    
    private static final long DEFAULT_MAX_WAIT_MS = 200;  // Max wait time for packet
    private static final int MAX_BUFFER_SIZE = 100;        // Max packets in buffer
    
    // Adaptive jitter control
    private long targetBufferMs;
    private long minBufferMs;
    private long maxBufferMs;
    
    private PriorityBlockingQueue<JitterPacket> packetQueue;
    private AtomicBoolean isRunning = new AtomicBoolean(false);
    
    // Statistics
    private long packetsReceived = 0;
    private long packetsPlayed = 0;
    private long packetsLate = 0;
    private long packetsLost = 0;
    private double currentJitterMs = 0;
    
    private int sampleRate;
    private long lastSequenceNumber = -1;
    private long baseTimestamp = 0;
    private boolean baseTimestampSet = false;
    
    public JitterBuffer(long minBufferMs, long maxBufferMs, int sampleRate) {
        this.minBufferMs = minBufferMs;
        this.maxBufferMs = maxBufferMs;
        this.targetBufferMs = minBufferMs;
        this.sampleRate = sampleRate;
        
        // Priority queue sorted by timestamp
        this.packetQueue = new PriorityBlockingQueue<>(MAX_BUFFER_SIZE, 
            Comparator.comparingLong(p -> p.timestamp));
    }
    
    /**
     * Add packet to jitter buffer
     * @param sequenceNumber RTP sequence number
     * @param timestamp RTP timestamp
     * @param data Packet data
     * @return true if packet was added successfully
     */
    public synchronized boolean addPacket(long sequenceNumber, long timestamp, byte[] data) {
        if (!baseTimestampSet) {
            baseTimestamp = timestamp;
            baseTimestampSet = true;
        }
        
        // Normalize timestamp
        long normalizedTimestamp = timestamp - baseTimestamp;
        
        // Check if packet is too old (already played)
        if (sequenceNumber < lastSequenceNumber - 10) {
            packetsLate++;
            Log.v(TAG, "Packet too late: seq=" + sequenceNumber);
            return false;
        }
        
        // Create jitter packet
        JitterPacket packet = new JitterPacket(sequenceNumber, normalizedTimestamp, 
                                               System.currentTimeMillis(), data);
        
        // Add to queue (evict oldest if full)
        if (packetQueue.size() >= MAX_BUFFER_SIZE) {
            JitterPacket oldest = packetQueue.poll();
            if (oldest != null) {
                packetsLost++;
                Log.v(TAG, "Buffer full, evicted oldest packet: seq=" + oldest.sequenceNumber);
            }
        }
        
        packetQueue.offer(packet);
        packetsReceived++;
        
        // Update jitter estimation
        updateJitterEstimate(packet);
        
        return true;
    }
    
    /**
     * Get next packet from buffer (blocking with timeout)
     * @param timeoutMs Maximum time to wait
     * @return Packet or null if timeout/no packet
     */
    public JitterPacket getPacket(long timeoutMs) throws InterruptedException {
        // Adaptive target buffer - wait until we have enough buffered
        long bufferSize = getBufferSizeMs();
        
        if (bufferSize < targetBufferMs && packetQueue.size() < 3) {
            // Not enough buffered yet, wait for more packets
            Thread.sleep(Math.min(timeoutMs, targetBufferMs - bufferSize));
        }
        
        JitterPacket packet = packetQueue.poll(timeoutMs, TimeUnit.MILLISECONDS);
        
        if (packet != null) {
            lastSequenceNumber = packet.sequenceNumber;
            packetsPlayed++;
        }
        
        return packet;
    }
    
    /**
     * Peek at next packet without removing
     */
    public JitterPacket peekPacket() {
        return packetQueue.peek();
    }
    
    /**
     * Get current buffer size in milliseconds
     */
    public long getBufferSizeMs() {
        if (packetQueue.isEmpty()) return 0;
        
        JitterPacket oldest = packetQueue.peek();
        JitterPacket newest = null;
        for (JitterPacket p : packetQueue) {
            if (newest == null || p.timestamp > newest.timestamp) {
                newest = p;
            }
        }
        
        if (oldest != null && newest != null) {
            long timestampDiff = newest.timestamp - oldest.timestamp;
            return (timestampDiff * 1000) / sampleRate;
        }
        return 0;
    }
    
    /**
     * Get current queue depth (number of packets)
     */
    public int getQueueDepth() {
        return packetQueue.size();
    }
    
    /**
     * Update adaptive target buffer based on network jitter
     */
    private void updateJitterEstimate(JitterPacket packet) {
        // Simple jitter calculation - can be enhanced with more sophisticated algorithms
        long arrivalJitter = System.currentTimeMillis() - packet.arrivalTime;
        currentJitterMs = (currentJitterMs * 0.9) + (arrivalJitter * 0.1);
        
        // Adjust target buffer
        if (currentJitterMs > targetBufferMs * 1.5) {
            targetBufferMs = Math.min(targetBufferMs + 10, maxBufferMs);
        } else if (currentJitterMs < targetBufferMs * 0.5 && targetBufferMs > minBufferMs) {
            targetBufferMs = Math.max(targetBufferMs - 5, minBufferMs);
        }
    }
    
    /**
     * Get statistics
     */
    public JitterStatistics getStatistics() {
        return new JitterStatistics(
            packetsReceived,
            packetsPlayed,
            packetsLate,
            packetsLost,
            currentJitterMs,
            getBufferSizeMs(),
            packetQueue.size()
        );
    }
    
    /**
     * Clear all packets
     */
    public void clear() {
        packetQueue.clear();
        baseTimestampSet = false;
        lastSequenceNumber = -1;
        packetsReceived = 0;
        packetsPlayed = 0;
        packetsLate = 0;
        packetsLost = 0;
    }
    
    public void release() {
        clear();
    }
    
    // Inner class for jitter packets
    public static class JitterPacket {
        public final long sequenceNumber;
        public final long timestamp;
        public final long arrivalTime;
        public final byte[] data;
        
        public JitterPacket(long sequenceNumber, long timestamp, long arrivalTime, byte[] data) {
            this.sequenceNumber = sequenceNumber;
            this.timestamp = timestamp;
            this.arrivalTime = arrivalTime;
            this.data = data;
        }
    }
    
    // Statistics class
    public static class JitterStatistics {
        public final long packetsReceived;
        public final long packetsPlayed;
        public final long packetsLate;
        public final long packetsLost;
        public final double currentJitterMs;
        public final long bufferSizeMs;
        public final int queueDepth;
        
        public JitterStatistics(long packetsReceived, long packetsPlayed, long packetsLate,
                               long packetsLost, double currentJitterMs, long bufferSizeMs, 
                               int queueDepth) {
            this.packetsReceived = packetsReceived;
            this.packetsPlayed = packetsPlayed;
            this.packetsLate = packetsLate;
            this.packetsLost = packetsLost;
            this.currentJitterMs = currentJitterMs;
            this.bufferSizeMs = bufferSizeMs;
            this.queueDepth = queueDepth;
        }
    }
}

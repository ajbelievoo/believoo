package com.believoo.live;

import android.util.Log;
import org.java_websocket.client.WebSocketClient;
import org.java_websocket.handshake.ServerHandshake;
import org.webrtc.IceCandidate;
import org.webrtc.SessionDescription;
import java.net.URI;

class BelieVooWebSocket extends WebSocketClient {
    private static final String TAG = "BelieVooWS";
    private PeerConnectionHandler handler;
    
    interface PeerConnectionHandler {
        void onOffer(String offer);
        void onAnswer(String answer);
        void onIceCandidate(IceCandidate candidate);
    }
    
    BelieVooWebSocket(URI serverUri, PeerConnectionHandler handler) {
        super(serverUri);
        this.handler = handler;
    }
    
    @Override public void onOpen(ServerHandshake handshake) {
        Log.i(TAG, "Connected to BelieVoo streaming server");
    }
    
    @Override public void onMessage(String message) {
        try {
            org.json.JSONObject json = new org.json.JSONObject(message);
            String type = json.getString("type");
            
            if ("offer".equals(type)) {
                handler.onOffer(json.getString("sdp"));
            } else if ("answer".equals(type)) {
                handler.onAnswer(json.getString("sdp"));
            } else if ("ice-candidate".equals(type)) {
                // Parse ICE candidate
            }
        } catch (Exception e) {
            Log.e(TAG, "Error parsing message: " + e.getMessage());
        }
    }
    
    @Override public void onClose(int code, String reason, boolean remote) {
        Log.i(TAG, "Disconnected: " + reason);
    }
    
    @Override public void onError(Exception ex) {
        Log.e(TAG, "WebSocket error: " + ex.getMessage());
    }
    
    void sendOffer(String sdp) {
        try {
            org.json.JSONObject json = new org.json.JSONObject();
            json.put("type", "offer");
            json.put("sdp", sdp);
            send(json.toString());
        } catch (Exception e) {
            Log.e(TAG, "Error sending offer");
        }
    }
    
    void sendIceCandidate(IceCandidate candidate) {
        try {
            org.json.JSONObject json = new org.json.JSONObject();
            json.put("type", "ice-candidate");
            json.put("sdpMid", candidate.sdpMid);
            json.put("sdpMLineIndex", candidate.sdpMLineIndex);
            json.put("candidate", candidate.sdp);
            send(json.toString());
        } catch (Exception e) {
            Log.e(TAG, "Error sending ICE");
        }
    }
}

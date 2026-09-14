import Foundation
import WebRTC

@objc public class BelieVooLive: NSObject {
    private var appId: String
    private var host: String
    private var webSocket: URLSessionWebSocketTask?
    private var peerConnection: RTCPeerConnection?
    private var localVideoTrack: RTCVideoTrack?
    private var remoteVideoTrack: RTCVideoTrack?
    
    @objc public static func initWithHost(_ host: String) {
        print("[BelieVoo] iOS SDK initialized with \(host)")
    }
    
    @objc public init(appId: String, appCertificate: String) {
        self.appId = appId
        self.host = "wss://live.believoo.com"
        super.init()
        print("[BelieVoo] iOS SDK v2.0.0 initialized")
    }
    
    @objc public func joinBroadcastChannel(_ channel: String, token: String, uid: Int) {
        print("[BelieVoo] Broadcasting to \(channel) as uid \(uid)")
        connectWebSocket(channel: channel, token: token, uid: uid, role: "broadcaster")
    }
    
    @objc public func joinAudienceChannel(_ channel: String, token: String, uid: Int) {
        print("[BelieVoo] Watching \(channel) as uid \(uid)")
        connectWebSocket(channel: channel, token: token, uid: uid, role: "audience")
    }
    
    private func connectWebSocket(channel: String, token: String, uid: Int, role: String) {
        let url = URL(string: "\(host)/ws/\(channel)?token=\(token)&uid=\(uid)&role=\(role)")!
        webSocket = URLSession.shared.webSocketTask(with: url)
        webSocket?.resume()
    }
    
    @objc public func leaveChannel() {
        webSocket?.cancel(with: .normalClosure, reason: nil)
        peerConnection?.close()
        print("[BelieVoo] Left channel")
    }
    
    @objc public func release() {
        leaveChannel()
        print("[BelieVoo] Released")
    }
}

BelieVooLiveSDK.framework/
├── Headers/
│   ├── BelieVooLive.h
│   └── BelieVooLive-Swift.h
├── Modules/
│   └── module.modulemap
├── BelieVooLiveSDK (binary)
└── Info.plist

Integration:
1. Drag BelieVooLiveSDK.framework to your project
2. Add to "Embedded Binaries"
3. Import: #import <BelieVooLiveSDK/BelieVooLive.h>

Usage:
let config = LiveConfig(appId: "bel_id", appCert: "cert")
let live = BelieVooLive(config: config)
live.joinBroadcastChannel("channel", token: "token", uid: 12345)

Podfile:
pod "BelieVooLiveSDK", "~> 2.0.0"

App ID: bel_06ad61f458468c57b1d48a29
Docs: https://believoo.com/docs/streaming


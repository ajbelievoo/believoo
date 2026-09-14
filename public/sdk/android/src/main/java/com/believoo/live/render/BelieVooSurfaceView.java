package com.believoo.live.render;

import android.content.Context;
import android.util.AttributeSet;
import org.webrtc.SurfaceViewRenderer;

public class BelieVooSurfaceView extends SurfaceViewRenderer {
    public BelieVooSurfaceView(Context context) {
        super(context);
        init();
    }
    
    public BelieVooSurfaceView(Context context, AttributeSet attrs) {
        super(context, attrs);
        init();
    }
    
    private void init() {
        setMirror(true);
        setEnableHardwareScaler(true);
    }
}

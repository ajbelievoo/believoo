<x-layouts.believoo>
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
    .it-wrap{--acc:#00b7ff;--acc2:#7c3aed}
    .it-card{background:var(--bg-secondary,#141428);border:1px solid var(--border-color,rgba(255,255,255,.08));border-radius:16px}
    html.light .it-card{background:#fff;border-color:#e2e8f0;box-shadow:0 2px 12px rgba(0,0,0,.05)}
    .it-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:10px;padding:10px 18px;font-weight:700;font-size:.9rem;cursor:pointer;border:1px solid transparent;transition:all .15s ease;text-decoration:none}
    .it-btn-primary{background:linear-gradient(135deg,#00b7ff,#0090d0);color:#04121c}
    .it-btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
    .it-btn-ghost{background:rgba(0,183,255,.08);color:var(--acc);border-color:rgba(0,183,255,.25)}
    html.light .it-btn-ghost{background:rgba(2,132,199,.08);color:#0284c7}
    .it-btn-ghost:hover{background:rgba(0,183,255,.16)}
    .it-btn-dim{background:rgba(128,128,160,.12);color:var(--text-muted,#94a3b8);border-color:var(--border-color,rgba(255,255,255,.08))}
    .it-btn-dim:hover{color:var(--text-primary,#fff)}
    .it-btn:disabled{opacity:.45;cursor:not-allowed;transform:none!important}
    .it-btn.active{background:var(--acc);color:#04121c;border-color:var(--acc)}
    .it-input{width:100%;background:rgba(128,128,160,.1);border:1px solid var(--border-color,rgba(255,255,255,.1));border-radius:10px;padding:10px 14px;color:var(--text-primary,#fff);font-size:.9rem;outline:none}
    html.light .it-input{background:#f8fafc;border-color:#e2e8f0;color:#0f172a}
    .it-input:focus{border-color:var(--acc);box-shadow:0 0 0 3px rgba(0,183,255,.15)}
    .it-label{font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted,#94a3b8);margin-bottom:6px;display:block}
    .it-tab{padding:10px 22px;border-radius:10px;font-weight:700;font-size:.9rem;cursor:pointer;color:var(--text-muted,#94a3b8);border:1px solid transparent;background:transparent;transition:all .15s}
    .it-tab:hover{color:var(--text-primary,#fff)}
    .it-tab.active{background:rgba(0,183,255,.12);color:var(--acc);border-color:rgba(0,183,255,.3)}
    html.light .it-tab.active{color:#0284c7}
    .it-drop{border:2px dashed rgba(0,183,255,.35);border-radius:20px;transition:all .2s;cursor:pointer}
    .it-drop:hover,.it-drop.drag{border-color:var(--acc);background:rgba(0,183,255,.06)}
    .it-chip{font-size:.72rem;padding:5px 12px;border-radius:999px;background:rgba(128,128,160,.12);color:var(--text-muted,#94a3b8);cursor:pointer;border:1px solid transparent;font-weight:600}
    .it-chip:hover{border-color:var(--acc);color:var(--acc)}
    .it-chip.active{background:var(--acc);color:#04121c}
    .it-stat{font-size:.78rem;color:var(--text-muted,#94a3b8)}
    .it-stat b{color:var(--text-primary,#fff)}
    .cropper-container{max-width:100%}
    .it-crop-box{max-height:440px;background:repeating-conic-gradient(rgba(128,128,160,.15) 0 25%,transparent 0 50%) 0 0/20px 20px}
    .it-crop-box img{max-width:100%;display:block}
    .it-preview-box{max-height:440px;overflow:auto;background:repeating-conic-gradient(rgba(128,128,160,.15) 0 25%,transparent 0 50%) 0 0/20px 20px;border-radius:12px;display:flex;align-items:center;justify-content:center;min-height:200px}
    .it-preview-box img{max-width:100%;height:auto}
    input[type=range].it-range{width:100%;accent-color:var(--acc)}
    .it-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);background:#14142a;color:#fff;border:1px solid rgba(0,183,255,.35);padding:12px 22px;border-radius:12px;font-size:.85rem;font-weight:600;opacity:0;pointer-events:none;transition:all .3s;z-index:9999;box-shadow:0 8px 30px rgba(0,0,0,.4)}
    html.light .it-toast{background:#0f172a}
    .it-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
    .cropper-view-box,.cropper-face,.cropper-line,.cropper-point{outline-color:rgba(0,183,255,.7)}
    .cropper-point{background:#00b7ff}
</style>
@endpush

<div class="it-wrap" style="max-width:1100px;margin:0 auto;padding:130px 20px 70px;">
    {{-- Header --}}
    <div class="text-center" style="margin-bottom:36px;">
        <span class="it-chip" style="cursor:default;border-color:rgba(0,183,255,.3);color:var(--acc);">FREE TOOL</span>
        <h1 style="font-size:2.2rem;font-weight:800;margin:14px 0 8px;">Image Studio</h1>
        <p style="color:var(--text-muted,#94a3b8);max-width:640px;margin:0 auto;font-size:.95rem;line-height:1.6;">
            Crop, resize, compress aur format convert — sab ek jagah. Photo ko exact size (10KB, 100KB, 1MB…) tak compress karo,
            JPG · PNG · WebP · GIF · BMP · ICO · AVIF mein download karo. <b>Transparent PNG</b> ka alpha channel bhi preserve rehta hai.
            <b style="color:var(--acc);">100% private</b> — photo kabhi server par upload nahi hoti, sab aapke browser mein hota hai.
        </p>
    </div>

    {{-- Upload zone --}}
    <div id="dropZone" class="it-drop" style="padding:56px 24px;text-align:center;">
        <i class="fas fa-cloud-upload-alt" style="font-size:3rem;color:var(--acc);opacity:.85;"></i>
        <div style="font-size:1.15rem;font-weight:700;margin:14px 0 6px;">Photo yahan drop karo ya click karke choose karo</div>
        <div class="it-stat">JPG · PNG · WebP · GIF · BMP · AVIF — Ctrl+V paste bhi chalega</div>
        <input type="file" id="fileInput" accept="image/*" style="display:none;">
    </div>

    {{-- Editor --}}
    <div id="editor" style="display:none;margin-top:24px;">
        {{-- File info bar --}}
        <div class="it-card" style="padding:14px 20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
            <i class="fas fa-image" style="color:var(--acc);font-size:1.2rem;"></i>
            <div style="min-width:0;flex:1;">
                <div id="fileName" style="font-weight:700;font-size:.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                <div class="it-stat" id="fileMeta"></div>
            </div>
            <button class="it-btn it-btn-dim" id="btnUndo" disabled><i class="fas fa-undo"></i> Undo</button>
            <button class="it-btn it-btn-dim" id="btnNew"><i class="fas fa-arrow-rotate-left"></i> New Photo</button>
        </div>

        {{-- Tabs --}}
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
            <button class="it-tab active" data-tab="crop"><i class="fas fa-crop-simple" style="margin-right:7px;"></i>Crop &amp; Rotate</button>
            <button class="it-tab" data-tab="resize"><i class="fas fa-expand" style="margin-right:7px;"></i>Resize</button>
            <button class="it-tab" data-tab="effects"><i class="fas fa-wand-magic-sparkles" style="margin-right:7px;"></i>Effects</button>
            <button class="it-tab" data-tab="compress"><i class="fas fa-file-zipper" style="margin-right:7px;"></i>Compress &amp; Convert</button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 320px;gap:18px;" id="editorGrid">
            {{-- Left: canvas area --}}
            <div>
                <div class="it-card" style="padding:14px;">
                    <div id="cropArea" class="it-crop-box"><img id="cropImg" alt=""></div>
                    <div id="previewArea" class="it-preview-box" style="display:none;"><img id="previewImg" alt=""></div>
                </div>

                {{-- Crop toolbar --}}
                <div id="cropTools" style="margin-top:12px;">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <span class="it-label" style="margin:0 4px 0 0;">Ratio:</span>
                        <button class="it-chip ratio-chip active" data-ratio="NaN">Free</button>
                        <button class="it-chip ratio-chip" data-ratio="1">1:1</button>
                        <button class="it-chip ratio-chip" data-ratio="1.3333">4:3</button>
                        <button class="it-chip ratio-chip" data-ratio="1.7777">16:9</button>
                        <button class="it-chip ratio-chip" data-ratio="0.5625">9:16</button>
                        <button class="it-chip ratio-chip" data-ratio="0.6666">2:3</button>
                        <span style="width:1px;height:22px;background:var(--border-color,rgba(255,255,255,.12));margin:0 6px;"></span>
                        <button class="it-btn it-btn-dim" id="btnRotL" title="Rotate left" style="padding:8px 12px;"><i class="fas fa-rotate-left"></i></button>
                        <button class="it-btn it-btn-dim" id="btnRotR" title="Rotate right" style="padding:8px 12px;"><i class="fas fa-rotate-right"></i></button>
                        <button class="it-btn it-btn-dim" id="btnFlipH" title="Flip horizontal" style="padding:8px 12px;"><i class="fas fa-arrows-left-right"></i></button>
                        <button class="it-btn it-btn-dim" id="btnFlipV" title="Flip vertical" style="padding:8px 12px;"><i class="fas fa-arrows-up-down"></i></button>
                        <button class="it-btn it-btn-dim" id="btnCropReset" style="padding:8px 12px;"><i class="fas fa-undo"></i> Reset</button>
                        <button class="it-btn it-btn-primary" id="btnApplyCrop" style="margin-left:auto;"><i class="fas fa-check"></i> Apply Crop</button>
                    </div>
                </div>
            </div>

            {{-- Right: controls --}}
            <div>
                {{-- Resize panel --}}
                <div class="it-card" id="panelResize" style="padding:20px;display:none;">
                    <h3 style="font-weight:800;font-size:1rem;margin-bottom:16px;"><i class="fas fa-expand" style="color:var(--acc);margin-right:8px;"></i>Resize</h3>
                    <div style="display:flex;gap:10px;">
                        <div style="flex:1;">
                            <label class="it-label">Width (px)</label>
                            <input type="number" class="it-input" id="inW" min="1" placeholder="Width">
                        </div>
                        <div style="flex:1;">
                            <label class="it-label">Height (px)</label>
                            <input type="number" class="it-input" id="inH" min="1" placeholder="Height">
                        </div>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;margin:12px 0;font-size:.85rem;color:var(--text-muted,#94a3b8);cursor:pointer;">
                        <input type="checkbox" id="lockAspect" checked style="accent-color:var(--acc);"> Lock aspect ratio
                    </label>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;">
                        <button class="it-chip pct-chip" data-pct="25">25%</button>
                        <button class="it-chip pct-chip" data-pct="50">50%</button>
                        <button class="it-chip pct-chip" data-pct="75">75%</button>
                        <button class="it-chip pct-chip" data-pct="100">100%</button>
                        <button class="it-chip pct-chip" data-pct="150">150%</button>
                        <button class="it-chip pct-chip" data-pct="200">200%</button>
                    </div>
                    <button class="it-btn it-btn-primary" id="btnApplyResize" style="width:100%;"><i class="fas fa-check"></i> Apply Resize</button>
                </div>

                {{-- Effects panel --}}
                <div class="it-card" id="panelEffects" style="padding:20px;display:none;">
                    <h3 style="font-weight:800;font-size:1rem;margin-bottom:14px;"><i class="fas fa-wand-magic-sparkles" style="color:var(--acc);margin-right:8px;"></i>Effects</h3>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button class="it-chip fx-chip" data-fx="none">None</button>
                        <button class="it-chip fx-chip" data-fx="grayscale(1)">B&amp;W</button>
                        <button class="it-chip fx-chip" data-fx="sepia(1)">Sepia</button>
                        <button class="it-chip fx-chip" data-fx="sepia(.5) contrast(1.1) saturate(.85)">Vintage</button>
                        <button class="it-chip fx-chip" data-fx="brightness(1.15) contrast(1.05)">Bright</button>
                        <button class="it-chip fx-chip" data-fx="sepia(.25) saturate(1.25)">Warm</button>
                        <button class="it-chip fx-chip" data-fx="hue-rotate(15deg) saturate(1.15)">Cool</button>
                        <button class="it-chip fx-chip" data-fx="saturate(1.6) contrast(1.08)">Vivid</button>
                        <button class="it-chip fx-chip" data-fx="invert(1)">Invert</button>
                        <button class="it-chip fx-chip" data-fx="blur(2px)">Blur</button>
                    </div>
                    <p class="it-stat" style="margin-top:12px;line-height:1.6;">Har effect <b>original photo</b> par lagta hai — naya select karne pe purana replace ho jata hai (stack nahi hota). <b>None</b> = effect hatao, ya upar <b>Undo</b> button se koi bhi last action wapas lo.</p>
                </div>

                {{-- Compress panel --}}
                <div class="it-card" id="panelCompress" style="padding:20px;display:none;">
                    <h3 style="font-weight:800;font-size:1rem;margin-bottom:16px;"><i class="fas fa-file-zipper" style="color:var(--acc);margin-right:8px;"></i>Compress &amp; Convert</h3>

                    <label class="it-label">Quick presets</label>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;">
                        <button class="it-chip preset-chip" data-w="413" data-h="531" data-kb="100"><i class="fas fa-id-card" style="margin-right:4px;"></i>Passport</button>
                        <button class="it-chip preset-chip" data-w="140" data-h="60" data-kb="20"><i class="fas fa-signature" style="margin-right:4px;"></i>Signature</button>
                        <button class="it-chip preset-chip" data-w="200" data-h="230" data-kb="50"><i class="fas fa-file-lines" style="margin-right:4px;"></i>Govt Form</button>
                        <button class="it-chip preset-chip" data-w="128" data-h="128" data-kb="30"><i class="fas fa-circle-user" style="margin-right:4px;"></i>DP/Avatar</button>
                    </div>

                    <label class="it-label">Target file size</label>
                    <div style="display:flex;gap:8px;">
                        <input type="number" class="it-input" id="inTarget" min="1" step="any" placeholder="e.g. 100" style="flex:1;">
                        <select class="it-input" id="inUnit" style="width:88px;flex:none;">
                            <option value="1024" selected>KB</option>
                            <option value="1048576">MB</option>
                        </select>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin:10px 0 16px;">
                        <button class="it-chip target-chip" data-kb="10">10KB</button>
                        <button class="it-chip target-chip" data-kb="20">20KB</button>
                        <button class="it-chip target-chip" data-kb="50">50KB</button>
                        <button class="it-chip target-chip" data-kb="100">100KB</button>
                        <button class="it-chip target-chip" data-kb="200">200KB</button>
                        <button class="it-chip target-chip" data-kb="500">500KB</button>
                        <button class="it-chip target-chip" data-kb="1024">1MB</button>
                    </div>

                    <label class="it-label">Output format</label>
                    <select class="it-input" id="inFormat" style="margin-bottom:6px;">
                        <option value="image/jpeg" selected>JPG — sabse chhota size</option>
                        <option value="image/png">PNG — lossless + transparent</option>
                        <option value="image/webp">WebP — chhota + transparent</option>
                        <option value="image/gif">GIF</option>
                        <option value="image/bmp">BMP</option>
                        <option value="image/x-icon">ICO — favicon</option>
                        <option value="image/avif">AVIF — newest (browser dependent)</option>
                    </select>
                    <div class="it-stat" id="formatNote" style="margin-bottom:12px;min-height:16px;"></div>

                    <div id="bgRow" style="margin-bottom:14px;">
                        <label class="it-label">Background (transparency ki jagah)</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" id="inBg" value="#ffffff" style="width:44px;height:36px;border:1px solid var(--border-color,rgba(255,255,255,.15));border-radius:8px;background:transparent;padding:2px;cursor:pointer;">
                            <span class="it-stat">JPG/GIF/BMP transparency support nahi karte — transparent area is color se fill hoga</span>
                        </div>
                    </div>

                    <div id="qualityRow">
                        <label class="it-label">Quality: <b id="qVal" style="color:var(--acc);">80%</b></label>
                        <input type="range" class="it-range" id="inQuality" min="1" max="100" value="80" style="margin-bottom:14px;">
                    </div>

                    <button class="it-btn it-btn-primary" id="btnCompressTarget" style="width:100%;margin-bottom:8px;">
                        <i class="fas fa-wand-magic-sparkles"></i> Compress to Target Size
                    </button>
                    <button class="it-btn it-btn-ghost" id="btnCompressQuality" style="width:100%;">
                        <i class="fas fa-sliders"></i> Apply Quality Only
                    </button>
                    <div class="it-stat" id="compressStatus" style="margin-top:12px;min-height:18px;"></div>
                </div>

                {{-- Crop side info --}}
                <div class="it-card" id="panelCropInfo" style="padding:20px;">
                    <h3 style="font-weight:800;font-size:1rem;margin-bottom:12px;"><i class="fas fa-crop-simple" style="color:var(--acc);margin-right:8px;"></i>Crop</h3>
                    <p class="it-stat" style="line-height:1.6;margin:0;">Photo par box drag karke crop area select karo. Ratio fix kar sakte ho, rotate/flip bhi. Phir <b>Apply Crop</b> dabao.</p>
                    <div class="it-stat" id="cropInfo" style="margin-top:12px;"></div>
                </div>
            </div>
        </div>

        {{-- Result bar --}}
        <div class="it-card" id="resultBar" style="margin-top:18px;padding:18px 22px;display:none;">
            <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
                <div>
                    <div class="it-stat">FINAL SIZE</div>
                    <div id="finalSize" style="font-size:1.5rem;font-weight:800;color:var(--acc);"></div>
                </div>
                <div>
                    <div class="it-stat">DIMENSIONS</div>
                    <div id="finalDim" style="font-weight:700;"></div>
                </div>
                <div id="savedBadge" style="display:none;">
                    <div class="it-stat">SAVED</div>
                    <div id="savedPct" style="font-weight:800;color:#34d399;"></div>
                </div>
                <div style="margin-left:auto;display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="it-btn it-btn-ghost" id="btnCompare"><i class="fas fa-eye"></i> Preview</button>
                    <button class="it-btn it-btn-primary" id="btnDownload"><i class="fas fa-download"></i> Download</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="it-toast" id="toast"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
(function(){
    'use strict';
    var $ = function(id){ return document.getElementById(id); };
    var dropZone=$('dropZone'), fileInput=$('fileInput'), editor=$('editor'),
        cropImg=$('cropImg'), previewImg=$('previewImg'),
        cropArea=$('cropArea'), previewArea=$('previewArea'),
        cropper=null, sourceCanvas=null, baseCanvas=null, resultBlob=null, resultName='image',
        origSize=0, scaleX=1, scaleY=1, undoStack=[], activeFx=null;

    function fmt(b){ if(b<1024)return b+' B'; if(b<1048576)return (b/1024).toFixed(1)+' KB'; return (b/1048576).toFixed(2)+' MB'; }
    function toast(msg){ var t=$('toast'); t.textContent=msg; t.classList.add('show'); clearTimeout(t._h); t._h=setTimeout(function(){t.classList.remove('show');},2600); }
    function ext(mime){ return {'image/jpeg':'jpg','image/png':'png','image/webp':'webp','image/gif':'gif','image/bmp':'bmp','image/x-icon':'ico','image/avif':'avif'}[mime]||'jpg'; }
    function loadScript(src){ return new Promise(function(res,rej){ var s=document.createElement('script'); s.src=src; s.onload=res; s.onerror=rej; document.head.appendChild(s); }); }

    /* ---------- load image ---------- */
    function loadFile(file){
        if(!file || !file.type.match(/^image\//)){ toast('Sirf image file chalegi'); return; }
        var reader=new FileReader();
        reader.onload=function(e){
            var img=new Image();
            img.onload=function(){
                sourceCanvas=document.createElement('canvas');
                sourceCanvas.width=img.naturalWidth; sourceCanvas.height=img.naturalHeight;
                sourceCanvas.getContext('2d').drawImage(img,0,0);
                baseCanvas=sourceCanvas; undoStack.length=0; setActiveFx(null); syncUndoBtn();
                origSize=file.size;
                resultName=(file.name||'image').replace(/\.[^.]+$/,'');
                $('fileName').textContent=file.name||'pasted-image';
                $('fileMeta').innerHTML='Original: <b>'+fmt(file.size)+'</b> · <b>'+img.naturalWidth+' × '+img.naturalHeight+' px</b> · '+file.type;
                dropZone.style.display='none'; editor.style.display='block';
                resultBlob=null; $('resultBar').style.display='none';
                initCropper(); syncResizeInputs(); switchTab('crop');
                URL.revokeObjectURL(img.src);
            };
            img.onerror=function(){ toast('Image load nahi hui'); };
            img.src=e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function initCropper(){
        if(cropper){ cropper.destroy(); cropper=null; }
        cropImg.src=sourceCanvas.toDataURL('image/png');
        cropper=new Cropper(cropImg,{
            viewMode:1, autoCropArea:0.9, responsive:true, background:false,
            ready:function(){ updateCropInfo(); },
            crop:function(){ updateCropInfo(); }
        });
        scaleX=1; scaleY=1;
        document.querySelectorAll('.ratio-chip').forEach(function(c,i){ c.classList.toggle('active',i===0); });
    }
    function updateCropInfo(){
        if(!cropper)return;
        var d=cropper.getData(true);
        $('cropInfo').innerHTML='Selection: <b>'+Math.round(d.width)+' × '+Math.round(d.height)+' px</b> @ ('+Math.round(d.x)+', '+Math.round(d.y)+')';
    }

    /* ---------- tabs ---------- */
    var tabs=document.querySelectorAll('.it-tab');
    function switchTab(name){
        tabs.forEach(function(t){ t.classList.toggle('active',t.dataset.tab===name); });
        var isCrop=name==='crop';
        cropArea.style.display=isCrop?'block':'none';
        previewArea.style.display=isCrop?'none':'flex';
        $('cropTools').style.display=isCrop?'flex':'none';
        $('panelCropInfo').style.display=isCrop?'block':'none';
        $('panelResize').style.display=name==='resize'?'block':'none';
        $('panelEffects').style.display=name==='effects'?'block':'none';
        $('panelCompress').style.display=name==='compress'?'block':'none';
        if(!isCrop) refreshPreview();
        if(isCrop && cropper) cropper.enable();
    }
    tabs.forEach(function(t){ t.addEventListener('click',function(){ switchTab(t.dataset.tab); }); });

    /* ---------- preview ---------- */
    var previewUrl=null;
    function refreshPreview(){
        if(previewUrl){ URL.revokeObjectURL(previewUrl); previewUrl=null; }
        if(resultBlob){ previewUrl=URL.createObjectURL(resultBlob); previewImg.src=previewUrl; }
        else previewImg.src=sourceCanvas.toDataURL('image/png');
    }

    /* ---------- undo ---------- */
    function pushUndo(){ undoStack.push(sourceCanvas); if(undoStack.length>12) undoStack.shift(); syncUndoBtn(); }
    function syncUndoBtn(){ $('btnUndo').disabled=!undoStack.length; }
    function setActiveFx(fx){
        activeFx=fx;
        document.querySelectorAll('.fx-chip').forEach(function(x){
            x.classList.toggle('active', x.dataset.fx===(fx||'none'));
        });
    }
    $('btnUndo').onclick=function(){
        if(!undoStack.length)return;
        sourceCanvas=undoStack.pop(); baseCanvas=sourceCanvas;
        resultBlob=null; $('resultBar').style.display='none';
        initCropper(); syncResizeInputs(); refreshPreview(); setActiveFx(null); syncUndoBtn();
        toast('Undo ho gaya');
    };

    /* ---------- effects (hamesha ORIGINAL pe lagta hai — stack nahi hota) ---------- */
    document.querySelectorAll('.fx-chip').forEach(function(c){
        c.addEventListener('click',function(){
            var fx=c.dataset.fx;
            pushUndo();
            if(fx==='none'){
                sourceCanvas=baseCanvas;
            }else{
                var tmp=document.createElement('canvas');
                tmp.width=baseCanvas.width; tmp.height=baseCanvas.height;
                var ctx=tmp.getContext('2d');
                ctx.filter=fx; ctx.drawImage(baseCanvas,0,0);
                sourceCanvas=tmp;
            }
            resultBlob=null; $('resultBar').style.display='none';
            initCropper(); syncResizeInputs(); refreshPreview(); setActiveFx(fx);
            toast(fx==='none'?'Effect removed ✓':'Effect applied ✓');
        });
    });

    /* ---------- crop toolbar ---------- */
    document.querySelectorAll('.ratio-chip').forEach(function(c){
        c.addEventListener('click',function(){
            document.querySelectorAll('.ratio-chip').forEach(function(x){x.classList.remove('active');});
            c.classList.add('active');
            if(cropper) cropper.setAspectRatio(parseFloat(c.dataset.ratio));
        });
    });
    $('btnRotL').onclick=function(){ if(cropper)cropper.rotate(-90); };
    $('btnRotR').onclick=function(){ if(cropper)cropper.rotate(90); };
    $('btnFlipH').onclick=function(){ if(cropper){scaleX=-scaleX;cropper.scaleX(scaleX);} };
    $('btnFlipV').onclick=function(){ if(cropper){scaleY=-scaleY;cropper.scaleY(scaleY);} };
    $('btnCropReset').onclick=function(){ if(cropper){cropper.reset();scaleX=scaleY=1;cropper.scale(1,1);} };
    $('btnApplyCrop').onclick=function(){
        if(!cropper)return;
        var c=cropper.getCroppedCanvas();
        if(!c||!c.width){ toast('Crop area select karo pehle'); return; }
        pushUndo();
        sourceCanvas=c; baseCanvas=c; resultBlob=null; $('resultBar').style.display='none';
        initCropper(); syncResizeInputs(); refreshPreview(); setActiveFx(null);
        toast('Crop applied: '+c.width+' × '+c.height+' px');
    };

    /* ---------- resize ---------- */
    function syncResizeInputs(){
        $('inW').value=sourceCanvas.width; $('inH').value=sourceCanvas.height;
    }
    var inW=$('inW'), inH=$('inH'), lock=$('lockAspect'), editing=null;
    inW.addEventListener('input',function(){
        if(editing)return; editing=true;
        if(lock.checked && sourceCanvas.width) inH.value=Math.round(inW.value*sourceCanvas.height/sourceCanvas.width)||'';
        editing=false;
    });
    inH.addEventListener('input',function(){
        if(editing)return; editing=true;
        if(lock.checked && sourceCanvas.height) inW.value=Math.round(inH.value*sourceCanvas.width/sourceCanvas.height)||'';
        editing=false;
    });
    document.querySelectorAll('.pct-chip').forEach(function(c){
        c.addEventListener('click',function(){
            var p=parseInt(c.dataset.pct,10)/100;
            editing=true;
            inW.value=Math.max(1,Math.round(sourceCanvas.width*p));
            inH.value=Math.max(1,Math.round(sourceCanvas.height*p));
            editing=false;
        });
    });
    $('btnApplyResize').onclick=function(){
        var w=parseInt(inW.value,10), h=parseInt(inH.value,10);
        if(!w||!h||w<1||h<1){ toast('Valid width/height daalo'); return; }
        if(w>12000||h>12000){ toast('Max 12000px allowed'); return; }
        var c=document.createElement('canvas'); c.width=w; c.height=h;
        var ctx=c.getContext('2d'); ctx.imageSmoothingEnabled=true; ctx.imageSmoothingQuality='high';
        ctx.drawImage(sourceCanvas,0,0,w,h);
        pushUndo();
        sourceCanvas=c; baseCanvas=c; resultBlob=null; $('resultBar').style.display='none';
        initCropper(); syncResizeInputs(); refreshPreview(); setActiveFx(null);
        toast('Resized to '+w+' × '+h+' px');
    };

    /* ---------- encode helpers ---------- */
    function canvasToBlob(canvas,mime,q){
        return new Promise(function(res){ canvas.toBlob(function(b){res(b);},mime,q); });
    }
    var NO_ALPHA={'image/jpeg':1,'image/gif':1,'image/bmp':1};   // formats jinhe bg fill chahiye
    var QUALITY_FMT={'image/jpeg':1,'image/webp':1,'image/avif':1}; // jinpe quality binary-search chalti hai

    async function encodeMax(canvas,mime,q,bg){
        var tmp=document.createElement('canvas'); tmp.width=canvas.width; tmp.height=canvas.height;
        var ctx=tmp.getContext('2d');
        if(NO_ALPHA[mime]){ ctx.fillStyle=bg||'#ffffff'; ctx.fillRect(0,0,tmp.width,tmp.height); }
        ctx.imageSmoothingEnabled=true; ctx.imageSmoothingQuality='high';
        ctx.drawImage(canvas,0,0);
        if(mime==='image/avif'){
            var b=await canvasToBlob(tmp,mime,q);
            return (b&&b.type==='image/avif')?b:null;
        }
        return canvasToBlob(tmp,mime,q);
    }

    /* --- GIF via gif.js (lazy loaded, worker blob-se banate hain taaki CDN chale) --- */
    var gifReady=null, GIF_WORKER=null;
    function ensureGif(){
        if(gifReady) return gifReady;
        gifReady=(async function(){
            await loadScript('https://cdnjs.cloudflare.com/ajax/libs/gif.js/0.2.0/gif.js');
            var wsrc=await (await fetch('https://cdnjs.cloudflare.com/ajax/libs/gif.js/0.2.0/gif.worker.js')).text();
            GIF_WORKER=URL.createObjectURL(new Blob([wsrc],{type:'application/javascript'}));
        })();
        return gifReady;
    }
    async function encodeGif(canvas,bg){
        await ensureGif();
        var tmp=document.createElement('canvas'); tmp.width=canvas.width; tmp.height=canvas.height;
        var ctx=tmp.getContext('2d'); ctx.fillStyle=bg||'#ffffff'; ctx.fillRect(0,0,tmp.width,tmp.height);
        ctx.drawImage(canvas,0,0);
        return new Promise(function(res,rej){
            var g=new GIF({workers:2,quality:10,workerScript:GIF_WORKER,width:tmp.width,height:tmp.height});
            g.addFrame(tmp,{delay:0,copy:true});
            g.on('finished',res); g.on('abort',function(){rej(new Error('gif aborted'));});
            g.render();
        });
    }

    /* --- BMP 24-bit encoder (bottom-up rows, 4-byte padding) --- */
    function encodeBmp(canvas,bg){
        var tmp=document.createElement('canvas'); tmp.width=canvas.width; tmp.height=canvas.height;
        var ctx=tmp.getContext('2d'); ctx.fillStyle=bg||'#ffffff'; ctx.fillRect(0,0,tmp.width,tmp.height);
        ctx.drawImage(canvas,0,0);
        var w=tmp.width,h=tmp.height,d=ctx.getImageData(0,0,w,h).data;
        var rowSize=Math.ceil(w*3/4)*4, imgSize=rowSize*h, fileSize=54+imgSize;
        var v=new DataView(new ArrayBuffer(fileSize));
        v.setUint8(0,0x42); v.setUint8(1,0x4D);
        v.setUint32(2,fileSize,true); v.setUint32(10,54,true);
        v.setUint32(14,40,true); v.setInt32(18,w,true); v.setInt32(22,h,true);
        v.setUint16(26,1,true); v.setUint16(28,24,true); v.setUint32(34,imgSize,true);
        v.setInt32(38,2835,true); v.setInt32(42,2835,true);
        for(var y=0;y<h;y++){
            var o=54+(h-1-y)*rowSize;
            for(var x=0;x<w;x++){ var i=(y*w+x)*4; v.setUint8(o++,d[i+2]); v.setUint8(o++,d[i+1]); v.setUint8(o++,d[i]); }
        }
        return new Blob([v.buffer],{type:'image/bmp'});
    }

    /* --- ICO encoder: PNG ko ICO container mein embed (transparency preserved) --- */
    async function encodeIco(canvas){
        var c=canvas;
        if(c.width>256||c.height>256) c=scaledCanvas(c,Math.min(256/c.width,256/c.height));
        var pngBuf=await (await canvasToBlob(c,'image/png')).arrayBuffer();
        var v=new DataView(new ArrayBuffer(22+pngBuf.byteLength));
        v.setUint16(2,1,true); v.setUint16(4,1,true);
        v.setUint8(6,c.width===256?0:c.width); v.setUint8(7,c.height===256?0:c.height);
        v.setUint16(10,1,true); v.setUint16(12,32,true);
        v.setUint32(14,pngBuf.byteLength,true); v.setUint32(18,22,true);
        new Uint8Array(v.buffer,22).set(new Uint8Array(pngBuf));
        return {blob:new Blob([v.buffer],{type:'image/x-icon'}),canvas:c};
    }

    /* Unified encoder: returns {blob, canvas} (canvas final dims ke liye) */
    async function encodeDispatch(canvas,mime,q,bg){
        if(mime==='image/gif')  return {blob:await encodeGif(canvas,bg),canvas:canvas};
        if(mime==='image/bmp')  return {blob:encodeBmp(canvas,bg),canvas:canvas};
        if(mime==='image/x-icon') return encodeIco(canvas);
        var b=await encodeMax(canvas,mime,q,bg);
        return {blob:b,canvas:canvas};
    }

    function scaledCanvas(canvas,scale){
        var c=document.createElement('canvas');
        c.width=Math.max(1,Math.round(canvas.width*scale));
        c.height=Math.max(1,Math.round(canvas.height*scale));
        var ctx=c.getContext('2d'); ctx.imageSmoothingEnabled=true; ctx.imageSmoothingQuality='high';
        ctx.drawImage(canvas,0,0,c.width,c.height);
        return c;
    }

    /* ---------- compress ---------- */
    var inTarget=$('inTarget'), inUnit=$('inUnit'), inFormat=$('inFormat'), inQ=$('inQuality'), inBg=$('inBg');
    inQ.addEventListener('input',function(){ $('qVal').textContent=inQ.value+'%'; });
    document.querySelectorAll('.target-chip').forEach(function(c){
        c.addEventListener('click',function(){ inUnit.value='1024'; inTarget.value=c.dataset.kb; });
    });

    var FMT_NOTES={
        'image/jpeg':'Sabse chhota size. Transparency nahi — bg color fill hoga.',
        'image/png':'✓ Transparency preserved. Lossless — isliye size bada ho sakta hai.',
        'image/webp':'✓ Transparency preserved. JPG se ~30% chhota, same quality.',
        'image/gif':'GIF ban jayega (single frame). 256 colors — photos ke liye JPG better.',
        'image/bmp':'BMP — uncompressed, size bohot bada hoga. Sirf zarurat ho toh.',
        'image/x-icon':'✓ Transparency preserved. Favicon ke liye — max 256×256 auto-fit.',
        'image/avif':'✓ Transparency preserved. Sabse naya format — purane browsers nahi khol payenge.'
    };
    function syncFormatUI(){
        var m=inFormat.value;
        $('formatNote').textContent=FMT_NOTES[m]||'';
        $('bgRow').style.display=NO_ALPHA[m]?'block':'none';
        $('qualityRow').style.display=QUALITY_FMT[m]?'block':'none';
    }
    inFormat.addEventListener('change',syncFormatUI); syncFormatUI();

    // Govt-form style presets: resize + target size ek saath
    document.querySelectorAll('.preset-chip').forEach(function(c){
        c.addEventListener('click',function(){
            var w=parseInt(c.dataset.w,10), h=parseInt(c.dataset.h,10), kb=parseInt(c.dataset.kb,10);
            var cv=document.createElement('canvas'); cv.width=w; cv.height=h;
            var ctx=cv.getContext('2d'); ctx.imageSmoothingEnabled=true; ctx.imageSmoothingQuality='high';
            ctx.drawImage(sourceCanvas,0,0,w,h);
            pushUndo();
            sourceCanvas=cv; baseCanvas=cv; resultBlob=null; $('resultBar').style.display='none';
            initCropper(); syncResizeInputs(); refreshPreview(); setActiveFx(null);
            inUnit.value='1024'; inTarget.value=kb;
            if(inFormat.value!=='image/jpeg'){ inFormat.value='image/jpeg'; syncFormatUI(); }
            toast('Preset applied: '+w+'×'+h+' px, target '+kb+'KB — ab Compress dabao');
        });
    });

    function setStatus(msg){ $('compressStatus').innerHTML=msg; }

    function showResult(blob,mime,dims){
        resultBlob=blob;
        if(previewUrl){ URL.revokeObjectURL(previewUrl); }
        previewUrl=URL.createObjectURL(blob);
        previewImg.src=previewUrl;
        $('resultBar').style.display='block';
        $('finalSize').textContent=fmt(blob.size);
        $('finalDim').textContent=dims+' · '+ext(mime).toUpperCase();
        if(origSize>0){
            var saved=Math.round((1-blob.size/origSize)*100);
            $('savedBadge').style.display='block';
            $('savedPct').textContent=(saved>=0?saved+'%':'+'+Math.abs(saved)+'%');
        }
    }

    $('btnCompressQuality').onclick=async function(){
        var mime=inFormat.value, q=inQ.value/100, bg=inBg.value;
        setStatus('<i class="fas fa-spinner fa-spin"></i> Processing…');
        var r=await encodeDispatch(sourceCanvas,mime,q,bg);
        if(!r.blob){ setStatus('Ye format aapke browser mein support nahi hai — JPG/WebP try karo'); return; }
        showResult(r.blob,mime,r.canvas.width+' × '+r.canvas.height);
        setStatus('Done — '+ext(mime).toUpperCase()+(QUALITY_FMT[mime]?' @ quality '+inQ.value+'%':''));
    };

    $('btnCompressTarget').onclick=async function(){
        var target=parseFloat(inTarget.value)*parseInt(inUnit.value,10);
        if(!target||target<=0){ toast('Target size daalo (e.g. 100 KB)'); return; }
        var mime=inFormat.value, bg=inBg.value;
        setStatus('<i class="fas fa-spinner fa-spin"></i> Compressing…');

        // AVIF browser support check — nahi toh aage null crash hoga
        if(mime==='image/avif' && !(await encodeMax(sourceCanvas,mime,0.5,bg))){
            setStatus('AVIF aapke browser mein support nahi hai — WebP try karo (almost same fayda)');
            return;
        }

        // Lossless formats (PNG/GIF/BMP/ICO): quality search nahi — sirf dimensions shrink
        if(!QUALITY_FMT[mime]){
            var c=sourceCanvas, r=await encodeDispatch(c,mime,1,bg), tries=0;
            while(r.blob && r.blob.size>target && tries<12 && c.width>16){
                c=scaledCanvas(c,Math.sqrt(target/r.blob.size)*0.92);
                r=await encodeDispatch(c,mime,1,bg); tries++;
            }
            if(!r.blob){ setStatus('Ye format browser mein support nahi hai'); return; }
            showResult(r.blob,mime,c.width+' × '+c.height);
            setStatus(r.blob.size<=target?'Done — target achieved ✓':'Nearest possible: '+fmt(r.blob.size)+' (lossless format hai — JPG/WebP try karo)');
            return;
        }

        // 1) binary search on quality at full size
        var lo=0.01, hi=1.0, best=null, i, b;
        for(i=0;i<8;i++){
            var mid=(lo+hi)/2;
            b=await encodeMax(sourceCanvas,mime,mid,bg);
            if(b && b.size<=target){ best={blob:b,q:mid}; lo=mid; }
            else if(b) hi=mid;
            else { hi=mid; }
        }
        if(best){ showResult(best.blob,mime,sourceCanvas.width+' × '+sourceCanvas.height); setStatus('Done — '+fmt(best.blob.size)+' @ quality '+Math.round(best.q*100)+'% ✓'); return; }

        // 2) quality floor pe bhi bada hai → dimensions shrink karke retry
        var scale=0.9, c=sourceCanvas, attempts=0;
        while(attempts<14){
            c=scaledCanvas(sourceCanvas,scale);
            lo=0.01; hi=1.0; best=null;
            for(i=0;i<7;i++){
                var m=(lo+hi)/2;
                b=await encodeMax(c,mime,m,bg);
                if(b && b.size<=target){ best={blob:b,q:m}; lo=m; } else hi=m;
            }
            if(best){ showResult(best.blob,mime,c.width+' × '+c.height); setStatus('Done — '+fmt(best.blob.size)+' @ '+c.width+'×'+c.height+'px, q'+Math.round(best.q*100)+'% ✓'); return; }
            scale*=0.75; attempts++;
            setStatus('<i class="fas fa-spinner fa-spin"></i> Chhota kar rahe hain… '+Math.round(scale*100)+'%');
        }
        // last resort: smallest possible
        b=await encodeMax(c,mime,0.01,bg);
        showResult(b,mime,c.width+' × '+c.height);
        setStatus('Nearest possible: '+fmt(b.size)+' — itna chhota target is image ke liye mushkil hai');
    };

    /* ---------- download ---------- */
    $('btnDownload').onclick=function(){
        if(!resultBlob){ toast('Pehle compress/process karo'); return; }
        var a=document.createElement('a');
        a.href=URL.createObjectURL(resultBlob);
        a.download=resultName+'-believoo.'+ext(resultBlob.type);
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(function(){URL.revokeObjectURL(a.href);},4000);
        toast('Download started ⬇');
    };
    $('btnCompare').onclick=function(){ switchTab('compress'); previewArea.scrollIntoView({behavior:'smooth',block:'center'}); };

    /* ---------- upload events ---------- */
    dropZone.addEventListener('click',function(){ fileInput.click(); });
    fileInput.addEventListener('change',function(){ if(fileInput.files[0]) loadFile(fileInput.files[0]); fileInput.value=''; });
    ['dragover','dragenter'].forEach(function(ev){ dropZone.addEventListener(ev,function(e){ e.preventDefault(); dropZone.classList.add('drag'); }); });
    ['dragleave','drop'].forEach(function(ev){ dropZone.addEventListener(ev,function(e){ e.preventDefault(); dropZone.classList.remove('drag'); }); });
    dropZone.addEventListener('drop',function(e){ if(e.dataTransfer.files[0]) loadFile(e.dataTransfer.files[0]); });
    document.addEventListener('paste',function(e){
        var items=(e.clipboardData||{}).items||[];
        for(var i=0;i<items.length;i++){ if(items[i].type.indexOf('image')===0){ loadFile(items[i].getAsFile()); break; } }
    });
    $('btnNew').onclick=function(){
        if(cropper){cropper.destroy();cropper=null;}
        editor.style.display='none'; dropZone.style.display='block';
        resultBlob=null; $('resultBar').style.display='none';
    };

    // responsive: stack sidebar under canvas on small screens
    function fixGrid(){ $('editorGrid').style.gridTemplateColumns=window.innerWidth<860?'1fr':'1fr 320px'; }
    window.addEventListener('resize',fixGrid); fixGrid();
})();
</script>
</x-layouts.believoo>

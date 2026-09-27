/**
 * Reusable Live Camera Capture & Proof Upload Controller
 * LMS v2.0 - Golden Trust Finance
 * 
 * Note: Only creates and mounts the modal when window.openCameraModal() is called.
 * Completely cleans up and removes the modal from DOM on close.
 */
(function(window) {
    'use strict';

    class CameraCaptureModal {
        constructor() {
            this.stream = null;
            this.currentFacingMode = 'environment'; // default to rear camera
            this.targetFileInput = null;
            this.targetHiddenInput = null;
            this.targetPreviewImg = null;
            this.onCaptureCallback = null;
            this.capturedDataUrl = null;
            this.modalElement = null;
            this.autoSubmit = false;
            this.targetForm = null;
        }

        buildModalDOM() {
            // If already exists in DOM, remove old one first
            const existing = document.getElementById('global-camera-modal');
            if (existing) {
                existing.remove();
            }

            const modalHtml = `
            <div id="global-camera-modal" class="fixed inset-0 z-[9999] items-center justify-center bg-slate-950/85 backdrop-blur-md p-3 sm:p-6" style="display: flex !important;">
                <div class="relative w-full max-w-lg rounded-3xl border border-slate-700 bg-slate-900 text-white shadow-2xl overflow-hidden flex flex-col max-h-[95vh] animate-in fade-in zoom-in-95 duration-200">
                    
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800 bg-slate-900/90">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400">
                                <i class="fa-solid fa-camera text-sm"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-white tracking-wide">Live Camera Proof Capture</h3>
                                <p class="text-[11px] text-slate-400 font-medium" id="camera-status-msg">Align document or proof in frame</p>
                            </div>
                        </div>
                        <button type="button" id="btn-close-camera" class="rounded-xl p-2 text-slate-400 hover:text-white hover:bg-slate-800 transition">
                            <i class="fa-solid fa-xmark text-base"></i>
                        </button>
                    </div>

                    <!-- Viewfinder / Preview Container -->
                    <div class="relative bg-black flex-1 min-h-[300px] sm:min-h-[360px] flex items-center justify-center overflow-hidden">
                        
                        <!-- Video Stream Element -->
                        <video id="camera-video" autoplay playsinline muted class="w-full h-full object-contain max-h-[60vh]"></video>
                        
                        <!-- Canvas for snapshot extraction (hidden) -->
                        <canvas id="camera-canvas" class="hidden" style="display: none;"></canvas>
                        
                        <!-- Captured Image Preview Element -->
                        <img id="camera-captured-preview" class="hidden w-full h-full object-contain max-h-[60vh]" style="display: none;" alt="Captured Proof">

                        <!-- Viewfinder Guidelines / Reticle Overlay (visible during live feed) -->
                        <div id="camera-overlay-grid" class="absolute inset-4 pointer-events-none border-2 border-dashed border-amber-400/40 rounded-2xl flex flex-col justify-between p-3">
                            <div class="flex justify-between">
                                <span class="w-4 h-4 border-t-2 border-l-2 border-amber-400 rounded-tl"></span>
                                <span class="w-4 h-4 border-t-2 border-r-2 border-amber-400 rounded-tr"></span>
                            </div>
                            <div class="text-center">
                                <span class="bg-black/60 backdrop-blur-sm text-[10px] font-bold text-amber-300 px-3 py-1 rounded-full uppercase tracking-wider">
                                    <i class="fa-solid fa-expand mr-1"></i> Center Proof Here
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="w-4 h-4 border-b-2 border-l-2 border-amber-400 rounded-bl"></span>
                                <span class="w-4 h-4 border-b-2 border-r-2 border-amber-400 rounded-br"></span>
                            </div>
                        </div>

                        <!-- Flash Animation Effect -->
                        <div id="camera-flash" class="absolute inset-0 bg-white opacity-0 pointer-events-none transition-opacity duration-200"></div>

                        <!-- Loading / Error Message -->
                        <div id="camera-loading" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/90 text-center p-6 space-y-3">
                            <div class="animate-spin text-amber-500 text-3xl">
                                <i class="fa-solid fa-circle-notch"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-300">Connecting to Camera...</p>
                            <p class="text-[11px] text-slate-400 max-w-xs">Please allow camera permissions in your browser if prompted.</p>
                            <div class="flex items-center justify-center gap-2 pt-2">
                                <button type="button" id="btn-loading-choose-file" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold rounded-xl shadow cursor-pointer">
                                    <i class="fa-solid fa-folder-open mr-1"></i> Choose File Instead
                                </button>
                                <button type="button" id="btn-loading-cancel" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 rounded-xl cursor-pointer">Cancel</button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Controls / Action Bar -->
                    <div class="px-5 py-4 border-t border-slate-800 bg-slate-900 flex items-center justify-between gap-3">
                        
                        <!-- Camera Switch Button (front / back) -->
                        <div class="flex items-center gap-2">
                            <button type="button" id="btn-switch-camera" class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-700 transition flex items-center gap-1.5" title="Switch between Front and Back Camera">
                                <i class="fa-solid fa-camera-rotate text-amber-400"></i>
                                <span class="hidden sm:inline">Flip Camera</span>
                            </button>
                        </div>

                        <!-- Live Capture State Action Buttons -->
                        <div id="controls-live" class="flex items-center gap-3">
                            <button type="button" id="btn-take-photo" class="rounded-full bg-amber-500 hover:bg-amber-400 text-slate-950 px-6 py-2.5 text-xs font-black shadow-lg shadow-amber-500/25 transition transform active:scale-95 flex items-center gap-2 cursor-pointer">
                                <i class="fa-solid fa-camera text-sm"></i>
                                <span>Capture Proof</span>
                            </button>
                        </div>

                        <!-- Review State Action Buttons -->
                        <div id="controls-review" class="hidden flex items-center gap-2" style="display: none;">
                            <button type="button" id="btn-retake-photo" class="rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-200 px-3.5 py-2 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-rotate-left text-rose-400"></i>
                                <span>Retake</span>
                            </button>
                            <button type="button" id="btn-use-photo" class="rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 px-5 py-2 text-xs font-black shadow-lg shadow-emerald-500/25 transition flex items-center gap-1.5 cursor-pointer">
                                ${this.autoSubmit 
                                    ? '<i class="fa-solid fa-cloud-arrow-up text-sm"></i> <span>Use & Upload Photo</span>' 
                                    : '<i class="fa-solid fa-check text-sm"></i> <span>Use This Proof</span>'
                                }
                            </button>
                        </div>

                    </div>

                </div>
            </div>`;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            this.modalElement = document.getElementById('global-camera-modal');
            this.bindEvents();
        }

        bindEvents() {
            if (!this.modalElement) return;

            const btnClose = document.getElementById('btn-close-camera');
            const btnSwitch = document.getElementById('btn-switch-camera');
            const btnTakePhoto = document.getElementById('btn-take-photo');
            const btnRetake = document.getElementById('btn-retake-photo');
            const btnUsePhoto = document.getElementById('btn-use-photo');

            if (btnClose) btnClose.addEventListener('click', () => this.close());
            if (btnSwitch) btnSwitch.addEventListener('click', () => this.switchFacingMode());
            if (btnTakePhoto) btnTakePhoto.addEventListener('click', () => this.takeSnapshot());
            if (btnRetake) btnRetake.addEventListener('click', () => this.resumeLiveStream());
            if (btnUsePhoto) btnUsePhoto.addEventListener('click', () => this.confirmPhoto());

            // Close when clicking modal backdrop outside card
            this.modalElement.addEventListener('click', (e) => {
                if (e.target === this.modalElement) {
                    this.close();
                }
            });

            // Close on escape key
            this.escapeHandler = (e) => {
                if (e.key === 'Escape') {
                    this.close();
                }
            };
            window.addEventListener('keydown', this.escapeHandler);
        }

        async open(options = {}) {
            this.targetFileInput = options.fileInput || null;
            this.targetHiddenInput = options.hiddenInput || null;
            this.targetPreviewImg = options.previewImg || null;
            this.onCaptureCallback = options.onCapture || null;
            this.autoSubmit = !!options.autoSubmit;
            this.targetForm = options.form || null;
            this.capturedDataUrl = null;

            this.buildModalDOM();
            await this.startStream();
        }

        renderError(title, message) {
            const loading = document.getElementById('camera-loading');
            if (loading) {
                loading.style.display = 'flex';
                loading.classList.remove('hidden');
                loading.innerHTML = `
                    <div class="text-rose-400 text-3xl mb-1"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <p class="text-xs font-bold text-white">${title}</p>
                    <p class="text-[11px] text-slate-400 max-w-xs mt-0.5">${message}</p>
                    <div class="flex items-center justify-center gap-2 mt-3">
                        <button type="button" id="btn-err-choose-file" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold rounded-xl shadow cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-folder-open"></i> Choose Photo File
                        </button>
                        <button type="button" id="btn-err-cancel" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 rounded-xl cursor-pointer">Close</button>
                    </div>
                `;
                const btnChoose = document.getElementById('btn-err-choose-file');
                if (btnChoose && this.targetFileInput) {
                    btnChoose.onclick = () => {
                        const fi = typeof this.targetFileInput === 'string' ? document.getElementById(this.targetFileInput) : this.targetFileInput;
                        this.close();
                        if (fi) fi.click();
                    };
                }
                const btnCancel = document.getElementById('btn-err-cancel');
                if (btnCancel) {
                    btnCancel.onclick = () => this.close();
                }
            }
        }

        async startStream() {
            const loading = document.getElementById('camera-loading');
            const video = document.getElementById('camera-video');
            const overlay = document.getElementById('camera-overlay-grid');
            const preview = document.getElementById('camera-captured-preview');
            const controlsLive = document.getElementById('controls-live');
            const controlsReview = document.getElementById('controls-review');

            if (loading) { loading.style.display = 'flex'; loading.classList.remove('hidden'); }
            if (preview) { preview.style.display = 'none'; preview.classList.add('hidden'); }
            if (video) { video.style.display = 'block'; video.classList.remove('hidden'); }
            if (overlay) { overlay.style.display = 'flex'; overlay.classList.remove('hidden'); }
            if (controlsLive) { controlsLive.style.display = 'flex'; controlsLive.classList.remove('hidden'); }
            if (controlsReview) { controlsReview.style.display = 'none'; controlsReview.classList.add('hidden'); }

            this.stopStream();

            // Setup loading cancel / choose file buttons
            const btnLoadingChoose = document.getElementById('btn-loading-choose-file');
            if (btnLoadingChoose && this.targetFileInput) {
                btnLoadingChoose.onclick = () => {
                    const fi = typeof this.targetFileInput === 'string' ? document.getElementById(this.targetFileInput) : this.targetFileInput;
                    this.close();
                    if (fi) fi.click();
                };
            }
            const btnLoadingCancel = document.getElementById('btn-loading-cancel');
            if (btnLoadingCancel) {
                btnLoadingCancel.onclick = () => this.close();
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.renderError('Camera Access Not Supported', 'Camera requires HTTPS or localhost. You can upload a photo directly using the file picker.');
                return;
            }

            try {
                let stream = null;
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: {
                            facingMode: { ideal: this.currentFacingMode },
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        }
                    });
                } catch (e1) {
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({
                            audio: false,
                            video: {
                                facingMode: this.currentFacingMode
                            }
                        });
                    } catch (e2) {
                        stream = await navigator.mediaDevices.getUserMedia({
                            audio: false,
                            video: true
                        });
                    }
                }

                this.stream = stream;
                if (video) {
                    video.srcObject = this.stream;

                    const hideLoading = () => {
                        if (loading) {
                            loading.style.display = 'none';
                            loading.classList.add('hidden');
                        }
                        const statusEl = document.getElementById('camera-status-msg');
                        const label = this.currentFacingMode === 'environment' ? 'Rear Camera' : 'Front Camera';
                        if (statusEl) statusEl.innerText = `Live Feed Active (${label})`;
                    };

                    video.onloadedmetadata = () => {
                        video.play().then(hideLoading).catch(e => {
                            console.warn('Video play caught:', e);
                            hideLoading();
                        });
                    };
                    video.onplaying = hideLoading;
                    video.oncanplay = hideLoading;

                    if (video.readyState >= 2) {
                        video.play().then(hideLoading).catch(hideLoading);
                    }

                    // Fallback timer: ensure loading is hidden if stream is active
                    setTimeout(() => {
                        if (this.stream && this.stream.active) {
                            hideLoading();
                        }
                    }, 1500);
                }
            } catch (err) {
                console.error('Camera access error:', err);
                let message = 'Please allow camera permissions in your browser or select an image file directly.';
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    message = 'Camera permission was denied in your browser settings. Please allow camera permissions or choose a file.';
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    message = 'No camera device found on this system. Please choose a photo file directly.';
                } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                    message = 'The camera is currently in use by another application. Please close other camera apps and try again.';
                }
                this.renderError('Camera Unavailable', message);
            }
        }

        stopStream() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
        }

        switchFacingMode() {
            this.currentFacingMode = (this.currentFacingMode === 'environment') ? 'user' : 'environment';
            this.startStream();
        }

        takeSnapshot() {
            const video = document.getElementById('camera-video');
            const canvas = document.getElementById('camera-canvas');
            const preview = document.getElementById('camera-captured-preview');
            const flash = document.getElementById('camera-flash');
            const overlay = document.getElementById('camera-overlay-grid');
            const controlsLive = document.getElementById('controls-live');
            const controlsReview = document.getElementById('controls-review');

            if (!video || !video.videoWidth) return;

            if (flash) {
                flash.style.opacity = '1';
                setTimeout(() => { flash.style.opacity = '0'; }, 150);
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            this.capturedDataUrl = canvas.toDataURL('image/jpeg', 0.92);
            if (preview) {
                preview.src = this.capturedDataUrl;
                preview.style.display = 'block';
                preview.classList.remove('hidden');
            }

            if (video) { video.style.display = 'none'; video.classList.add('hidden'); }
            if (overlay) { overlay.style.display = 'none'; overlay.classList.add('hidden'); }
            if (controlsLive) { controlsLive.style.display = 'none'; controlsLive.classList.add('hidden'); }
            if (controlsReview) { controlsReview.style.display = 'flex'; controlsReview.classList.remove('hidden'); }

            const statusEl = document.getElementById('camera-status-msg');
            if (statusEl) {
                statusEl.innerText = this.autoSubmit 
                    ? 'Review captured proof - Click "Use & Upload Photo" to save'
                    : 'Review captured proof - Click "Use This Proof" to attach';
            }
        }

        resumeLiveStream() {
            const video = document.getElementById('camera-video');
            const preview = document.getElementById('camera-captured-preview');
            const overlay = document.getElementById('camera-overlay-grid');
            const controlsLive = document.getElementById('controls-live');
            const controlsReview = document.getElementById('controls-review');

            if (preview) { preview.style.display = 'none'; preview.classList.add('hidden'); }
            if (video) { video.style.display = 'block'; video.classList.remove('hidden'); }
            if (overlay) { overlay.style.display = 'flex'; overlay.classList.remove('hidden'); }
            if (controlsLive) { controlsLive.style.display = 'flex'; controlsLive.classList.remove('hidden'); }
            if (controlsReview) { controlsReview.style.display = 'none'; controlsReview.classList.add('hidden'); }

            const statusEl = document.getElementById('camera-status-msg');
            if (statusEl) statusEl.innerText = 'Align document or proof in frame';
        }

        confirmPhoto() {
            if (!this.capturedDataUrl) return;

            if (this.targetPreviewImg) {
                const el = typeof this.targetPreviewImg === 'string' ? document.getElementById(this.targetPreviewImg) : this.targetPreviewImg;
                if (el) el.src = this.capturedDataUrl;
            }

            if (this.targetHiddenInput) {
                const hiddenEl = typeof this.targetHiddenInput === 'string' ? document.getElementById(this.targetHiddenInput) : this.targetHiddenInput;
                if (hiddenEl) hiddenEl.value = this.capturedDataUrl;
            }

            if (this.targetFileInput) {
                const fileEl = typeof this.targetFileInput === 'string' ? document.getElementById(this.targetFileInput) : this.targetFileInput;
                if (fileEl) {
                    try {
                        const file = this.dataURLtoFile(this.capturedDataUrl, 'camera_proof_' + Date.now() + '.jpg');
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        fileEl.files = dataTransfer.files;
                        fileEl.dispatchEvent(new Event('change', { bubbles: true }));
                    } catch (e) {
                        console.warn('DataTransfer file injection fallback:', e);
                    }
                }
            }

            if (typeof this.onCaptureCallback === 'function') {
                this.onCaptureCallback(this.capturedDataUrl);
            }

            // If autoSubmit is enabled and target form exists, submit form immediately
            if (this.autoSubmit && this.targetForm) {
                const formEl = typeof this.targetForm === 'string' ? document.getElementById(this.targetForm) : this.targetForm;
                if (formEl) {
                    const btnUse = document.getElementById('btn-use-photo');
                    if (btnUse) {
                        btnUse.disabled = true;
                        btnUse.innerHTML = `<i class="fa-solid fa-circle-notch animate-spin text-sm"></i> <span>Uploading Photo...</span>`;
                    }
                    const btnRetake = document.getElementById('btn-retake-photo');
                    if (btnRetake) {
                        btnRetake.disabled = true;
                        btnRetake.classList.add('opacity-50', 'pointer-events-none');
                    }
                    const statusEl = document.getElementById('camera-status-msg');
                    if (statusEl) statusEl.innerText = 'Uploading photo to server...';

                    this.stopStream();

                    try {
                        HTMLFormElement.prototype.submit.call(formEl);
                    } catch (e) {
                        formEl.submit();
                    }
                    return;
                }
            }

            this.close();
        }

        dataURLtoFile(dataurl, filename) {
            const arr = dataurl.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new File([u8arr], filename, { type: mime });
        }

        close() {
            this.stopStream();
            if (this.escapeHandler) {
                window.removeEventListener('keydown', this.escapeHandler);
                this.escapeHandler = null;
            }
            const modal = document.getElementById('global-camera-modal');
            if (modal) {
                modal.remove();
            }
            this.modalElement = null;
            window.cameraModal = null;
        }
    }

    // Helper global function: ONLY instantiates and shows when explicitly clicked
    window.openCameraModal = function(options) {
        if (!window.cameraModal) {
            window.cameraModal = new CameraCaptureModal();
        }
        window.cameraModal.open(options);
    };

})(window);

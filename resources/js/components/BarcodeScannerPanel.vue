<template>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Panel Header -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                    <ScanLine class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 leading-tight">Pemindai Barcode E-ICV</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Scan QR kamera cepat, unggah gambar sertifikat, atau scanner USB</p>
                </div>
            </div>
            <button
                type="button"
                @click="$emit('toggle-sound')"
                :title="soundEnabled ? 'Matikan suara bip' : 'Aktifkan suara bip'"
                class="p-2 rounded-lg border text-slate-500 hover:text-teal-700 hover:bg-teal-50 transition-colors cursor-pointer"
                :class="soundEnabled ? 'border-teal-200 bg-teal-50/50 text-teal-700' : 'border-slate-200 bg-white'"
            >
                <Volume2 v-if="soundEnabled" class="w-4 h-4" />
                <VolumeX v-else class="w-4 h-4" />
            </button>
        </div>

        <!-- Mode Selector Tabs -->
        <div class="p-3 bg-slate-50/80 border-b border-slate-100">
            <div class="grid grid-cols-3 gap-1.5 bg-slate-200/60 p-1 rounded-xl">
                <button
                    type="button"
                    @click="switchMode('camera')"
                    class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                    :class="mode === 'camera'
                        ? 'bg-white text-teal-700 shadow-xs'
                        : 'text-slate-600 hover:text-slate-900'"
                >
                    <Camera class="w-3.5 h-3.5" />
                    <span>Kamera</span>
                </button>
                <button
                    type="button"
                    @click="switchMode('upload')"
                    class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                    :class="mode === 'upload'
                        ? 'bg-white text-teal-700 shadow-xs'
                        : 'text-slate-600 hover:text-slate-900'"
                >
                    <ImageUp class="w-3.5 h-3.5" />
                    <span>Upload Foto</span>
                </button>
                <button
                    type="button"
                    @click="switchMode('manual')"
                    class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                    :class="mode === 'manual'
                        ? 'bg-white text-teal-700 shadow-xs'
                        : 'text-slate-600 hover:text-slate-900'"
                >
                    <Keyboard class="w-3.5 h-3.5" />
                    <span>Scanner USB / URL</span>
                </button>
            </div>
        </div>

        <!-- Mode Body -->
        <div class="p-5">
            <!-- 1. LIVE CAMERA MODE (Ultra-Fast Dual Engine: Native BarcodeDetector + Optimized jsQR) -->
            <div v-show="mode === 'camera'" class="space-y-4">
                <div class="relative rounded-2xl overflow-hidden bg-slate-950 aspect-[4/3] flex items-center justify-center border border-slate-800">
                    <!-- Direct native video element (zero wrapper overhead) -->
                    <video
                        ref="videoEl"
                        playsinline
                        muted
                        autoplay
                        class="w-full h-full object-cover"
                        :class="cameraActive ? 'opacity-100' : 'opacity-0'"
                    ></video>

                    <!-- Live Overlay (Engine Badge, Torch/Zoom, Reticle) -->
                    <div
                        v-if="cameraActive"
                        class="pointer-events-none absolute inset-0 flex flex-col justify-between p-3"
                    >
                        <!-- Top Controls Row -->
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-900/75 text-teal-300 text-[10px] font-bold tracking-wide backdrop-blur-xs border border-teal-400/25"
                            >
                                <Zap class="w-3 h-3 fill-current" />
                                <span>{{ engineLabel }}</span>
                            </span>

                            <div class="flex items-center gap-1.5 pointer-events-auto">
                                <button
                                    v-if="zoomSupported"
                                    type="button"
                                    @click="cycleZoom"
                                    title="Ubah zoom kamera"
                                    class="px-2.5 py-1 rounded-full bg-slate-900/75 hover:bg-slate-900 text-white text-[11px] font-bold backdrop-blur-xs border border-white/20 cursor-pointer"
                                >
                                    {{ currentZoom.toFixed(1) }}x
                                </button>
                                <button
                                    v-if="torchSupported"
                                    type="button"
                                    @click="toggleTorch"
                                    :title="torchOn ? 'Matikan lampu flash' : 'Nyalakan lampu flash'"
                                    class="p-1.5 rounded-full backdrop-blur-xs border transition-colors cursor-pointer"
                                    :class="torchOn
                                        ? 'bg-amber-400 text-slate-950 border-amber-300'
                                        : 'bg-slate-900/75 hover:bg-slate-900 text-white border-white/20'"
                                >
                                    <Flashlight class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Center Reticle (Visual Guide — Scanner detects full frame!) -->
                        <div class="flex items-center justify-center">
                            <div
                                class="relative w-52 h-52 sm:w-56 sm:h-56 transition-transform duration-150"
                                :class="scanFlash ? 'scale-105' : ''"
                            >
                                <span
                                    class="absolute top-0 left-0 w-7 h-7 border-t-4 border-l-4 rounded-tl-xl transition-colors"
                                    :class="scanFlash ? 'border-emerald-400' : 'border-teal-400'"
                                ></span>
                                <span
                                    class="absolute top-0 right-0 w-7 h-7 border-t-4 border-r-4 rounded-tr-xl transition-colors"
                                    :class="scanFlash ? 'border-emerald-400' : 'border-teal-400'"
                                ></span>
                                <span
                                    class="absolute bottom-0 left-0 w-7 h-7 border-b-4 border-l-4 rounded-bl-xl transition-colors"
                                    :class="scanFlash ? 'border-emerald-400' : 'border-teal-400'"
                                ></span>
                                <span
                                    class="absolute bottom-0 right-0 w-7 h-7 border-b-4 border-r-4 rounded-br-xl transition-colors"
                                    :class="scanFlash ? 'border-emerald-400' : 'border-teal-400'"
                                ></span>
                                <!-- Animated Laser Line -->
                                <div
                                    v-if="!loading && !paused"
                                    class="absolute inset-x-2 h-0.5 bg-gradient-to-r from-transparent via-teal-400 to-transparent shadow-[0_0_12px_#2dd4bf] animate-scan-laser"
                                ></div>
                            </div>
                        </div>

                        <!-- Bottom Status Pill -->
                        <div class="flex justify-center">
                            <span class="px-3 py-1 rounded-full bg-slate-900/75 text-white text-[11px] font-medium backdrop-blur-xs border border-white/15">
                                <template v-if="loading">Memverifikasi data SINKARKES...</template>
                                <template v-else-if="paused">Hasil verifikasi ditampilkan</template>
                                <template v-else>Arahkan QR Code E-ICV ke kamera (deteksi otomatis)</template>
                            </span>
                        </div>
                    </div>

                    <!-- Idle Camera Placeholder -->
                    <div
                        v-if="!cameraActive"
                        class="absolute inset-0 flex flex-col items-center justify-center text-center p-6 bg-gradient-to-b from-slate-900 to-slate-950"
                    >
                        <div class="w-16 h-16 rounded-2xl bg-teal-500/15 border border-teal-400/30 flex items-center justify-center text-teal-300 mb-3">
                            <QrCode class="w-8 h-8" />
                        </div>
                        <p class="text-sm font-semibold text-white">Kamera Pemindai Cepat Siap</p>
                        <p class="text-xs text-slate-400 mt-1 max-w-xs">
                            Mendukung akselerasi hardware, fokus otomatis, dan deteksi QR menyeluruh untuk hasil scan lebih cepat.
                        </p>
                        <button
                            type="button"
                            @click="startCamera()"
                            :disabled="cameraStarting"
                            class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-teal-500 hover:bg-teal-400 disabled:opacity-60 text-white text-xs font-bold shadow-lg shadow-teal-950/40 transition-all cursor-pointer"
                        >
                            <Loader2 v-if="cameraStarting" class="w-4 h-4 animate-spin" />
                            <Camera v-else class="w-4 h-4" />
                            <span>{{ cameraStarting ? 'Membuka Kamera...' : 'Aktifkan Kamera' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Camera Controls -->
                <div v-if="cameraActive || cameras.length > 1" class="flex flex-wrap items-center gap-2">
                    <select
                        v-if="cameras.length > 1"
                        v-model="selectedCameraId"
                        @change="restartWithSelectedCamera"
                        class="flex-1 min-w-[160px] text-xs rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
                    >
                        <option v-for="(cam, i) in cameras" :key="cam.deviceId" :value="cam.deviceId">
                            {{ cam.label || 'Kamera ' + (i + 1) }}
                        </option>
                    </select>
                    <button
                        v-if="cameraActive"
                        type="button"
                        @click="stopCamera"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition-colors cursor-pointer"
                    >
                        <Square class="w-3.5 h-3.5 fill-current" />
                        <span>Hentikan Kamera</span>
                    </button>
                </div>

                <!-- Camera Error Notice -->
                <div
                    v-if="cameraError"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-900 space-y-2"
                >
                    <div class="flex items-start gap-2">
                        <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="font-semibold">Kamera tidak dapat diakses</p>
                            <p class="text-amber-800 mt-0.5">{{ cameraError }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2 pl-6">
                        <button
                            type="button"
                            @click="switchMode('upload')"
                            class="text-xs font-semibold text-teal-700 underline hover:text-teal-900 cursor-pointer"
                        >
                            Upload Gambar Sertifikat
                        </button>
                        <span class="text-amber-400">•</span>
                        <button
                            type="button"
                            @click="switchMode('manual')"
                            class="text-xs font-semibold text-teal-700 underline hover:text-teal-900 cursor-pointer"
                        >
                            Gunakan Scanner USB / URL
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. UPLOAD IMAGE MODE -->
            <div v-if="mode === 'upload'" class="space-y-4">
                <div
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                    @click="fileInput?.click()"
                    class="relative rounded-2xl border-2 border-dashed p-6 text-center transition-all cursor-pointer"
                    :class="isDragging
                        ? 'border-teal-500 bg-teal-50/70'
                        : 'border-slate-300 hover:border-teal-400 hover:bg-slate-50/80 bg-slate-50/40'"
                >
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="handleFileSelect"
                    />

                    <div v-if="uploadPreview" class="space-y-3">
                        <div class="relative mx-auto max-h-56 overflow-hidden rounded-xl border border-slate-200 bg-white inline-block shadow-xs">
                            <img :src="uploadPreview" alt="Pratinjau Sertifikat E-ICV" class="max-h-56 w-auto object-contain mx-auto" />
                            <div
                                v-if="decodingImage"
                                class="absolute inset-0 bg-slate-900/60 backdrop-blur-[1px] flex flex-col items-center justify-center text-white gap-2"
                            >
                                <Loader2 class="w-6 h-6 animate-spin text-teal-300" />
                                <span class="text-xs font-semibold">Membaca QR Code...</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">Klik atau seret gambar lain untuk memindai ulang</p>
                    </div>

                    <div v-else class="py-4 space-y-2.5">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100">
                            <ImageUp class="w-7 h-7" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Unggah Foto / Screenshot E-ICV</p>
                            <p class="text-xs text-slate-500 mt-1">
                                Seret file PNG/JPG ke sini, klik untuk memilih file, atau tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-mono text-[10px]">Ctrl+V</kbd>
                            </p>
                        </div>
                        <span class="inline-block text-[11px] font-medium text-teal-700 bg-teal-50 px-3 py-1 rounded-full">
                            Mendukung foto full sertifikat E-ICV maupun crop QR Code
                        </span>
                    </div>
                </div>

                <div
                    v-if="uploadError"
                    class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800 flex items-start gap-2"
                >
                    <AlertCircle class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                    <span>{{ uploadError }}</span>
                </div>

                <div
                    v-if="lastDecodedFromUpload"
                    class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs"
                >
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Hasil Dekode Barcode:</p>
                    <p class="font-mono text-slate-800 break-all mt-1">{{ lastDecodedFromUpload }}</p>
                </div>
            </div>

            <!-- 3. MANUAL / USB SCANNER MODE -->
            <div v-if="mode === 'manual'" class="space-y-4">
                <form @submit.prevent="submitManual" class="space-y-3">
                    <div>
                        <label for="manual-barcode-input" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Hasil Scan Scanner Barcode USB / Tempel Link E-ICV
                        </label>
                        <div class="relative">
                            <input
                                ref="manualInput"
                                id="manual-barcode-input"
                                v-model="manualUrl"
                                type="text"
                                autocomplete="off"
                                spellcheck="false"
                                placeholder="https://sinkarkes.kemkes.go.id/index.php/welcome/check_document?t=..."
                                class="w-full rounded-xl border border-slate-300 bg-white pl-3.5 pr-9 py-3 text-xs font-mono text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-shadow"
                            />
                            <button
                                v-if="manualUrl"
                                type="button"
                                @click="manualUrl = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 rounded-md text-slate-400 hover:text-slate-700 cursor-pointer"
                                title="Bersihkan"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1.5">
                            Tips: Arahkan kursor di kolom atas lalu tembak QR Code menggunakan barcode scanner USB — sistem otomatis memverifikasi saat tombol Enter dikirim.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="submit"
                            :disabled="loading || !manualUrl.trim()"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
                        >
                            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                            <ShieldCheck v-else class="w-4 h-4" />
                            <span>{{ loading ? 'Memverifikasi...' : 'Verifikasi Keaslian E-ICV' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Collapsible Official 8 Prefixes Rule Reference -->
        <div class="border-t border-slate-100 bg-slate-50/70">
            <button
                type="button"
                @click="showPrefixes = !showPrefixes"
                class="w-full px-5 py-3 flex items-center justify-between text-xs font-semibold text-slate-600 hover:text-teal-700 transition-colors cursor-pointer"
            >
                <span class="flex items-center gap-2">
                    <Shield class="w-3.5 h-3.5 text-teal-600" />
                    <span>Daftar {{ allowedPrefixes.length }} Awalan Link Resmi Kemenkes RI</span>
                </span>
                <ChevronDown
                    class="w-4 h-4 transition-transform duration-200"
                    :class="showPrefixes ? 'rotate-180' : ''"
                />
            </button>

            <div v-if="showPrefixes" class="px-5 pb-4 space-y-1.5">
                <p class="text-[11px] text-slate-500 mb-2">
                    Hasil scan barcode dinyatakan memiliki link <strong>ASLI</strong> hanya jika diawali salah satu URL resmi berikut:
                </p>
                <div
                    v-for="(prefix, idx) in allowedPrefixes"
                    :key="prefix"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-white border border-slate-200/80 text-[11px] font-mono text-slate-700"
                >
                    <span class="w-4 h-4 rounded-full bg-teal-50 text-teal-700 font-sans font-bold text-[10px] flex items-center justify-center shrink-0">
                        {{ idx + 1 }}
                    </span>
                    <span class="break-all">{{ prefix }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import jsQR from 'jsqr';
import {
    AlertCircle,
    AlertTriangle,
    Camera,
    ChevronDown,
    Flashlight,
    ImageUp,
    Keyboard,
    Loader2,
    QrCode,
    ScanLine,
    Shield,
    ShieldCheck,
    Square,
    Volume2,
    VolumeX,
    X,
    Zap,
} from 'lucide-vue-next';
import { decodeQrFromImageFile } from '../utils/qrImageDecoder';
import { playScanBeep } from '../utils/soundFeedback';

const props = defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
    paused: {
        type: Boolean,
        default: false,
    },
    allowedPrefixes: {
        type: Array,
        default: () => [],
    },
    soundEnabled: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['scanned', 'toggle-sound']);

const mode = ref('camera');
const showPrefixes = ref(false);

// Ultra-fast camera state
const videoEl = ref(null);
const cameraActive = ref(false);
const cameraStarting = ref(false);
const cameraError = ref('');
const cameras = ref([]);
const selectedCameraId = ref('');
const engineLabel = ref('Fast QR Scan');
const scanFlash = ref(false);

// Torch & Zoom state
const torchSupported = ref(false);
const torchOn = ref(false);
const zoomSupported = ref(false);
const currentZoom = ref(1);
let zoomMin = 1;
let zoomMax = 1;

// Internal scanner loop handles
let mediaStream = null;
let videoTrack = null;
let nativeDetector = null;
let rafId = null;
let detectingFrame = false;
let lastFrameTime = 0;
let frameCounter = 0;
let offscreenCanvas = null;
let offscreenCtx = null;
let lastScannedText = '';
let lastScannedAt = 0;

// Upload state
const fileInput = ref(null);
const isDragging = ref(false);
const decodingImage = ref(false);
const uploadPreview = ref('');
const uploadError = ref('');
const lastDecodedFromUpload = ref('');

// Manual / USB scanner state
const manualInput = ref(null);
const manualUrl = ref('');

async function initNativeDetector() {
    if (nativeDetector) return nativeDetector;
    if (typeof window === 'undefined' || !('BarcodeDetector' in window)) return null;

    try {
        const supported = await window.BarcodeDetector.getSupportedFormats();
        const preferred = ['qr_code', 'data_matrix', 'code_128', 'code_39', 'ean_13', 'pdf417'];
        const formats = preferred.filter((f) => supported.includes(f));
        if (formats.includes('qr_code')) {
            nativeDetector = new window.BarcodeDetector({ formats });
            return nativeDetector;
        }
    } catch {
        // Fallback to optimized jsQR
    }
    return null;
}

async function switchMode(newMode) {
    if (mode.value === newMode) return;
    if (mode.value === 'camera' && cameraActive.value) {
        await stopCamera();
    }
    mode.value = newMode;
    if (newMode === 'manual') {
        await nextTick();
        manualInput.value?.focus();
    }
}

async function startCamera(deviceId = '') {
    cameraError.value = '';
    cameraStarting.value = true;

    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error(
                'Browser tidak mengizinkan akses kamera (pastikan situs diakses melalui HTTPS).'
            );
        }

        // Single-step fast stream open (no redundant double-open!)
        const videoConstraints = deviceId
            ? {
                  deviceId: { exact: deviceId },
                  width: { ideal: 1280 },
                  height: { ideal: 720 },
                  frameRate: { ideal: 30 },
              }
            : {
                  facingMode: { ideal: 'environment' },
                  width: { ideal: 1280 },
                  height: { ideal: 720 },
                  frameRate: { ideal: 30 },
              };

        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: videoConstraints,
            audio: false,
        });

        videoTrack = mediaStream.getVideoTracks()[0] || null;

        // Apply hardware continuous autofocus & detect torch / zoom support
        applyOptimalTrackSettings(videoTrack);

        const video = videoEl.value;
        if (!video) {
            throw new Error('Elemen video kamera tidak tersedia.');
        }

        video.srcObject = mediaStream;
        await video.play();

        // Populate camera dropdown AFTER permission is granted (instant, no extra open!)
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            cameras.value = devices.filter((d) => d.kind === 'videoinput');
            const activeDeviceId = videoTrack?.getSettings?.().deviceId;
            if (activeDeviceId) {
                selectedCameraId.value = activeDeviceId;
            } else if (!selectedCameraId.value && cameras.value.length > 0) {
                selectedCameraId.value = cameras.value[0].deviceId;
            }
        } catch {
            // Non-fatal
        }

        const detector = await initNativeDetector();
        engineLabel.value = detector ? 'Hardware Fast Scan' : 'Fast QR Engine';

        if (!detector) {
            offscreenCanvas = document.createElement('canvas');
            offscreenCtx = offscreenCanvas.getContext('2d', { willReadFrequently: true });
        }

        cameraActive.value = true;
        frameCounter = 0;
        lastFrameTime = 0;
        scheduleNextFrame();
    } catch (err) {
        cameraError.value = mapCameraError(err);
        await cleanupCamera();
    } finally {
        cameraStarting.value = false;
    }
}

function applyOptimalTrackSettings(track) {
    torchSupported.value = false;
    torchOn.value = false;
    zoomSupported.value = false;
    currentZoom.value = 1;

    if (!track || typeof track.getCapabilities !== 'function') return;

    try {
        const caps = track.getCapabilities();
        const advanced = [];

        if (Array.isArray(caps.focusMode) && caps.focusMode.includes('continuous')) {
            advanced.push({ focusMode: 'continuous' });
        }

        if (caps.torch) {
            torchSupported.value = true;
        }

        if (caps.zoom && typeof caps.zoom.max === 'number' && caps.zoom.max > 1.2) {
            zoomSupported.value = true;
            zoomMin = Math.max(1, caps.zoom.min || 1);
            zoomMax = Math.min(4, caps.zoom.max);
            currentZoom.value = track.getSettings?.().zoom || zoomMin;
        }

        if (advanced.length > 0 && typeof track.applyConstraints === 'function') {
            track.applyConstraints({ advanced }).catch(() => {});
        }
    } catch {
        // Capabilities optional per browser
    }
}

async function toggleTorch() {
    if (!videoTrack || !torchSupported.value) return;
    try {
        const nextState = !torchOn.value;
        await videoTrack.applyConstraints({ advanced: [{ torch: nextState }] });
        torchOn.value = nextState;
    } catch {
        // Ignore unsupported torch toggle
    }
}

async function cycleZoom() {
    if (!videoTrack || !zoomSupported.value) return;
    const steps = [zoomMin, Math.min(1.5, zoomMax), Math.min(2.2, zoomMax)].filter(
        (v, idx, arr) => idx === 0 || v > arr[idx - 1] + 0.1
    );
    const curIdx = steps.findIndex((s) => Math.abs(s - currentZoom.value) < 0.2);
    const nextZoom = steps[(curIdx + 1) % steps.length] || zoomMin;
    try {
        await videoTrack.applyConstraints({ advanced: [{ zoom: nextZoom }] });
        currentZoom.value = nextZoom;
    } catch {
        // Ignore zoom error
    }
}

function scheduleNextFrame() {
    rafId = requestAnimationFrame(scanFrameLoop);
}

async function scanFrameLoop(timestamp) {
    if (!cameraActive.value) return;

    const video = videoEl.value;
    // Throttle slightly: ~30 FPS for native BarcodeDetector (33ms), ~22 FPS for jsQR (45ms)
    const minInterval = nativeDetector ? 33 : 45;

    if (
        video &&
        video.readyState >= 2 &&
        video.videoWidth > 0 &&
        !props.loading &&
        !props.paused &&
        !detectingFrame &&
        timestamp - lastFrameTime >= minInterval
    ) {
        lastFrameTime = timestamp;
        detectingFrame = true;

        try {
            let decodedText = null;

            if (nativeDetector) {
                // Hardware-accelerated direct video decode (~3-8ms, zero canvas copy)
                const barcodes = await nativeDetector.detect(video);
                if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                    decodedText = barcodes[0].rawValue;
                }
            } else if (offscreenCanvas && offscreenCtx) {
                decodedText = decodeVideoFrameWithJsQr(video);
            }

            if (decodedText) {
                handleDecodedFromCamera(decodedText);
            }
        } catch {
            // Skip transient frame errors
        } finally {
            detectingFrame = false;
        }
    }

    if (cameraActive.value) {
        scheduleNextFrame();
    }
}

function decodeVideoFrameWithJsQr(video) {
    frameCounter += 1;
    const vw = video.videoWidth;
    const vh = video.videoHeight;

    // Alternate between fast center ROI (70% frame) and full frame for instant lock-on
    const isCenterPass = frameCounter % 3 !== 0;
    const sx = isCenterPass ? Math.floor(vw * 0.15) : 0;
    const sy = isCenterPass ? Math.floor(vh * 0.15) : 0;
    const sw = isCenterPass ? Math.floor(vw * 0.7) : vw;
    const sh = isCenterPass ? Math.floor(vh * 0.7) : vh;

    // Cap decode buffer to 560px max side (~8-14ms decode!)
    const maxSide = 560;
    const scale = Math.min(1, maxSide / Math.max(sw, sh));
    const tw = Math.max(1, Math.round(sw * scale));
    const th = Math.max(1, Math.round(sh * scale));

    if (offscreenCanvas.width !== tw || offscreenCanvas.height !== th) {
        offscreenCanvas.width = tw;
        offscreenCanvas.height = th;
    }

    offscreenCtx.drawImage(video, sx, sy, sw, sh, 0, 0, tw, th);
    const imgData = offscreenCtx.getImageData(0, 0, tw, th);

    // Most E-ICV QR codes are dark-on-light (dontInvert is 2x faster); try attemptBoth every 6th frame
    const inversionAttempts = frameCounter % 6 === 0 ? 'attemptBoth' : 'dontInvert';
    const result = jsQR(imgData.data, tw, th, { inversionAttempts });
    return result ? result.data : null;
}

function handleDecodedFromCamera(decodedText) {
    const now = Date.now();
    if (decodedText === lastScannedText && now - lastScannedAt < 3000) return;

    lastScannedText = decodedText;
    lastScannedAt = now;

    // Quick green reticle flash feedback
    scanFlash.value = true;
    setTimeout(() => {
        scanFlash.value = false;
    }, 350);

    if (props.soundEnabled) {
        playScanBeep();
    }

    emit('scanned', {
        url: decodedText,
        method: 'camera',
    });
}

function mapCameraError(err) {
    if (!err) return 'Gagal mengaktifkan kamera.';
    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
        return 'Izin akses kamera ditolak oleh browser. Aktifkan izin kamera pada pengaturan situs browser Anda.';
    }
    if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
        return 'Perangkat kamera tidak ditemukan pada perangkat ini.';
    }
    if (err.name === 'NotReadableError') {
        return 'Kamera sedang digunakan oleh aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
    }
    return err.message || 'Gagal mengaktifkan kamera. Periksa izin kamera browser Anda.';
}

async function stopCamera() {
    await cleanupCamera();
}

async function restartWithSelectedCamera() {
    if (!cameraActive.value) return;
    await cleanupCamera();
    await startCamera(selectedCameraId.value);
}

async function cleanupCamera() {
    cameraActive.value = false;
    detectingFrame = false;

    if (rafId !== null) {
        cancelAnimationFrame(rafId);
        rafId = null;
    }

    if (torchOn.value && videoTrack) {
        try {
            await videoTrack.applyConstraints({ advanced: [{ torch: false }] });
        } catch {
            // Ignore
        }
    }
    torchOn.value = false;
    torchSupported.value = false;
    zoomSupported.value = false;

    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }
    videoTrack = null;

    if (videoEl.value) {
        videoEl.value.srcObject = null;
    }
}

function handleFileSelect(event) {
    const file = event.target.files?.[0];
    if (file) {
        processImageFile(file);
    }
    if (event.target) {
        event.target.value = '';
    }
}

function handleDrop(event) {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file && file.type.startsWith('image/')) {
        processImageFile(file);
    } else {
        uploadError.value = 'Mohon unggah file gambar (PNG, JPG, atau WEBP).';
    }
}

async function processImageFile(file) {
    uploadError.value = '';
    lastDecodedFromUpload.value = '';

    if (uploadPreview.value) {
        URL.revokeObjectURL(uploadPreview.value);
    }
    uploadPreview.value = URL.createObjectURL(file);
    decodingImage.value = true;

    try {
        const decodedText = await decodeQrFromImageFile(file);
        lastDecodedFromUpload.value = decodedText;
        if (props.soundEnabled) {
            playScanBeep();
        }
        emit('scanned', {
            url: decodedText,
            method: 'upload',
        });
    } catch (err) {
        uploadError.value = err?.message || 'QR Code tidak terdeteksi pada gambar.';
    } finally {
        decodingImage.value = false;
    }
}

function submitManual() {
    const trimmed = manualUrl.value.trim();
    if (!trimmed || props.loading) return;
    if (props.soundEnabled) {
        playScanBeep();
    }
    emit('scanned', {
        url: trimmed,
        method: 'manual',
    });
}

function handlePaste(event) {
    if (mode.value !== 'upload') return;
    const items = event.clipboardData?.items;
    if (!items) return;

    for (const item of items) {
        if (item.type.startsWith('image/')) {
            const file = item.getAsFile();
            if (file) {
                event.preventDefault();
                processImageFile(file);
                break;
            }
        }
    }
}

function focusForRescan() {
    lastScannedText = '';
    lastScannedAt = 0;
    if (mode.value === 'manual') {
        manualUrl.value = '';
        nextTick(() => manualInput.value?.focus());
    }
}

defineExpose({ focusForRescan, stopCamera });

onMounted(() => {
    window.addEventListener('paste', handlePaste);
});

onBeforeUnmount(() => {
    window.removeEventListener('paste', handlePaste);
    if (uploadPreview.value) {
        URL.revokeObjectURL(uploadPreview.value);
    }
    cleanupCamera();
});
</script>

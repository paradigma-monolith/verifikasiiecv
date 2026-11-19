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
                    <p class="text-xs text-slate-500 mt-0.5">Scan QR kamera, unggah gambar sertifikat, atau input scanner USB</p>
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
            <!-- 1. LIVE CAMERA MODE -->
            <div v-show="mode === 'camera'" class="space-y-4">
                <div class="relative rounded-2xl overflow-hidden bg-slate-950 aspect-[4/3] flex items-center justify-center border border-slate-800">
                    <!-- html5-qrcode target container -->
                    <div id="eicv-qr-reader" class="w-full h-full"></div>

                    <!-- Scanning Reticle Overlay -->
                    <div
                        v-if="cameraActive"
                        class="pointer-events-none absolute inset-0 flex items-center justify-center"
                    >
                        <div class="relative w-52 h-52 sm:w-56 sm:h-56">
                            <!-- Corner Brackets -->
                            <span class="absolute top-0 left-0 w-7 h-7 border-t-4 border-l-4 border-teal-400 rounded-tl-xl"></span>
                            <span class="absolute top-0 right-0 w-7 h-7 border-t-4 border-r-4 border-teal-400 rounded-tr-xl"></span>
                            <span class="absolute bottom-0 left-0 w-7 h-7 border-b-4 border-l-4 border-teal-400 rounded-bl-xl"></span>
                            <span class="absolute bottom-0 right-0 w-7 h-7 border-b-4 border-r-4 border-teal-400 rounded-br-xl"></span>
                            <!-- Animated Laser Line -->
                            <div class="absolute inset-x-2 h-0.5 bg-gradient-to-r from-transparent via-teal-400 to-transparent shadow-[0_0_12px_#2dd4bf] animate-scan-laser"></div>
                        </div>
                        <div class="absolute bottom-3 inset-x-0 flex justify-center">
                            <span class="px-3 py-1 rounded-full bg-slate-900/75 text-white text-[11px] font-medium backdrop-blur-xs border border-white/15">
                                {{ loading ? 'Memverifikasi data SINKARKES...' : 'Arahkan QR Code E-ICV ke dalam kotak' }}
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
                        <p class="text-sm font-semibold text-white">Kamera Pemindai Siap</p>
                        <p class="text-xs text-slate-400 mt-1 max-w-xs">
                            Klik tombol di bawah untuk mengaktifkan kamera dan memindai QR Code pada sertifikat E-ICV.
                        </p>
                        <button
                            type="button"
                            @click="startCamera"
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
                        <option v-for="cam in cameras" :key="cam.id" :value="cam.id">
                            {{ cam.label || 'Kamera ' + cam.id }}
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
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import {
    AlertCircle,
    AlertTriangle,
    Camera,
    ChevronDown,
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
} from 'lucide-vue-next';
import { decodeQrFromImageFile } from '../utils/qrImageDecoder';
import { playScanBeep } from '../utils/soundFeedback';

const props = defineProps({
    loading: {
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

// Camera state
const cameraActive = ref(false);
const cameraStarting = ref(false);
const cameraError = ref('');
const cameras = ref([]);
const selectedCameraId = ref('');
let html5QrCode = null;
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

async function startCamera() {
    cameraError.value = '';
    cameraStarting.value = true;

    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error(
                'Browser tidak mengizinkan akses kamera (pastikan situs diakses melalui localhost atau HTTPS).'
            );
        }

        const availableCameras = await Html5Qrcode.getCameras();
        cameras.value = availableCameras || [];

        if (!selectedCameraId.value && cameras.value.length > 0) {
            const backCam = cameras.value.find((c) => /back|rear|environment/i.test(c.label || ''));
            selectedCameraId.value = backCam ? backCam.id : cameras.value[0].id;
        }

        html5QrCode = new Html5Qrcode('eicv-qr-reader', {
            verbose: false,
            formatsToSupport: [
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.DATA_MATRIX,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.PDF_417,
            ],
        });

        const cameraTarget = selectedCameraId.value || { facingMode: 'environment' };

        await html5QrCode.start(
            cameraTarget,
            {
                fps: 12,
                qrbox: { width: 220, height: 220 },
                aspectRatio: 1.333,
            },
            (decodedText) => {
                const now = Date.now();
                if (props.loading) return;
                if (decodedText === lastScannedText && now - lastScannedAt < 3500) return;

                lastScannedText = decodedText;
                lastScannedAt = now;

                if (props.soundEnabled) {
                    playScanBeep();
                }

                emit('scanned', {
                    url: decodedText,
                    method: 'camera',
                });
            },
            () => {
                // Frame without code — ignore quietly
            }
        );

        cameraActive.value = true;
    } catch (err) {
        cameraError.value = err?.message || 'Gagal mengaktifkan kamera. Periksa izin kamera browser Anda.';
        await cleanupScannerInstance();
    } finally {
        cameraStarting.value = false;
    }
}

async function stopCamera() {
    await cleanupScannerInstance();
    cameraActive.value = false;
}

async function restartWithSelectedCamera() {
    if (!cameraActive.value) return;
    await stopCamera();
    await startCamera();
}

async function cleanupScannerInstance() {
    if (!html5QrCode) return;
    try {
        if (html5QrCode.isScanning) {
            await html5QrCode.stop();
        }
        await html5QrCode.clear();
    } catch {
        // Ignore cleanup errors
    } finally {
        html5QrCode = null;
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
    cleanupScannerInstance();
});
</script>

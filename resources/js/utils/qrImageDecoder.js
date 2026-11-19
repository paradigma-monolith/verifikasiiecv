import jsQR from 'jsqr';

let cachedImageDetector = null;

async function getNativeBarcodeDetector() {
    if (cachedImageDetector !== null) {
        return cachedImageDetector || null;
    }

    if (typeof window === 'undefined' || !('BarcodeDetector' in window)) {
        cachedImageDetector = false;
        return null;
    }

    try {
        const supported = await window.BarcodeDetector.getSupportedFormats();
        const wanted = ['qr_code', 'data_matrix', 'code_128', 'code_39', 'ean_13', 'pdf417'];
        const formats = wanted.filter((f) => supported.includes(f));
        if (formats.length === 0) {
            cachedImageDetector = false;
            return null;
        }
        cachedImageDetector = new window.BarcodeDetector({ formats });
        return cachedImageDetector;
    } catch {
        cachedImageDetector = false;
        return null;
    }
}

function loadImageFromFile(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            URL.revokeObjectURL(url);
            resolve(img);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('File gambar tidak dapat dibaca.'));
        };
        img.src = url;
    });
}

function tryJsQrOnCrop(img, sx, sy, sw, sh, scale = 1, boostContrast = false, inversionAttempts = 'dontInvert') {
    const targetW = Math.max(1, Math.round(sw * scale));
    const targetH = Math.max(1, Math.round(sh * scale));

    const canvas = document.createElement('canvas');
    canvas.width = targetW;
    canvas.height = targetH;

    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) return null;

    ctx.imageSmoothingEnabled = scale < 1;
    ctx.drawImage(img, sx, sy, sw, sh, 0, 0, targetW, targetH);

    const imageData = ctx.getImageData(0, 0, targetW, targetH);

    if (boostContrast) {
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
            const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
            const val = gray > 140 ? 255 : 0;
            data[i] = val;
            data[i + 1] = val;
            data[i + 2] = val;
        }
    }

    const result = jsQR(imageData.data, targetW, targetH, { inversionAttempts });
    return result ? result.data : null;
}

export async function decodeQrFromImageFile(file) {
    // Pass 0 (Fastest ~10-25ms): Hardware-accelerated native BarcodeDetector API
    const detector = await getNativeBarcodeDetector();
    if (detector && typeof createImageBitmap === 'function') {
        let bitmap = null;
        try {
            bitmap = await createImageBitmap(file);
            const barcodes = await detector.detect(bitmap);
            if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                return barcodes[0].rawValue;
            }
        } catch {
            // Fall through to canvas passes
        } finally {
            bitmap?.close?.();
        }
    }

    const img = await loadImageFromFile(file);
    const w = img.naturalWidth || img.width;
    const h = img.naturalHeight || img.height;

    // Pass 1 (Fast ~25ms): Downscaled full image, single-pass dark-on-light (dontInvert)
    const maxDim = Math.max(w, h);
    const fastScale = maxDim > 1000 ? 1000 / maxDim : 1;
    let decoded = tryJsQrOnCrop(img, 0, 0, w, h, fastScale, false, 'dontInvert');
    if (decoded) return decoded;

    // Pass 2 (Fast E-ICV zone): Top-right quadrant where Kemenkes E-ICV places QR code
    const trSx = Math.floor(w * 0.5);
    const trSw = Math.floor(w * 0.5);
    const trSh = Math.floor(h * 0.55);
    const trScale = Math.max(1, Math.min(2, 800 / Math.max(trSw, trSh, 1)));
    decoded = tryJsQrOnCrop(img, trSx, 0, trSw, trSh, trScale, false, 'dontInvert');
    if (decoded) return decoded;

    // Pass 3: Native full image (if originally > 1000px) with attemptBoth
    if (fastScale < 1 && maxDim <= 2600) {
        decoded = tryJsQrOnCrop(img, 0, 0, w, h, 1, false, 'attemptBoth');
        if (decoded) return decoded;
    }

    // Pass 4: Targeted region crops with contrast boost & inversion fallback
    const regions = [
        [0.5, 0.0, 0.5, 0.55],   // Top-right (contrast/inverted)
        [0.55, 0.1, 0.45, 0.45], // Tighter top-right
        [0.0, 0.0, 0.5, 0.5],    // Top-left
        [0.5, 0.45, 0.5, 0.55],  // Bottom-right
        [0.0, 0.45, 0.5, 0.55],  // Bottom-left
        [0.2, 0.2, 0.6, 0.6],    // Center
    ];

    for (const [rx, ry, rw, rh] of regions) {
        const sx = Math.floor(w * rx);
        const sy = Math.floor(h * ry);
        const sw = Math.floor(w * rw);
        const sh = Math.floor(h * rh);
        const upscale = Math.max(1, Math.min(2.2, 850 / Math.max(sw, sh, 1)));

        decoded = tryJsQrOnCrop(img, sx, sy, sw, sh, upscale, false, 'attemptBoth');
        if (decoded) return decoded;

        decoded = tryJsQrOnCrop(img, sx, sy, sw, sh, upscale, true, 'attemptBoth');
        if (decoded) return decoded;
    }

    // Pass 5: Fallback to Html5Qrcode file scanner (1D barcodes & extra edge cases)
    const tempId = `eicv-file-scan-${Date.now()}`;
    const hiddenEl = document.createElement('div');
    hiddenEl.id = tempId;
    hiddenEl.style.display = 'none';
    document.body.appendChild(hiddenEl);

    try {
        const { Html5Qrcode } = await import('html5-qrcode');
        const scanner = new Html5Qrcode(tempId, { verbose: false });
        const result = await scanner.scanFile(file, false);
        await scanner.clear();
        if (result) return result;
    } catch {
        // Ignore fallback failure
    } finally {
        hiddenEl.remove();
    }

    throw new Error('QR Code atau barcode tidak terdeteksi pada gambar ini. Pastikan QR Code terlihat jelas dan tidak terpotong.');
}

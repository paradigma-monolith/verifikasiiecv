import jsQR from 'jsqr';
import { Html5Qrcode } from 'html5-qrcode';

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

function tryJsQrOnCrop(img, sx, sy, sw, sh, scale = 1, boostContrast = false) {
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

    const result = jsQR(imageData.data, targetW, targetH, {
        inversionAttempts: 'attemptBoth',
    });

    return result ? result.data : null;
}

export async function decodeQrFromImageFile(file) {
    const img = await loadImageFromFile(file);
    const w = img.naturalWidth || img.width;
    const h = img.naturalHeight || img.height;

    // Pass 1: Full image scaled nicely
    const maxDim = Math.max(w, h);
    const initialScale = maxDim > 1400 ? 1400 / maxDim : 1;
    let decoded = tryJsQrOnCrop(img, 0, 0, w, h, initialScale, false);
    if (decoded) return decoded;

    // Pass 2: Full native resolution (if scaled down above)
    if (initialScale < 1 && maxDim <= 3000) {
        decoded = tryJsQrOnCrop(img, 0, 0, w, h, 1, false);
        if (decoded) return decoded;
    }

    // Pass 3: Targeted regions (top-right first since Kemenkes E-ICV places QR code at top-right)
    const regions = [
        [0.5, 0.0, 0.5, 0.55],   // Top-right (E-ICV QR zone)
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
        const upscale = Math.min(2.2, 900 / Math.max(sw, sh, 1));

        decoded = tryJsQrOnCrop(img, sx, sy, sw, sh, Math.max(1, upscale), false);
        if (decoded) return decoded;

        decoded = tryJsQrOnCrop(img, sx, sy, sw, sh, Math.max(1, upscale), true);
        if (decoded) return decoded;
    }

    // Pass 4: Fallback to Html5Qrcode file scanner (supports 1D barcodes & extra formats)
    const tempId = `eicv-file-scan-${Date.now()}`;
    const hiddenEl = document.createElement('div');
    hiddenEl.id = tempId;
    hiddenEl.style.display = 'none';
    document.body.appendChild(hiddenEl);

    try {
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

<template>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden print-card">
        <!-- Print-Only Official BKK Bandung Letterhead -->
        <div class="hidden print-only px-6 pt-5 pb-3 border-b-2 border-slate-800">
            <img :src="`${baseUrl}/images/Kop_BKK_bandung.png`" alt="Kop BKK Kelas I Bandung" class="w-full max-h-24 object-contain" />
            <p class="text-center text-xs font-bold uppercase tracking-wider text-slate-800 mt-2">
                Bukti Hasil Verifikasi Keaslian Electronic International Certificate of Vaccination (E-ICV)
            </p>
        </div>

        <!-- LOADING STATE -->
        <div v-if="loading" class="p-8 sm:p-10 flex flex-col items-center justify-center text-center min-h-[420px]">
            <div class="relative w-16 h-16 mb-5">
                <div class="absolute inset-0 rounded-full border-4 border-teal-100"></div>
                <div class="absolute inset-0 rounded-full border-4 border-teal-600 border-t-transparent animate-spin"></div>
                <div class="absolute inset-0 flex items-center justify-center text-teal-700">
                    <ShieldCheck class="w-7 h-7" />
                </div>
            </div>
            <h3 class="text-base font-bold text-slate-900">Memverifikasi Keaslian E-ICV...</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm">
                Sistem sedang mencocokkan awalan link resmi Kemenkes RI dan membaca data sertifikat langsung dari server SINKARKES.
            </p>

            <div class="mt-6 w-full max-w-xs space-y-2.5 text-left">
                <div class="flex items-center gap-2.5 text-xs text-teal-800 bg-teal-50/80 px-3 py-2 rounded-xl">
                    <CheckCircle2 class="w-4 h-4 text-teal-600 shrink-0" />
                    <span>Validasi 8 awalan link resmi Kemenkes RI</span>
                </div>
                <div class="flex items-center gap-2.5 text-xs text-slate-700 bg-slate-50 px-3 py-2 rounded-xl">
                    <Loader2 class="w-4 h-4 text-teal-600 animate-spin shrink-0" />
                    <span>Membaca isi sertifikat & nama pemilik dari SINKARKES</span>
                </div>
                <div class="flex items-center gap-2.5 text-xs text-slate-400 bg-slate-50/50 px-3 py-2 rounded-xl">
                    <Database class="w-4 h-4 shrink-0" />
                    <span>Mencatat riwayat verifikasi ke database MySQL</span>
                </div>
            </div>
        </div>

        <!-- EMPTY / READY STATE -->
        <div v-else-if="!result" class="p-8 sm:p-10 flex flex-col items-center justify-center text-center min-h-[420px]">
            <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-teal-50 to-emerald-50 border border-teal-100 flex items-center justify-center text-teal-600 mb-5 shadow-xs">
                <FileCheck2 class="w-10 h-10" />
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 text-teal-700 text-[11px] font-bold uppercase tracking-wider mb-3">
                Siap Memverifikasi
            </span>
            <h3 class="text-lg font-extrabold text-slate-900">Hasil Verifikasi Sertifikat E-ICV</h3>
            <p class="text-xs sm:text-sm text-slate-500 mt-1.5 max-w-md leading-relaxed">
                Pindai QR Code pada sertifikat E-ICV melalui kamera, unggah foto sertifikat, atau gunakan scanner barcode USB. Nama pemilik sertifikat dan status keaslian akan tampil di sini.
            </p>

            <div class="mt-7 grid grid-cols-1 sm:grid-cols-3 gap-3 w-full max-w-lg text-left">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
                    <span class="w-6 h-6 rounded-lg bg-teal-600 text-white text-xs font-bold flex items-center justify-center mb-2">1</span>
                    <p class="text-xs font-bold text-slate-800">Scan Barcode / QR</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Baca URL yang tertanam pada QR Code E-ICV.</p>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
                    <span class="w-6 h-6 rounded-lg bg-teal-600 text-white text-xs font-bold flex items-center justify-center mb-2">2</span>
                    <p class="text-xs font-bold text-slate-800">Cek Awalan Resmi</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Cocokkan dengan 8 awalan resmi Kemenkes / SINKARKES.</p>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
                    <span class="w-6 h-6 rounded-lg bg-teal-600 text-white text-xs font-bold flex items-center justify-center mb-2">3</span>
                    <p class="text-xs font-bold text-slate-800">Tampilkan Nama</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Baca isi link & tampilkan nama serta data vaksinasi.</p>
                </div>
            </div>
        </div>

        <!-- RESULT VIEW -->
        <div v-else>
            <!-- Top Status Banner -->
            <div class="px-6 py-5 border-b" :class="statusTheme.bannerBg">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-xs" :class="statusTheme.iconBg">
                            <ShieldCheck v-if="result.verification_status === 'asli'" class="w-6 h-6" />
                            <ShieldX v-else-if="result.verification_status === 'palsu'" class="w-6 h-6" />
                            <ShieldAlert v-else class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider" :class="statusTheme.badge">
                                    {{ statusTheme.shortBadge }}
                                </span>
                                <span
                                    v-if="result.is_valid_prefix"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/80 text-teal-800 border border-teal-200 text-[11px] font-semibold"
                                >
                                    <Lock class="w-3 h-3" />
                                    Domain Resmi Kemenkes
                                </span>
                            </div>
                            <h3 class="text-lg sm:text-xl font-extrabold mt-1.5 leading-tight" :class="statusTheme.title">
                                {{ statusTheme.headline }}
                            </h3>
                            <p class="text-xs mt-1 leading-relaxed" :class="statusTheme.subtitle">
                                {{ result.message }}
                            </p>
                        </div>
                    </div>

                    <div class="text-right text-[11px] text-slate-500 shrink-0">
                        <p class="font-semibold text-slate-700">Waktu Verifikasi</p>
                        <p>{{ formatDateTime(result.verified_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- Body Content -->
            <div class="p-6 space-y-5">
                <!-- AUTHENTIC CERTIFICATE HERO IDENTITY CARD -->
                <div
                    v-if="result.verification_status === 'asli'"
                    class="rounded-2xl border-2 border-amber-300/80 bg-gradient-to-br from-[#fef9e7] via-[#fdf3d0]/60 to-[#eef8f7] p-5 relative overflow-hidden"
                >
                    <!-- Subtle Official Watermark Badge -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-amber-900/75">
                                <UserCheck class="w-3.5 h-3.5 text-teal-700" />
                                Nama Pemilik Sertifikat E-ICV
                            </span>
                            <p class="text-2xl sm:text-3xl font-extrabold text-slate-950 tracking-tight leading-tight">
                                {{ result.holder_name || 'Nama tidak tercantum' }}
                            </p>
                            <p class="text-xs text-slate-600">
                                International Certificate of Vaccination (Prophylaxis) · Kementerian Kesehatan RI
                            </p>
                        </div>

                        <div
                            v-if="result.certificate_number"
                            class="sm:text-right bg-white/85 backdrop-blur-xs border border-amber-300/80 rounded-xl px-4 py-2.5 shrink-0 shadow-2xs"
                        >
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor E-ICV</p>
                            <p class="text-base font-extrabold font-mono text-slate-900 mt-0.5">{{ result.certificate_number }}</p>
                            <p v-if="result.document_type" class="text-[10px] font-semibold uppercase text-teal-700 mt-0.5">
                                Jenis: {{ result.document_type }}
                            </p>
                        </div>
                    </div>

                    <!-- Identity Metadata Grid -->
                    <div class="mt-4 pt-4 border-t border-amber-300/50 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="bg-white/75 rounded-xl px-3.5 py-2.5 border border-amber-200/60">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor Paspor</p>
                            <p class="text-sm font-bold text-slate-900 mt-0.5 font-mono">
                                {{ result.passport_number || '-' }}
                            </p>
                        </div>
                        <div class="bg-white/75 rounded-xl px-3.5 py-2.5 border border-amber-200/60">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanggal Lahir</p>
                            <p class="text-sm font-bold text-slate-900 mt-0.5">
                                {{ result.birth_date || '-' }}
                            </p>
                        </div>
                        <div class="bg-white/75 rounded-xl px-3.5 py-2.5 border border-amber-200/60">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Status Keaslian</p>
                            <p class="text-sm font-extrabold text-emerald-700 mt-0.5 flex items-center gap-1">
                                <BadgeCheck class="w-4 h-4" />
                                Terdaftar SINKARKES
                            </p>
                        </div>
                    </div>
                </div>

                <!-- VACCINATION DETAILS TABLE -->
                <div v-if="result.vaccinations && result.vaccinations.length > 0" class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                            <Syringe class="w-4 h-4 text-teal-600" />
                            <span>Detail Vaksinasi (International Health Regulations)</span>
                        </h4>
                        <span class="text-[11px] font-semibold text-slate-500">
                            {{ result.vaccinations.length }} data vaksin
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-600">
                                    <th class="px-3.5 py-2.5 font-bold">Vaksin / Profilaksis</th>
                                    <th class="px-3.5 py-2.5 font-bold">Produsen & Batch</th>
                                    <th class="px-3.5 py-2.5 font-bold">Tanggal</th>
                                    <th class="px-3.5 py-2.5 font-bold">Berlaku Hingga</th>
                                    <th class="px-3.5 py-2.5 font-bold">Lokasi & Dokter</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(vac, idx) in result.vaccinations"
                                    :key="idx"
                                    class="hover:bg-teal-50/30 transition-colors"
                                >
                                    <td class="px-3.5 py-3 font-bold text-slate-900">{{ vac.vaccine || '-' }}</td>
                                    <td class="px-3.5 py-3 font-mono text-slate-700">{{ vac.manufacturer_batch || '-' }}</td>
                                    <td class="px-3.5 py-3 text-slate-700 whitespace-nowrap">{{ vac.date || '-' }}</td>
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200/70">
                                            {{ vac.valid_until || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3 text-slate-700">{{ vac.location_clinician || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- OTHER VACCINATIONS / BOOSTERS (if present) -->
                <div v-if="result.other_vaccinations && result.other_vaccinations.length > 0" class="space-y-2">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                        Vaksinasi Tambahan / Booster
                    </h4>
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-600">
                                    <th class="px-3.5 py-2 font-bold">Disease Targeted</th>
                                    <th class="px-3.5 py-2 font-bold">Date</th>
                                    <th class="px-3.5 py-2 font-bold">Manufacturer & Batch</th>
                                    <th class="px-3.5 py-2 font-bold">Next Booster</th>
                                    <th class="px-3.5 py-2 font-bold">Stamp / Signature</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(ov, idx) in result.other_vaccinations" :key="idx">
                                    <td class="px-3.5 py-2.5 font-bold text-slate-900">{{ ov.disease_targeted || '-' }}</td>
                                    <td class="px-3.5 py-2.5 text-slate-700">{{ ov.date || '-' }}</td>
                                    <td class="px-3.5 py-2.5 font-mono text-slate-700">{{ ov.manufacturer_batch || '-' }}</td>
                                    <td class="px-3.5 py-2.5 text-slate-700">{{ ov.next_booster || '-' }}</td>
                                    <td class="px-3.5 py-2.5 text-slate-700">{{ ov.stamp_signature || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- NON-AUTHENTIC / FAKE WARNING DETAILS -->
                <div
                    v-if="result.verification_status === 'palsu'"
                    class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 space-y-2.5 text-xs text-rose-900"
                >
                    <p class="font-bold flex items-center gap-1.5 text-rose-800">
                        <AlertOctagon class="w-4 h-4" />
                        Alasan Penolakan Verifikasi:
                    </p>
                    <ul class="list-disc pl-5 space-y-1 text-rose-800/90">
                        <li>Teks / URL hasil scan barcode <strong>tidak diawali</strong> salah satu dari 8 awalan link resmi Kementerian Kesehatan RI (<code class="font-mono bg-white/80 px-1 rounded">sinkarkes.kemkes.go.id</code> atau <code class="font-mono bg-white/80 px-1 rounded">kespel.depkes.go.id</code>).</li>
                        <li>Sertifikat E-ICV asli hanya diterbitkan melalui sistem resmi <strong>SINKARKES Kemenkes RI</strong>.</li>
                        <li>Jangan membuka tautan mencurigakan ini. Laporkan dugaan pemalsuan dokumen ke Balai Kekarantinaan Kesehatan terdekat.</li>
                    </ul>
                </div>

                <!-- OFFICIAL PREFIX BUT NOT FOUND / FETCH FAILED DETAILS -->
                <div
                    v-if="result.verification_status === 'tidak_ditemukan' || result.verification_status === 'gagal_baca'"
                    class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 space-y-2 text-xs text-amber-950"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-bold text-amber-900">Informasi Awalan & Dokumen Terdeteksi:</span>
                        <span
                            v-if="result.certificate_number"
                            class="font-mono font-bold px-2.5 py-1 rounded-lg bg-white border border-amber-300 text-slate-900"
                        >
                            No. Dokumen: {{ result.certificate_number }}
                        </span>
                    </div>
                    <p class="text-amber-900/90 leading-relaxed">
                        <template v-if="result.verification_status === 'tidak_ditemukan'">
                            Awalan URL memang mengarah ke domain resmi Kemenkes, tetapi nomor sertifikat tersebut tidak ditemukan pada basis data SINKARKES. Periksa kembali keutuhan QR Code atau nomor dokumen.
                        </template>
                        <template v-else>
                            Awalan URL sudah tervalidasi sebagai <strong>link resmi Kemenkes RI</strong>, namun aplikasi gagal mengambil isi halaman dari SINKARKES saat ini. Pastikan koneksi internet aktif lalu klik <strong>Coba Baca Ulang</strong>.
                        </template>
                    </p>
                </div>

                <!-- TECHNICAL AUDIT / LINK PROOF -->
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-4 space-y-2.5 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-bold text-slate-700 flex items-center gap-1.5">
                            <Link2 class="w-3.5 h-3.5 text-teal-600" />
                            Data Hasil Scan Barcode
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[11px] font-semibold text-slate-600">
                                Metode: {{ formatMethod(result.scan_method) }}
                            </span>
                            <span
                                v-if="result.source_http_status"
                                class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[11px] font-mono text-slate-600"
                            >
                                HTTP {{ result.source_http_status }}
                            </span>
                        </div>
                    </div>

                    <div v-if="result.matched_prefix" class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] text-slate-500">Awalan cocok:</span>
                        <code class="text-[11px] font-mono px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 break-all">
                            {{ result.matched_prefix }}
                        </code>
                    </div>

                    <div class="bg-white rounded-lg border border-slate-200 px-3 py-2 font-mono text-[11px] text-slate-700 break-all select-all">
                        {{ result.scanned_url }}
                    </div>
                </div>

                <!-- ACTION BAR -->
                <div class="flex flex-wrap items-center justify-between gap-2.5 pt-1 no-print">
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            v-if="result.is_valid_prefix"
                            :href="result.scanned_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-xs transition-colors"
                        >
                            <ExternalLink class="w-3.5 h-3.5" />
                            <span>Buka Link SINKARKES</span>
                        </a>

                        <button
                            v-if="result.id && result.is_valid_prefix"
                            type="button"
                            @click="$emit('recheck', result.id)"
                            :disabled="rechecking"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <RefreshCw class="w-3.5 h-3.5" :class="rechecking ? 'animate-spin' : ''" />
                            <span>{{ rechecking ? 'Membaca Ulang...' : 'Coba Baca Ulang' }}</span>
                        </button>

                        <button
                            type="button"
                            @click="printResult"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                        >
                            <Printer class="w-3.5 h-3.5" />
                            <span>Cetak Bukti</span>
                        </button>
                    </div>

                    <button
                        v-if="showReset"
                        type="button"
                        @click="$emit('reset')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-teal-700 hover:bg-teal-50 transition-colors cursor-pointer"
                    >
                        <ScanLine class="w-3.5 h-3.5" />
                        <span>Scan Berikutnya</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    AlertOctagon,
    BadgeCheck,
    CheckCircle2,
    Database,
    ExternalLink,
    FileCheck2,
    Link2,
    Loader2,
    Lock,
    Printer,
    RefreshCw,
    ScanLine,
    ShieldAlert,
    ShieldCheck,
    ShieldX,
    Syringe,
    UserCheck,
} from 'lucide-vue-next';

const props = defineProps({
    result: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    rechecking: {
        type: Boolean,
        default: false,
    },
    baseUrl: {
        type: String,
        default: '',
    },
    showReset: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['recheck', 'reset']);

const statusTheme = computed(() => {
    const status = props.result?.verification_status;

    if (status === 'asli') {
        return {
            bannerBg: 'bg-gradient-to-r from-emerald-50 via-teal-50/80 to-white border-emerald-200/80',
            iconBg: 'bg-emerald-600 text-white',
            badge: 'bg-emerald-600 text-white',
            shortBadge: 'ASLI & TERVERIFIKASI',
            title: 'text-emerald-950',
            subtitle: 'text-emerald-800/90',
            headline: 'Sertifikat E-ICV Asli & Terdaftar Resmi',
        };
    }

    if (status === 'palsu') {
        return {
            bannerBg: 'bg-gradient-to-r from-rose-50 via-red-50/70 to-white border-rose-200/80',
            iconBg: 'bg-rose-600 text-white',
            badge: 'bg-rose-600 text-white',
            shortBadge: 'TIDAK ASLI / PALSU',
            title: 'text-rose-950',
            subtitle: 'text-rose-800/90',
            headline: 'Peringatan: Bukan Link Resmi Kemenkes RI',
        };
    }

    if (status === 'tidak_ditemukan') {
        return {
            bannerBg: 'bg-gradient-to-r from-amber-50 via-orange-50/60 to-white border-amber-200/80',
            iconBg: 'bg-amber-500 text-white',
            badge: 'bg-amber-500 text-white',
            shortBadge: 'DATA TIDAK DITEMUKAN',
            title: 'text-amber-950',
            subtitle: 'text-amber-900/85',
            headline: 'Link Domain Resmi — Dokumen Tidak Ditemukan',
        };
    }

    return {
        bannerBg: 'bg-gradient-to-r from-sky-50 via-amber-50/50 to-white border-sky-200/80',
        iconBg: 'bg-sky-600 text-white',
        badge: 'bg-sky-600 text-white',
        shortBadge: 'LINK ASLI — GAGAL BACA SERVER',
        title: 'text-slate-900',
        subtitle: 'text-slate-700',
        headline: 'Awalan Link Resmi Valid, Gagal Membaca SINKARKES',
    };
});

function formatMethod(method) {
    if (method === 'camera') return 'Kamera Live';
    if (method === 'upload') return 'Upload Foto';
    return 'Scanner USB / Manual';
}

function formatDateTime(value) {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function printResult() {
    window.print();
}
</script>

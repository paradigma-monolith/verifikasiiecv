<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="result"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/65 backdrop-blur-xs overflow-y-auto no-print"
                role="dialog"
                aria-modal="true"
                aria-labelledby="eicv-summary-modal-title"
                @click.self="$emit('close')"
            >
                <div
                    class="w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden my-auto transform transition-all"
                >
                    <!-- Status Header -->
                    <div class="relative px-6 pt-6 pb-5 text-center" :class="theme.headerBg">
                        <button
                            type="button"
                            @click="$emit('close')"
                            aria-label="Tutup pop-up"
                            class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/80 hover:bg-white text-slate-500 hover:text-slate-900 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                        >
                            <X class="w-4 h-4" />
                        </button>

                        <div
                            class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center shadow-md mb-3"
                            :class="theme.iconBg"
                        >
                            <ShieldCheck v-if="result.verification_status === 'asli'" class="w-9 h-9" />
                            <ShieldX v-else-if="result.verification_status === 'palsu'" class="w-9 h-9" />
                            <ShieldAlert v-else class="w-9 h-9" />
                        </div>

                        <span
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider"
                            :class="theme.badge"
                        >
                            {{ theme.badgeText }}
                        </span>

                        <h2
                            id="eicv-summary-modal-title"
                            class="text-lg sm:text-xl font-extrabold mt-2 leading-tight"
                            :class="theme.title"
                        >
                            {{ theme.headline }}
                        </h2>
                        <p class="text-xs mt-1 leading-relaxed max-w-md mx-auto" :class="theme.subtitle">
                            {{ result.message }}
                        </p>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-5 space-y-4 max-h-[60vh] overflow-y-auto">
                        <!-- AUTHENTIC SUMMARY -->
                        <template v-if="result.verification_status === 'asli'">
                            <!-- Hero Name Banner -->
                            <div
                                class="rounded-2xl border-2 border-amber-300/80 bg-gradient-to-br from-[#fef9e7] via-[#fdf3d0]/60 to-[#eef8f7] p-4 text-center"
                            >
                                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-900/75 flex items-center justify-center gap-1.5">
                                    <UserCheck class="w-3.5 h-3.5 text-teal-700" />
                                    Nama Pemilik Sertifikat E-ICV
                                </p>
                                <p class="text-2xl sm:text-3xl font-extrabold text-slate-950 tracking-tight mt-1 break-words">
                                    {{ result.holder_name || '-' }}
                                </p>
                                <div v-if="result.certificate_number" class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/90 border border-amber-300/80 text-xs font-mono font-bold text-slate-800">
                                    <span>No. E-ICV:</span>
                                    <span class="text-teal-800">{{ result.certificate_number }}</span>
                                </div>
                            </div>

                            <!-- Key Identity Metadata -->
                            <div class="grid grid-cols-2 gap-2.5 text-xs">
                                <div class="rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor Paspor</p>
                                    <p class="font-mono font-bold text-slate-900 mt-0.5 text-sm">
                                        {{ result.passport_number || '-' }}
                                    </p>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanggal Lahir</p>
                                    <p class="font-bold text-slate-900 mt-0.5 text-sm">
                                        {{ result.birth_date || '-' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Vaccination Summary List -->
                            <div v-if="result.vaccinations && result.vaccinations.length > 0" class="space-y-2">
                                <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                                    <Syringe class="w-3.5 h-3.5 text-teal-600" />
                                    <span>Ringkasan Vaksinasi ({{ result.vaccinations.length }})</span>
                                </p>
                                <div class="space-y-2">
                                    <div
                                        v-for="(vac, idx) in result.vaccinations"
                                        :key="idx"
                                        class="rounded-xl border border-slate-200/80 bg-white px-3.5 py-2.5 text-xs space-y-1 shadow-2xs"
                                    >
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="font-extrabold text-slate-900">{{ vac.vaccine || '-' }}</span>
                                            <span
                                                v-if="vac.valid_until"
                                                class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/80 text-[11px] font-bold"
                                            >
                                                Berlaku s.d. {{ vac.valid_until }}
                                            </span>
                                            <span
                                                v-else-if="vac.date"
                                                class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[11px] font-semibold"
                                            >
                                                Tgl: {{ vac.date }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-slate-500">
                                            <span v-if="vac.manufacturer_batch" class="font-mono">Batch: {{ vac.manufacturer_batch }}</span>
                                            <span v-if="vac.date && vac.valid_until">Diberikan: {{ vac.date }}</span>
                                        </div>
                                        <p v-if="vac.location_clinician" class="text-[11px] text-slate-600">
                                            {{ vac.location_clinician }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- NON-AUTHENTIC / OTHER STATUS SUMMARY -->
                        <template v-else>
                            <div
                                v-if="result.certificate_number"
                                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 flex items-center justify-between text-xs"
                            >
                                <span class="font-semibold text-amber-900">Nomor Dokumen Terbaca:</span>
                                <span class="font-mono font-bold text-slate-900">{{ result.certificate_number }}</span>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 space-y-1.5 text-xs">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    Isi Hasil Scan Barcode
                                </p>
                                <p class="font-mono text-[11px] text-slate-800 break-all bg-white border border-slate-200 rounded-lg p-2.5">
                                    {{ result.scanned_url }}
                                </p>
                                <p v-if="result.matched_prefix" class="text-[11px] text-emerald-700 font-semibold pt-0.5 flex items-center gap-1">
                                    <Lock class="w-3 h-3" />
                                    Awalan resmi cocok: <span class="font-mono">{{ result.matched_prefix }}</span>
                                </p>
                            </div>
                        </template>

                        <!-- Meta Footer Inside Body -->
                        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                            <span>Metode: <strong class="text-slate-700">{{ methodLabel }}</strong></span>
                            <span>{{ formattedTime }}</span>
                        </div>
                    </div>

                    <!-- Modal Actions Footer -->
                    <div class="px-6 py-4 bg-slate-50/90 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <button
                                v-if="result.id && (result.verification_status === 'gagal_baca' || result.verification_status === 'tidak_ditemukan')"
                                type="button"
                                @click="$emit('recheck', result.id)"
                                :disabled="rechecking"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <RefreshCw class="w-3.5 h-3.5" :class="rechecking ? 'animate-spin' : ''" />
                                <span>{{ rechecking ? 'Membaca...' : 'Baca Ulang' }}</span>
                            </button>

                            <a
                                v-if="result.is_valid_prefix"
                                :href="result.scanned_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-teal-200 bg-teal-50/80 hover:bg-teal-100 text-teal-800 text-xs font-bold transition-colors"
                            >
                                <ExternalLink class="w-3.5 h-3.5" />
                                <span>SINKARKES</span>
                            </a>

                            <button
                                type="button"
                                @click="$emit('view-detail')"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                            >
                                <span>Lihat Detail</span>
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="$emit('scan-next')"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-white text-xs font-bold shadow-xs transition-colors cursor-pointer"
                            :class="result.verification_status === 'palsu'
                                ? 'bg-rose-600 hover:bg-rose-700'
                                : 'bg-teal-600 hover:bg-teal-700'"
                        >
                            <ScanLine class="w-4 h-4" />
                            <span>Scan Berikutnya</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount } from 'vue';
import {
    ExternalLink,
    Lock,
    RefreshCw,
    ScanLine,
    ShieldAlert,
    ShieldCheck,
    ShieldX,
    Syringe,
    UserCheck,
    X,
} from 'lucide-vue-next';

const props = defineProps({
    result: {
        type: Object,
        default: null,
    },
    rechecking: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'scan-next', 'view-detail', 'recheck']);

const theme = computed(() => {
    const status = props.result?.verification_status;

    if (status === 'asli') {
        return {
            headerBg: 'bg-gradient-to-b from-emerald-50 via-teal-50/70 to-white border-b border-emerald-100',
            iconBg: 'bg-emerald-600 text-white shadow-emerald-600/25',
            badge: 'bg-emerald-600 text-white',
            badgeText: 'E-ICV ASLI & TERVERIFIKASI',
            title: 'text-emerald-950',
            subtitle: 'text-emerald-800/85',
            headline: 'Sertifikat E-ICV Terdaftar Resmi',
        };
    }

    if (status === 'palsu') {
        return {
            headerBg: 'bg-gradient-to-b from-rose-50 via-red-50/70 to-white border-b border-rose-100',
            iconBg: 'bg-rose-600 text-white shadow-rose-600/25',
            badge: 'bg-rose-600 text-white',
            badgeText: 'TIDAK ASLI / PALSU',
            title: 'text-rose-950',
            subtitle: 'text-rose-800/85',
            headline: 'Peringatan: Bukan Link Resmi Kemenkes',
        };
    }

    if (status === 'tidak_ditemukan') {
        return {
            headerBg: 'bg-gradient-to-b from-amber-50 via-orange-50/60 to-white border-b border-amber-100',
            iconBg: 'bg-amber-500 text-white shadow-amber-500/25',
            badge: 'bg-amber-500 text-white',
            badgeText: 'DATA TIDAK DITEMUKAN',
            title: 'text-amber-950',
            subtitle: 'text-amber-900/80',
            headline: 'Domain Resmi — Dokumen Tidak Ditemukan',
        };
    }

    return {
        headerBg: 'bg-gradient-to-b from-sky-50 via-amber-50/40 to-white border-b border-sky-100',
        iconBg: 'bg-sky-600 text-white shadow-sky-600/25',
        badge: 'bg-sky-600 text-white',
        badgeText: 'LINK ASLI — GAGAL BACA SERVER',
        title: 'text-slate-900',
        subtitle: 'text-slate-600',
        headline: 'Link Resmi Valid, Server SINKARKES Tidak Merespons',
    };
});

const methodLabel = computed(() => {
    const m = props.result?.scan_method;
    if (m === 'camera') return 'Kamera Live';
    if (m === 'upload') return 'Upload Foto';
    return 'Scanner USB / Manual';
});

const formattedTime = computed(() => {
    const val = props.result?.verified_at;
    if (!val) return '-';
    const d = new Date(val);
    if (Number.isNaN(d.getTime())) return val;
    return d.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
});

function handleKeydown(e) {
    if (e.key === 'Escape' && props.result) {
        emit('close');
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

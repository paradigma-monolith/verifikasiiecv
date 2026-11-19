<template>
    <div class="min-h-screen flex flex-col bg-[#f3f6f9] text-slate-900">
        <!-- Official Kemenkes Accent Top Bar -->
        <div class="h-1.5 w-full bg-gradient-to-r from-[#00918b] via-[#00b1a9] to-[#c4d600] no-print"></div>

        <!-- Header Navigation -->
        <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-2xs no-print">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4 py-3">
                    <!-- Official BKK Bandung Branding -->
                    <div class="flex items-center gap-3.5">
                        <div class="flex items-center gap-2.5 pr-3.5 border-r border-slate-200">
                            <img
                                :src="`${baseUrl}/images/Logo BKK Bandung.png`"
                                alt="Kemenkes BKK Bandung"
                                class="h-9 sm:h-10 w-auto object-contain"
                            />
                            <img
                                :src="`${baseUrl}/images/LOGO KARANTINA.png`"
                                alt="Karantina Kesehatan"
                                class="h-9 sm:h-10 w-auto object-contain hidden sm:block"
                            />
                        </div>
                        <div>
                            <h1 class="text-sm sm:text-base font-extrabold text-[#0f1f4b] tracking-tight leading-tight">
                                Verifikasi Keaslian E-ICV
                            </h1>
                            <p class="text-[11px] text-slate-500 font-medium leading-tight mt-0.5">
                                Balai Kekarantinaan Kesehatan Kelas I Bandung · Kemenkes RI
                            </p>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <nav class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl">
                        <button
                            type="button"
                            @click="activeTab = 'scanner'"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer"
                            :class="activeTab === 'scanner'
                                ? 'bg-white text-teal-700 shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'"
                        >
                            <ScanLine class="w-4 h-4" />
                            <span>Verifikasi Scanner</span>
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'history'"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer"
                            :class="activeTab === 'history'
                                ? 'bg-white text-teal-700 shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'"
                        >
                            <History class="w-4 h-4" />
                            <span>Riwayat & Laporan</span>
                            <span
                                v-if="stats.total > 0"
                                class="ml-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold"
                                :class="activeTab === 'history' ? 'bg-teal-100 text-teal-800' : 'bg-slate-200 text-slate-700'"
                            >
                                {{ stats.total }}
                            </span>
                        </button>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <!-- KPI Summary Strip -->
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 no-print">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Verifikasi</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ stats.total }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tersimpan di MySQL</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                        <QrCode class="w-5 h-5" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-emerald-200/70 p-4 shadow-2xs flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">E-ICV Asli</p>
                        <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ stats.asli }}</p>
                        <p class="text-[11px] text-emerald-600/80 mt-0.5">Terdaftar SINKARKES</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <ShieldCheck class="w-5 h-5" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-rose-200/70 p-4 shadow-2xs flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Palsu / Ditolak</p>
                        <p class="text-2xl font-extrabold text-rose-700 mt-1">{{ stats.palsu }}</p>
                        <p class="text-[11px] text-rose-600/80 mt-0.5">Bukan domain Kemenkes</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <ShieldX class="w-5 h-5" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-teal-200/70 p-4 shadow-2xs flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-teal-700">Scan Hari Ini</p>
                        <p class="text-2xl font-extrabold text-teal-700 mt-1">{{ stats.today_total }}</p>
                        <p class="text-[11px] text-teal-600/80 mt-0.5">{{ stats.today_asli }} asli hari ini</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                        <CalendarCheck2 class="w-5 h-5" />
                    </div>
                </div>
            </section>

            <!-- API Error Toast Banner -->
            <div
                v-if="apiError"
                class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800 flex items-center justify-between gap-3 no-print"
            >
                <div class="flex items-center gap-2">
                    <AlertCircle class="w-4 h-4 text-rose-600 shrink-0" />
                    <span>{{ apiError }}</span>
                </div>
                <button
                    type="button"
                    @click="apiError = ''"
                    class="text-rose-600 hover:text-rose-900 font-bold cursor-pointer"
                >
                    Tutup
                </button>
            </div>

            <!-- TAB 1: SCANNER + RESULT WORKSPACE -->
            <section v-if="activeTab === 'scanner'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- Left: Scanner Controls -->
                    <div class="lg:col-span-5 no-print">
                        <BarcodeScannerPanel
                            ref="scannerPanel"
                            :loading="verifying"
                            :paused="Boolean(summaryModalResult)"
                            :allowed-prefixes="allowedPrefixes"
                            :sound-enabled="soundEnabled"
                            @scanned="handleScanned"
                            @toggle-sound="soundEnabled = !soundEnabled"
                        />
                    </div>

                    <!-- Right: Verification Result -->
                    <div ref="resultCardSection" class="lg:col-span-7">
                        <VerificationResultCard
                            :result="currentResult"
                            :loading="verifying"
                            :rechecking="rechecking"
                            :base-url="baseUrl"
                            @recheck="handleRecheck"
                            @reset="handleReset"
                        />
                    </div>
                </div>

                <!-- Recent 5 Verifications Quick Strip -->
                <div
                    v-if="recentLogs.length > 0"
                    class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden no-print"
                >
                    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                            <Clock class="w-4 h-4 text-teal-600" />
                            <span>Verifikasi Terakhir</span>
                        </h3>
                        <button
                            type="button"
                            @click="activeTab = 'history'"
                            class="text-xs font-bold text-teal-700 hover:text-teal-900 cursor-pointer"
                        >
                            Lihat Semua Riwayat &rarr;
                        </button>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <button
                            v-for="item in recentLogs"
                            :key="item.id"
                            type="button"
                            @click="currentResult = item"
                            class="w-full px-5 py-3 flex flex-wrap items-center justify-between gap-3 text-left hover:bg-teal-50/40 transition-colors cursor-pointer"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span
                                    class="w-2.5 h-2.5 rounded-full shrink-0"
                                    :class="item.verification_status === 'asli'
                                        ? 'bg-emerald-500'
                                        : item.verification_status === 'palsu'
                                        ? 'bg-rose-500'
                                        : 'bg-amber-500'"
                                ></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 truncate">
                                        {{ item.holder_name || (item.verification_status === 'palsu' ? 'Link Tidak Resmi / Palsu' : item.certificate_number || 'Tanpa Nama') }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 font-mono truncate max-w-md">
                                        {{ item.certificate_number ? item.certificate_number + ' · ' : '' }}{{ item.scanned_url }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 shrink-0">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                    :class="item.verification_status === 'asli'
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : item.verification_status === 'palsu'
                                        ? 'bg-rose-50 text-rose-700 border border-rose-200'
                                        : 'bg-amber-50 text-amber-800 border border-amber-200'"
                                >
                                    {{ shortStatus(item.verification_status) }}
                                </span>
                                <span class="text-[11px] text-slate-400">{{ formatShortTime(item.verified_at) }}</span>
                            </div>
                        </button>
                    </div>
                </div>
            </section>

            <!-- TAB 2: FULL HISTORY & REPORT TABLE -->
            <section v-if="activeTab === 'history'" class="no-print">
                <VerificationHistoryTable
                    :base-url="baseUrl"
                    :refresh-trigger="historyRefreshTrigger"
                    @inspect="openDetailModal"
                    @mutated="handleHistoryMutated"
                />
            </section>
        </main>

        <!-- Scan Result Summary Pop-Up Modal -->
        <VerificationSummaryModal
            :result="summaryModalResult"
            :rechecking="rechecking"
            @close="closeSummaryModal"
            @scan-next="handleSummaryScanNext"
            @view-detail="handleSummaryViewDetail"
            @recheck="handleRecheck"
        />

        <!-- Detail Modal (when inspecting from History tab) -->
        <div
            v-if="inspectedRecord"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
            @click.self="inspectedRecord = null"
        >
            <div class="w-full max-w-3xl my-8 relative">
                <button
                    type="button"
                    @click="inspectedRecord = null"
                    class="no-print absolute -top-3 -right-3 z-10 w-9 h-9 rounded-full bg-white border border-slate-200 shadow-md flex items-center justify-center text-slate-600 hover:text-slate-900 cursor-pointer"
                    title="Tutup"
                >
                    <X class="w-4 h-4" />
                </button>
                <VerificationResultCard
                    :result="inspectedRecord"
                    :loading="false"
                    :rechecking="rechecking"
                    :base-url="baseUrl"
                    :show-reset="false"
                    @recheck="handleModalRecheck"
                />
            </div>
        </div>

        <!-- Official Footer -->
        <footer class="border-t border-slate-200/80 bg-white mt-auto no-print">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between gap-3 text-[11px] text-slate-500">
                <div class="flex items-center gap-2">
                    <img :src="`${baseUrl}/images/logokemenkes.png`" alt="Kemenkes" class="h-5 w-auto" />
                    <span>
                        <strong class="text-slate-700">Balai Kekarantinaan Kesehatan Kelas I Bandung</strong> · Jl. Cikapayang No. 5, Bandung 40116
                    </span>
                </div>
                <p>Verifikasi resmi mengacu pada portal <span class="font-mono text-teal-700 font-semibold">sinkarkes.kemkes.go.id</span></p>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import {
    AlertCircle,
    CalendarCheck2,
    Clock,
    History,
    QrCode,
    ScanLine,
    ShieldCheck,
    ShieldX,
    X,
} from 'lucide-vue-next';
import BarcodeScannerPanel from './components/BarcodeScannerPanel.vue';
import VerificationResultCard from './components/VerificationResultCard.vue';
import VerificationHistoryTable from './components/VerificationHistoryTable.vue';
import VerificationSummaryModal from './components/VerificationSummaryModal.vue';
import { playVerificationTone } from './utils/soundFeedback';

const props = defineProps({
    baseUrl: {
        type: String,
        default: '',
    },
});

const activeTab = ref('scanner');
const scannerPanel = ref(null);
const resultCardSection = ref(null);
const soundEnabled = ref(true);

const verifying = ref(false);
const rechecking = ref(false);
const apiError = ref('');
const currentResult = ref(null);
const summaryModalResult = ref(null);
const inspectedRecord = ref(null);
const recentLogs = ref([]);
const allowedPrefixes = ref([]);
const historyRefreshTrigger = ref(0);

const stats = reactive({
    total: 0,
    asli: 0,
    tidak_ditemukan: 0,
    gagal_baca: 0,
    palsu: 0,
    today_total: 0,
    today_asli: 0,
});

async function fetchStats() {
    try {
        const { data } = await axios.get('/api/verifications/stats');
        if (data.stats) {
            Object.assign(stats, data.stats);
        }
        if (Array.isArray(data.allowed_prefixes)) {
            allowedPrefixes.value = data.allowed_prefixes;
        }
    } catch (err) {
        console.error('Gagal memuat statistik:', err);
    }
}

async function fetchRecentLogs() {
    try {
        const { data } = await axios.get('/api/verifications', {
            params: { per_page: 5, page: 1 },
        });
        recentLogs.value = data.data || [];
    } catch (err) {
        console.error('Gagal memuat riwayat terbaru:', err);
    }
}

async function handleScanned({ url, method }) {
    if (!url || verifying.value) return;

    apiError.value = '';
    verifying.value = true;

    try {
        const { data } = await axios.post('/api/verifications/verify', {
            scanned_url: url,
            scan_method: method,
        });

        currentResult.value = data.data;
        summaryModalResult.value = data.data;
        if (soundEnabled.value && data.data?.verification_status) {
            playVerificationTone(data.data.verification_status);
        }

        await Promise.all([fetchStats(), fetchRecentLogs()]);
        historyRefreshTrigger.value += 1;
    } catch (err) {
        apiError.value =
            err?.response?.data?.message ||
            'Terjadi kesalahan saat menghubungi server verifikasi. Pastikan database MySQL dan server Laravel berjalan.';
    } finally {
        verifying.value = false;
    }
}

async function handleRecheck(id) {
    if (!id || rechecking.value) return;
    apiError.value = '';
    rechecking.value = true;

    try {
        const { data } = await axios.post(`/api/verifications/${id}/recheck`);
        currentResult.value = data.data;
        summaryModalResult.value = data.data;
        if (soundEnabled.value && data.data?.verification_status) {
            playVerificationTone(data.data.verification_status);
        }
        await Promise.all([fetchStats(), fetchRecentLogs()]);
        historyRefreshTrigger.value += 1;
    } catch (err) {
        apiError.value = err?.response?.data?.message || 'Gagal melakukan pengecekan ulang ke SINKARKES.';
    } finally {
        rechecking.value = false;
    }
}

async function handleModalRecheck(id) {
    if (!id || rechecking.value) return;
    rechecking.value = true;
    try {
        const { data } = await axios.post(`/api/verifications/${id}/recheck`);
        inspectedRecord.value = data.data;
        if (currentResult.value?.id === id) {
            currentResult.value = data.data;
        }
        await Promise.all([fetchStats(), fetchRecentLogs()]);
        historyRefreshTrigger.value += 1;
    } catch (err) {
        apiError.value = err?.response?.data?.message || 'Gagal melakukan pengecekan ulang.';
    } finally {
        rechecking.value = false;
    }
}

function closeSummaryModal() {
    summaryModalResult.value = null;
}

function handleSummaryScanNext() {
    summaryModalResult.value = null;
    scannerPanel.value?.focusForRescan();
}

function handleSummaryViewDetail() {
    summaryModalResult.value = null;
    resultCardSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function handleReset() {
    currentResult.value = null;
    summaryModalResult.value = null;
    apiError.value = '';
    scannerPanel.value?.focusForRescan();
}

function openDetailModal(record) {
    inspectedRecord.value = record;
}

async function handleHistoryMutated(updatedRecord) {
    if (updatedRecord && currentResult.value?.id === updatedRecord.id) {
        currentResult.value = updatedRecord;
    }
    await Promise.all([fetchStats(), fetchRecentLogs()]);
}

function shortStatus(status) {
    if (status === 'asli') return 'Asli';
    if (status === 'palsu') return 'Palsu';
    if (status === 'tidak_ditemukan') return 'Tdk Ditemukan';
    return 'Gagal Baca';
}

function formatShortTime(val) {
    if (!val) return '';
    return new Date(val).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

onMounted(() => {
    fetchStats();
    fetchRecentLogs();
});
</script>

<template>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Toolbar Header -->
        <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                    <History class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Riwayat Verifikasi E-ICV</h2>
                    <p class="text-xs text-slate-500">Data verifikasi tersimpan di database MySQL</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="fetchRecords(pagination.current_page)"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                >
                    <RefreshCw class="w-3.5 h-3.5" :class="loading ? 'animate-spin' : ''" />
                    <span>Muat Ulang</span>
                </button>
                <a
                    :href="exportUrl"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-xs transition-colors"
                >
                    <Download class="w-3.5 h-3.5" />
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-5 py-3.5 bg-slate-50/70 border-b border-slate-100 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[220px]">
                <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Cari nama pemilik, nomor E-ICV, paspor, vaksin, atau URL..."
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500"
                />
            </div>

            <select
                v-model="filters.status"
                @change="fetchRecords(1)"
                class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-teal-500"
            >
                <option value="">Semua Status</option>
                <option value="asli">Asli & Terverifikasi</option>
                <option value="tidak_ditemukan">Data Tidak Ditemukan</option>
                <option value="gagal_baca">Gagal Baca Server</option>
                <option value="palsu">Tidak Asli / Palsu</option>
            </select>

            <input
                v-model="filters.date"
                type="date"
                @change="fetchRecords(1)"
                class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
            />

            <button
                v-if="filters.search || filters.status || filters.date"
                type="button"
                @click="resetFilters"
                class="text-xs font-semibold text-slate-500 hover:text-rose-600 px-2 py-1.5 cursor-pointer"
            >
                Reset Filter
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-500">
                        <th class="px-4 py-3 font-bold">Waktu</th>
                        <th class="px-4 py-3 font-bold">Status</th>
                        <th class="px-4 py-3 font-bold">Nama Sertifikat E-ICV</th>
                        <th class="px-4 py-3 font-bold">No. E-ICV / Paspor</th>
                        <th class="px-4 py-3 font-bold">Vaksin</th>
                        <th class="px-4 py-3 font-bold">Metode</th>
                        <th class="px-4 py-3 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading && records.length === 0">
                        <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                            <Loader2 class="w-6 h-6 animate-spin text-teal-600 mx-auto mb-2" />
                            <span>Memuat riwayat verifikasi...</span>
                        </td>
                    </tr>

                    <tr v-else-if="records.length === 0">
                        <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                            <Inbox class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                            <p class="font-semibold text-slate-700">Belum ada data riwayat verifikasi</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Lakukan scan barcode E-ICV untuk mulai mencatat verifikasi ke database MySQL.
                            </p>
                        </td>
                    </tr>

                    <tr
                        v-for="item in records"
                        :key="item.id"
                        class="hover:bg-teal-50/30 transition-colors"
                    >
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                            <p class="font-semibold text-slate-800">{{ formatDate(item.verified_at) }}</p>
                            <p class="text-[11px] text-slate-400">{{ formatTime(item.verified_at) }}</p>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                :class="badgeClass(item.verification_status)"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="dotClass(item.verification_status)"></span>
                                {{ statusLabel(item.verification_status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <p v-if="item.holder_name" class="font-bold text-slate-900 text-sm">
                                {{ item.holder_name }}
                            </p>
                            <p v-else class="text-slate-400 italic">
                                {{ item.verification_status === 'palsu' ? 'Bukan sertifikat resmi' : 'Tidak tersedia' }}
                            </p>
                            <p class="text-[11px] text-slate-400 font-mono truncate max-w-[260px]" :title="item.scanned_url">
                                {{ item.scanned_url }}
                            </p>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <p class="font-mono font-bold text-slate-800">{{ item.certificate_number || '-' }}</p>
                            <p v-if="item.passport_number" class="text-[11px] text-slate-500 font-mono">
                                Paspor: {{ item.passport_number }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-slate-700 font-medium max-w-[200px] truncate" :title="item.vaccine_summary || ''">
                                {{ item.vaccine_summary || '-' }}
                            </p>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[11px] font-semibold">
                                {{ methodLabel(item.scan_method) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="$emit('inspect', item)"
                                    title="Lihat detail sertifikat"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-teal-700 hover:bg-teal-50 transition-colors cursor-pointer"
                                >
                                    <Eye class="w-4 h-4" />
                                </button>
                                <button
                                    v-if="item.is_valid_prefix"
                                    type="button"
                                    @click="recheckRecord(item.id)"
                                    :disabled="recheckingId === item.id"
                                    title="Baca ulang dari SINKARKES"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-sky-700 hover:bg-sky-50 transition-colors cursor-pointer disabled:opacity-50"
                                >
                                    <RefreshCw class="w-4 h-4" :class="recheckingId === item.id ? 'animate-spin' : ''" />
                                </button>
                                <button
                                    type="button"
                                    @click="deleteRecord(item.id)"
                                    :disabled="deletingId === item.id"
                                    title="Hapus riwayat"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer disabled:opacity-50"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div
            v-if="pagination.total > 0"
            class="px-5 py-3.5 bg-slate-50/70 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600"
        >
            <p>
                Menampilkan <strong>{{ pagination.from }}–{{ pagination.to }}</strong> dari
                <strong>{{ pagination.total }}</strong> riwayat verifikasi
            </p>
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    :disabled="pagination.current_page <= 1 || loading"
                    @click="fetchRecords(pagination.current_page - 1)"
                    class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 font-semibold cursor-pointer"
                >
                    Sebelumnya
                </button>
                <span class="px-2.5 py-1.5 font-semibold text-slate-700">
                    Hal. {{ pagination.current_page }} / {{ pagination.last_page }}
                </span>
                <button
                    type="button"
                    :disabled="pagination.current_page >= pagination.last_page || loading"
                    @click="fetchRecords(pagination.current_page + 1)"
                    class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 font-semibold cursor-pointer"
                >
                    Berikutnya
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import {
    Download,
    Eye,
    History,
    Inbox,
    Loader2,
    RefreshCw,
    Search,
    Trash2,
} from 'lucide-vue-next';

const props = defineProps({
    baseUrl: {
        type: String,
        default: '',
    },
    refreshTrigger: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['inspect', 'mutated']);

const records = ref([]);
const loading = ref(false);
const recheckingId = ref(null);
const deletingId = ref(null);

const filters = reactive({
    search: '',
    status: '',
    date: '',
});

const pagination = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
    from: 0,
    to: 0,
});

let searchTimer = null;

watch(
    () => filters.search,
    () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => fetchRecords(1), 350);
    }
);

watch(
    () => props.refreshTrigger,
    () => {
        fetchRecords(1);
    }
);

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (filters.search) params.set('search', filters.search);
    if (filters.status) params.set('status', filters.status);
    if (filters.date) params.set('date', filters.date);
    const qs = params.toString();
    return `${props.baseUrl}/api/verifications/export/csv${qs ? '?' + qs : ''}`;
});

async function fetchRecords(page = 1) {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/verifications', {
            params: {
                page,
                per_page: 10,
                search: filters.search || undefined,
                status: filters.status || undefined,
                date: filters.date || undefined,
            },
        });
        records.value = data.data || [];
        pagination.current_page = data.current_page || 1;
        pagination.last_page = data.last_page || 1;
        pagination.total = data.total || 0;
        pagination.from = data.from || 0;
        pagination.to = data.to || 0;
    } catch (err) {
        console.error('Gagal memuat riwayat:', err);
    } finally {
        loading.value = false;
    }
}

async function recheckRecord(id) {
    recheckingId.value = id;
    try {
        const { data } = await axios.post(`/api/verifications/${id}/recheck`);
        const idx = records.value.findIndex((r) => r.id === id);
        if (idx !== -1 && data.data) {
            records.value[idx] = data.data;
        }
        emit('mutated', data.data);
    } catch (err) {
        console.error('Gagal cek ulang:', err);
    } finally {
        recheckingId.value = null;
    }
}

async function deleteRecord(id) {
    if (!window.confirm('Hapus riwayat verifikasi ini dari database?')) return;
    deletingId.value = id;
    try {
        await axios.delete(`/api/verifications/${id}`);
        await fetchRecords(pagination.current_page);
        emit('mutated');
    } catch (err) {
        console.error('Gagal menghapus:', err);
    } finally {
        deletingId.value = null;
    }
}

function resetFilters() {
    filters.search = '';
    filters.status = '';
    filters.date = '';
    fetchRecords(1);
}

function statusLabel(status) {
    if (status === 'asli') return 'Asli';
    if (status === 'palsu') return 'Tidak Asli / Palsu';
    if (status === 'tidak_ditemukan') return 'Tidak Ditemukan';
    return 'Gagal Baca';
}

function badgeClass(status) {
    if (status === 'asli') return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    if (status === 'palsu') return 'bg-rose-50 text-rose-700 border border-rose-200';
    if (status === 'tidak_ditemukan') return 'bg-amber-50 text-amber-800 border border-amber-200';
    return 'bg-sky-50 text-sky-700 border border-sky-200';
}

function dotClass(status) {
    if (status === 'asli') return 'bg-emerald-500';
    if (status === 'palsu') return 'bg-rose-500';
    if (status === 'tidak_ditemukan') return 'bg-amber-500';
    return 'bg-sky-500';
}

function methodLabel(method) {
    if (method === 'camera') return 'Kamera';
    if (method === 'upload') return 'Upload Foto';
    return 'USB / Manual';
}

function formatDate(val) {
    if (!val) return '-';
    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatTime(val) {
    if (!val) return '';
    return new Date(val).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

onMounted(() => {
    fetchRecords(1);
});
</script>

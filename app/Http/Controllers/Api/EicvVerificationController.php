<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EicvVerification;
use App\Services\EicvVerifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EicvVerificationController extends Controller
{
    public function __construct(
        protected EicvVerifierService $verifier
    ) {}

    public function stats(): JsonResponse
    {
        $today = now()->toDateString();

        $total = EicvVerification::count();
        $asli = EicvVerification::where('verification_status', EicvVerification::STATUS_ASLI)->count();
        $tidakDitemukan = EicvVerification::where('verification_status', EicvVerification::STATUS_TIDAK_DITEMUKAN)->count();
        $gagalBaca = EicvVerification::where('verification_status', EicvVerification::STATUS_GAGAL_BACA)->count();
        $palsu = EicvVerification::where('verification_status', EicvVerification::STATUS_PALSU)->count();
        $todayTotal = EicvVerification::whereDate('verified_at', $today)->count();
        $todayAsli = EicvVerification::whereDate('verified_at', $today)
            ->where('verification_status', EicvVerification::STATUS_ASLI)
            ->count();

        return response()->json([
            'stats' => [
                'total' => $total,
                'asli' => $asli,
                'tidak_ditemukan' => $tidakDitemukan,
                'gagal_baca' => $gagalBaca,
                'palsu' => $palsu,
                'today_total' => $todayTotal,
                'today_asli' => $todayAsli,
            ],
            'allowed_prefixes' => $this->verifier->allowedPrefixes(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->filteredQuery($request);
        $perPage = min(max((int) $request->integer('per_page', 10), 5), 100);

        $records = $query
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($records);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scanned_url' => ['required', 'string', 'max:2048'],
            'scan_method' => ['nullable', 'string', 'in:camera,upload,manual'],
        ], [
            'scanned_url.required' => 'Hasil scan barcode / URL tidak boleh kosong.',
            'scanned_url.max' => 'Panjang teks barcode melebihi batas maksimum.',
        ]);

        $result = $this->verifier->verify($validated['scanned_url']);

        $verification = EicvVerification::create([
            ...$result,
            'scan_method' => $validated['scan_method'] ?? 'manual',
            'verified_by_ip' => $request->ip(),
            'verified_at' => now(),
        ]);

        return response()->json([
            'data' => $verification,
        ], 201);
    }

    public function recheck(Request $request, EicvVerification $verification): JsonResponse
    {
        $result = $this->verifier->verify($verification->scanned_url, forceRefresh: true);

        $verification->update([
            ...$result,
            'verified_by_ip' => $request->ip(),
            'verified_at' => now(),
        ]);

        return response()->json([
            'data' => $verification->fresh(),
        ]);
    }

    public function show(EicvVerification $verification): JsonResponse
    {
        return response()->json([
            'data' => $verification,
        ]);
    }

    public function destroy(EicvVerification $verification): JsonResponse
    {
        $verification->delete();

        return response()->json([
            'message' => 'Riwayat verifikasi berhasil dihapus.',
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)
            ->orderByDesc('verified_at')
            ->orderByDesc('id');

        $filename = 'riwayat-verifikasi-eicv-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM so Microsoft Excel opens Indonesian characters cleanly
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Waktu Verifikasi',
                'Status Verifikasi',
                'Awalan Resmi Valid',
                'Nama Pemilik Sertifikat',
                'Nomor E-ICV',
                'Nomor Paspor',
                'Tanggal Lahir',
                'Vaksin',
                'Metode Scan',
                'Awalan Link Cocok',
                'Link Hasil Scan',
                'Keterangan',
            ]);

            $query->chunk(200, function ($rows) use ($handle): void {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        optional($row->verified_at)->format('Y-m-d H:i:s'),
                        strtoupper($row->verification_status),
                        $row->is_valid_prefix ? 'YA' : 'TIDAK',
                        $row->holder_name ?? '-',
                        $row->certificate_number ?? '-',
                        $row->passport_number ?? '-',
                        $row->birth_date ?? '-',
                        $row->vaccine_summary ?? '-',
                        strtoupper($row->scan_method),
                        $row->matched_prefix ?? '-',
                        $row->scanned_url,
                        $row->message ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function filteredQuery(Request $request)
    {
        $query = EicvVerification::query();

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search): void {
                $like = '%'.$search.'%';
                $q->where('holder_name', 'like', $like)
                    ->orWhere('certificate_number', 'like', $like)
                    ->orWhere('passport_number', 'like', $like)
                    ->orWhere('vaccine_summary', 'like', $like)
                    ->orWhere('scanned_url', 'like', $like);
            });
        }

        $status = (string) $request->query('status', '');
        if (in_array($status, [
            EicvVerification::STATUS_ASLI,
            EicvVerification::STATUS_TIDAK_DITEMUKAN,
            EicvVerification::STATUS_GAGAL_BACA,
            EicvVerification::STATUS_PALSU,
        ], true)) {
            $query->where('verification_status', $status);
        }

        if ($date = trim((string) $request->query('date', ''))) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $query->whereDate('verified_at', $date);
            }
        }

        return $query;
    }
}

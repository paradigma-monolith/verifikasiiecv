<?php

namespace App\Services;

use App\Models\EicvVerification;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

class EicvVerifierService
{
    /**
     * Allowed official prefixes from config, sorted longest-first for specific matching.
     *
     * @return array<int, string>
     */
    public function allowedPrefixes(): array
    {
        return config('eicv.allowed_prefixes', []);
    }

    /**
     * Validate whether the scanned text starts with one of the official Kemenkes E-ICV prefixes
     * and resolves strictly to an official Kemenkes/Depkes host over HTTPS.
     */
    public function matchAllowedPrefix(string $rawInput): ?string
    {
        $url = trim($rawInput);

        if ($url === '' || preg_match('/[\s\x00-\x1F\x7F]/', $url)) {
            return null;
        }

        $parsed = parse_url($url);
        if (! is_array($parsed)) {
            return null;
        }

        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
        $host = strtolower((string) ($parsed['host'] ?? ''));

        // Defense against credential-in-URL spoofing (e.g. https://sinkarkes.kemkes.go.id@evil.com)
        if ($scheme !== 'https' || $host === '' || isset($parsed['user']) || isset($parsed['pass'])) {
            return null;
        }

        $allowedHosts = array_map('strtolower', config('eicv.allowed_hosts', []));
        if (! in_array($host, $allowedHosts, true)) {
            return null;
        }

        // Normalize only scheme & host to lowercase while keeping case-sensitive path/query tokens intact.
        $normalizedUrl = preg_replace_callback(
            '/^(https?:\/\/)([^\/?#]+)/i',
            static fn (array $m): string => strtolower($m[1].$m[2]),
            $url
        ) ?? $url;

        $prefixes = $this->allowedPrefixes();
        usort($prefixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($prefixes as $prefix) {
            if (str_starts_with($normalizedUrl, $prefix)) {
                return $prefix;
            }
        }

        return null;
    }

    /**
     * Decode SINKARKES `?t=<base64>` token (e.g. "RTI2LTAwMTEzMDYwMy5pY3Y=" -> "E26-001130603.icv")
     * or direct query parameters if present.
     *
     * @return array{certificate_number: ?string, document_type: ?string}
     */
    public function extractParamsFromUrl(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return ['certificate_number' => null, 'document_type' => null];
        }

        parse_str($query, $params);

        if (! empty($params['no_dokumen']) && is_string($params['no_dokumen'])) {
            return [
                'certificate_number' => trim($params['no_dokumen']),
                'document_type' => ! empty($params['jenis_dokumen']) && is_string($params['jenis_dokumen'])
                    ? trim($params['jenis_dokumen'])
                    : 'icv',
            ];
        }

        if (empty($params['t']) || ! is_string($params['t'])) {
            return ['certificate_number' => null, 'document_type' => null];
        }

        $token = str_replace([' ', '-', '_'], ['+', '+', '/'], trim($params['t']));
        $padding = strlen($token) % 4;
        if ($padding > 0) {
            $token .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($token, true);
        if ($decoded === false || trim($decoded) === '') {
            return ['certificate_number' => null, 'document_type' => null];
        }

        $decoded = trim($decoded);
        $lastDot = strrpos($decoded, '.');

        if ($lastDot !== false) {
            $docNumber = trim(substr($decoded, 0, $lastDot));
            $docType = strtolower(trim(substr($decoded, $lastDot + 1)));

            $knownTypes = config('eicv.known_document_types', ['icv', 'icv-haji']);
            if ($docNumber !== '' && in_array($docType, $knownTypes, true)) {
                return [
                    'certificate_number' => $docNumber,
                    'document_type' => $docType,
                ];
            }
        }

        // If decoded value looks like an ICV code without extension
        if (preg_match('/^[A-Za-z0-9\-_\/]{4,64}$/', $decoded)) {
            return [
                'certificate_number' => $decoded,
                'document_type' => 'icv',
            ];
        }

        return ['certificate_number' => null, 'document_type' => null];
    }

    /**
     * Full end-to-end verification:
     * 1. Validate URL prefix against the 8 official Kemenkes prefixes.
     * 2. Fetch and parse certificate contents from SINKARKES / official link.
     * 3. Return structured result ready for DB persistence and API response.
     *
     * @return array<string, mixed>
     */
    public function verify(string $rawUrl): array
    {
        $url = trim($rawUrl);
        $matchedPrefix = $this->matchAllowedPrefix($url);

        if ($matchedPrefix === null) {
            return [
                'scanned_url' => $url,
                'matched_prefix' => null,
                'is_valid_prefix' => false,
                'verification_status' => EicvVerification::STATUS_PALSU,
                'document_type' => null,
                'certificate_number' => null,
                'holder_name' => null,
                'passport_number' => null,
                'birth_date' => null,
                'vaccine_summary' => null,
                'vaccinations' => [],
                'other_vaccinations' => [],
                'source_http_status' => null,
                'message' => 'Link hasil scan barcode TIDAK diawali domain resmi Kementerian Kesehatan RI / SINKARKES. Sertifikat E-ICV terindikasi tidak asli / palsu.',
            ];
        }

        $urlParams = $this->extractParamsFromUrl($url);
        $docNumber = $urlParams['certificate_number'];
        $docType = $urlParams['document_type'];

        $httpStatus = null;
        $lastError = null;

        // Strategy A: If `?t=` cleanly decoded to document number + type, query SINKARKES search endpoint directly
        if ($docNumber !== null && $docType !== null) {
            try {
                $searchUrl = config('eicv.search_endpoint', 'https://sinkarkes.kemkes.go.id/welcome/check_document/search');
                $searchResponse = $this->httpClient()->get($searchUrl, [
                    'jenis_dokumen' => $docType,
                    'no_dokumen' => $docNumber,
                ]);
                $httpStatus = $searchResponse->status();

                if ($searchResponse->successful()) {
                    $parsed = $this->parseCertificateHtml($searchResponse->body(), $docNumber, $docType);

                    if ($parsed['found']) {
                        return $this->buildFoundResult($url, $matchedPrefix, $parsed, $httpStatus);
                    }

                    if ($parsed['not_found']) {
                        return $this->buildNotFoundResult($url, $matchedPrefix, $docNumber, $docType, $httpStatus);
                    }
                }
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        // Strategy B: Fetch the scanned official URL directly (and follow embedded check_document AJAX loader if present)
        try {
            $pageResponse = $this->httpClient()->get($url);
            $httpStatus = $pageResponse->status();

            if (! $pageResponse->successful()) {
                return $this->buildFetchFailedResult(
                    $url,
                    $matchedPrefix,
                    $docNumber,
                    $docType,
                    $httpStatus,
                    'Link tervalidasi sebagai awalan resmi Kemenkes, namun server mengembalikan status HTTP '.$httpStatus.'.'
                );
            }

            $body = $pageResponse->body();

            // Case B1: The scanned page itself already contains the certificate HTML
            $directParsed = $this->parseCertificateHtml($body, $docNumber, $docType);
            if ($directParsed['found']) {
                return $this->buildFoundResult($url, $matchedPrefix, $directParsed, $httpStatus);
            }

            if ($directParsed['not_found']) {
                return $this->buildNotFoundResult($url, $matchedPrefix, $docNumber, $docType, $httpStatus);
            }

            // Case B2: The page is SINKARKES check_document loader page — extract JS/form parameters and query /search
            $loaderMeta = $this->extractSinkarkesLoaderMeta($body);
            $resolvedDocNumber = $loaderMeta['no_dokumen'] ?? $docNumber;
            $resolvedDocType = $loaderMeta['jenis_dokumen'] ?? $docType ?? 'icv';
            $searchEndpoint = $loaderMeta['search_endpoint'] ?? config('eicv.search_endpoint');

            if ($resolvedDocNumber !== null && $searchEndpoint !== null && $this->isOfficialEndpoint($searchEndpoint)) {
                $ajaxResponse = $this->httpClient()->get($searchEndpoint, [
                    'jenis_dokumen' => $resolvedDocType,
                    'no_dokumen' => $resolvedDocNumber,
                ]);
                $httpStatus = $ajaxResponse->status();

                if ($ajaxResponse->successful()) {
                    $ajaxParsed = $this->parseCertificateHtml($ajaxResponse->body(), $resolvedDocNumber, $resolvedDocType);

                    if ($ajaxParsed['found']) {
                        return $this->buildFoundResult($url, $matchedPrefix, $ajaxParsed, $httpStatus);
                    }

                    if ($ajaxParsed['not_found']) {
                        return $this->buildNotFoundResult($url, $matchedPrefix, $resolvedDocNumber, $resolvedDocType, $httpStatus);
                    }
                }
            }

            return $this->buildFetchFailedResult(
                $url,
                $matchedPrefix,
                $resolvedDocNumber,
                $resolvedDocType,
                $httpStatus,
                'Awalan link resmi Kemenkes terverifikasi, namun halaman tujuan tidak memuat data sertifikat E-ICV yang dapat dibaca otomatis.'
            );
        } catch (Throwable $e) {
            $reason = $lastError ?: $e->getMessage();

            return $this->buildFetchFailedResult(
                $url,
                $matchedPrefix,
                $docNumber,
                $docType,
                $httpStatus,
                'Awalan link resmi Kemenkes terverifikasi (ASLI), namun koneksi ke server SINKARKES gagal dibaca saat ini ('.$this->summarizeConnectionError($reason).').'
            );
        }
    }

    /**
     * Parse official SINKARKES certificate HTML into structured fields.
     *
     * @return array<string, mixed>
     */
    public function parseCertificateHtml(string $html, ?string $fallbackDocNumber = null, ?string $fallbackDocType = null): array
    {
        $trimmed = trim($html);
        if ($trimmed === '') {
            return ['found' => false, 'not_found' => false];
        }

        $hasMainData = str_contains($trimmed, 'main-data') || str_contains($trimmed, 'icv-body') || str_contains($trimmed, 'icv-table');
        if (! $hasMainData && preg_match('/tidak\s+dapat\s+kami\s+temukan|dokumen.*tidak\s+ditemukan|data\s+tidak\s+ditemukan|certificate\s+not\s+found/i', strip_tags($trimmed))) {
            return ['found' => false, 'not_found' => true];
        }

        $dom = new DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$trimmed, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $xpath = new DOMXPath($dom);

        $holderName = null;
        $passportNumber = null;
        $birthDate = null;
        $certificateNumber = $fallbackDocNumber;
        $vaccinations = [];
        $otherVaccinations = [];

        // 1. Primary SINKARKES E-ICV card layout (.main-data)
        $mainDataNodes = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' main-data ')]");
        if ($mainDataNodes !== false && $mainDataNodes->length > 0) {
            $mainData = $mainDataNodes->item(0);
            $cells = $xpath->query('.//tr[1]/td', $mainData);

            if ($cells !== false && $cells->length > 0) {
                $infoCell = $cells->item(0);
                $paragraphs = $xpath->query('.//p', $infoCell);

                if ($paragraphs !== false && $paragraphs->length > 0) {
                    $remaining = [];
                    /** @var DOMElement $p */
                    foreach ($paragraphs as $p) {
                        $text = $this->cleanText($p->textContent);
                        if ($text === '') {
                            continue;
                        }

                        $classAttr = ' '.strtolower((string) $p->getAttribute('class')).' ';

                        if (preg_match('/^passport\s*[:\-]?\s*(.+)$/i', $text, $m)) {
                            $passportNumber = trim($m[1]);
                            continue;
                        }

                        if ($holderName === null && str_contains($classAttr, ' text-bold ')) {
                            $holderName = $text;
                            continue;
                        }

                        $remaining[] = $text;
                    }

                    foreach ($remaining as $item) {
                        if ($holderName === null && ! preg_match('/^\d{1,2}(st|nd|rd|th)?\s+[A-Za-z]+\s+\d{4}$/i', $item)) {
                            $holderName = $item;
                        } elseif ($birthDate === null) {
                            $birthDate = $item;
                        }
                    }
                }

                // Second cell typically holds the QR image and printed certificate code (e.g. E26-001130603)
                if ($cells->length > 1) {
                    $codeText = $this->cleanText($cells->item(1)->textContent);
                    if ($codeText !== '') {
                        $certificateNumber = $codeText;
                    }
                }
            }
        }

        // 2. Vaccination tables (.icv-table)
        $tables = $xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' icv-table ')]");
        if ($tables !== false && $tables->length > 0) {
            // Table 1: Primary International Health Regulations vaccination rows
            $primaryRows = $xpath->query('.//tbody/tr', $tables->item(0));
            if ($primaryRows !== false) {
                foreach ($primaryRows as $row) {
                    $tds = $xpath->query('./td', $row);
                    if ($tds === false || $tds->length === 0) {
                        continue;
                    }

                    $values = [];
                    for ($i = 0; $i < $tds->length; $i++) {
                        $values[] = $this->cleanSlashSpacing($this->cleanText($tds->item($i)->textContent));
                    }

                    if (implode('', $values) === '') {
                        continue;
                    }

                    $vaccinations[] = [
                        'vaccine' => $values[0] ?? null,
                        'manufacturer_batch' => $values[1] ?? null,
                        'date' => $this->normalizeDateCell($values[2] ?? null),
                        'valid_until' => $this->normalizeDateCell($values[3] ?? null),
                        'location_clinician' => $values[4] ?? null,
                    ];
                }
            }

            // Table 2: Other Details / Boosters
            if ($tables->length > 1) {
                $secondaryRows = $xpath->query('.//tbody/tr', $tables->item(1));
                if ($secondaryRows !== false) {
                    foreach ($secondaryRows as $row) {
                        $tds = $xpath->query('./td', $row);
                        if ($tds === false || $tds->length === 0) {
                            continue;
                        }

                        $values = [];
                        for ($i = 0; $i < $tds->length; $i++) {
                            $values[] = $this->cleanSlashSpacing($this->cleanText($tds->item($i)->textContent));
                        }

                        if (implode('', $values) === '') {
                            continue;
                        }

                        $otherVaccinations[] = [
                            'disease_targeted' => $values[0] ?? null,
                            'date' => $values[1] ?? null,
                            'manufacturer_batch' => $values[2] ?? null,
                            'next_booster' => $values[3] ?? null,
                            'stamp_signature' => $values[4] ?? null,
                        ];
                    }
                }
            }
        }

        // 3. Fallback key-value table parser (for icv-haji or legacy kespel.depkes.go.id pages)
        if ($holderName === null) {
            $kvRows = $xpath->query('//tr');
            if ($kvRows !== false) {
                foreach ($kvRows as $row) {
                    $cells = $xpath->query('./th|./td', $row);
                    if ($cells === false || $cells->length < 2) {
                        continue;
                    }

                    $label = strtolower($this->cleanText($cells->item(0)->textContent));
                    $valueCellIndex = $cells->length >= 3 && trim($cells->item(1)->textContent) === ':' ? 2 : 1;
                    $value = $this->cleanText($cells->item($valueCellIndex)->textContent);

                    if ($value === '' || $value === ':') {
                        continue;
                    }

                    if ($holderName === null && preg_match('/^(nama|nama lengkap|nama jamaah|name|holder name|full name)\b/i', $label)) {
                        $holderName = $value;
                    } elseif ($passportNumber === null && preg_match('/^(no\.?\s*paspor|paspor|passport|passport\s*no)/i', $label)) {
                        $passportNumber = $value;
                    } elseif ($birthDate === null && preg_match('/^(tanggal\s*lahir|tgl\.?\s*lahir|date\s*of\s*birth|dob)/i', $label)) {
                        $birthDate = $value;
                    } elseif ($certificateNumber === null && preg_match('/^(no\.?\s*icv|nomor\s*icv|no\.?\s*dokumen|certificate\s*no)/i', $label)) {
                        $certificateNumber = $value;
                    }
                }
            }
        }

        $found = $holderName !== null || count($vaccinations) > 0;

        $vaccineNames = array_values(array_filter(array_map(
            static fn (array $v): ?string => $v['vaccine'] ?? null,
            $vaccinations
        )));

        return [
            'found' => $found,
            'not_found' => false,
            'document_type' => $fallbackDocType ?: 'icv',
            'certificate_number' => $certificateNumber,
            'holder_name' => $holderName,
            'passport_number' => $passportNumber,
            'birth_date' => $birthDate,
            'vaccine_summary' => count($vaccineNames) > 0 ? implode(', ', array_unique($vaccineNames)) : null,
            'vaccinations' => $vaccinations,
            'other_vaccinations' => $otherVaccinations,
        ];
    }

    /**
     * Extract embedded JS/form metadata from SINKARKES check_document loader page.
     *
     * @return array{jenis_dokumen: ?string, no_dokumen: ?string, search_endpoint: ?string}
     */
    protected function extractSinkarkesLoaderMeta(string $html): array
    {
        $jenisDokumen = null;
        $noDokumen = null;
        $searchEndpoint = null;

        if (preg_match('/(?:let|var|const)\s+jenis_dokumen\s*=\s*[\'"]([^\'"]+)[\'"]/i', $html, $m)) {
            $jenisDokumen = trim($m[1]);
        }

        if (preg_match('/(?:let|var|const)\s+barcode\s*=\s*[\'"]([^\'"]+)[\'"]/i', $html, $m)) {
            $noDokumen = trim($m[1]);
        }

        if ($noDokumen === null && preg_match('/id=["\']no_dokumen["\'][^>]*value=["\']([^"\']+)["\']/i', $html, $m)) {
            $noDokumen = trim($m[1]);
        }

        if (preg_match('/\.load\(\s*[\'"](https:\/\/[^\'"]+check_document\/search[^\'"]*)[\'"]/i', $html, $m)) {
            $searchEndpoint = trim($m[1]);
        }

        return [
            'jenis_dokumen' => $jenisDokumen,
            'no_dokumen' => $noDokumen,
            'search_endpoint' => $searchEndpoint,
        ];
    }

    protected function isOfficialEndpoint(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $allowedHosts = array_map('strtolower', config('eicv.allowed_hosts', []));

        return $scheme === 'https' && in_array($host, $allowedHosts, true);
    }

    protected function httpClient(): PendingRequest
    {
        $verifyOption = config('eicv.verify_ssl', true);
        if ($verifyOption === true || $verifyOption === 'true' || $verifyOption === 1) {
            $caBundle = config('eicv.ca_bundle');
            $verifyOption = is_string($caBundle) && is_file($caBundle) ? $caBundle : true;
        } else {
            $verifyOption = false;
        }

        return Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        ])
            ->timeout((int) config('eicv.http_timeout', 25))
            ->connectTimeout((int) config('eicv.connect_timeout', 12))
            ->withOptions([
                'verify' => $verifyOption,
                'allow_redirects' => ['max' => 5],
            ]);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    protected function buildFoundResult(string $url, string $matchedPrefix, array $parsed, ?int $httpStatus): array
    {
        $holderName = $parsed['holder_name'] ?? null;
        $message = $holderName
            ? "Sertifikat E-ICV ASLI dan terdaftar resmi di SINKARKES Kemenkes RI atas nama {$holderName}."
            : 'Sertifikat E-ICV ASLI dan terdaftar resmi di SINKARKES Kemenkes RI.';

        return [
            'scanned_url' => $url,
            'matched_prefix' => $matchedPrefix,
            'is_valid_prefix' => true,
            'verification_status' => EicvVerification::STATUS_ASLI,
            'document_type' => $parsed['document_type'] ?? 'icv',
            'certificate_number' => $parsed['certificate_number'] ?? null,
            'holder_name' => $holderName,
            'passport_number' => $parsed['passport_number'] ?? null,
            'birth_date' => $parsed['birth_date'] ?? null,
            'vaccine_summary' => $parsed['vaccine_summary'] ?? null,
            'vaccinations' => $parsed['vaccinations'] ?? [],
            'other_vaccinations' => $parsed['other_vaccinations'] ?? [],
            'source_http_status' => $httpStatus,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildNotFoundResult(
        string $url,
        string $matchedPrefix,
        ?string $docNumber,
        ?string $docType,
        ?int $httpStatus
    ): array {
        return [
            'scanned_url' => $url,
            'matched_prefix' => $matchedPrefix,
            'is_valid_prefix' => true,
            'verification_status' => EicvVerification::STATUS_TIDAK_DITEMUKAN,
            'document_type' => $docType ?: 'icv',
            'certificate_number' => $docNumber,
            'holder_name' => null,
            'passport_number' => null,
            'birth_date' => null,
            'vaccine_summary' => null,
            'vaccinations' => [],
            'other_vaccinations' => [],
            'source_http_status' => $httpStatus,
            'message' => 'Awalan link berasal dari domain resmi Kemenkes, namun nomor dokumen tidak ditemukan pada database SINKARKES.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildFetchFailedResult(
        string $url,
        string $matchedPrefix,
        ?string $docNumber,
        ?string $docType,
        ?int $httpStatus,
        string $message
    ): array {
        return [
            'scanned_url' => $url,
            'matched_prefix' => $matchedPrefix,
            'is_valid_prefix' => true,
            'verification_status' => EicvVerification::STATUS_GAGAL_BACA,
            'document_type' => $docType ?: 'icv',
            'certificate_number' => $docNumber,
            'holder_name' => null,
            'passport_number' => null,
            'birth_date' => null,
            'vaccine_summary' => null,
            'vaccinations' => [],
            'other_vaccinations' => [],
            'source_http_status' => $httpStatus,
            'message' => $message,
        ];
    }

    protected function cleanText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    protected function cleanSlashSpacing(string $text): string
    {
        return trim(preg_replace('/\s*\/\s*/', ' / ', $text) ?? $text);
    }

    protected function normalizeDateCell(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        if (str_contains($text, '-0001') || str_contains($text, '0000-00-00') || str_contains($text, '1970-01-01')) {
            return null;
        }

        return $text;
    }

    protected function summarizeConnectionError(string $error): string
    {
        if (str_contains(strtolower($error), 'timed out') || str_contains(strtolower($error), 'timeout')) {
            return 'waktu koneksi habis / timeout';
        }

        if (str_contains(strtolower($error), 'could not resolve host')) {
            return 'tidak ada koneksi internet atau DNS gagal';
        }

        if (str_contains(strtolower($error), 'ssl')) {
            return 'kesalahan sertifikat SSL';
        }

        return 'server tidak dapat dijangkau';
    }
}

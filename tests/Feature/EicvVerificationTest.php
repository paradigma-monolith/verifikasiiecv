<?php

namespace Tests\Feature;

use App\Models\EicvVerification;
use App\Services\EicvVerifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EicvVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function sampleSinkarkesCertificateHtml(): string
    {
        return <<<'HTML'
<div class="row">
	<div class="col-md-6 col-md-offset-4 icv">
		<section class="icv-header">
			<h3>International Certificate of Vaccination (Prophylaxis)</h3>
		</section>
		<section class="icv-body">
			<div class="main-data">
				<table style="width: 100%">
					<tr>
						<td>
							<p class="text-bold">A Badruhida***</p>
							<p>Passport Y0194***</p>
							<p>22nd May 1961</p>
						</td>
						<td style="width:146px; text-align: center">
							<img src="https://sinkarkes.kemkes.go.id/welcome/check_document/qrcode?data=test" alt="">
							E26-001130603
						</td>
					</tr>
				</table>
			</div>
			<div class="data-detail">
				<table class="icv-table">
					<tbody>
						<tr>
							<td class="text-bold">MENINGITIS MENINGOCOCCUS</td>
							<td>MERSI E3202409014</td>
							<td>19th November 2026</td>
							<td>3rd December 2029</td>
							<td>Bandung / Bandung / dr. NADINDRA MADHYASTHA</td>
						</tr>
						<tr>
							<td class="text-bold">POLIO</td>
							<td>BIOFARMA 21001426</td>
							<td>19th November 2026</td>
							<td>30th November -0001</td>
							<td>Bandung / Klinik Pratama Yeka Medika / dr. Roni halomoan</td>
						</tr>
					</tbody>
				</table>
			</div>
		</section>
	</div>
</div>
HTML;
    }

    public function test_all_eight_official_prefixes_are_recognized_as_valid(): void
    {
        $service = app(EicvVerifierService::class);

        $validUrls = [
            'https://sinkarkes.kemkes.go.id/welcome/check_document?t=RTI2LTAwMTEzMDYwMy5pY3Y=',
            'https://www.sinkarkes.kemkes.go.id/welcome/check_document?t=RTI2LTAwMTEzMDYwMy5pY3Y=',
            'https://kespel.depkes.go.id/welcome/check_document?t=RTI2LTAwMTEzMDYwMy5pY3Y=',
            'https://www.kespel.depkes.go.id/welcome/check_document?t=RTI2LTAwMTEzMDYwMy5pY3Y=',
            'https://www.sinkarkes.kemkes.go.id/index.php/welcome/check_document?t=RTI2LTAwMTEzMDYwMy5pY3Y=',
            'https://www.sinkarkes.kemkes.go.id/vaksinasi/cek/E26-001130603',
            'https://sinkarkes.kemkes.go.id/vaksinasi/cek/E26-001130603',
            'https://sinkarkes.kemkes.go.id/index.php/welcome/check_document?t=RTI2LTAwMTEzMDYwMy5pY3Y=',
        ];

        foreach ($validUrls as $url) {
            $matched = $service->matchAllowedPrefix($url);
            $this->assertNotNull($matched, "Expected prefix to match for: {$url}");
        }
    }

    public function test_spoofed_and_non_official_urls_are_rejected(): void
    {
        $service = app(EicvVerifierService::class);

        $invalidUrls = [
            'https://sinkarkes.kemkes.go.id.verifikasi.com/welcome/check_document?t=abc',
            'https://sinkarkes-kemkes.com/welcome/check_document?t=abc',
            'https://sinkarkesgo.id/welcome/check_document?t=abc',
            'http://sinkarkes.kemkes.go.id/welcome/check_document?t=abc',
            'https://sinkarkes.kemkes.go.id@evil.com/welcome/check_document?t=abc',
            'https://example.com/eicv/12345',
            'E26-001130603',
        ];

        foreach ($invalidUrls as $url) {
            $this->assertNull($service->matchAllowedPrefix($url), "Expected URL to be rejected: {$url}");
        }
    }

    public function test_verify_authentic_eicv_reads_holder_name_and_persists_record(): void
    {
        Http::fake([
            'https://sinkarkes.kemkes.go.id/welcome/check_document/search*' => Http::response(
                $this->sampleSinkarkesCertificateHtml(),
                200
            ),
        ]);

        $response = $this->postJson('/api/verifications/verify', [
            'scanned_url' => 'https://sinkarkes.kemkes.go.id/index.php/welcome/check_document?t=RTI2LTAwMTEzMDYwMy5pY3Y=',
            'scan_method' => 'camera',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_valid_prefix', true)
            ->assertJsonPath('data.verification_status', EicvVerification::STATUS_ASLI)
            ->assertJsonPath('data.holder_name', 'A Badruhida***')
            ->assertJsonPath('data.certificate_number', 'E26-001130603')
            ->assertJsonPath('data.passport_number', 'Y0194***')
            ->assertJsonPath('data.birth_date', '22nd May 1961')
            ->assertJsonPath('data.vaccine_summary', 'MENINGITIS MENINGOCOCCUS, POLIO')
            ->assertJsonPath('data.vaccinations.0.valid_until', '3rd December 2029')
            ->assertJsonPath('data.vaccinations.1.valid_until', null)
            ->assertJsonPath('data.matched_prefix', 'https://sinkarkes.kemkes.go.id/index.php/welcome/check_document?t=');

        $this->assertDatabaseHas('eicv_verifications', [
            'holder_name' => 'A Badruhida***',
            'certificate_number' => 'E26-001130603',
            'verification_status' => EicvVerification::STATUS_ASLI,
            'is_valid_prefix' => true,
        ]);
    }

    public function test_verify_fake_url_marks_palsu_without_making_http_request(): void
    {
        Http::fake();

        $response = $this->postJson('/api/verifications/verify', [
            'scanned_url' => 'https://sinkarkes-palsu.com/welcome/check_document?t=RTI2LTAwMTEzMDYwMy5pY3Y=',
            'scan_method' => 'upload',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_valid_prefix', false)
            ->assertJsonPath('data.verification_status', EicvVerification::STATUS_PALSU)
            ->assertJsonPath('data.holder_name', null);

        Http::assertNothingSent();

        $this->assertDatabaseHas('eicv_verifications', [
            'verification_status' => EicvVerification::STATUS_PALSU,
            'is_valid_prefix' => false,
        ]);
    }

    public function test_verify_official_url_when_document_not_found_in_sinkarkes(): void
    {
        Http::fake([
            'https://sinkarkes.kemkes.go.id/welcome/check_document/search*' => Http::response(
                '<p class="h3">Maaf, dokumen yang Anda cari tidak dapat Kami temukan.</p>',
                200
            ),
        ]);

        // base64("E26-000000000.icv") = "RTI2LTAwMDAwMDAwMC5pY3Y="
        $response = $this->postJson('/api/verifications/verify', [
            'scanned_url' => 'https://sinkarkes.kemkes.go.id/welcome/check_document?t=RTI2LTAwMDAwMDAwMC5pY3Y=',
            'scan_method' => 'manual',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_valid_prefix', true)
            ->assertJsonPath('data.verification_status', EicvVerification::STATUS_TIDAK_DITEMUKAN)
            ->assertJsonPath('data.certificate_number', 'E26-000000000');
    }

    public function test_verify_follows_loader_page_when_url_has_no_token_param(): void
    {
        $loaderHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
<input type="text" id="no_dokumen" value="E26-001130603" />
<script>
let jenis_dokumen = "icv";
let barcode = "E26-001130603";
$('#result-block').load('https://sinkarkes.kemkes.go.id/welcome/check_document/search', {});
</script>
</body>
</html>
HTML;

        Http::fake([
            'https://kespel.depkes.go.id/cek/E26-001130603' => Http::response($loaderHtml, 200),
            'https://sinkarkes.kemkes.go.id/welcome/check_document/search*' => Http::response(
                $this->sampleSinkarkesCertificateHtml(),
                200
            ),
        ]);

        $response = $this->postJson('/api/verifications/verify', [
            'scanned_url' => 'https://kespel.depkes.go.id/cek/E26-001130603',
            'scan_method' => 'manual',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_valid_prefix', true)
            ->assertJsonPath('data.verification_status', EicvVerification::STATUS_ASLI)
            ->assertJsonPath('data.holder_name', 'A Badruhida***');
    }

    public function test_stats_listing_csv_export_and_delete_endpoints_work(): void
    {
        Http::fake([
            'https://sinkarkes.kemkes.go.id/welcome/check_document/search*' => Http::response(
                $this->sampleSinkarkesCertificateHtml(),
                200
            ),
        ]);

        $created = $this->postJson('/api/verifications/verify', [
            'scanned_url' => 'https://sinkarkes.kemkes.go.id/index.php/welcome/check_document?t=RTI2LTAwMTEzMDYwMy5pY3Y=',
            'scan_method' => 'camera',
        ])->json('data');

        $this->postJson('/api/verifications/verify', [
            'scanned_url' => 'https://phishing.example.com/fake',
            'scan_method' => 'manual',
        ]);

        $this->getJson('/api/verifications/stats')
            ->assertOk()
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('stats.asli', 1)
            ->assertJsonPath('stats.palsu', 1)
            ->assertJsonCount(8, 'allowed_prefixes');

        $this->getJson('/api/verifications?search=Badruhida&status=asli')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.holder_name', 'A Badruhida***');

        $csv = $this->get('/api/verifications/export/csv');
        $csv->assertOk();
        $this->assertStringContainsString('A Badruhida***', $csv->streamedContent());

        $this->deleteJson('/api/verifications/'.$created['id'])
            ->assertOk();

        $this->assertDatabaseMissing('eicv_verifications', ['id' => $created['id']]);
    }
}

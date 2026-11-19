<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eicv_verifications', function (Blueprint $table) {
            $table->id();
            $table->text('scanned_url');
            $table->string('matched_prefix', 255)->nullable();
            $table->boolean('is_valid_prefix')->default(false)->index();
            $table->string('verification_status', 32)->index(); // asli, tidak_ditemukan, gagal_baca, palsu
            $table->string('scan_method', 20)->default('camera'); // camera, upload, manual
            $table->string('document_type', 64)->nullable();
            $table->string('certificate_number', 100)->nullable()->index();
            $table->string('holder_name', 255)->nullable()->index();
            $table->string('passport_number', 100)->nullable();
            $table->string('birth_date', 100)->nullable();
            $table->string('vaccine_summary', 255)->nullable();
            $table->json('vaccinations')->nullable();
            $table->json('other_vaccinations')->nullable();
            $table->unsignedSmallInteger('source_http_status')->nullable();
            $table->text('message')->nullable();
            $table->string('verified_by_ip', 45)->nullable();
            $table->timestamp('verified_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eicv_verifications');
    }
};

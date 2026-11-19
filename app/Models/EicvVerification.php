<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EicvVerification extends Model
{
    use HasFactory;

    public const STATUS_ASLI = 'asli';
    public const STATUS_TIDAK_DITEMUKAN = 'tidak_ditemukan';
    public const STATUS_GAGAL_BACA = 'gagal_baca';
    public const STATUS_PALSU = 'palsu';

    protected $fillable = [
        'scanned_url',
        'matched_prefix',
        'is_valid_prefix',
        'verification_status',
        'scan_method',
        'document_type',
        'certificate_number',
        'holder_name',
        'passport_number',
        'birth_date',
        'vaccine_summary',
        'vaccinations',
        'other_vaccinations',
        'source_http_status',
        'message',
        'verified_by_ip',
        'verified_at',
    ];

    protected $casts = [
        'is_valid_prefix' => 'boolean',
        'vaccinations' => 'array',
        'other_vaccinations' => 'array',
        'source_http_status' => 'integer',
        'verified_at' => 'datetime',
    ];
}

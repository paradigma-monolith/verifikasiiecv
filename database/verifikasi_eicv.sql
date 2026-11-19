-- Database Schema for Aplikasi Verifikasi Keaslian E-ICV
-- Balai Kekarantinaan Kesehatan Kelas I Bandung - Kementerian Kesehatan RI
-- Generated for MySQL 8.0+ / MariaDB 10.4+

CREATE DATABASE IF NOT EXISTS `verifikasi_eicv`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `verifikasi_eicv`;

CREATE TABLE IF NOT EXISTS `eicv_verifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scanned_url` TEXT NOT NULL,
  `matched_prefix` VARCHAR(255) NULL,
  `is_valid_prefix` TINYINT(1) NOT NULL DEFAULT 0,
  `verification_status` VARCHAR(32) NOT NULL COMMENT 'asli, tidak_ditemukan, gagal_baca, palsu',
  `scan_method` VARCHAR(20) NOT NULL DEFAULT 'camera' COMMENT 'camera, upload, manual',
  `document_type` VARCHAR(64) NULL,
  `certificate_number` VARCHAR(100) NULL,
  `holder_name` VARCHAR(255) NULL,
  `passport_number` VARCHAR(100) NULL,
  `birth_date` VARCHAR(100) NULL,
  `vaccine_summary` VARCHAR(255) NULL,
  `vaccinations` JSON NULL,
  `other_vaccinations` JSON NULL,
  `source_http_status` SMALLINT UNSIGNED NULL,
  `message` TEXT NULL,
  `verified_by_ip` VARCHAR(45) NULL,
  `verified_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `eicv_verifications_is_valid_prefix_index` (`is_valid_prefix`),
  INDEX `eicv_verifications_verification_status_index` (`verification_status`),
  INDEX `eicv_verifications_certificate_number_index` (`certificate_number`),
  INDEX `eicv_verifications_holder_name_index` (`holder_name`),
  INDEX `eicv_verifications_verified_at_index` (`verified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

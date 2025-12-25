/*
 Navicat Premium Data Transfer

 Source Server         : local
 Source Server Type    : MariaDB
 Source Server Version : 101110 (10.11.10-MariaDB)
 Source Host           : localhost:3306
 Source Schema         : spm

 Target Server Type    : MariaDB
 Target Server Version : 101110 (10.11.10-MariaDB)
 File Encoding         : 65001

 Date: 25/12/2025 19:31:18
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for akreditasi_edpm_catatans
-- ----------------------------
DROP TABLE IF EXISTS `akreditasi_edpm_catatans`;
CREATE TABLE `akreditasi_edpm_catatans`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `akreditasi_id` bigint(20) UNSIGNED NOT NULL,
  `pesantren_id` bigint(20) UNSIGNED NOT NULL,
  `komponen_id` bigint(20) UNSIGNED NOT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `akreditasi_edpm_catatans_akreditasi_id_foreign`(`akreditasi_id`) USING BTREE,
  INDEX `akreditasi_edpm_catatans_pesantren_id_foreign`(`pesantren_id`) USING BTREE,
  INDEX `akreditasi_edpm_catatans_komponen_id_foreign`(`komponen_id`) USING BTREE,
  CONSTRAINT `akreditasi_edpm_catatans_akreditasi_id_foreign` FOREIGN KEY (`akreditasi_id`) REFERENCES `akreditasis` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `akreditasi_edpm_catatans_komponen_id_foreign` FOREIGN KEY (`komponen_id`) REFERENCES `master_edpm_komponens` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `akreditasi_edpm_catatans_pesantren_id_foreign` FOREIGN KEY (`pesantren_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of akreditasi_edpm_catatans
-- ----------------------------
INSERT INTO `akreditasi_edpm_catatans` VALUES (1, 1, 2, 1, 'ok', '2025-12-25 10:19:06', '2025-12-25 10:19:06');
INSERT INTO `akreditasi_edpm_catatans` VALUES (2, 1, 2, 2, 'ok', '2025-12-25 10:19:06', '2025-12-25 10:19:06');
INSERT INTO `akreditasi_edpm_catatans` VALUES (3, 2, 2, 1, 'ok', '2025-12-25 15:45:06', '2025-12-25 15:45:28');
INSERT INTO `akreditasi_edpm_catatans` VALUES (4, 2, 2, 2, 'ok', '2025-12-25 15:45:06', '2025-12-25 15:45:28');

-- ----------------------------
-- Table structure for akreditasi_edpms
-- ----------------------------
DROP TABLE IF EXISTS `akreditasi_edpms`;
CREATE TABLE `akreditasi_edpms`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `akreditasi_id` bigint(20) UNSIGNED NOT NULL,
  `pesantren_id` bigint(20) UNSIGNED NOT NULL,
  `butir_id` bigint(20) UNSIGNED NOT NULL,
  `isian` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `akreditasi_edpms_akreditasi_id_foreign`(`akreditasi_id`) USING BTREE,
  INDEX `akreditasi_edpms_pesantren_id_foreign`(`pesantren_id`) USING BTREE,
  INDEX `akreditasi_edpms_butir_id_foreign`(`butir_id`) USING BTREE,
  CONSTRAINT `akreditasi_edpms_akreditasi_id_foreign` FOREIGN KEY (`akreditasi_id`) REFERENCES `akreditasis` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `akreditasi_edpms_butir_id_foreign` FOREIGN KEY (`butir_id`) REFERENCES `master_edpm_butirs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `akreditasi_edpms_pesantren_id_foreign` FOREIGN KEY (`pesantren_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of akreditasi_edpms
-- ----------------------------
INSERT INTO `akreditasi_edpms` VALUES (1, 1, 2, 1, 'ok', '2025-12-25 10:19:06', '2025-12-25 10:19:06');
INSERT INTO `akreditasi_edpms` VALUES (2, 1, 2, 2, 'ok', '2025-12-25 10:19:06', '2025-12-25 10:19:06');
INSERT INTO `akreditasi_edpms` VALUES (3, 1, 2, 3, 'ok', '2025-12-25 10:19:06', '2025-12-25 10:19:06');
INSERT INTO `akreditasi_edpms` VALUES (4, 1, 2, 4, 'ok', '2025-12-25 10:19:06', '2025-12-25 10:19:06');
INSERT INTO `akreditasi_edpms` VALUES (5, 1, 2, 5, 'ok', '2025-12-25 10:19:06', '2025-12-25 10:19:06');
INSERT INTO `akreditasi_edpms` VALUES (6, 2, 2, 1, 'ok', '2025-12-25 15:45:06', '2025-12-25 15:45:28');
INSERT INTO `akreditasi_edpms` VALUES (7, 2, 2, 2, 'ok', '2025-12-25 15:45:06', '2025-12-25 15:45:28');
INSERT INTO `akreditasi_edpms` VALUES (8, 2, 2, 3, 'ok', '2025-12-25 15:45:06', '2025-12-25 15:45:28');
INSERT INTO `akreditasi_edpms` VALUES (9, 2, 2, 4, 'ok', '2025-12-25 15:45:06', '2025-12-25 15:45:28');
INSERT INTO `akreditasi_edpms` VALUES (10, 2, 2, 5, 'ok', '2025-12-25 15:45:06', '2025-12-25 15:45:28');

-- ----------------------------
-- Table structure for akreditasis
-- ----------------------------
DROP TABLE IF EXISTS `akreditasis`;
CREATE TABLE `akreditasis`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `parent` bigint(20) NULL DEFAULT NULL,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor_sk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `no_sk` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '',
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of akreditasis
-- ----------------------------
INSERT INTO `akreditasis` VALUES (1, 2, NULL, '03316589-5428-40f6-8423-21a471e65c10', NULL, '', 'again', 2, '2025-12-25 10:17:51', '2025-12-25 15:43:25', NULL);
INSERT INTO `akreditasis` VALUES (2, 2, NULL, 'ca9c60d9-b247-4a63-8d54-5e2c48fdf967', 'xxx', '', NULL, 1, '2025-12-25 15:44:25', '2025-12-25 15:46:30', NULL);

-- ----------------------------
-- Table structure for asesors
-- ----------------------------
DROP TABLE IF EXISTS `asesors`;
CREATE TABLE `asesors`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `nama_dengan_gelar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_tanpa_gelar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nbm_nia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `whatsapp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `nik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tempat_lahir` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tanggal_lahir` date NULL DEFAULT NULL,
  `unit_kerja` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `jabatan_utama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `jenis_kelamin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `alamat_kantor` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `alamat_rumah` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email_pribadi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `layanan_satuan_pendidikan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `rombel_sd` int(11) NOT NULL DEFAULT 0,
  `rombel_mi` int(11) NOT NULL DEFAULT 0,
  `rombel_smp` int(11) NOT NULL DEFAULT 0,
  `rombel_mts` int(11) NOT NULL DEFAULT 0,
  `rombel_sma` int(11) NOT NULL DEFAULT 0,
  `rombel_ma` int(11) NOT NULL DEFAULT 0,
  `rombel_smk` int(11) NOT NULL DEFAULT 0,
  `rombel_spm` int(11) NOT NULL DEFAULT 0,
  `luas_tanah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `luas_bangunan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ktp_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ijazah_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `kartu_nbm_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `asesors_user_id_foreign`(`user_id`) USING BTREE,
  CONSTRAINT `asesors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of asesors
-- ----------------------------
INSERT INTO `asesors` VALUES (1, 3, 'Noni', 'Noni', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, '2025-12-24 15:55:13', '2025-12-24 15:55:13');

-- ----------------------------
-- Table structure for assessments
-- ----------------------------
DROP TABLE IF EXISTS `assessments`;
CREATE TABLE `assessments`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `akreditasi_id` bigint(20) UNSIGNED NOT NULL,
  `asesor_id` bigint(20) UNSIGNED NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_berakhir` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `assessments_akreditasi_id_foreign`(`akreditasi_id`) USING BTREE,
  INDEX `assessments_asesor_id_foreign`(`asesor_id`) USING BTREE,
  CONSTRAINT `assessments_akreditasi_id_foreign` FOREIGN KEY (`akreditasi_id`) REFERENCES `akreditasis` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `assessments_asesor_id_foreign` FOREIGN KEY (`asesor_id`) REFERENCES `asesors` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of assessments
-- ----------------------------
INSERT INTO `assessments` VALUES (1, 1, 1, '2025-12-25', '2025-12-31', '2025-12-25 10:18:15', '2025-12-25 10:18:15');
INSERT INTO `assessments` VALUES (2, 2, 1, '2025-12-25', '2025-12-31', '2025-12-25 15:44:52', '2025-12-25 15:44:52');

-- ----------------------------
-- Table structure for cache
-- ----------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache`  (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cache
-- ----------------------------
INSERT INTO `cache` VALUES ('laravel-cache-da4b9237bacccdf19c0760cab7aec4a8359010b0', 'i:3;', 1766590432);
INSERT INTO `cache` VALUES ('laravel-cache-da4b9237bacccdf19c0760cab7aec4a8359010b0:timer', 'i:1766590432;', 1766590432);
INSERT INTO `cache` VALUES ('spm-cache-noni@mail|127.0.0.1', 'i:1;', 1766664948);
INSERT INTO `cache` VALUES ('spm-cache-noni@mail|127.0.0.1:timer', 'i:1766664948;', 1766664948);

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks`  (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cache_locks
-- ----------------------------

-- ----------------------------
-- Table structure for edpm_catatans
-- ----------------------------
DROP TABLE IF EXISTS `edpm_catatans`;
CREATE TABLE `edpm_catatans`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `komponen_id` bigint(20) UNSIGNED NOT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `edpm_catatans_user_id_foreign`(`user_id`) USING BTREE,
  INDEX `edpm_catatans_komponen_id_foreign`(`komponen_id`) USING BTREE,
  CONSTRAINT `edpm_catatans_komponen_id_foreign` FOREIGN KEY (`komponen_id`) REFERENCES `master_edpm_komponens` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `edpm_catatans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of edpm_catatans
-- ----------------------------
INSERT INTO `edpm_catatans` VALUES (1, 2, 1, 'gas', '2025-12-24 16:40:32', '2025-12-24 16:40:32');
INSERT INTO `edpm_catatans` VALUES (2, 2, 2, 'gas', '2025-12-24 16:40:32', '2025-12-24 16:40:32');

-- ----------------------------
-- Table structure for edpms
-- ----------------------------
DROP TABLE IF EXISTS `edpms`;
CREATE TABLE `edpms`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `butir_id` bigint(20) UNSIGNED NOT NULL,
  `isian` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `edpms_user_id_foreign`(`user_id`) USING BTREE,
  INDEX `edpms_butir_id_foreign`(`butir_id`) USING BTREE,
  CONSTRAINT `edpms_butir_id_foreign` FOREIGN KEY (`butir_id`) REFERENCES `master_edpm_butirs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `edpms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of edpms
-- ----------------------------
INSERT INTO `edpms` VALUES (1, 2, 1, 'ok', '2025-12-24 16:40:32', '2025-12-24 16:40:32');
INSERT INTO `edpms` VALUES (2, 2, 2, 'ok', '2025-12-24 16:40:32', '2025-12-24 16:40:32');
INSERT INTO `edpms` VALUES (3, 2, 3, 'ok', '2025-12-24 16:40:32', '2025-12-24 16:40:32');
INSERT INTO `edpms` VALUES (4, 2, 4, 'ok', '2025-12-24 16:40:32', '2025-12-24 16:40:32');
INSERT INTO `edpms` VALUES (5, 2, 5, 'ok', '2025-12-24 16:40:32', '2025-12-24 16:40:32');

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `failed_jobs_uuid_unique`(`uuid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for ipms
-- ----------------------------
DROP TABLE IF EXISTS `ipms`;
CREATE TABLE `ipms`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `nsp_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `lulus_santri_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `kurikulum_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `buku_ajar_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `ipms_user_id_foreign`(`user_id`) USING BTREE,
  CONSTRAINT `ipms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of ipms
-- ----------------------------
INSERT INTO `ipms` VALUES (1, 2, 'ipm_docs/64IgBQ58VtuLbnzw8hyvFfjBl8rdT4ctAvb1GJX4.pdf', 'ipm_docs/THzsU2LdTSzHiIlScRKSZKzNk8nowP3ltFrnUpST.pdf', 'ipm_docs/nxYkdat0A3SQo4HVNktHZ3NMFbpR9XfuBM59rPNt.pdf', NULL, '2025-12-24 15:32:41', '2025-12-24 15:33:00');

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches`  (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cancelled_at` int(11) NULL DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of job_batches
-- ----------------------------

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED NULL DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `jobs_queue_index`(`queue`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of jobs
-- ----------------------------

-- ----------------------------
-- Table structure for master_edpm_butirs
-- ----------------------------
DROP TABLE IF EXISTS `master_edpm_butirs`;
CREATE TABLE `master_edpm_butirs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `komponen_id` bigint(20) UNSIGNED NOT NULL,
  `no_sk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor_butir` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `butir_pernyataan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `master_edpm_butirs_komponen_id_foreign`(`komponen_id`) USING BTREE,
  CONSTRAINT `master_edpm_butirs_komponen_id_foreign` FOREIGN KEY (`komponen_id`) REFERENCES `master_edpm_komponens` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of master_edpm_butirs
-- ----------------------------
INSERT INTO `master_edpm_butirs` VALUES (1, 1, '1', '1', 'Menjadi pribadi yang bertaqwa (berakidah lurus; beribadah secara benar; dan berakhlak mulia', '2025-12-24 16:12:48', '2025-12-24 16:12:48');
INSERT INTO `master_edpm_butirs` VALUES (2, 1, '2', '2', 'Santri Mampu Membaca, Menghafal, dan Memahami Makna Al-Quran.', '2025-12-24 16:13:11', '2025-12-24 16:13:11');
INSERT INTO `master_edpm_butirs` VALUES (3, 1, '3', '3', 'Santri Mampu Menjadi Pendidik, Muballigh, dan Imam Shalat.', '2025-12-24 16:13:29', '2025-12-24 16:13:29');
INSERT INTO `master_edpm_butirs` VALUES (4, 2, '1', '9', 'Proses Pembelajaran dilaksanakan secara holistik, integratif, dan HOTS', '2025-12-24 16:16:22', '2025-12-24 16:16:22');
INSERT INTO `master_edpm_butirs` VALUES (5, 2, '1', '10', 'Pembelajaran yang Menerapkan Nilai-Nilai Keteladanan, Menumbuhkan Kemauan, dan Mengembangkan Kreativitas', '2025-12-24 16:16:41', '2025-12-24 16:16:41');

-- ----------------------------
-- Table structure for master_edpm_komponens
-- ----------------------------
DROP TABLE IF EXISTS `master_edpm_komponens`;
CREATE TABLE `master_edpm_komponens`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of master_edpm_komponens
-- ----------------------------
INSERT INTO `master_edpm_komponens` VALUES (1, 'MUTU LULUSAN', '2025-12-24 16:12:12', '2025-12-24 16:12:12');
INSERT INTO `master_edpm_komponens` VALUES (2, 'PROSES PEMBELAJARAN', '2025-12-24 16:16:01', '2025-12-24 16:16:01');

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` VALUES (4, '2025_12_24_145200_create_roles_table', 2);
INSERT INTO `migrations` VALUES (5, '2025_12_24_145913_add_role_id_to_users_table', 3);
INSERT INTO `migrations` VALUES (6, '2025_12_24_151139_create_pesantrens_table', 4);
INSERT INTO `migrations` VALUES (7, '2025_12_24_153129_create_ipms_table', 5);
INSERT INTO `migrations` VALUES (8, '2025_12_24_153926_create_sdm_pesantrens_table', 6);
INSERT INTO `migrations` VALUES (9, '2025_12_24_155339_create_asesors_table', 7);
INSERT INTO `migrations` VALUES (11, '2025_12_24_160929_create_master_edpm_komponens_table', 8);
INSERT INTO `migrations` VALUES (12, '2025_12_24_160935_create_master_edpm_butirs_table', 8);
INSERT INTO `migrations` VALUES (13, '2025_12_24_163746_create_edpms_table', 9);
INSERT INTO `migrations` VALUES (14, '2025_12_24_163753_create_edpm_catatans_table', 9);
INSERT INTO `migrations` VALUES (15, '2025_12_25_022133_create_akreditasis_table', 10);
INSERT INTO `migrations` VALUES (16, '2025_12_25_024357_create_assessments_table', 11);
INSERT INTO `migrations` VALUES (17, '2025_12_25_101315_create_akreditasi_edpms_table', 12);
INSERT INTO `migrations` VALUES (18, '2025_12_25_101319_create_akreditasi_edpm_catatans_table', 12);
INSERT INTO `migrations` VALUES (19, '2025_12_25_154216_add_nomor_sk_and_catatan_to_akreditasis_table', 13);

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens`  (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for pesantrens
-- ----------------------------
DROP TABLE IF EXISTS `pesantrens`;
CREATE TABLE `pesantrens`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `nama_pesantren` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ns_pesantren` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `kota_kabupaten` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `provinsi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tahun_pendirian` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `nama_mudir` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `jenjang_pendidikan_mudir` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `telp_pesantren` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hp_wa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email_pesantren` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `persyarikatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `visi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `misi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `layanan_satuan_pendidikan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `rombel_sd` int(11) NOT NULL DEFAULT 0,
  `rombel_mi` int(11) NOT NULL DEFAULT 0,
  `rombel_smp` int(11) NOT NULL DEFAULT 0,
  `rombel_mts` int(11) NOT NULL DEFAULT 0,
  `rombel_sma` int(11) NOT NULL DEFAULT 0,
  `rombel_ma` int(11) NOT NULL DEFAULT 0,
  `rombel_smk` int(11) NOT NULL DEFAULT 0,
  `rombel_spm` int(11) NOT NULL DEFAULT 0,
  `luas_tanah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `luas_bangunan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status_kepemilikan_tanah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `sertifikat_nsp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `rk_anggaran` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `silabus_rpp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `peraturan_kepegawaian` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `file_lk_iapm` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `laporan_tahunan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_profil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_nsp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_renstra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_rk_anggaran` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_kurikulum` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_silabus_rpp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_kepengasuhan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_peraturan_kepegawaian` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_sarpras` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_laporan_tahunan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dok_sop` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `pesantrens_user_id_foreign`(`user_id`) USING BTREE,
  CONSTRAINT `pesantrens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of pesantrens
-- ----------------------------
INSERT INTO `pesantrens` VALUES (1, 2, 'Kilat', '1001', 'Jln. Ring Road', 'Mbantul', 'Jogjakarta', '2000', 'Mahmud', 'S3 Al Azhar', '0865', '085713190065', 'kilat@mail', 'SPM', 'Selamat dunia', 'Selamat Akhirat', 'Satgas Kilat', 100, 100, 100, 100, 100, 100, 100, 100, '100', '100 Hektar', 'pesantren_docs/uWXoKPzLshSwk75GwF348X8q4luxDCLr1bKv4o6J.pdf', 'pesantren_docs/ho7tBBXZVllBaMpIaagkEmZ49qC0qmRR9BBIHvSk.pdf', 'pesantren_docs/IHK74o94FS4TT5sMM8byqBxQJ7pZwi2kHj5FEVLU.pdf', 'pesantren_docs/kCa1XUb4fIlCKgYMpZq0QzjIhst8o5jYVrMAuEhz.pdf', 'pesantren_docs/irMQ4BF2yUkPP3zoNxwEduqUk8mEmJLSSGC1IdeP.pdf', 'pesantren_docs/Em4MBAi6uq54YdMzKSu0vZi3LXPqVim1fJLtzXhY.pdf', NULL, 'pesantren_docs/Sq7gky3xz6syHISx9YHu2Bk8TEvuWxqw1NbCPDBS.pdf', 'pesantren_docs/3JuetW0cVfOJXhJkOY44uAnvdlpDQB2nT9NZMXBr.pdf', 'pesantren_docs/VvLpd4M3ERgI5nQj2JhjCwrzlOT3o7exlMMOoK2Q.pdf', 'pesantren_docs/rnEK5WBGQKOvrmWl2lb8OS1NRMgdyYpZVeUz8Yfi.pdf', 'pesantren_docs/Qxkr3tWr6w6MdCjmpoREIg1MHnc9qTqsEXN4okUd.pdf', 'pesantren_docs/V9VijA0b4h8QETAHwrIgSJRwgufTI2XVw3b37Zaj.pdf', 'pesantren_docs/uwQCFqUii94x1Q9dgZ1IhZwg6cljmy85a2uUisAI.pdf', 'pesantren_docs/ZVHyFYGB227rr4Ml2lZOyQp5LxTjXppbbT7HufxH.pdf', 'pesantren_docs/86Wt0PkAVboDbomg4dKeD7G9q1Mfpc7kxNj92Nai.pdf', 'pesantren_docs/5yigX7m44bDggBcIMamIm6ynP6SIhWTwrA70ODGr.pdf', 'pesantren_docs/a5NSmJtvAqSRDUNn0OiamT8qZyM3ygNcPeFVWrfI.pdf', '2025-12-24 15:16:22', '2025-12-24 15:21:19');

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameter` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of roles
-- ----------------------------
INSERT INTO `roles` VALUES (1, 'Admin', 'admin', '2025-12-24 14:54:35', '2025-12-25 15:19:43');
INSERT INTO `roles` VALUES (2, 'Asesor', 'asesor', '2025-12-24 14:54:43', '2025-12-25 19:08:42');
INSERT INTO `roles` VALUES (3, 'Pesantren', 'pesantren', '2025-12-24 14:54:53', '2025-12-25 15:19:27');

-- ----------------------------
-- Table structure for sdm_pesantrens
-- ----------------------------
DROP TABLE IF EXISTS `sdm_pesantrens`;
CREATE TABLE `sdm_pesantrens`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tingkat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `santri_l` int(11) NOT NULL DEFAULT 0,
  `santri_p` int(11) NOT NULL DEFAULT 0,
  `ustadz_dirosah_l` int(11) NOT NULL DEFAULT 0,
  `ustadz_dirosah_p` int(11) NOT NULL DEFAULT 0,
  `ustadz_non_dirosah_l` int(11) NOT NULL DEFAULT 0,
  `ustadz_non_dirosah_p` int(11) NOT NULL DEFAULT 0,
  `pamong_l` int(11) NOT NULL DEFAULT 0,
  `pamong_p` int(11) NOT NULL DEFAULT 0,
  `musyrif_l` int(11) NOT NULL DEFAULT 0,
  `musyrif_p` int(11) NOT NULL DEFAULT 0,
  `tendik_l` int(11) NOT NULL DEFAULT 0,
  `tendik_p` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sdm_pesantrens_user_id_foreign`(`user_id`) USING BTREE,
  CONSTRAINT `sdm_pesantrens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of sdm_pesantrens
-- ----------------------------
INSERT INTO `sdm_pesantrens` VALUES (1, 2, 'SD', 1, 1, 12, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-12-24 15:46:29', '2025-12-24 15:49:38');
INSERT INTO `sdm_pesantrens` VALUES (2, 2, 'MI', 11, 1, 1, 1, 11, 1, 1, 1, 1, 1, 1, 1, '2025-12-24 15:46:29', '2025-12-24 15:49:38');
INSERT INTO `sdm_pesantrens` VALUES (3, 2, 'SMP', 4, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-12-24 15:46:29', '2025-12-24 15:49:38');
INSERT INTO `sdm_pesantrens` VALUES (4, 2, 'MTs', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-12-24 15:46:29', '2025-12-24 15:49:38');
INSERT INTO `sdm_pesantrens` VALUES (5, 2, 'SMA', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-12-24 15:46:29', '2025-12-24 15:49:38');
INSERT INTO `sdm_pesantrens` VALUES (6, 2, 'MA', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-12-24 15:46:29', '2025-12-24 15:49:38');
INSERT INTO `sdm_pesantrens` VALUES (7, 2, 'SMK', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-12-24 15:46:29', '2025-12-24 15:49:38');
INSERT INTO `sdm_pesantrens` VALUES (8, 2, 'MAK', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-12-24 15:46:29', '2025-12-24 15:49:38');
INSERT INTO `sdm_pesantrens` VALUES (9, 2, 'Satuan Pesantren Muadalah (SPM)', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-12-24 15:46:29', '2025-12-24 15:49:38');

-- ----------------------------
-- Table structure for sessions
-- ----------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions`  (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sessions_user_id_index`(`user_id`) USING BTREE,
  INDEX `sessions_last_activity_index`(`last_activity`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of sessions
-- ----------------------------
INSERT INTO `sessions` VALUES ('BB61Pvc4VHH9GYdQ04JhXa4nt29JZpQMxDcyQIbO', NULL, '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVWlKZk5XbTZCSzBEellLbGg5TjNxUlVNVVBlQXplUzFxSlpoQVBiYyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9fQ==', 1766665768);
INSERT INTO `sessions` VALUES ('IZgdnZ9GhcuO3BC4s3TUifK2Zz25ReTj0nR8qslS', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiS25qSkdjSlVta2IzSlpDT1dOVHBuaG1jaEJjZVlKd29OYjBCQ01neSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mzt9', 1766665717);
INSERT INTO `sessions` VALUES ('VkJZirzxAJ5tmotq88VPSzPtguk2r68L2RZq19HM', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiWU5BaDU2NXR6V1JSRVV4R0VTbHdrenlZT2d6VmRNQ1RQTGpic21HWiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQyOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvcGVzYW50cmVuL2FrcmVkaXRhc2kiO3M6NToicm91dGUiO3M6MjA6InBlc2FudHJlbi5ha3JlZGl0YXNpIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9', 1766665718);

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `users_email_unique`(`email`) USING BTREE,
  INDEX `users_role_id_foreign`(`role_id`) USING BTREE,
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 1, 'Alan', 'alan@mail', NULL, '$2y$12$AEqYtVI20P9cU2sOGmiXCOwLBpE9oaU8LIh7FgbOzHcxoLS02GOv6', NULL, '2025-12-24 14:43:06', '2025-12-24 15:01:00');
INSERT INTO `users` VALUES (2, 3, 'Walker', 'walker@mail', NULL, '$2y$12$o/DlxDVcS42IK4lyYqyL8eePbu3EIw43aEQDPx530d0yOGZsvltJG', NULL, '2025-12-24 15:01:41', '2025-12-24 15:01:41');
INSERT INTO `users` VALUES (3, 2, 'Noni', 'nona@mail', NULL, '$2y$12$YDhKuX13SEm414sWE.yobuQQR5A/T5J8aO82xWlloXCjzud5B6gcC', NULL, '2025-12-24 15:50:50', '2025-12-24 15:51:13');

SET FOREIGN_KEY_CHECKS = 1;

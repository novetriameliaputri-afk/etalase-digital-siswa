CREATE DATABASE IF NOT EXISTS etalse_digital_siswa
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE etalse_digital_siswa;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('SA', 'Admin', 'Siswa') NOT NULL DEFAULT 'Siswa',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET @role_column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
);
SET @role_migration = IF(
    @role_column_exists = 0,
    "ALTER TABLE users ADD COLUMN role ENUM('SA', 'Admin', 'Siswa') NOT NULL DEFAULT 'Siswa' AFTER password",
    'SELECT 1'
);
PREPARE role_statement FROM @role_migration;
EXECUTE role_statement;
DEALLOCATE PREPARE role_statement;

CREATE TABLE IF NOT EXISTS siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    nis VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    jurusan VARCHAR(100) NOT NULL,
    CONSTRAINT fk_siswa_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS proyek (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT NOT NULL,
    gambar VARCHAR(255) DEFAULT NULL,
    link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyek_judul (judul),
    INDEX idx_proyek_user (user_id),
    CONSTRAINT fk_proyek_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET @updated_at_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'proyek' AND COLUMN_NAME = 'updated_at'
);
SET @updated_at_migration = IF(
    @updated_at_exists = 0,
    'ALTER TABLE proyek ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    'SELECT 1'
);
PREPARE updated_at_statement FROM @updated_at_migration;
EXECUTE updated_at_statement;
DEALLOCATE PREPARE updated_at_statement;

SET @siswa_user_unique_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'siswa' AND COLUMN_NAME = 'user_id' AND NON_UNIQUE = 0
);
SET @siswa_user_unique_migration = IF(
    @siswa_user_unique_exists = 0,
    'ALTER TABLE siswa ADD UNIQUE KEY uq_siswa_user_id (user_id)',
    'SELECT 1'
);
PREPARE siswa_user_unique_statement FROM @siswa_user_unique_migration;
EXECUTE siswa_user_unique_statement;
DEALLOCATE PREPARE siswa_user_unique_statement;

SET @siswa_nis_unique_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'siswa' AND COLUMN_NAME = 'nis' AND NON_UNIQUE = 0
);
SET @siswa_nis_unique_migration = IF(
    @siswa_nis_unique_exists = 0,
    'ALTER TABLE siswa ADD UNIQUE KEY uq_siswa_nis (nis)',
    'SELECT 1'
);
PREPARE siswa_nis_unique_statement FROM @siswa_nis_unique_migration;
EXECUTE siswa_nis_unique_statement;
DEALLOCATE PREPARE siswa_nis_unique_statement;

SET @siswa_fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'siswa' AND CONSTRAINT_NAME = 'fk_siswa_user'
);
SET @siswa_fk_migration = IF(
    @siswa_fk_exists = 0,
    'ALTER TABLE siswa ADD CONSTRAINT fk_siswa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE siswa_fk_statement FROM @siswa_fk_migration;
EXECUTE siswa_fk_statement;
DEALLOCATE PREPARE siswa_fk_statement;

SET @proyek_fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'proyek' AND CONSTRAINT_NAME = 'fk_proyek_user'
);
SET @proyek_fk_migration = IF(
    @proyek_fk_exists = 0,
    'ALTER TABLE proyek ADD CONSTRAINT fk_proyek_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE proyek_fk_statement FROM @proyek_fk_migration;
EXECUTE proyek_fk_statement;
DEALLOCATE PREPARE proyek_fk_statement;

SET @legacy_karya_exists = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'karya'
);
SET @legacy_migration = IF(
    @legacy_karya_exists = 1,
    'INSERT INTO proyek (user_id, judul, deskripsi, gambar, link, created_at) SELECT k.user_id, k.judul, k.deskripsi, k.gambar, k.link_proyek, k.created_at FROM karya k WHERE NOT EXISTS (SELECT 1 FROM proyek p WHERE p.user_id = k.user_id AND p.judul = k.judul AND p.created_at = k.created_at)',
    'SELECT 1'
);
PREPARE legacy_statement FROM @legacy_migration;
EXECUTE legacy_statement;
DEALLOCATE PREPARE legacy_statement;

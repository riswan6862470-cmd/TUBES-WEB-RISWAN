-- ============================================================
-- Smart Waste Management System - Database Schema
-- Database: waste_management_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS waste_management_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE waste_management_db;

-- ============================================================
-- Table: admin
-- ============================================================
DROP TABLE IF EXISTS admin;
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admin (username, password) VALUES
('admin', 'admin123');

-- ============================================================
-- Table: users
-- ============================================================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    alamat TEXT,
    no_hp VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (nama, username, password, alamat, no_hp) VALUES
('Budi Santoso', 'budi', 'budi123', 'Jl. Merdeka No. 10, Jakarta', '081234567890'),
('Siti Rahayu', 'siti', 'siti123', 'Jl. Pahlawan No. 5, Bandung', '082345678901'),
('Andi Wijaya', 'andi', 'andi123', 'Jl. Sudirman No. 20, Surabaya', '083456789012');

-- ============================================================
-- Table: waste_data
-- ============================================================
DROP TABLE IF EXISTS waste_data;
CREATE TABLE waste_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_sampah VARCHAR(100) NOT NULL,
    kategori ENUM('Organik','Anorganik','B3') NOT NULL,
    berat DECIMAL(8,2) NOT NULL,
    lokasi_pengumpulan VARCHAR(200) NOT NULL,
    tanggal_input DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO waste_data (nama_sampah, kategori, berat, lokasi_pengumpulan, tanggal_input) VALUES
('Sisa Makanan', 'Organik', 25.50, 'TPS Blok A', '2026-07-01'),
('Daun Kering', 'Organik', 12.00, 'TPS Blok B', '2026-07-01'),
('Botol Plastik', 'Anorganik', 8.75, 'TPS Blok A', '2026-07-01'),
('Kardus Bekas', 'Anorganik', 15.00, 'TPS Blok C', '2026-07-02'),
('Baterai Bekas', 'B3', 2.50, 'TPS Blok D', '2026-07-02'),
('Cat Bekas', 'B3', 5.00, 'TPS Blok D', '2026-07-02'),
('Sayuran Busuk', 'Organik', 18.00, 'TPS Blok B', '2026-07-02'),
('Kaleng Minuman', 'Anorganik', 6.50, 'TPS Blok C', '2026-07-02');

-- ============================================================
-- Table: bins
-- ============================================================
DROP TABLE IF EXISTS bins;
CREATE TABLE bins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lokasi VARCHAR(200) NOT NULL,
    kapasitas_max INT NOT NULL,
    tingkat_kepenuhan INT NOT NULL DEFAULT 0,
    status ENUM('NORMAL','PENUH') NOT NULL DEFAULT 'NORMAL',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO bins (lokasi, kapasitas_max, tingkat_kepenuhan, status) VALUES
('TPS Blok A - Jl. Merdeka', 100, 85, 'PENUH'),
('TPS Blok B - Jl. Pahlawan', 100, 60, 'NORMAL'),
('TPS Blok C - Jl. Sudirman', 100, 45, 'NORMAL'),
('TPS Blok D - Jl. Diponegoro', 100, 92, 'PENUH'),
('TPS Blok E - Jl. Gatot Subroto', 100, 30, 'NORMAL');

-- ============================================================
-- Table: pickup_schedule
-- ============================================================
DROP TABLE IF EXISTS pickup_schedule;
CREATE TABLE pickup_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lokasi VARCHAR(200) NOT NULL,
    tanggal_jemput DATE NOT NULL,
    jam_jemput TIME NOT NULL,
    status ENUM('MENUNGGU','DIJEMPUT','SELESAI') NOT NULL DEFAULT 'MENUNGGU',
    catatan TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO pickup_schedule (lokasi, tanggal_jemput, jam_jemput, status, catatan) VALUES
('TPS Blok A - Jl. Merdeka', '2026-07-02', '07:00:00', 'MENUNGGU', 'Bin sudah penuh, prioritas utama'),
('TPS Blok D - Jl. Diponegoro', '2026-07-02', '08:30:00', 'MENUNGGU', 'Sampah B3, gunakan APD'),
('TPS Blok B - Jl. Pahlawan', '2026-07-03', '07:00:00', 'MENUNGGU', NULL),
('TPS Blok C - Jl. Sudirman', '2026-07-03', '09:00:00', 'MENUNGGU', NULL),
('TPS Blok E - Jl. Gatot Subroto', '2026-07-04', '07:30:00', 'MENUNGGU', 'Jadwal rutin mingguan');

-- ============================================================
-- Table: waste_reports
-- ============================================================
DROP TABLE IF EXISTS waste_reports;
CREATE TABLE waste_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lokasi VARCHAR(200) NOT NULL,
    jenis_sampah VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    foto VARCHAR(255),
    status ENUM('MENUNGGU','DIPROSES','SELESAI') NOT NULL DEFAULT 'MENUNGGU',
    tanggal_laporan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO waste_reports (user_id, lokasi, jenis_sampah, deskripsi, foto, status) VALUES
(1, 'Jl. Merdeka No. 15', 'Organik', 'Tumpukan sampah sisa pasar di depan toko', NULL, 'MENUNGGU'),
(2, 'Jl. Pahlawan No. 8', 'Anorganik', 'Sampah plastik berserakan di pinggir jalan', NULL, 'DIPROSES'),
(3, 'Jl. Sudirman No. 25', 'B3', 'Oli bekas dibuang sembarangan', NULL, 'SELESAI');

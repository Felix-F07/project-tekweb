-- 1. BERSIHKAN & BUAT DATABASE BARU
DROP DATABASE IF EXISTS warnet_db;
CREATE DATABASE warnet_db;
USE warnet_db;

-- 2. TABEL ROLES (Harus dibuat duluan)
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- 3. TABEL USERS (Menyimpan Akun & Waktu)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL, 
    billing_seconds INT DEFAULT 0, -- Sisa waktu main (Detik)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- 4. TABEL PC (Daftar Komputer)
CREATE TABLE pc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL, -- Contoh: "PC-01"
    status ENUM('available', 'used', 'maintenance') DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 5. TABEL TRANSACTIONS (Riwayat Transaksi)
-- 5. TABEL PAKET_BILLING (Daftar paket yang bisa dibeli)
CREATE TABLE paket_billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,       -- Contoh: 'Paket 1 Jam'
    price INT NOT NULL DEFAULT 0,      -- Harga dalam Rupiah (angka bulat)
    seconds INT NOT NULL DEFAULT 0,    -- Durasi paket dalam detik
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL
);

-- 6. TABEL TRANSACTIONS (Riwayat Transaksi)
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pc_id INT NULL,      -- Boleh kosong kalau cuma beli voucher
    paket_id INT NULL,   -- FK ke paket_billing jika pembelian paket
    billing_amount INT NOT NULL, -- Nominal uang (Rp)
    seconds_added INT DEFAULT 0, -- Durasi tambahan dalam detik (jika paket)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (pc_id) REFERENCES pc(id),
    FOREIGN KEY (paket_id) REFERENCES paket_billing(id)
);

-- =============================================
-- ISI DATA DUMMY (Supaya bisa langsung ditest)
-- =============================================

-- 1. Isi Role (1=Admin, 2=User)
INSERT INTO roles (name) VALUES ('Admin'), ('User');

-- 2. Isi PC
INSERT INTO pc (name, status) VALUES 
('PC-01', 'available'),
('PC-02', 'used'),
('PC-03', 'maintenance');

-- 3. Isi User 
-- Admin (role_id 1)
INSERT INTO users (username, password, role_id, billing_seconds) VALUES 
('admin', 'admin123', 1, 0);

-- User Biasa: Kenji (role_id 2, punya waktu 1 jam)
INSERT INTO users (username, password, role_id, billing_seconds) VALUES 
('Kenji', '123', 2, 3600);

-- User Biasa: Felix (role_id 2, waktu habis)
INSERT INTO users (username, password, role_id, billing_seconds) VALUES 
('Felix', '123', 2, 0);

-- 4. Isi Transaksi Contoh
INSERT INTO transactions (user_id, pc_id, billing_amount) VALUES 
(2, 1, 5000);

-- 5. Isi Paket Billing (Seed contoh sesuai UI)
INSERT INTO paket_billing (name, price, seconds, description) VALUES
('Paket 1 Jam', 5000, 3600, 'Tambahan 1 jam penggunaan'),
('Paket 2 Jam', 10000, 7200, 'Tambahan 2 jam penggunaan'),
('Paket 5 Jam', 20000, 18000, 'Tambahan 5 jam penggunaan');
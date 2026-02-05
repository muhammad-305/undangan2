-- Database: undangan_online
CREATE DATABASE IF NOT EXISTS undangan_online CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE undangan_online;

-- Tabel Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    nama_lengkap VARCHAR(100),
    telepon VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Template Undangan
CREATE TABLE template_undangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_template VARCHAR(100) NOT NULL,
    background_image VARCHAR(255),
    primary_color VARCHAR(7) DEFAULT '#D4AF37',
    secondary_color VARCHAR(7) DEFAULT '#FFFFFF',
    font_primary VARCHAR(50) DEFAULT 'Playfair Display',
    font_secondary VARCHAR(50) DEFAULT 'Poppins',
    music_url VARCHAR(255),
    music_file VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Undangan
CREATE TABLE undangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    template_id INT NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    
    -- Data Mempelai Pria
    nama_pria VARCHAR(100) NOT NULL,
    nama_lengkap_pria VARCHAR(150),
    nama_ayah_pria VARCHAR(100),
    nama_ibu_pria VARCHAR(100),
    foto_pria VARCHAR(255),
    instagram_pria VARCHAR(50),
    
    -- Data Mempelai Wanita
    nama_wanita VARCHAR(100) NOT NULL,
    nama_lengkap_wanita VARCHAR(150),
    nama_ayah_wanita VARCHAR(100),
    nama_ibu_wanita VARCHAR(100),
    foto_wanita VARCHAR(255),
    instagram_wanita VARCHAR(50),
    
    -- Pembuka
    pembuka_text TEXT,
    
    -- Acara
    tanggal_akad DATE,
    waktu_akad_mulai TIME,
    waktu_akad_selesai TIME,
    tempat_akad VARCHAR(200),
    alamat_akad TEXT,
    maps_akad TEXT,
    
    tanggal_resepsi DATE,
    waktu_resepsi_mulai TIME,
    waktu_resepsi_selesai TIME,
    tempat_resepsi VARCHAR(200),
    alamat_resepsi TEXT,
    maps_resepsi TEXT,
    
    -- Cerita
    cerita_json TEXT,
    
    -- Galeri
    galeri_json TEXT,
    
    -- Musik
    music_url VARCHAR(255),
    music_file VARCHAR(255),
    
    -- Customization
    background_image VARCHAR(255),
    primary_color VARCHAR(7),
    secondary_color VARCHAR(7),
    
    -- Status
    is_published TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES template_undangan(id),
    INDEX idx_slug (slug),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Hadiah
CREATE TABLE hadiah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    undangan_id INT NOT NULL,
    jenis ENUM('bank', 'ewallet') NOT NULL,
    nama_bank VARCHAR(50),
    nama_ewallet VARCHAR(50),
    nomor_rekening VARCHAR(50),
    atas_nama VARCHAR(100),
    qr_code VARCHAR(255),
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (undangan_id) REFERENCES undangan(id) ON DELETE CASCADE,
    INDEX idx_undangan_id (undangan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Link Tamu
CREATE TABLE link_tamu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    undangan_id INT NOT NULL,
    nama_tamu VARCHAR(100) NOT NULL,
    slug_tamu VARCHAR(100) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (undangan_id) REFERENCES undangan(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slug (undangan_id, slug_tamu),
    INDEX idx_undangan_id (undangan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Ucapan & Doa
CREATE TABLE ucapan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    undangan_id INT NOT NULL,
    nama VARCHAR(100) NOT NULL,
    ucapan TEXT NOT NULL,
    kehadiran ENUM('hadir', 'tidak_hadir', 'masih_ragu') DEFAULT 'masih_ragu',
    jumlah_tamu INT DEFAULT 1,
    ip_address VARCHAR(45),
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (undangan_id) REFERENCES undangan(id) ON DELETE CASCADE,
    INDEX idx_undangan_id (undangan_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Log Aktivitas
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin
INSERT INTO users (username, email, password, role, nama_lengkap, status) 
VALUES ('admin', 'admin@undangan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', 'active');
-- Password default: password

-- Insert template default
INSERT INTO template_undangan (nama_template, background_image, primary_color, secondary_color) VALUES
('Classic Gold', 'Elegant-Classic.jpeg', '#D4AF37', '#FFFFFF'),
('Romantic Rose', 'Romantic-Rose.jpg', '#FF69B4', '#FFFFFF'),
('Modern Blue', 'Modern-Minimalist.jpg', '#4169E1', '#FFFFFF'),
('Elegant Purple', 'Royal-Elegance.jpg', '#9370DB', '#FFFFFF');

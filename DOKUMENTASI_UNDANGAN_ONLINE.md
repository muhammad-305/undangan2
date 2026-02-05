# Dokumentasi Proyek Undangan Online

## 📋 Daftar Isi
- [Overview Proyek](#overview-proyek)
- [Struktur Folder](#struktur-folder)
- [Konfigurasi Server](#konfigurasi-server)
- [Database Schema](#database-schema)
- [Fitur & Halaman](#fitur--halaman)
- [Keamanan](#keamanan)
- [Deployment](#deployment)

---

## 🎯 Overview Proyek

Aplikasi undangan online berbasis PHP native dengan sistem multi-role (Admin & Customer) yang memungkinkan pembuatan dan pengelolaan undangan pernikahan digital.

**URL Produksi:** https://undangan.muza-project.com/

---

## 📁 Struktur Folder

```
undangan-online/
│
├── .htaccess                 # URL rewriting configuration
├── index.php                 # Entry point
├── config/
│   ├── database.php         # Koneksi database
│   ├── config.php           # Konfigurasi umum
│   └── security.php         # Fungsi keamanan
│
├── pages/
│   ├── home.php             # Halaman beranda
│   ├── login-admin.php      # Login admin
│   ├── login-customer.php   # Login customer
│   │
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── undangan-list.php
│   │   ├── undangan-create.php
│   │   ├── undangan-edit.php
│   │   ├── undangan-preview.php
│   │   └── kelola-user.php
│   │
│   ├── customer/
│   │   ├── dashboard.php
│   │   ├── edit-undangan.php
│   │   ├── kelola-ucapan.php
│   │   ├── kelola-hadiah.php
│   │   └── kelola-link.php
│   │
│   └── view-undangan.php    # Tampilan undangan publik
│
├── css/
│   ├── main.css             # Style utama
│   ├── home.css             # Style halaman beranda
│   ├── undangan.css         # Style halaman undangan
│   ├── admin.css            # Style dashboard admin
│   └── customer.css         # Style dashboard customer
│
├── js/
│   ├── main.js              # JavaScript utama
│   ├── countdown.js         # Countdown timer
│   ├── gallery.js           # Galeri foto
│   ├── form-validation.js   # Validasi form
│   └── admin.js             # Fungsi admin
│
├── images/
│   ├── background/          # Background undangan (multiple)
│   │   ├── Beach-Wedding.jpg
│   │   ├── Elegant-Classic.jpg
│   │   └── ...
│   ├── qr-muza.png        # Contoh QR code
│   ├── pengantin pria.jpg      # Contoh foto pria
│   └── pengantin wanita.jpg    # Contoh foto wanita
│
├── uploads/
│   ├── undangan/            # Upload foto undangan
│   ├── qr/                  # Upload QR code
│   └── gallery/             # Upload galeri
│   └── music/               # Upload music
│
└── includes/
    ├── header.php           # Header template
    ├── footer.php           # Footer template
    └── functions.php        # Helper functions
```

---

## ⚙️ Konfigurasi Server

### 1. File .htaccess (Root)

```apache
# Enable Rewrite Engine
RewriteEngine On
RewriteBase /

# Remove .php extension
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}\.php -f
RewriteRule ^(.*)$ $1.php [L]

# Remove trailing slash
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)/$ /$1 [L,R=301]

# Custom URL for undangan
# Format: /nama-wanita-nama-pria/nama-tamu
RewriteRule ^([a-zA-Z0-9-]+)/([a-zA-Z0-9-]+)$ pages/view-undangan?slug=$1&tamu=$2 [L,QSA]

# Protect sensitive files
<FilesMatch "^(config|database|security)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Enable GZIP compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### 2. Konfigurasi Local (config/config.php)

```php
<?php
// Environment
define('ENVIRONMENT', 'development'); // 'development' atau 'production'

// Base URL
if (ENVIRONMENT === 'development') {
    define('BASE_URL', 'http://localhost/undangan-online/');
} else {
    define('BASE_URL', 'https://undangan.muza-project.com/');
}

// Path
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');

// Security
define('SECRET_KEY', 'your-secret-key-here-change-this');
define('SESSION_LIFETIME', 3600); // 1 jam

// Timezone
date_default_timezone_set('Asia/Jakarta');
?>
```

### 3. Database Configuration (config/database.php)

```php
<?php
// Database credentials
if (ENVIRONMENT === 'development') {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'undangan_online');
} else {
    define('DB_HOST', 'localhost'); // Sesuaikan dengan hosting
    define('DB_USER', 'your_db_user');
    define('DB_PASS', 'your_db_password');
    define('DB_NAME', 'undangan_online');
}

// Create connection
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}
?>
```

---

## 🗄️ Database Schema

### SQL untuk membuat database

```sql
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
    cerita_json TEXT, -- JSON untuk timeline cerita
    
    -- Galeri
    galeri_json TEXT, -- JSON untuk array foto galeri
    
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
    nama_bank VARCHAR(50), -- BCA, Mandiri, BRI, etc
    nama_ewallet VARCHAR(50), -- GoPay, OVO, Dana, etc
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
('Classic Gold', 'background/bg-1.jpg', '#D4AF37', '#FFFFFF'),
('Romantic Pink', 'background/bg-2.jpg', '#FF69B4', '#FFFFFF'),
('Modern Blue', 'background/bg-3.jpg', '#4169E1', '#FFFFFF'),
('Elegant Purple', 'background/bg-4.jpg', '#9370DB', '#FFFFFF');
```

---

## 🎨 Fitur & Halaman

### 1. Halaman Beranda (pages/home.php)

**Fitur:**
- Header dengan logo dan button login
- Hero section menarik
- Showcase template undangan (multiple backgrounds dari folder images/background/)
- Fitur-fitur aplikasi
- Testimoni (opsional)
- Pricing (opsional)
- Footer dengan informasi kontak

**Desain:**
- Modern dan eye-catching
- Responsive design
- Smooth scrolling
- Animasi subtle
- CTA yang jelas

### 2. Halaman View Undangan (pages/view-undangan.php)

**URL Format:** `undangan.muza-project.com/nama-wanita-nama-pria/nama-tamu`

**Struktur Halaman (Urutan):**

#### a. Cover
- Background dari database (sesuai pilihan customer)
- Nama mempelai
- Tanggal pernikahan
- Nama tamu (dari URL)
- Button "Buka Undangan" dengan musik play

#### b. Section Mempelai
- Foto mempelai pria & wanita
- Nama lengkap & orang tua
- Quote pembuka: "Dengan memohon rahmat dan ridho Allah SWT, kami bermaksud menyelenggarakan pernikahan putra-putri kami"
- Doa untuk kedua mempelai

#### c. Cerita Kami (Timeline)
- Timeline pertemuan hingga menikah
- Format: Tahun - Judul - Deskripsi
- Layout menarik dengan line connector

#### d. Menghitung Hari
- Countdown timer real-time
- Hari, Jam, Menit, Detik
- Update setiap detik

#### e. Acara Pernikahan
- **Akad Nikah:**
  - Hari, Tanggal
  - Waktu
  - Tempat & Alamat
  - Button "Lihat Lokasi" (Google Maps)
  
- **Resepsi:**
  - Hari, Tanggal
  - Waktu
  - Tempat & Alamat
  - Button "Lihat Lokasi" (Google Maps)

#### f. Galeri Foto
- Multiple foto (dari database)
- Lightbox untuk view full image
- Grid responsive layout

#### g. Hadiah Pernikahan
- List bank & e-wallet
- Untuk setiap item:
  - Icon bank/ewallet
  - Nama bank/ewallet
  - Nomor rekening
  - Atas nama
  - QR Code
  - Button "Salin Nomor"
- Toast notification saat berhasil copy

#### h. Konfirmasi Kehadiran
- Form input:
  - Nama
  - Ucapan & Doa (textarea)
  - Konfirmasi kehadiran (radio: Hadir / Tidak Hadir / Masih Ragu)
  - Jumlah tamu (jika hadir)
- Button submit

#### i. Ucapan & Doa
- List ucapan yang sudah masuk
- Tampilkan:
  - Nama
  - Ucapan
  - Status kehadiran
  - Waktu (relative time)
- Pagination atau load more
- Filter by kehadiran (opsional)

#### j. Footer
- Terima kasih
- Copyright
- Powered by

#### k. Background Music
- Auto-play setelah klik "Buka Undangan"
- Control play/pause di pojok
- Musik bisa custom per undangan

**Interaksi:**
- Smooth scroll antar section
- Animasi on scroll
- Sticky music player
- Share button (WhatsApp, Facebook, Copy Link)

### 3. Halaman Login Admin (pages/login-admin.php)

**Form:**
- Username/Email
- Password
- Remember me
- Button Login
- Link lupa password (opsional)

**Validasi:**
- Client-side & server-side validation
- CSRF protection
- Rate limiting login attempt

### 4. Dashboard Admin

#### a. CRUD Undangan (pages/admin/undangan-*.php)

**List Undangan:**
- Tabel dengan kolom:
  - ID
  - Nama Mempelai
  - Customer
  - Template
  - Status (Published/Draft)
  - Views
  - Action (Edit, Preview, Delete)
- Search & filter
- Pagination

**Create/Edit Undangan:**

Form wizard dengan tahapan:

**Step 1: Pilih Template**
- Grid template dengan preview
- Radio button untuk pilih

**Step 2: Data Mempelai**
- Upload foto pria & wanita
- Nama lengkap
- Nama orang tua
- Instagram (opsional)

**Step 3: Data Acara**
- Tanggal & waktu akad
- Tempat akad & alamat
- Google Maps embed akad
- Tanggal & waktu resepsi
- Tempat resepsi & alamat
- Google Maps embed resepsi

**Step 4: Cerita & Galeri**
- Timeline builder (bisa add/remove/reorder)
- Upload galeri foto (multiple)
- Drag & drop reorder

**Step 5: Customization**
- Upload background custom
- Color picker (primary & secondary)
- Upload musik (atau pilih dari library)

**Step 6: Preview & Publish**
- Preview undangan
- Assign ke customer
- Button Publish

**Fitur:**
- Drag & drop upload
- Image cropper
- Preview real-time
- Auto-save draft
- Validasi form lengkap

#### b. Preview Undangan (pages/admin/undangan-preview.php)
- Full preview seperti tampilan publik
- Button kembali ke edit

#### c. Kelola User (pages/admin/kelola-user.php)

**Fitur:**
- List customer
- Add/Edit/Delete customer
- Reset password
- Set status (Active/Inactive)
- View customer's undangan

**Form Customer:**
- Username
- Email
- Password
- Nama lengkap
- Telepon
- Status

### 5. Halaman Login Customer (pages/login-customer.php)

Sama seperti login admin, tapi untuk customer.

### 6. Dashboard Customer

#### a. Edit Undangan (pages/customer/edit-undangan.php)

**Yang bisa diubah:**
- Foto mempelai pria & wanita
- Nama lengkap & orang tua
- Instagram
- Text pembuka
- Timeline cerita (add/edit/delete)
- Upload galeri foto (add/delete)
- Tanggal & waktu acara (jika admin ijinkan)
- Tempat & alamat

**Yang tidak bisa diubah (read-only):**
- Template
- Background
- Color scheme

#### b. Kelola Ucapan (pages/customer/kelola-ucapan.php)

**Fitur:**
- List semua ucapan
- Filter by kehadiran
- Search ucapan
- Delete ucapan (jika inappropriate)
- Export ke Excel/PDF

**Stats:**
- Total ucapan
- Jumlah hadir
- Jumlah tidak hadir
- Jumlah masih ragu
- Total tamu yang hadir

#### c. Kelola Hadiah (pages/customer/kelola-hadiah.php)

**Fitur:**
- Add/Edit/Delete hadiah
- Upload QR code
- Drag & drop untuk reorder
- Preview QR

**Form Hadiah:**
- Jenis (Bank/E-wallet)
- Nama bank/ewallet (dropdown)
- Nomor rekening
- Atas nama
- Upload QR code

#### d. Kelola Link (pages/customer/kelola-link.php)

**Fitur:**
- Add link tamu baru
- Edit nama tamu
- Delete link
- Copy link (with button)
- Bulk add (import CSV)

**Table:**
- No
- Nama Tamu
- Link
- Action (Copy, Edit, Delete)

**Form Add Link:**
- Nama tamu
- Keterangan (opsional)
- Auto-generate slug dari nama

**URL yang di-generate:**
`undangan.muza-project.com/[nama-wanita]-[nama-pria]/[nama-tamu-slug]`

Contoh:
- Tamu: "Budi dan Keluarga"
- URL: `undangan.muza-project.com/sarah-ahmad/budi-dan-keluarga`

---

## 🔒 Keamanan

### 1. Autentikasi & Autorisasi

```php
// config/security.php

<?php
// Password hashing
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Session management
function startSecureSession() {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1); // Hanya HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
    
    // Regenerate session ID
    if (!isset($_SESSION['created'])) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Check login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isCustomer() {
    return isLoggedIn() && $_SESSION['role'] === 'customer';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL);
        exit;
    }
}

function requireCustomer() {
    requireLogin();
    if (!isCustomer()) {
        header('Location: ' . BASE_URL);
        exit;
    }
}
?>
```

### 2. CSRF Protection

```php
// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// HTML helper
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}
```

### 3. Input Validation & Sanitization

```php
// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate URL
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// Clean filename
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}
```

### 4. SQL Injection Prevention

```php
// Selalu gunakan prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
```

### 5. File Upload Security

```php
function validateImageUpload($file, $maxSize = 2097152) { // 2MB
    $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    
    // Check if file exists
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'File tidak valid'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File terlalu besar (max 2MB)'];
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowed)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    // Check actual image
    if (!getimagesize($file['tmp_name'])) {
        return ['success' => false, 'message' => 'File bukan gambar yang valid'];
    }
    
    return ['success' => true];
}

function uploadImage($file, $destination, $newFilename = null) {
    $validation = validateImageUpload($file);
    
    if (!$validation['success']) {
        return $validation;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $newFilename ?? uniqid() . '_' . time() . '.' . $extension;
    $filepath = $destination . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Gagal upload file'];
}
```

### 6. Rate Limiting

```php
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    // $identifier bisa IP address atau user ID
    // Check di database atau session
    
    if (!isset($_SESSION['rate_limit'][$identifier])) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$identifier];
    
    // Reset jika sudah lewat time window
    if (time() - $data['first_attempt'] > $timeWindow) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    // Increment attempts
    $_SESSION['rate_limit'][$identifier]['attempts']++;
    
    // Check limit
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    return true;
}
```

### 7. XSS Prevention

- Selalu gunakan `htmlspecialchars()` saat output data user
- Gunakan Content Security Policy (CSP) header
- Validasi dan sanitize semua input

### 8. Additional Security Headers

Sudah ada di `.htaccess`, pastikan enabled:
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin

---

## 🚀 Deployment

### Local Development Setup

1. **Install XAMPP/WAMP/LAMP**

2. **Clone/Extract project ke htdocs**
   ```
   C:/xampp/htdocs/undangan-online/
   ```

3. **Create Database**
   - Buka phpMyAdmin (http://localhost/phpmyadmin)
   - Import file `database.sql`
   - Atau jalankan SQL schema di atas

4. **Configure**
   - Edit `config/config.php`
   - Set `ENVIRONMENT` = `'development'`
   - Set `BASE_URL` = `'http://localhost/undangan-online/'`

5. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/undangan/
   chmod 755 uploads/qr/
   chmod 755 uploads/gallery/
   ```

6. **Access**
   ```
   http://localhost/undangan-online/
   ```

### Production Deployment

1. **Upload files via FTP/cPanel File Manager**
   - Upload semua file ke public_html atau subdomain folder

2. **Create Database**
   - Buat database via cPanel MySQL Databases
   - Import database schema
   - Create database user dan assign privileges

3. **Configure**
   - Edit `config/config.php`
   - Set `ENVIRONMENT` = `'production'`
   - Set `BASE_URL` = `'https://undangan.muza-project.com/'`
   - Update database credentials

4. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 644 config/*.php
   ```

5. **SSL Certificate**
   - Pastikan SSL active (HTTPS)
   - Update session secure cookie settings

6. **Test**
   - Test semua fitur
   - Test upload files
   - Test email (jika ada)

7. **Security Checklist**
   - [ ] Ganti SECRET_KEY
   - [ ] Ganti password admin default
   - [ ] Disable error display (`display_errors = Off`)
   - [ ] Set proper file permissions
   - [ ] Enable HTTPS only
   - [ ] Test CSRF protection
   - [ ] Test rate limiting
   - [ ] Test file upload validation
   - [ ] Backup database

---

## 📝 Development Notes

### Coding Standards

1. **PHP**
   - Gunakan PHP 7.4+
   - Follow PSR-12 coding standard
   - Pisahkan logic dari view
   - Gunakan prepared statements SELALU
   - Comment code yang kompleks

2. **CSS**
   - Mobile-first approach
   - Gunakan CSS variables untuk colors
   - BEM naming convention (opsional)
   - Organize by component

3. **JavaScript**
   - ES6+ syntax
   - Use `const` dan `let`, bukan `var`
   - Modular code
   - Comment functions

### Best Practices

1. **Security First**
   - Validate semua input
   - Sanitize semua output
   - Use CSRF protection
   - Rate limit sensitive actions

2. **Performance**
   - Optimize images (compress)
   - Minify CSS/JS untuk production
   - Use lazy loading untuk images
   - Cache static assets
   - Optimize database queries

3. **User Experience**
   - Loading indicators
   - Error messages yang jelas
   - Success notifications
   - Responsive design
   - Fast page load

4. **Maintainability**
   - Code yang bersih dan terorganisir
   - Naming yang descriptive
   - Dokumentasi code
   - Version control (Git)

---

## 🎯 Priority Implementation Order

### Phase 1: Core Setup
1. Setup struktur folder
2. Database creation
3. Konfigurasi dasar
4. Security functions
5. Authentication system

### Phase 2: Admin Panel
1. Login admin
2. Dashboard admin
3. CRUD template
4. CRUD undangan (basic)
5. Kelola user

### Phase 3: Customer Panel
1. Login customer
2. Dashboard customer
3. Edit undangan
4. Kelola hadiah
5. Kelola link tamu

### Phase 4: Public View
1. Halaman beranda
2. View undangan (all sections)
3. Form ucapan
4. List ucapan

### Phase 5: Polish
1. Styling & animations
2. Responsive design
3. Image optimization
4. Testing semua fitur
5. Bug fixing

### Phase 6: Deployment
1. Setup production server
2. Database migration
3. Security hardening
4. Performance optimization
5. Final testing

---

## 📚 Resources

### Libraries yang Direkomendasikan

**CSS Frameworks:**
- Tailwind CSS / Bootstrap (untuk cepat)
- Atau custom CSS (lebih flexible)

**JavaScript Libraries:**
- jQuery (jika perlu, untuk kompatibilitas)
- Lightbox library (untuk galeri)
- CountdownJS (untuk countdown)
- SweetAlert2 (untuk alerts cantik)

**PHP Libraries:**
- PHPMailer (untuk email)
- Intervention Image (untuk image manipulation)

**Icons:**
- Font Awesome / Feather Icons

**Fonts:**
- Google Fonts (Playfair Display, Poppins, Great Vibes, dll)

---

## 🐛 Testing Checklist

### Functional Testing

**Authentication:**
- [ ] Login admin berhasil
- [ ] Login customer berhasil
- [ ] Logout berhasil
- [ ] Invalid credentials ditolak
- [ ] Rate limiting berfungsi

**Admin - CRUD Undangan:**
- [ ] Create undangan berhasil
- [ ] Edit undangan berhasil
- [ ] Delete undangan berhasil
- [ ] Upload images berhasil
- [ ] Preview undangan tampil benar

**Customer - Manage Undangan:**
- [ ] Edit data mempelai berhasil
- [ ] Upload foto berhasil
- [ ] Edit cerita berhasil
- [ ] Upload galeri berhasil

**Customer - Manage Hadiah:**
- [ ] Add hadiah berhasil
- [ ] Edit hadiah berhasil
- [ ] Delete hadiah berhasil
- [ ] Upload QR berhasil

**Customer - Manage Link:**
- [ ] Add link tamu berhasil
- [ ] Edit link berhasil
- [ ] Delete link berhasil
- [ ] Copy link berhasil
- [ ] URL slugs benar

**Public View:**
- [ ] URL custom berfungsi
- [ ] Semua section tampil
- [ ] Countdown berfungsi
- [ ] Musik play/pause
- [ ] Form ucapan submit berhasil
- [ ] List ucapan tampil
- [ ] Copy nomor rekening berhasil
- [ ] Maps link berfungsi
- [ ] Share buttons berfungsi

### Security Testing

- [ ] SQL injection dicegah
- [ ] XSS dicegah
- [ ] CSRF protection berfungsi
- [ ] File upload validation berfungsi
- [ ] Unauthorized access ditolak
- [ ] Session management aman
- [ ] Password hashing kuat

### Performance Testing

- [ ] Page load < 3 detik
- [ ] Images optimized
- [ ] No N+1 queries
- [ ] Caching berfungsi

### Compatibility Testing

- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge
- [ ] Mobile browsers

### Responsive Testing

- [ ] Desktop (1920px+)
- [ ] Laptop (1366px)
- [ ] Tablet (768px)
- [ ] Mobile (375px)

---

## 📞 Support & Contact

Untuk pertanyaan atau bantuan development, hubungi:
- Email: support@muza-project.com
- Website: https://muza-project.com
- Wa: http://wa.me/+6285179669566

---

**Last Updated:** 2026-02-05
**Version:** 1.0
**Status:** Ready for Development

---

## 🎉 Good Luck!

Dokumentasi ini sudah sangat lengkap untuk memulai development. Pastikan AI agent Anda membaca semua bagian dengan teliti sebelum mulai coding.

**Tips untuk AI Agent:**
1. Mulai dari phase 1 dan ikuti urutan
2. Test setiap fitur sebelum lanjut ke fitur berikutnya
3. Commit code secara berkala
4. Buat backup database sebelum migration
5. Dokumentasikan setiap perubahan penting

Happy Coding! 🚀

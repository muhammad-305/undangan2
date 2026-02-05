# 💍 Undangan Online - Wedding Invitation System

Platform online untuk membuat undangan pernikahan digital yang indah dan elegan.

## 📋 Fitur Utama

### 🔐 Multi-Role System
- **Admin Panel** - Kelola undangan, template, dan user
- **Customer Dashboard** - Edit undangan, kelola ucapan, hadiah, dan link tamu
- **Public View** - Tampilan undangan untuk tamu dengan personalisasi

### ✨ Fitur Lengkap
- 🎨 Multiple template backgrounds
- 📸 Upload foto mempelai dan galeri
- ⏰ Countdown timer real-time
- 🎵 Background music player
- 💌 Sistem RSVP dan ucapan
- 🎁 Manajemen hadiah digital (Bank & E-wallet dengan QR code)
- 🔗 Link personal untuk setiap tamu
- 📊 Statistik views dan kehadiran
- 🔒 Sistem keamanan lengkap (CSRF, SQL injection prevention, XSS protection)

## 🚀 Instalasi

### Persyaratan Sistem
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Apache dengan mod_rewrite enabled
- Web browser modern

### Langkah Instalasi

#### 1. Clone atau Download Repository
```bash
git clone https://github.com/muhammad-305/undangan2.git
cd undangan2
```

#### 2. Setup Database
```bash
# Buat database MySQL
mysql -u root -p

# Di MySQL console:
CREATE DATABASE undangan_online CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Import schema
mysql -u root -p undangan_online < database.sql
```

#### 3. Konfigurasi
Edit file `config/config.php`:
```php
// Set environment
define('ENVIRONMENT', 'development'); // atau 'production'

// Set base URL sesuai instalasi Anda
define('BASE_URL', 'http://localhost/undangan-online/');
```

Edit file `config/database.php` (untuk production):
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'undangan_online');
```

#### 4. Set Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/undangan/
chmod 755 uploads/qr/
chmod 755 uploads/gallery/
chmod 755 uploads/music/
```

#### 5. Akses Aplikasi
- Homepage: `http://localhost/undangan-online/`
- Admin Login: `http://localhost/undangan-online/login-admin`
- Customer Login: `http://localhost/undangan-online/login-customer`

### Default Login Admin
- **Username:** admin
- **Password:** password

⚠️ **PENTING:** Ganti password admin setelah login pertama!

## 📖 Panduan Penggunaan

### Admin Panel

#### 1. Dashboard Admin
- Melihat statistik total undangan, customer, views, ucapan
- Akses quick links untuk manajemen

#### 2. Kelola Undangan
- Buat undangan baru dengan wizard form
- Edit undangan existing
- Preview undangan sebelum publish
- Hapus undangan

#### 3. Kelola User
- Tambah customer baru
- Edit data customer
- Reset password customer
- Hapus customer

### Customer Dashboard

#### 1. Edit Undangan
- Upload foto mempelai
- Edit data mempelai dan orang tua
- Tambah/edit cerita timeline
- Upload galeri foto
- Edit detail acara

#### 2. Kelola Ucapan
- Lihat semua ucapan dan RSVP
- Filter by status kehadiran
- Hapus ucapan yang tidak sesuai
- Export data ke CSV

#### 3. Kelola Hadiah
- Tambah rekening bank atau e-wallet
- Upload QR code untuk hadiah
- Edit dan hapus hadiah
- Reorder urutan tampilan

#### 4. Kelola Link Tamu
- Generate link personal untuk tamu
- Copy link dengan satu klik
- Edit nama tamu
- Bulk import via CSV

### Public View (Undangan)

#### Format URL
```
https://undangan.muza-project.com/sarah-ahmad
https://undangan.muza-project.com/sarah-ahmad/budi-dan-keluarga
```

#### Struktur Undangan
1. **Cover** - Nama mempelai, tanggal, greeting untuk tamu
2. **Mempelai** - Foto dan data mempelai
3. **Cerita Kami** - Timeline kisah cinta
4. **Countdown** - Hitung mundur hari H
5. **Acara** - Detail akad dan resepsi
6. **Galeri** - Koleksi foto
7. **Hadiah** - Info rekening dan QR code
8. **RSVP** - Form konfirmasi kehadiran
9. **Ucapan** - Daftar ucapan dari tamu

## 🛡️ Keamanan

### Fitur Keamanan Implemented
- ✅ Password hashing (bcrypt, cost 12)
- ✅ CSRF protection pada semua form
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (htmlspecialchars)
- ✅ Rate limiting pada login
- ✅ Session management yang aman
- ✅ File upload validation
- ✅ Activity logging

### Checklist Deployment Production
- [ ] Ganti `SECRET_KEY` di `config/config.php`
- [ ] Set `ENVIRONMENT` ke `'production'`
- [ ] Update database credentials
- [ ] Ganti password admin default
- [ ] Enable HTTPS dan SSL certificate
- [ ] Set `session.cookie_secure` ke true
- [ ] Disable error display (`display_errors = Off`)
- [ ] Setup backup database otomatis
- [ ] Review file permissions
- [ ] Test semua fitur keamanan

## 📁 Struktur Folder

```
undangan-online/
├── .htaccess              # URL rewriting & security
├── index.php              # Entry point
├── database.sql           # Database schema
├── config/
│   ├── config.php        # Konfigurasi umum
│   ├── database.php      # Koneksi database
│   └── security.php      # Fungsi keamanan
├── pages/
│   ├── home.php          # Homepage
│   ├── login-admin.php   # Login admin
│   ├── login-customer.php # Login customer
│   ├── logout.php        # Logout
│   ├── view-undangan.php # Public invitation view
│   ├── admin/            # Admin pages
│   │   ├── dashboard.php
│   │   ├── undangan-list.php
│   │   ├── undangan-preview.php
│   │   └── kelola-user.php
│   └── customer/         # Customer pages
│       ├── dashboard.php
│       ├── edit-undangan.php
│       ├── kelola-ucapan.php
│       ├── kelola-hadiah.php
│       └── kelola-link.php
├── css/
│   ├── main.css          # Base styles
│   ├── home.css          # Homepage styles
│   ├── undangan.css      # Invitation styles
│   ├── admin.css         # Admin panel styles
│   └── customer.css      # Customer dashboard styles
├── js/
│   ├── main.js           # Core JavaScript
│   ├── countdown.js      # Countdown timer
│   ├── gallery.js        # Gallery lightbox
│   ├── form-validation.js # Form validation
│   └── admin.js          # Admin functions
├── includes/
│   └── functions.php     # Helper functions
├── images/               # Static images
│   └── background/       # Template backgrounds
└── uploads/              # User uploads
    ├── undangan/         # Mempelai photos
    ├── qr/               # QR codes
    ├── gallery/          # Gallery photos
    └── music/            # Music files
```

## 🎨 Customization

### Menambah Background Template
1. Upload gambar ke `images/background/`
2. Nama file akan otomatis menjadi nama template
3. Gunakan format: `Name-Of-Template.jpg`

### Mengubah Theme Color
Edit di `css/main.css`:
```css
:root {
    --primary-color: #D4AF37;  /* Gold */
    --secondary-color: #8B7355; /* Brown */
}
```

### Menambah Font
Tambahkan di `<head>` section:
```html
<link href="https://fonts.googleapis.com/css2?family=Your+Font&display=swap" rel="stylesheet">
```

## 🔧 Troubleshooting

### URL Rewriting Tidak Berfungsi
```bash
# Pastikan mod_rewrite enabled
sudo a2enmod rewrite
sudo service apache2 restart
```

### Upload File Gagal
```bash
# Check permissions
chmod 755 uploads/
chmod 755 uploads/*

# Check PHP upload settings di php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

### Database Connection Error
- Cek credentials di `config/database.php`
- Pastikan MySQL service running
- Cek user memiliki privileges

## 📞 Support

Untuk bantuan atau pertanyaan:
- **Email:** support@muza-project.com
- **WhatsApp:** +62 851 7966 9566
- **Website:** https://muza-project.com

## 📄 License

Copyright © 2026 Muza Project. All rights reserved.

## 🙏 Credits

- **Developer:** Muza Project Team
- **Framework:** PHP Native
- **Icons:** Font Awesome
- **Fonts:** Google Fonts

---

Made with ❤️ by [Muza Project](https://muza-project.com)

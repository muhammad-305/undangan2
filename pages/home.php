<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Online - Buat Undangan Pernikahan Digital</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/home.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="<?php echo BASE_URL; ?>logo.png" alt="Logo" height="50">
                    <span>Undangan Online</span>
                </div>
                <nav class="nav">
                    <a href="#features">Fitur</a>
                    <a href="#templates">Template</a>
                    <a href="#contact">Kontak</a>
                    <a href="<?php echo BASE_URL; ?>login-admin" class="btn-login">Login Admin</a>
                    <a href="<?php echo BASE_URL; ?>login-customer" class="btn-login btn-customer">Login Customer</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Buat Undangan Pernikahan <br>Digital yang Menawan</h1>
                <p>Platform mudah untuk membuat undangan online yang indah dan elegan</p>
                <div class="hero-buttons">
                    <a href="<?php echo BASE_URL; ?>login-customer" class="btn btn-primary">Mulai Sekarang</a>
                    <a href="#templates" class="btn btn-secondary">Lihat Template</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Fitur Unggulan</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-palette"></i>
                    <h3>Desain Elegan</h3>
                    <p>Template undangan yang cantik dan mudah dikustomisasi</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Responsive</h3>
                    <p>Tampil sempurna di semua perangkat</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-link"></i>
                    <h3>Link Personal</h3>
                    <p>Buat link khusus untuk setiap tamu undangan</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-gift"></i>
                    <h3>Digital Gift</h3>
                    <p>Terima hadiah digital dengan mudah</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-comments"></i>
                    <h3>Ucapan & RSVP</h3>
                    <p>Terima ucapan dan konfirmasi kehadiran</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-music"></i>
                    <h3>Musik Background</h3>
                    <p>Tambahkan musik favorit Anda</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Templates Section -->
    <section id="templates" class="templates">
        <div class="container">
            <h2 class="section-title">Template Pilihan</h2>
            <div class="templates-grid">
                <?php
                $backgrounds = getBackgrounds();
                foreach (array_slice($backgrounds, 0, 6) as $bg) {
                    $name = pathinfo($bg, PATHINFO_FILENAME);
                    $name = str_replace(['-', '_'], ' ', $name);
                    echo '
                    <div class="template-card">
                        <div class="template-image" style="background-image: url(\'' . BASE_URL . 'images/background/' . $bg . '\')"></div>
                        <div class="template-info">
                            <h3>' . ucwords($name) . '</h3>
                        </div>
                    </div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Siap Membuat Undangan Anda?</h2>
            <p>Daftar sekarang dan buat undangan pernikahan digital yang berkesan</p>
            <a href="<?php echo BASE_URL; ?>login-customer" class="btn btn-primary">Mulai Gratis</a>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>Undangan Online</h3>
                    <p>Platform terbaik untuk membuat undangan pernikahan digital</p>
                </div>
                <div class="footer-col">
                    <h3>Kontak</h3>
                    <p><i class="fas fa-envelope"></i> support@muza-project.com</p>
                    <p><i class="fab fa-whatsapp"></i> +62 851 7966 9566</p>
                </div>
                <div class="footer-col">
                    <h3>Follow Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Undangan Online by Muza Project. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>js/main.js"></script>
</body>
</html>

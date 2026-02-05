<?php
// Public invitation viewing page
require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'includes/functions.php';

// Get parameters from URL
$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';
$tamu_slug = isset($_GET['tamu']) ? sanitizeInput($_GET['tamu']) : '';

if (empty($slug)) {
    header('Location: ' . BASE_URL);
    exit;
}

// Get undangan data
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM undangan WHERE slug = ? AND is_published = 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$undangan = $result->fetch_assoc();
$stmt->close();

if (!$undangan) {
    header('Location: ' . BASE_URL);
    exit;
}

// Get tamu data if tamu_slug provided
$tamu = null;
if (!empty($tamu_slug)) {
    $stmt = $conn->prepare("SELECT * FROM link_tamu WHERE undangan_id = ? AND slug_tamu = ?");
    $stmt->bind_param("is", $undangan['id'], $tamu_slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $tamu = $result->fetch_assoc();
    $stmt->close();
}

// Get hadiah data
$stmt = $conn->prepare("SELECT * FROM hadiah WHERE undangan_id = ? ORDER BY urutan ASC, id ASC");
$stmt->bind_param("i", $undangan['id']);
$stmt->execute();
$result = $stmt->get_result();
$hadiah_list = [];
while ($row = $result->fetch_assoc()) {
    $hadiah_list[] = $row;
}
$stmt->close();

// Get ucapan data (limited to 10)
$stmt = $conn->prepare("SELECT * FROM ucapan WHERE undangan_id = ? AND is_approved = 1 ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $undangan['id']);
$stmt->execute();
$result = $stmt->get_result();
$ucapan_list = [];
while ($row = $result->fetch_assoc()) {
    $ucapan_list[] = $row;
}
$stmt->close();

// Decode JSON fields
$cerita = !empty($undangan['cerita_json']) ? json_decode($undangan['cerita_json'], true) : [];
$galeri = !empty($undangan['galeri_json']) ? json_decode($undangan['galeri_json'], true) : [];

// Handle RSVP form submission
$form_success = false;
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rsvp'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $form_error = 'Token keamanan tidak valid';
    } else {
        $nama = sanitizeInput($_POST['nama']);
        $ucapan_text = sanitizeInput($_POST['ucapan']);
        $kehadiran = sanitizeInput($_POST['kehadiran']);
        $jumlah_tamu = isset($_POST['jumlah_tamu']) ? (int)$_POST['jumlah_tamu'] : 1;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        if (empty($nama) || empty($ucapan_text)) {
            $form_error = 'Nama dan ucapan harus diisi';
        } else {
            $stmt = $conn->prepare("INSERT INTO ucapan (undangan_id, nama, ucapan, kehadiran, jumlah_tamu, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssis", $undangan['id'], $nama, $ucapan_text, $kehadiran, $jumlah_tamu, $ip_address);
            if ($stmt->execute()) {
                $form_success = true;
                // Refresh ucapan list
                $stmt2 = $conn->prepare("SELECT * FROM ucapan WHERE undangan_id = ? AND is_approved = 1 ORDER BY created_at DESC LIMIT 10");
                $stmt2->bind_param("i", $undangan['id']);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $ucapan_list = [];
                while ($row = $result2->fetch_assoc()) {
                    $ucapan_list[] = $row;
                }
                $stmt2->close();
            } else {
                $form_error = 'Gagal mengirim ucapan';
            }
            $stmt->close();
        }
    }
}

// Increment view counter (once per session)
if (!isset($_SESSION['viewed_' . $undangan['id']])) {
    $stmt = $conn->prepare("UPDATE undangan SET views = views + 1 WHERE id = ?");
    $stmt->bind_param("i", $undangan['id']);
    $stmt->execute();
    $stmt->close();
    $_SESSION['viewed_' . $undangan['id']] = true;
}

$conn->close();

// Prepare music source
$music_src = '';
if (!empty($undangan['music_file'])) {
    $music_src = BASE_URL . 'uploads/music/' . $undangan['music_file'];
} elseif (!empty($undangan['music_url'])) {
    $music_src = $undangan['music_url'];
}

// Prepare background image
$bg_image = BASE_URL . 'images/background/' . $undangan['background_image'];

// Prepare colors
$primary_color = $undangan['primary_color'] ?? '#D4AF37';
$secondary_color = $undangan['secondary_color'] ?? '#FFFFFF';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Pernikahan <?php echo escapeOutput($undangan['nama_wanita']); ?> & <?php echo escapeOutput($undangan['nama_pria']); ?></title>
    
    <meta name="description" content="Undangan pernikahan <?php echo escapeOutput($undangan['nama_wanita']); ?> dan <?php echo escapeOutput($undangan['nama_pria']); ?>">
    <meta property="og:title" content="Undangan Pernikahan <?php echo escapeOutput($undangan['nama_wanita']); ?> & <?php echo escapeOutput($undangan['nama_pria']); ?>">
    <meta property="og:description" content="Kami mengundang Anda untuk berbagi kebahagiaan di hari istimewa kami">
    <meta property="og:image" content="<?php echo $bg_image; ?>">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&family=Great+Vibes&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/undangan.css">
    
    <style>
        :root {
            --primary-color: <?php echo $primary_color; ?>;
            --secondary-color: <?php echo $secondary_color; ?>;
        }
    </style>
</head>
<body>
    <!-- Cover Section -->
    <div id="cover" class="cover-section" style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('<?php echo $bg_image; ?>');">
        <div class="cover-content">
            <div class="cover-text">
                <h1 class="cover-title">The Wedding of</h1>
                <h2 class="cover-names"><?php echo escapeOutput($undangan['nama_wanita']); ?> & <?php echo escapeOutput($undangan['nama_pria']); ?></h2>
                <p class="cover-date"><?php echo formatDate($undangan['tanggal_resepsi'], 'd F Y'); ?></p>
                
                <?php if ($tamu): ?>
                <div class="cover-tamu">
                    <p>Kepada Yth:</p>
                    <h3><?php echo escapeOutput($tamu['nama_tamu']); ?></h3>
                </div>
                <?php endif; ?>
            </div>
            
            <button id="openInvitation" class="btn-open">
                <i class="fas fa-envelope-open"></i> Buka Undangan
            </button>
        </div>
    </div>

    <!-- Main Content (hidden until cover opened) -->
    <div id="mainContent" class="main-content" style="display: none;">
        
        <!-- Mempelai Section -->
        <section id="mempelai" class="section mempelai-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="arabic-text">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</h2>
                    <p class="section-subtitle">Assalamu'alaikum Warahmatullahi Wabarakatuh</p>
                </div>
                
                <div class="opening-text">
                    <p><?php echo nl2br(escapeOutput($undangan['pembuka_text'] ?? 'Dengan memohon rahmat dan ridho Allah SWT, kami bermaksud menyelenggarakan pernikahan putra-putri kami')); ?></p>
                </div>
                
                <div class="mempelai-container">
                    <div class="mempelai-item">
                        <div class="mempelai-photo">
                            <?php if (!empty($undangan['foto_wanita'])): ?>
                            <img src="<?php echo BASE_URL . 'uploads/undangan/' . $undangan['foto_wanita']; ?>" alt="<?php echo escapeOutput($undangan['nama_wanita']); ?>">
                            <?php else: ?>
                            <img src="<?php echo BASE_URL; ?>images/pengantin wanita.jpg" alt="<?php echo escapeOutput($undangan['nama_wanita']); ?>">
                            <?php endif; ?>
                        </div>
                        <h3 class="mempelai-name"><?php echo escapeOutput($undangan['nama_lengkap_wanita'] ?? $undangan['nama_wanita']); ?></h3>
                        <p class="mempelai-parents">Putri dari</p>
                        <p class="parent-name">Bapak <?php echo escapeOutput($undangan['nama_ayah_wanita']); ?></p>
                        <p class="parent-name">&</p>
                        <p class="parent-name">Ibu <?php echo escapeOutput($undangan['nama_ibu_wanita']); ?></p>
                        <?php if (!empty($undangan['instagram_wanita'])): ?>
                        <a href="https://instagram.com/<?php echo escapeOutput($undangan['instagram_wanita']); ?>" target="_blank" class="social-link">
                            <i class="fab fa-instagram"></i> @<?php echo escapeOutput($undangan['instagram_wanita']); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mempelai-divider">
                        <i class="fas fa-heart"></i>
                    </div>
                    
                    <div class="mempelai-item">
                        <div class="mempelai-photo">
                            <?php if (!empty($undangan['foto_pria'])): ?>
                            <img src="<?php echo BASE_URL . 'uploads/undangan/' . $undangan['foto_pria']; ?>" alt="<?php echo escapeOutput($undangan['nama_pria']); ?>">
                            <?php else: ?>
                            <img src="<?php echo BASE_URL; ?>images/pengantin pria.jpg" alt="<?php echo escapeOutput($undangan['nama_pria']); ?>">
                            <?php endif; ?>
                        </div>
                        <h3 class="mempelai-name"><?php echo escapeOutput($undangan['nama_lengkap_pria'] ?? $undangan['nama_pria']); ?></h3>
                        <p class="mempelai-parents">Putra dari</p>
                        <p class="parent-name">Bapak <?php echo escapeOutput($undangan['nama_ayah_pria']); ?></p>
                        <p class="parent-name">&</p>
                        <p class="parent-name">Ibu <?php echo escapeOutput($undangan['nama_ibu_pria']); ?></p>
                        <?php if (!empty($undangan['instagram_pria'])): ?>
                        <a href="https://instagram.com/<?php echo escapeOutput($undangan['instagram_pria']); ?>" target="_blank" class="social-link">
                            <i class="fab fa-instagram"></i> @<?php echo escapeOutput($undangan['instagram_pria']); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="doa-text">
                    <p class="quote">"Dan di antara tanda-tanda (kebesaran)-Nya ialah Dia menciptakan pasangan-pasangan untukmu dari jenismu sendiri, agar kamu cenderung dan merasa tenteram kepadanya, dan Dia menjadikan di antaramu rasa kasih dan sayang. Sungguh, pada yang demikian itu benar-benar terdapat tanda-tanda (kebesaran Allah) bagi kaum yang berpikir."</p>
                    <p class="quote-source">- QS. Ar-Rum: 21 -</p>
                </div>
            </div>
        </section>

        <?php if (!empty($cerita) && is_array($cerita)): ?>
        <!-- Cerita Kami Section -->
        <section id="cerita" class="section cerita-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Cerita Kami</h2>
                    <div class="title-divider"></div>
                </div>
                
                <div class="timeline">
                    <?php foreach ($cerita as $index => $item): ?>
                    <div class="timeline-item <?php echo $index % 2 === 0 ? 'left' : 'right'; ?>">
                        <div class="timeline-content">
                            <span class="timeline-year"><?php echo escapeOutput($item['tahun'] ?? ''); ?></span>
                            <h3><?php echo escapeOutput($item['judul'] ?? ''); ?></h3>
                            <p><?php echo nl2br(escapeOutput($item['deskripsi'] ?? '')); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Countdown Section -->
        <section id="countdown" class="section countdown-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Menghitung Hari</h2>
                    <div class="title-divider"></div>
                </div>
                
                <div id="countdownTimer" class="countdown-timer" data-date="<?php echo $undangan['tanggal_resepsi'] . ' ' . $undangan['waktu_resepsi_mulai']; ?>">
                    <div class="countdown-item">
                        <span class="countdown-value" id="days">0</span>
                        <span class="countdown-label">Hari</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value" id="hours">0</span>
                        <span class="countdown-label">Jam</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value" id="minutes">0</span>
                        <span class="countdown-label">Menit</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value" id="seconds">0</span>
                        <span class="countdown-label">Detik</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Acara Section -->
        <section id="acara" class="section acara-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Acara Pernikahan</h2>
                    <div class="title-divider"></div>
                </div>
                
                <div class="acara-container">
                    <!-- Akad Nikah -->
                    <div class="acara-card">
                        <div class="acara-icon">
                            <i class="fas fa-ring"></i>
                        </div>
                        <h3 class="acara-title">Akad Nikah</h3>
                        <div class="acara-details">
                            <p class="acara-day"><?php echo getDayName($undangan['tanggal_akad']); ?>, <?php echo formatDate($undangan['tanggal_akad']); ?></p>
                            <p class="acara-time">
                                <i class="far fa-clock"></i> 
                                <?php echo date('H:i', strtotime($undangan['waktu_akad_mulai'])); ?> - 
                                <?php echo date('H:i', strtotime($undangan['waktu_akad_selesai'])); ?> WIB
                            </p>
                            <p class="acara-place">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo escapeOutput($undangan['tempat_akad']); ?>
                            </p>
                            <p class="acara-address"><?php echo nl2br(escapeOutput($undangan['alamat_akad'])); ?></p>
                            <?php if (!empty($undangan['maps_akad'])): ?>
                            <a href="<?php echo escapeOutput($undangan['maps_akad']); ?>" target="_blank" class="btn-map">
                                <i class="fas fa-map-marked-alt"></i> Lihat Lokasi
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Resepsi -->
                    <div class="acara-card">
                        <div class="acara-icon">
                            <i class="fas fa-glass-cheers"></i>
                        </div>
                        <h3 class="acara-title">Resepsi</h3>
                        <div class="acara-details">
                            <p class="acara-day"><?php echo getDayName($undangan['tanggal_resepsi']); ?>, <?php echo formatDate($undangan['tanggal_resepsi']); ?></p>
                            <p class="acara-time">
                                <i class="far fa-clock"></i> 
                                <?php echo date('H:i', strtotime($undangan['waktu_resepsi_mulai'])); ?> - 
                                <?php echo date('H:i', strtotime($undangan['waktu_resepsi_selesai'])); ?> WIB
                            </p>
                            <p class="acara-place">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo escapeOutput($undangan['tempat_resepsi']); ?>
                            </p>
                            <p class="acara-address"><?php echo nl2br(escapeOutput($undangan['alamat_resepsi'])); ?></p>
                            <?php if (!empty($undangan['maps_resepsi'])): ?>
                            <a href="<?php echo escapeOutput($undangan['maps_resepsi']); ?>" target="_blank" class="btn-map">
                                <i class="fas fa-map-marked-alt"></i> Lihat Lokasi
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php if (!empty($galeri) && is_array($galeri)): ?>
        <!-- Galeri Section -->
        <section id="galeri" class="section galeri-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Galeri Foto</h2>
                    <div class="title-divider"></div>
                </div>
                
                <div class="gallery-grid">
                    <?php foreach ($galeri as $foto): ?>
                    <div class="gallery-item">
                        <img src="<?php echo BASE_URL . 'uploads/gallery/' . escapeOutput($foto); ?>" alt="Gallery Photo" onclick="openLightbox('<?php echo BASE_URL . 'uploads/gallery/' . escapeOutput($foto); ?>')">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($hadiah_list)): ?>
        <!-- Hadiah Section -->
        <section id="hadiah" class="section hadiah-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Hadiah Pernikahan</h2>
                    <div class="title-divider"></div>
                    <p class="section-subtitle">Doa Restu Anda merupakan karunia yang sangat berarti bagi kami. Namun jika Anda ingin memberi hadiah, kami menyediakan:</p>
                </div>
                
                <div class="hadiah-container">
                    <?php foreach ($hadiah_list as $hadiah): ?>
                    <div class="hadiah-card">
                        <div class="hadiah-icon">
                            <?php if ($hadiah['jenis'] === 'bank'): ?>
                            <i class="fas fa-university"></i>
                            <?php else: ?>
                            <i class="fas fa-wallet"></i>
                            <?php endif; ?>
                        </div>
                        <h3 class="hadiah-name">
                            <?php echo escapeOutput($hadiah['jenis'] === 'bank' ? $hadiah['nama_bank'] : $hadiah['nama_ewallet']); ?>
                        </h3>
                        <p class="hadiah-number"><?php echo escapeOutput($hadiah['nomor_rekening']); ?></p>
                        <p class="hadiah-owner">a.n. <?php echo escapeOutput($hadiah['atas_nama']); ?></p>
                        <?php if (!empty($hadiah['qr_code'])): ?>
                        <div class="hadiah-qr">
                            <img src="<?php echo BASE_URL . 'uploads/qr/' . escapeOutput($hadiah['qr_code']); ?>" alt="QR Code">
                        </div>
                        <?php endif; ?>
                        <button class="btn-copy" onclick="copyToClipboard('<?php echo escapeOutput($hadiah['nomor_rekening']); ?>')">
                            <i class="far fa-copy"></i> Salin Nomor
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- RSVP Section -->
        <section id="rsvp" class="section rsvp-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Konfirmasi Kehadiran</h2>
                    <div class="title-divider"></div>
                </div>
                
                <?php if ($form_success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Terima kasih! Ucapan Anda telah terkirim.
                </div>
                <?php endif; ?>
                
                <?php if ($form_error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo escapeOutput($form_error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="rsvp-form" id="rsvpForm">
                    <?php echo csrfField(); ?>
                    
                    <div class="form-group">
                        <label for="nama">Nama <span class="required">*</span></label>
                        <input type="text" id="nama" name="nama" value="<?php echo $tamu ? escapeOutput($tamu['nama_tamu']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ucapan">Ucapan & Doa <span class="required">*</span></label>
                        <textarea id="ucapan" name="ucapan" rows="4" placeholder="Tuliskan ucapan dan doa untuk kami..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Konfirmasi Kehadiran <span class="required">*</span></label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="kehadiran" value="hadir" required>
                                <span>Hadir</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="kehadiran" value="tidak_hadir" required>
                                <span>Tidak Hadir</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="kehadiran" value="masih_ragu" required>
                                <span>Masih Ragu</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group" id="jumlahTamuGroup" style="display: none;">
                        <label for="jumlah_tamu">Jumlah Tamu</label>
                        <input type="number" id="jumlah_tamu" name="jumlah_tamu" min="1" value="1">
                    </div>
                    
                    <button type="submit" name="submit_rsvp" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Kirim Ucapan
                    </button>
                </form>
            </div>
        </section>

        <!-- Ucapan Section -->
        <section id="ucapan" class="section ucapan-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Ucapan & Doa</h2>
                    <div class="title-divider"></div>
                </div>
                
                <div class="ucapan-container">
                    <?php if (empty($ucapan_list)): ?>
                    <p class="no-ucapan">Belum ada ucapan. Jadilah yang pertama memberikan ucapan!</p>
                    <?php else: ?>
                    <?php foreach ($ucapan_list as $u): ?>
                    <div class="ucapan-card">
                        <div class="ucapan-header">
                            <div class="ucapan-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="ucapan-info">
                                <h4 class="ucapan-name"><?php echo escapeOutput($u['nama']); ?></h4>
                                <p class="ucapan-time"><?php echo timeAgo($u['created_at']); ?></p>
                            </div>
                            <span class="ucapan-badge <?php echo $u['kehadiran']; ?>">
                                <?php 
                                $badge_text = [
                                    'hadir' => 'Hadir',
                                    'tidak_hadir' => 'Tidak Hadir',
                                    'masih_ragu' => 'Masih Ragu'
                                ];
                                echo $badge_text[$u['kehadiran']] ?? 'Masih Ragu';
                                ?>
                            </span>
                        </div>
                        <div class="ucapan-body">
                            <p><?php echo nl2br(escapeOutput($u['ucapan'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <p class="footer-thanks">Terima kasih atas doa dan kehadiran Anda</p>
                <p class="footer-names"><?php echo escapeOutput($undangan['nama_wanita']); ?> & <?php echo escapeOutput($undangan['nama_pria']); ?></p>
                <div class="footer-divider"></div>
                <p class="footer-copyright">© 2026 Undangan Online by <a href="https://muza-project.com" target="_blank">Muza Project</a></p>
            </div>
        </footer>
    </div>

    <!-- Music Player -->
    <?php if (!empty($music_src)): ?>
    <div id="musicPlayer" class="music-player" style="display: none;">
        <button id="musicToggle" class="music-toggle">
            <i class="fas fa-play"></i>
        </button>
        <audio id="backgroundMusic" loop>
            <source src="<?php echo $music_src; ?>" type="audio/mpeg">
        </audio>
    </div>
    <?php endif; ?>

    <!-- Lightbox -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <span class="lightbox-close">&times;</span>
        <img id="lightboxImage" src="" alt="Lightbox Image">
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Share Button -->
    <div class="share-button">
        <button class="btn-share" onclick="toggleShareMenu()">
            <i class="fas fa-share-alt"></i>
        </button>
        <div class="share-menu" id="shareMenu">
            <a href="https://wa.me/?text=<?php echo urlencode('Undangan Pernikahan ' . $undangan['nama_wanita'] . ' & ' . $undangan['nama_pria'] . ' - ' . BASE_URL . $slug . ($tamu ? '/' . $tamu_slug : '')); ?>" target="_blank" class="share-item whatsapp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(BASE_URL . $slug . ($tamu ? '/' . $tamu_slug : '')); ?>" target="_blank" class="share-item facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <button onclick="copyLink()" class="share-item copy">
                <i class="fas fa-link"></i>
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Open invitation
        document.getElementById('openInvitation').addEventListener('click', function() {
            document.getElementById('cover').style.display = 'none';
            document.getElementById('mainContent').style.display = 'block';
            
            // Start music
            const music = document.getElementById('backgroundMusic');
            const musicPlayer = document.getElementById('musicPlayer');
            if (music && musicPlayer) {
                music.play();
                musicPlayer.style.display = 'block';
                document.getElementById('musicToggle').innerHTML = '<i class="fas fa-pause"></i>';
            }
            
            // Smooth scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Music toggle
        const musicToggle = document.getElementById('musicToggle');
        const backgroundMusic = document.getElementById('backgroundMusic');
        if (musicToggle && backgroundMusic) {
            musicToggle.addEventListener('click', function() {
                if (backgroundMusic.paused) {
                    backgroundMusic.play();
                    this.innerHTML = '<i class="fas fa-pause"></i>';
                } else {
                    backgroundMusic.pause();
                    this.innerHTML = '<i class="fas fa-play"></i>';
                }
            });
        }

        // Countdown timer
        function updateCountdown() {
            const countdownElement = document.getElementById('countdownTimer');
            if (!countdownElement) return;
            
            const targetDate = new Date(countdownElement.dataset.date).getTime();
            const now = new Date().getTime();
            const difference = targetDate - now;

            if (difference > 0) {
                const days = Math.floor(difference / (1000 * 60 * 60 * 24));
                const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((difference % (1000 * 60)) / 1000);

                document.getElementById('days').textContent = days;
                document.getElementById('hours').textContent = hours;
                document.getElementById('minutes').textContent = minutes;
                document.getElementById('seconds').textContent = seconds;
            } else {
                document.getElementById('days').textContent = '0';
                document.getElementById('hours').textContent = '0';
                document.getElementById('minutes').textContent = '0';
                document.getElementById('seconds').textContent = '0';
            }
        }
        setInterval(updateCountdown, 1000);
        updateCountdown();

        // Show jumlah tamu field when hadir selected
        const kehadiranRadios = document.querySelectorAll('input[name="kehadiran"]');
        const jumlahTamuGroup = document.getElementById('jumlahTamuGroup');
        kehadiranRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'hadir') {
                    jumlahTamuGroup.style.display = 'block';
                } else {
                    jumlahTamuGroup.style.display = 'none';
                }
            });
        });

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Nomor berhasil disalin!');
            }).catch(err => {
                showToast('Gagal menyalin nomor');
            });
        }

        // Show toast
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Lightbox
        function openLightbox(src) {
            document.getElementById('lightbox').style.display = 'flex';
            document.getElementById('lightboxImage').src = src;
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Share menu toggle
        function toggleShareMenu() {
            const shareMenu = document.getElementById('shareMenu');
            shareMenu.classList.toggle('active');
        }

        // Copy link
        function copyLink() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                showToast('Link berhasil disalin!');
            });
        }

        // Scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.section').forEach(section => {
            observer.observe(section);
        });

        // Auto-scroll after form submit
        <?php if ($form_success): ?>
        setTimeout(() => {
            document.getElementById('ucapan').scrollIntoView({ behavior: 'smooth' });
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>

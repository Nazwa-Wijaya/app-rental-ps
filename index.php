<?php
/**
 * Landing Page - Rental PS Booking System
 */
require_once __DIR__ . '/config/database.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_role = $is_logged_in ? $_SESSION['user_role'] : '';

// Handle CTA redirect link
$cta_link = $is_logged_in ? 'booking.php' : 'login.php';
if (!$is_logged_in && isset($_GET['action']) && $_GET['action'] === 'book') {
    $_SESSION['redirect_to_booking'] = true;
    $_SESSION['login_prompt'] = 'Harap login terlebih dahulu untuk memesan room gaming.';
    header("Location: login.php");
    exit;
}

try {
    // Fetch Rooms for display
    $stmt_rooms = $db->query("SELECT * FROM rooms ORDER BY price_per_hour ASC");
    $rooms = $stmt_rooms->fetchAll();

    // Fetch Foods for display
    $stmt_foods = $db->query("SELECT * FROM foods ORDER BY category, name ASC");
    $foods = $stmt_foods->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental PS Booking System - Gaming Room Reservation</title>
    <meta name="description" content="Sistem Reservasi Room Rental Nintendo Switch, PS4, dan PS5 Premium. Pilih room VIP, pesan makanan ringan favorit Anda, dan rasakan atmosfer gaming termewah.">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Specific page styling additions if needed */
        .hero-banner {
            background: linear-gradient(135deg, rgba(8, 3, 21, 0.9) 0%, rgba(20, 12, 40, 0.8) 100%), 
                        url('https://images.unsplash.com/photo-1600861195091-690c92f1d2cc?q=80&w=1470&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
    </style>
</head>
<body>

    <!-- Sticky Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">RENTAL<span>PS</span></a>
            <ul class="nav-links">
                <li class="nav-item"><a href="#home">Home</a></li>
                <li class="nav-item"><a href="#rooms">Rooms</a></li>
                <li class="nav-item"><a href="#food-drinks">Food & Drinks</a></li>
                <li class="nav-item"><a href="#faq">FAQ</a></li>
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a href="<?= $user_role === 'admin' ? 'admin/dashboard.php' : 'dashboard.php' ?>" class="user-nav-btn">
                            🎮 <?= sanitize($user_name) ?>
                        </a>
                    </li>
                    <li class="nav-item"><a href="logout.php" style="color: #e63946;">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a href="login.php" class="btn btn-primary btn-sm">Login / Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <header id="home" class="hero hero-banner">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <h1 class="text-light">Level Up Your <span class="text-cyan">Gaming</span> Night!</h1>
                    <p>
                        Pusat rental console game terkemuka. Rasakan sensasi bermain Nintendo Switch, PlayStation 4, dan PlayStation 5 di room VIP ber-AC lengkap dengan soundbar surround, reclining sofa, dan snack bar melimpah.
                    </p>
                    <a href="index.php?action=book" class="btn btn-secondary btn-lg">Book a Room Now</a>
                </div>
                <div class="hero-image-wrapper">
                    <!-- Standard Controller Illustration / Graphic representation of console -->
                    <svg viewBox="0 0 512 512" width="380" height="380" fill="none" xmlns="http://www.w3.org/2000/svg" class="hero-img" style="filter: drop-shadow(0 0 25px rgba(157, 78, 221, 0.6));">
                        <path d="M128 176c-35.35 0-64 28.65-64 64v80c0 35.35 28.65 64 64 64h256c35.35 0 64-28.65 64-64v-80c0-35.35-28.65-64-64-64H128z" fill="#9d4edd" />
                        <path d="M128 192h256c26.51 0 48 21.49 48 48v80c0 26.51-21.49 48-48 48H128c-26.51 0-48-21.49-48-48v-80c0-26.51 21.49-48 48-48z" fill="#120b2e" />
                        <circle cx="160" cy="280" r="28" fill="#9d4edd" />
                        <circle cx="352" cy="280" r="28" fill="#9d4edd" />
                        <path d="M160 260v40M140 280h40" stroke="#fff" stroke-width="8" stroke-linecap="round" />
                        <circle cx="332" cy="280" r="8" fill="#ffc107" />
                        <circle cx="372" cy="280" r="8" fill="#ffc107" />
                        <circle cx="352" cy="260" r="8" fill="#00f0ff" />
                        <circle cx="352" cy="300" r="8" fill="#00f0ff" />
                        <rect x="232" y="270" width="16" height="8" rx="4" fill="#fff" />
                        <rect x="264" y="270" width="16" height="8" rx="4" fill="#fff" />
                    </svg>
                </div>
            </div>
        </div>
    </header>

    <!-- Rooms Showcase Section -->
    <section id="rooms" class="rooms-section">
        <div class="container">
            <h2 class="section-title">Pilihan Gaming Rooms</h2>
            <p class="section-subtitle">Didesain khusus untuk memberikan kenyamanan maksimal bagi para gamer, dari perorangan hingga co-op mabar multiplayer.</p>
            
            <div class="grid-3">
                <?php foreach ($rooms as $room): ?>
                    <div class="card select-card" style="cursor: default;">
                        <div class="card-img-wrapper">
                            <!-- Visual mock of the room -->
                            <?php if ($room['id'] == 1): ?>
                                <img src="https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=640&auto=format&fit=crop" alt="Room Regular" class="card-img">
                            <?php elseif ($room['id'] == 2): ?>
                                <img src="https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=640&auto=format&fit=crop" alt="Room VIP" class="card-img">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1600861195091-690c92f1d2cc?q=80&w=640&auto=format&fit=crop" alt="Room VIP Luxury" class="card-img">
                            <?php endif; ?>
                            <span class="badge badge-pending" style="position: absolute; top: 15px; right: 15px; background: rgba(8,3,21,0.85); border-color: var(--primary); color: #fff;">
                                Max <?= $room['max_people'] ?> Orang
                            </span>
                        </div>
                        <h3 class="card-title"><?= sanitize($room['name']) ?></h3>
                        <p class="card-desc"><?= sanitize($room['description']) ?></p>
                        <div class="card-action">
                            <span class="card-price text-cyan"><?= format_rupiah($room['price_per_hour']) ?><span style="font-size:0.85rem; font-weight:normal;" class="text-muted">/jam</span></span>
                            <a href="index.php?action=book" class="btn btn-primary btn-sm">Book</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Food & Drinks Showcase Section -->
    <section id="food-drinks" class="foods-section" style="background: rgba(10, 5, 25, 0.5);">
        <div class="container">
            <h2 class="section-title">Food & Drinks Corner</h2>
            <p class="section-subtitle">Mabar jadi makin asyik dan tidak kelaparan dengan cemilan, ramen panas, kopi premium, dan minuman dingin segar langsung ke room Anda.</p>
            
            <div class="food-tabs" style="justify-content: center;">
                <button class="food-tab-btn active" data-category="all">Semua Menu</button>
                <button class="food-tab-btn" data-category="Basic Drinks">Basic Drinks</button>
                <button class="food-tab-btn" data-category="Coffee">Coffee</button>
                <button class="food-tab-btn" data-category="Tea">Tea</button>
                <button class="food-tab-btn" data-category="Snacks">Snacks</button>
                <button class="food-tab-btn" data-category="Instant Noodles">Instant Noodles</button>
                <button class="food-tab-btn" data-category="Others">Others</button>
            </div>

            <div class="card" style="padding: 10px 0;">
                <div id="food-items-container">
                    <?php foreach ($foods as $food): ?>
                        <div class="food-item" data-category="<?= sanitize($food['category']) ?>" data-food-id="<?= $food['id'] ?>">
                            <div class="food-img-wrapper">
                                <!-- Food mock items display -->
                                <?php if (strpos($food['name'], 'Cola') !== false): ?>
                                    <img src="https://images.unsplash.com/photo-1622483767028-3f66f32aef97?q=80&w=150&auto=format&fit=crop" alt="Cola" class="food-item-img">
                                <?php elseif (strpos($food['name'], 'Mineral') !== false): ?>
                                    <img src="https://images.unsplash.com/photo-1616118132534-381148898bb4?q=80&w=150&auto=format&fit=crop" alt="Aqua" class="food-item-img">
                                <?php elseif (strpos($food['name'], 'Cappuccino') !== false): ?>
                                    <img src="https://images.unsplash.com/photo-1572442388796-11668a67e53d?q=80&w=150&auto=format&fit=crop" alt="Cappuccino" class="food-item-img">
                                <?php elseif (strpos($food['name'], 'Espresso') !== false): ?>
                                    <img src="https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?q=80&w=150&auto=format&fit=crop" alt="Espresso" class="food-item-img">
                                <?php elseif (strpos($food['name'], 'Sweet Tea') !== false): ?>
                                    <img src="https://images.unsplash.com/photo-1556881286-fc6915169721?q=80&w=150&auto=format&fit=crop" alt="Sweet Tea" class="food-item-img">
                                <?php elseif (strpos($food['name'], 'Matcha') !== false): ?>
                                    <img src="https://images.unsplash.com/photo-1536256263959-770b48d82b0a?q=80&w=150&auto=format&fit=crop" alt="Matcha" class="food-item-img">
                                <?php elseif (strpos($food['name'], 'French') !== false): ?>
                                    <img src="https://images.unsplash.com/photo-1573080496219-bb080dd4f877?q=80&w=150&auto=format&fit=crop" alt="French Fries" class="food-item-img">
                                <?php elseif (strpos($food['name'], 'Indomie') !== false): ?>
                                    <img src="https://images.unsplash.com/photo-1569718212165-3a8278d5f624?q=80&w=150&auto=format&fit=crop" alt="Indomie" class="food-item-img">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1584947968564-ee7ad2164ba1?q=80&w=150&auto=format&fit=crop" alt="Toast" class="food-item-img">
                                <?php endif; ?>
                            </div>
                            <div class="food-info">
                                <span class="badge" style="background: rgba(157, 78, 221, 0.15); color: var(--primary); border: 1px solid var(--primary); margin-bottom: 5px;"><?= sanitize($food['category']) ?></span>
                                <h4 class="food-name"><?= sanitize($food['name']) ?></h4>
                                <p class="food-desc"><?= sanitize($food['description']) ?></p>
                            </div>
                            <div style="text-align: right; margin-right: 15px;">
                                <div class="food-price"><?= format_rupiah($food['price']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="faq-section">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Punya pertanyaan seputar layanan kami? Berikut jawaban dari pertanyaan yang paling sering ditanyakan.</p>
            
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question">Bagaimana cara melakukan booking room? <span class="text-cyan">+</span></div>
                    <div class="faq-answer">
                        Anda hanya perlu login ke akun Anda (atau daftar jika belum punya), lalu klik tombol "Book a Room". Ikuti langkah-langkah mudah mulai dari memilih console, room, menentukan tanggal/waktu, dan memesan makanan opsional. Setelah itu lakukan pembayaran instan untuk mengonfirmasi pesanan.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Console apa saja yang tersedia di Rental PS Booking System? <span class="text-cyan">+</span></div>
                    <div class="faq-answer">
                        Kami menyediakan 3 jenis console game premium terbaru yang bisa Anda pilih, yaitu Nintendo Switch (sangat cocok untuk game multiplayer mabar santai), PlayStation 4, dan PlayStation 5 (generasi terbaru dengan resolusi grafis 4K UHD).
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Berapa kapasitas maksimal dari room rental? <span class="text-cyan">+</span></div>
                    <div class="faq-answer">
                        Tergantung tipe room yang dipilih. Room tipe Reguler memiliki kapasitas maksimal 2 orang. Sementara room tipe VIP dan VIP Luxury dapat menampung hingga maksimal 4 orang gamer.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Apakah slot waktu booking bisa bentrok dengan orang lain? <span class="text-cyan">+</span></div>
                    <div class="faq-answer">
                        Sistem kami sudah terintegrasi dengan pengecekan slot waktu real-time. Jika suatu room/console sudah dibooking pada jam tertentu, slot waktu tersebut akan otomatis ditandai tidak tersedia, sehingga terhindar dari bentrokan jadwal bermain.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Metode pembayaran apa saja yang didukung? <span class="text-cyan">+</span></div>
                    <div class="faq-answer">
                        Kami mendukung metode pembayaran non-tunai yang praktis dan instan seperti QRIS (Gopay, OVO, Dana, LinkAja, ShopeePay), Transfer Bank Mandiri/BCA, serta E-Wallet. Status pembayaran akan terverifikasi otomatis.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <span>Rental PS Booking System</span>. All rights reserved.</p>
            <p style="font-size: 0.8rem; margin-top: 10px; color: var(--text-muted);">Didesain secara khusus untuk tugas kelompok pengembangan web.</p>
        </div>
    </footer>

    <!-- JS Main Script -->
    <script src="assets/js/script.js"></script>
    <script>
        // Additional front-end filter category logic for foods showcase
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.food-tab-btn');
            const items = document.querySelectorAll('.food-item');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    const cat = tab.dataset.category;
                    items.forEach(item => {
                        if (cat === 'all' || item.dataset.category === cat) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>

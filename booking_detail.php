<?php
/**
 * Booking Detail Page
 */
require_once __DIR__ . '/config/database.php';

// Guard login
check_login();

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($booking_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

try {
    // 1. Fetch main booking details
    $stmt = $db->prepare("
        SELECT b.*, 
               c.name as console_name, c.description as console_desc,
               r.name as room_name, r.price_per_hour as room_price, r.max_people,
               g.name as game_name,
               u.name as customer_name, u.email as customer_email, u.phone as customer_phone
        FROM bookings b
        JOIN consoles c ON b.console_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN games g ON b.game_id = g.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :booking_id
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        die("Data booking tidak ditemukan.");
    }

    // 2. Security Guard: Prevent standard users from viewing other people's bookings
    if ($user_role !== 'admin' && intval($booking['user_id']) !== intval($user_id)) {
        die("Akses ditolak. Anda tidak berhak melihat data booking ini.");
    }

    // 3. Fetch food list for this booking
    $food_stmt = $db->prepare("
        SELECT bf.*, f.name as food_name, f.category as food_category 
        FROM booking_foods bf
        JOIN foods f ON bf.food_id = f.id
        WHERE bf.booking_id = :booking_id
    ");
    $food_stmt->execute([':booking_id' => $booking_id]);
    $foods = $food_stmt->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Summary - <?= sanitize($booking['booking_code']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .invoice-card {
            border: 2px solid var(--primary);
            box-shadow: 0 0 20px var(--primary-glow);
        }
        .invoice-header {
            border-bottom: 2px dashed rgba(157, 78, 221, 0.3);
            padding-bottom: 25px;
            margin-bottom: 30px;
        }
        .invoice-footer {
            border-top: 2px dashed rgba(157, 78, 221, 0.3);
            padding-top: 20px;
            margin-top: 30px;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Sticky Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">RENTAL<span>PS</span></a>
            <ul class="nav-links">
                <li class="nav-item"><a href="index.php">Home</a></li>
                <li class="nav-item"><a href="index.php#rooms">Rooms</a></li>
                <li class="nav-item"><a href="index.php#food-drinks">Food & Drinks</a></li>
                <li class="nav-item"><a href="index.php#faq">FAQ</a></li>
                <li class="nav-item active">
                    <a href="<?= $user_role === 'admin' ? 'admin/bookings.php' : 'dashboard.php' ?>">
                        <?= $user_role === 'admin' ? 'Kembali ke Admin' : 'Dashboard' ?>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Invoice Detail Main Area -->
    <main class="container" style="padding: 40px 20px; flex-grow: 1;">
        
        <div style="margin-bottom: 20px;">
            <a href="<?= $user_role === 'admin' ? 'admin/bookings.php' : 'dashboard.php' ?>" class="btn btn-outline btn-sm">
                ← Kembali
            </a>
        </div>

        <div class="card invoice-card">
            
            <!-- Invoice Header -->
            <div class="invoice-header d-flex justify-between align-center" style="flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="font-size: 2.2rem; margin-bottom: 5px;">Rincian Reservasi</h1>
                    <p class="text-muted">Kode Booking: <strong class="text-cyan"><?= sanitize($booking['booking_code']) ?></strong></p>
                    <p class="text-muted">Dibuat pada: <?= date('d-m-Y H:i', strtotime($booking['created_at'])) ?></p>
                </div>
                <div style="text-align: right;">
                    <div style="margin-bottom: 8px;">
                        Status: 
                        <?php if ($booking['booking_status'] === 'paid'): ?>
                            <span class="badge badge-paid">PAID & CONFIRMED</span>
                        <?php elseif ($booking['booking_status'] === 'cancelled'): ?>
                            <span class="badge badge-cancelled">CANCELLED</span>
                        <?php else: ?>
                            <span class="badge badge-pending">PENDING PAYMENT</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted">Metode: <strong><?= sanitize($booking['payment_method'] ?? 'Belum memilih') ?></strong></div>
                </div>
            </div>

            <!-- Invoice Content Grid -->
            <div class="detail-grid">
                
                <!-- Left: Information details -->
                <div>
                    <div class="mb-4">
                        <h4 class="text-cyan" style="border-bottom: 1px solid rgba(0, 240, 255, 0.2); padding-bottom: 5px; margin-bottom: 10px;">Informasi Pelanggan</h4>
                        <table style="width: 100%; border-spacing: 0 8px; border-collapse: separate;">
                            <tr>
                                <td style="width: 140px;" class="text-muted">Nama Lengkap</td>
                                <td>: <strong><?= sanitize($booking['customer_name']) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">No. WhatsApp</td>
                                <td>: <?= sanitize($booking['customer_phone']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Alamat Email</td>
                                <td>: <?= sanitize($booking['customer_email']) ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="mb-4">
                        <h4 class="text-cyan" style="border-bottom: 1px solid rgba(0, 240, 255, 0.2); padding-bottom: 5px; margin-bottom: 10px;">Informasi Reservasi</h4>
                        <table style="width: 100%; border-spacing: 0 8px; border-collapse: separate;">
                            <tr>
                                <td style="width: 140px;" class="text-muted">Console Pilihan</td>
                                <td>: <?= sanitize($booking['console_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Room Pilihan</td>
                                <td>: <?= sanitize($booking['room_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Game Utama</td>
                                <td>: <?= sanitize($booking['game_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jumlah Pemain</td>
                                <td>: <?= $booking['people_count'] ?> orang (Kapasitas maks <?= $booking['max_people'] ?>)</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Waktu Main</td>
                                <td>: <?= date('d-m-Y', strtotime($booking['booking_date'])) ?> | Pukul <?= date('H:i', strtotime($booking['start_time'])) ?> s/d <?= date('H:i', strtotime($booking['end_time'])) ?> (<?= $booking['duration'] ?> jam)</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Catatan Pelanggan</td>
                                <td>: <span style="font-style: italic;"><?= !empty($booking['notes']) ? sanitize($booking['notes']) : '-' ?></span></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Right: Pricing Receipt -->
                <div>
                    <div class="card" style="background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(157, 78, 221, 0.2);">
                        <h4 class="text-purple" style="margin-bottom: 15px;">Rincian Pembayaran</h4>
                        
                        <div class="summary-list">
                            <!-- Room Cost Row -->
                            <div class="summary-row">
                                <span class="text-muted">Biaya Room (<?= $booking['duration'] ?> jam x <?= format_rupiah($booking['room_price']) ?>)</span>
                                <span><?= format_rupiah($booking['room_total']) ?></span>
                            </div>

                            <!-- Food Cost Row -->
                            <div class="summary-row">
                                <span class="text-muted">Layanan Makanan & Minuman</span>
                                <span><?= format_rupiah($booking['food_total']) ?></span>
                            </div>
                            
                            <!-- Food details breakdown -->
                            <?php if (count($foods) > 0): ?>
                                <ul class="summary-food-list">
                                    <?php foreach ($foods as $food): ?>
                                        <li>• <?= sanitize($food['food_name']) ?> x<?= $food['quantity'] ?> (<?= format_rupiah($food['subtotal']) ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Discount Row -->
                            <?php if (floatval($booking['discount']) > 0): ?>
                                <div class="summary-row" style="color: #2ec4b6;">
                                    <span>Diskon Promo (OPENINGYUK 10%)</span>
                                    <span>-<?= format_rupiah($booking['discount']) ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Service Fee -->
                            <div class="summary-row">
                                <span class="text-muted">Service Fee</span>
                                <span><?= format_rupiah($booking['service_fee']) ?></span>
                            </div>

                            <!-- Grand Total -->
                            <div class="summary-row total-row" style="border-top: 1px dashed rgba(157, 78, 221, 0.3); padding-top: 15px; margin-top: 15px;">
                                <span>Grand Total</span>
                                <span class="text-cyan"><?= format_rupiah($booking['grand_total']) ?></span>
                            </div>
                        </div>

                        <!-- Dummy QR Code display if Paid/QRIS -->
                        <?php if ($booking['booking_status'] === 'paid'): ?>
                            <div style="text-align: center; margin-top: 20px; padding: 10px; background: rgba(46, 196, 182, 0.1); border: 1px dashed #2ec4b6; border-radius: 6px;">
                                <p style="color: #2ec4b6; font-weight: bold; margin-bottom: 5px;">✓ TRANSAKSI SELESAI</p>
                                <p style="font-size: 0.8rem; color: var(--text-muted);">Pembayaran Lunas. Silakan tunjukkan Invoice ini kepada petugas di outlet.</p>
                            </div>
                        <?php elseif ($booking['booking_status'] === 'cancelled'): ?>
                            <div style="text-align: center; margin-top: 20px; padding: 10px; background: rgba(230, 57, 70, 0.1); border: 1px dashed #e63946; border-radius: 6px;">
                                <p style="color: #e63946; font-weight: bold; margin-bottom: 2px;">✗ PEMESANAN DIBATALKAN</p>
                                <p style="font-size: 0.8rem; color: var(--text-muted);">Reservasi ini dibatalkan oleh admin atau sistem.</p>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; margin-top: 20px; padding: 15px; background: rgba(255, 193, 7, 0.1); border: 1px dashed var(--secondary); border-radius: 6px;">
                                <p style="color: var(--secondary); font-weight: bold; margin-bottom: 5px;">⏳ MENUNGGU PEMBAYARAN</p>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;">Silakan selesaikan pembayaran sesuai metode terpilih untuk mengonfirmasi.</p>
                                <a href="payment.php" class="btn btn-secondary btn-sm" style="display: inline-flex;">Bayar Sekarang</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Invoice Footer / T&C -->
            <div class="invoice-footer">
                <p>Harap hadir di lokasi 10 menit sebelum jam mulai rental Anda.</p>
                <p class="text-muted" style="font-size: 0.8rem; margin-top: 5px;">Terima kasih telah mempercayai <span>Rental PS Booking System</span>.</p>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <span>Rental PS Booking System</span>. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>

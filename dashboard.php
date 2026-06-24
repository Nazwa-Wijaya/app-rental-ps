<?php
/**
 * User Dashboard Page
 */
require_once __DIR__ . '/config/database.php';

// Protect page
check_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$error_msg = '';
$success_msg = '';

if (isset($_SESSION['booking_success'])) {
    $success_msg = $_SESSION['booking_success'];
    unset($_SESSION['booking_success']);
}

try {
    // Retrieve booking history for this user
    // Join rooms, consoles, and games to show names
    $stmt = $db->prepare("
        SELECT b.*, c.name as console_name, r.name as room_name, g.name as game_name 
        FROM bookings b
        JOIN consoles c ON b.console_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN games g ON b.game_id = g.id
        WHERE b.user_id = :user_id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = 'Gagal memuat riwayat booking: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Rental PS Booking System</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                <li class="nav-item active"><a href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a href="logout.php" style="color: #e63946;">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Dashboard Section -->
    <main class="container" style="padding: 40px 20px; flex-grow: 1;">
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <strong>Berhasil!</strong> <?= sanitize($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <strong>Error!</strong> <?= sanitize($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Greeting -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>Halo, <span class="text-cyan"><?= sanitize($user_name) ?></span>!</h2>
                <p class="text-muted">Selamat datang kembali di panel reservasi game room Anda.</p>
            </div>
            <div>
                <a href="booking.php" class="btn btn-primary">🎮 Booking Sekarang</a>
            </div>
        </div>

        <!-- Bookings History Table -->
        <section class="dashboard-section">
            <h3>Daftar Riwayat Booking Anda</h3>
            
            <?php if (empty($bookings)): ?>
                <div class="card text-center" style="padding: 50px 20px;">
                    <span style="font-size: 3.5rem; display: block; margin-bottom: 15px;">🎮</span>
                    <h4 style="font-size: 1.5rem; margin-bottom: 10px;">Belum Ada Riwayat Pemesanan</h4>
                    <p class="text-muted mb-4" style="max-width: 500px; margin: 0 auto 20px auto;">
                        Anda belum pernah membuat pemesanan room rental. Klik tombol di bawah untuk membuat reservasi room game pertama Anda!
                    </p>
                    <a href="booking.php" class="btn btn-secondary btn-sm">Mulai Booking</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Kode Booking</th>
                                <th>Console</th>
                                <th>Room</th>
                                <th>Game Utama</th>
                                <th>Tanggal</th>
                                <th>Waktu (Durasi)</th>
                                <th>Total Harga</th>
                                <th>Status</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><strong class="text-cyan"><?= sanitize($b['booking_code']) ?></strong></td>
                                    <td><?= sanitize($b['console_name']) ?></td>
                                    <td><?= sanitize($b['room_name']) ?></td>
                                    <td><?= sanitize($b['game_name']) ?></td>
                                    <td><?= date('d-m-Y', strtotime($b['booking_date'])) ?></td>
                                    <td><?= date('H:i', strtotime($b['start_time'])) ?> (<?= $b['duration'] ?> jam)</td>
                                    <td class="text-gold" style="font-weight: 600;"><?= format_rupiah($b['grand_total']) ?></td>
                                    <td>
                                        <?php if ($b['booking_status'] === 'paid'): ?>
                                            <span class="badge badge-paid">Paid</span>
                                        <?php elseif ($b['booking_status'] === 'cancelled'): ?>
                                            <span class="badge badge-cancelled">Cancelled</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="booking_detail.php?id=<?= $b['id'] ?>" class="btn btn-outline btn-sm" style="padding: 6px 12px; font-size: 0.85rem;">Lihat Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

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

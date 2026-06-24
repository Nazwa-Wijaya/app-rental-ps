<?php
/**
 * Admin Dashboard Overview
 */
require_once dirname(__DIR__) . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rental PS Booking System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="admin-layout">
        
        <!-- Sidebar Navigation -->
        <?php include_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="admin-main">
            
            <div class="admin-header">
                <div>
                    <h2>Overview Dashboard</h2>
                    <p class="text-muted">Selamat datang, Administrator. Kelola semua data operasional rental PS Anda.</p>
                </div>
                <div>
                    <span class="text-gold" style="font-family: var(--font-heading); font-weight:700; font-size:1.1rem;">
                        📅 Hari ini: <?= date('d-m-Y') ?>
                    </span>
                </div>
            </div>

            <?php
            try {
                // Fetch statistics
                // 1. Console Count
                $c_count = $db->query("SELECT COUNT(*) FROM consoles")->fetchColumn();
                // 2. Room Count
                $r_count = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
                // 3. Game Count
                $g_count = $db->query("SELECT COUNT(*) FROM games")->fetchColumn();
                // 4. Booking Count
                $b_count = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
                // 5. Total Paid Revenue
                $revenue = $db->query("SELECT SUM(grand_total) FROM bookings WHERE booking_status = 'paid'")->fetchColumn() ?: 0;

                // Fetch 5 recent bookings
                $stmt_recent = $db->query("
                    SELECT b.*, u.name as user_name, r.name as room_name, c.name as console_name
                    FROM bookings b
                    JOIN users u ON b.user_id = u.id
                    JOIN rooms r ON b.room_id = r.id
                    JOIN consoles c ON b.console_id = c.id
                    ORDER BY b.created_at DESC
                    LIMIT 5
                ");
                $recent_bookings = $stmt_recent->fetchAll();

            } catch (PDOException $e) {
                die("Query failed: " . $e->getMessage());
            }
            ?>

            <!-- Stats Grid Display -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div class="stat-val"><?= $c_count ?></div>
                        <div class="stat-label">Consoles</div>
                    </div>
                    <div class="stat-icon">🎮</div>
                </div>

                <div class="stat-card">
                    <div>
                        <div class="stat-val"><?= $r_count ?></div>
                        <div class="stat-label">Rooms</div>
                    </div>
                    <div class="stat-icon">🚪</div>
                </div>

                <div class="stat-card">
                    <div>
                        <div class="stat-val"><?= $g_count ?></div>
                        <div class="stat-label">Games</div>
                    </div>
                    <div class="stat-icon">🕹️</div>
                </div>

                <div class="stat-card">
                    <div>
                        <div class="stat-val"><?= $b_count ?></div>
                        <div class="stat-label">Total Booking</div>
                    </div>
                    <div class="stat-icon">📅</div>
                </div>

                <div class="stat-card" style="border-color: var(--secondary);">
                    <div>
                        <div class="stat-val" style="color: var(--secondary); font-size: 1.8rem;"><?= format_rupiah($revenue) ?></div>
                        <div class="stat-label">Pendapatan Lunas</div>
                    </div>
                    <div class="stat-icon">💰</div>
                </div>
            </div>

            <!-- Recent Bookings Table -->
            <section class="card" style="padding: 25px;">
                <div class="d-flex justify-between align-center mb-3">
                    <h3 class="text-cyan" style="margin: 0;">5 Booking Terakhir</h3>
                    <a href="bookings.php" class="btn btn-primary btn-sm">Lihat Semua Booking</a>
                </div>

                <?php if (empty($recent_bookings)): ?>
                    <p class="text-muted text-center" style="padding: 20px 0;">Belum ada data booking masuk.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="custom-table" style="background: transparent;">
                            <thead>
                                <tr>
                                    <th>Kode Booking</th>
                                    <th>Pelanggan</th>
                                    <th>Console & Room</th>
                                    <th>Jadwal Bermain</th>
                                    <th>Total Tagihan</th>
                                    <th>Status</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $b): ?>
                                    <tr>
                                        <td><strong class="text-cyan"><?= sanitize($b['booking_code']) ?></strong></td>
                                        <td><?= sanitize($b['user_name']) ?></td>
                                        <td><?= sanitize($b['console_name']) ?> - <?= sanitize($b['room_name']) ?></td>
                                        <td><?= date('d-m-Y', strtotime($b['booking_date'])) ?> | <?= date('H:i', strtotime($b['start_time'])) ?> (<?= $b['duration'] ?> jam)</td>
                                        <td class="text-gold"><?= format_rupiah($b['grand_total']) ?></td>
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
                                            <a href="../booking_detail.php?id=<?= $b['id'] ?>" target="_blank" class="btn btn-outline btn-sm" style="padding: 6px 12px; font-size: 0.85rem;">Detail</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

        </main>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>

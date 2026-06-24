<?php
/**
 * Admin - Manage Bookings (List & Update Status / Delete)
 */
require_once dirname(__DIR__) . '/config/database.php';

$error = '';
$success = '';

// Handle Status Updates or Deletions
if (isset($_GET['action'])) {
    $action = sanitize($_GET['action']);
    $booking_id = intval($_GET['id'] ?? 0);

    if ($booking_id > 0) {
        try {
            if ($action === 'update_status') {
                $status = sanitize($_GET['status'] ?? '');
                if (in_array($status, ['pending', 'paid', 'cancelled'])) {
                    // Sync payment_status alongside booking_status
                    $pay_status = ($status === 'paid') ? 'paid' : 'pending';
                    
                    $stmt = $db->prepare("
                        UPDATE bookings 
                        SET booking_status = :status, payment_status = :pay_status 
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':status'     => $status,
                        ':pay_status' => $pay_status,
                        ':id'         => $booking_id
                    ]);
                    $success = 'Status booking berhasil diperbarui!';
                }
            } elseif ($action === 'delete') {
                $stmt = $db->prepare("DELETE FROM bookings WHERE id = ?");
                $stmt->execute([$booking_id]);
                $success = 'Data booking berhasil dihapus permanen!';
            }
        } catch (PDOException $e) {
            $error = 'Gagal memproses aksi: ' . $e->getMessage();
        }
    }
}

// Fetch all bookings with user details and rooms
try {
    $bookings = $db->query("
        SELECT b.*, u.name as customer_name, u.phone as customer_phone, 
               r.name as room_name, c.name as console_name, g.name as game_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        JOIN consoles c ON b.console_id = c.id
        JOIN games g ON b.game_id = g.id
        ORDER BY b.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - Rental PS Booking System</title>
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
                    <h2>Daftar Booking Transaksi</h2>
                    <p class="text-muted">Pantau status pembayaran, ubah status pesanan, dan hapus reservasi.</p>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <strong>Berhasil!</strong> <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <strong>Error!</strong> <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- Bookings List Card Table -->
            <div class="card" style="padding: 25px;">
                <h3 class="text-cyan mb-3">Semua Riwayat Reservasi</h3>
                
                <?php if (empty($bookings)): ?>
                    <p class="text-muted text-center" style="padding: 30px 0;">Belum ada pesanan masuk di database.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="custom-table" style="background: transparent;">
                            <thead>
                                <tr>
                                    <th>Kode Booking</th>
                                    <th>Pelanggan / WA</th>
                                    <th>Console / Room / Game</th>
                                    <th>Jadwal Main</th>
                                    <th>Tagihan</th>
                                    <th>Status</th>
                                    <th>Metode</th>
                                    <th style="text-align: center; width: 260px;">Ubah Status & Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-cyan"><?= sanitize($b['booking_code']) ?></strong>
                                        </td>
                                        <td>
                                            <strong><?= sanitize($b['customer_name']) ?></strong><br>
                                            <span style="font-size: 0.8rem;" class="text-muted">WA: <?= sanitize($b['customer_phone']) ?></span>
                                        </td>
                                        <td>
                                            <?= sanitize($b['console_name']) ?> / <?= sanitize($b['room_name']) ?><br>
                                            <span style="font-size: 0.8rem;" class="text-muted">Game: <?= sanitize($b['game_name']) ?></span>
                                        </td>
                                        <td>
                                            <?= date('d-m-Y', strtotime($b['booking_date'])) ?><br>
                                            <span style="font-size: 0.8rem;" class="text-muted">Jam: <?= date('H:i', strtotime($b['start_time'])) ?> (<?= $b['duration'] ?> jam)</span>
                                        </td>
                                        <td class="text-gold" style="font-weight:600;">
                                            <?= format_rupiah($b['grand_total']) ?>
                                        </td>
                                        <td>
                                            <?php if ($b['booking_status'] === 'paid'): ?>
                                                <span class="badge badge-paid">Paid</span>
                                            <?php elseif ($b['booking_status'] === 'cancelled'): ?>
                                                <span class="badge badge-cancelled">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge badge-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.9rem;" class="text-muted"><?= sanitize($b['payment_method'] ?? 'N/A') ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; gap: 4px; justify-content: center; margin-bottom: 6px;">
                                                <?php if ($b['booking_status'] !== 'paid'): ?>
                                                    <a href="bookings.php?action=update_status&id=<?= $b['id'] ?>&status=paid" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">Paid</a>
                                                <?php endif; ?>
                                                
                                                <?php if ($b['booking_status'] !== 'pending'): ?>
                                                    <a href="bookings.php?action=update_status&id=<?= $b['id'] ?>&status=pending" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 0.75rem; border-color: var(--secondary); color: var(--secondary);">Pend</a>
                                                <?php endif; ?>
                                                
                                                <?php if ($b['booking_status'] !== 'cancelled'): ?>
                                                    <a href="bookings.php?action=update_status&id=<?= $b['id'] ?>&status=cancelled" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 0.75rem; border-color:#e63946; color:#e63946;">Cancel</a>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div style="display: flex; gap: 4px; justify-content: center;">
                                                <a href="../booking_detail.php?id=<?= $b['id'] ?>" target="_blank" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">Detail</a>
                                                <a href="bookings.php?action=delete&id=<?= $b['id'] ?>" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.75rem;" onclick="return confirm('Apakah Anda yakin ingin menghapus data booking ini secara permanen?');">Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>

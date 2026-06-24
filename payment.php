<?php
/**
 * Checkout & Payment Simulation Page (Step 5)
 */
require_once __DIR__ . '/config/database.php';

// Ensure user is logged in
check_login();

// Guard against empty booking wizard session
if (
    !isset($_SESSION['booking_data']) || 
    empty($_SESSION['booking_data']['console_id']) || 
    empty($_SESSION['booking_data']['room_id']) || 
    empty($_SESSION['booking_data']['game_id']) || 
    empty($_SESSION['booking_data']['booking_date']) || 
    empty($_SESSION['booking_data']['start_time'])
) {
    header("Location: booking.php");
    exit;
}

$b_data = $_SESSION['booking_data'];
$user_id = $_SESSION['user_id'];
$error = '';

try {
    // Fetch selections details from DB
    $stmt_c = $db->prepare("SELECT name FROM consoles WHERE id = ?");
    $stmt_c->execute([$b_data['console_id']]);
    $console = $stmt_c->fetch();

    $stmt_r = $db->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt_r->execute([$b_data['room_id']]);
    $room = $stmt_r->fetch();

    $stmt_g = $db->prepare("SELECT name FROM games WHERE id = ?");
    $stmt_g->execute([$b_data['game_id']]);
    $game = $stmt_g->fetch();

    if (!$console || !$room || !$game) {
        throw new Exception("Master data selection invalid. Please restart booking.");
    }

    // Get food item names and prices for receipt summary
    $food_items = [];
    if (!empty($b_data['foods'])) {
        $food_ids = array_keys($b_data['foods']);
        $placeholders = implode(',', array_fill(0, count($food_ids), '?'));
        $stmt_f = $db->prepare("SELECT id, name, price FROM foods WHERE id IN ($placeholders)");
        $stmt_f->execute(array_values($food_ids));
        $db_foods = $stmt_f->fetchAll();
        
        foreach ($db_foods as $df) {
            $qty = intval($b_data['foods'][$df['id']]);
            if ($qty > 0) {
                $food_items[] = [
                    'id' => $df['id'],
                    'name' => $df['name'],
                    'qty' => $qty,
                    'price' => floatval($df['price']),
                    'subtotal' => floatval($df['price']) * $qty
                ];
            }
        }
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Handle Form Submission / Payment Simulation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cust_name = trim(sanitize($_POST['customer_name']));
    $cust_phone = trim(sanitize($_POST['customer_phone']));
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    $notes = trim(sanitize($_POST['notes']));
    $terms_check = isset($_POST['terms_check']);

    // Validations
    if (empty($cust_name) || empty($cust_phone)) {
        $error = 'Harap isi nama lengkap dan nomor WhatsApp Anda.';
    } elseif (empty($payment_method)) {
        $error = 'Harap pilih salah satu metode pembayaran.';
    } elseif (!$terms_check) {
        $error = 'Anda harus menyetujui syarat dan ketentuan untuk melanjutkan.';
    } else {
        try {
            $db->beginTransaction();

            // 1. Double check availability on server-side to prevent double booking
            $start_timestamp = strtotime($b_data['start_time']);
            $end_timestamp = $start_timestamp + (intval($b_data['duration']) * 3600);
            $start_time_str = date('H:i:s', $start_timestamp);
            $end_time_str = date('H:i:s', $end_timestamp);

            $stmt_check = $db->prepare("
                SELECT COUNT(*) as count 
                FROM bookings 
                WHERE booking_date = :booking_date 
                  AND booking_status != 'cancelled'
                  AND (room_id = :room_id OR console_id = :console_id)
                  AND (start_time < :end_time AND end_time > :start_time)
            ");
            $stmt_check->execute([
                ':booking_date' => $b_data['booking_date'],
                ':room_id'      => $b_data['room_id'],
                ':console_id'   => $b_data['console_id'],
                ':end_time'     => $end_time_str,
                ':start_time'   => $start_time_str
            ]);
            $conflict = $stmt_check->fetch();

            if (intval($conflict['count']) > 0) {
                $error = 'Jadwal yang Anda pilih barusan dibooking oleh user lain. Harap kembali dan ganti jadwal/room Anda.';
                $db->rollBack();
            } else {
                // 2. Generate incremental booking code: BOOK-YYYYMMDD-XXX
                $booking_date_db = $b_data['booking_date'];
                $stmt_code = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE booking_date = :b_date");
                $stmt_code->execute([':b_date' => $booking_date_db]);
                $code_count = $stmt_code->fetch();
                $inc = intval($code_count['count']) + 1;
                $booking_code = 'BOOK-' . date('Ymd', strtotime($booking_date_db)) . '-' . sprintf("%03d", $inc);

                // 3. Insert into Bookings
                // Set payment_status to 'paid' and booking_status to 'paid' upon successful simulation submit
                $insert_booking = $db->prepare("
                    INSERT INTO bookings (
                        booking_code, user_id, console_id, room_id, game_id, people_count,
                        booking_date, start_time, end_time, duration, room_total, food_total,
                        discount, service_fee, grand_total, notes, payment_method, payment_status, booking_status
                    ) VALUES (
                        :booking_code, :user_id, :console_id, :room_id, :game_id, :people_count,
                        :booking_date, :start_time, :end_time, :duration, :room_total, :food_total,
                        :discount, :service_fee, :grand_total, :notes, :payment_method, 'paid', 'paid'
                    )
                ");

                $insert_booking->execute([
                    ':booking_code'   => $booking_code,
                    ':user_id'        => $user_id,
                    ':console_id'     => $b_data['console_id'],
                    ':room_id'        => $b_data['room_id'],
                    ':game_id'        => $b_data['game_id'],
                    ':people_count'   => $b_data['people_count'],
                    ':booking_date'   => $booking_date_db,
                    ':start_time'     => $start_time_str,
                    ':end_time'       => $end_time_str,
                    ':duration'       => $b_data['duration'],
                    ':room_total'     => $b_data['room_total'],
                    ':food_total'     => $b_data['food_total'],
                    ':discount'       => $b_data['discount'],
                    ':service_fee'    => $b_data['service_fee'],
                    ':grand_total'    => $b_data['grand_total'],
                    ':notes'          => $notes,
                    ':payment_method' => $payment_method
                ]);

                $new_booking_id = $db->lastInsertId();

                // 4. Insert ordered food items into booking_foods
                if (!empty($food_items)) {
                    $insert_bf = $db->prepare("
                        INSERT INTO booking_foods (booking_id, food_id, quantity, price, subtotal)
                        VALUES (:booking_id, :food_id, :qty, :price, :subtotal)
                    ");
                    foreach ($food_items as $item) {
                        $insert_bf->execute([
                            ':booking_id' => $new_booking_id,
                            ':food_id'    => $item['id'],
                            ':qty'        => $item['qty'],
                            ':price'      => $item['price'],
                            ':subtotal'   => $item['subtotal']
                        ]);
                    }
                }

                $db->commit();

                // 5. Clean up booking session
                unset($_SESSION['booking_data']);

                $_SESSION['booking_success'] = "Booking berhasil dibayar! Kode Booking Anda adalah $booking_code.";
                header("Location: dashboard.php");
                exit;
            }

        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Terjadi kegagalan sistem saat menyimpan pemesanan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Pembayaran - Rental PS Booking System</title>
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

    <!-- Main Payment Summary Area -->
    <main class="container" style="padding: 40px 20px; flex-grow: 1;">
        
        <div style="margin-bottom: 20px;">
            <a href="booking.php" class="btn btn-outline btn-sm">← Kembali ke Pilihan Makanan</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error!</strong> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="payment.php" method="POST" autocomplete="off">
            <input type="hidden" id="payment_method" name="payment_method" value="">

            <div class="payment-grid">
                
                <!-- Left Column: Personal info & Payment Gateways -->
                <div>
                    <div class="card mb-4">
                        <h3 class="text-cyan mb-3">Informasi Pelanggan</h3>
                        
                        <!-- Autofill Checkbox -->
                        <div class="form-group">
                            <label class="checkbox-group">
                                <input type="checkbox" id="autofill_account">
                                <span>Gunakan informasi akun saya (Autofill)</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="customer_name" class="form-label">Nama Lengkap</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" placeholder="Nama lengkap Anda" required>
                        </div>

                        <div class="form-group">
                            <label for="customer_phone" class="form-label">Nomor WhatsApp</label>
                            <input type="text" id="customer_phone" name="customer_phone" class="form-control" placeholder="081234567890" required>
                        </div>

                        <div class="form-group">
                            <label for="notes" class="form-label">Catatan Tambahan (Opsional)</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Tulis catatan jika ada kebutuhan khusus..."></textarea>
                        </div>
                    </div>

                    <!-- Payment Gateway Dummy Simulator Selection -->
                    <div class="card mb-4">
                        <h3 class="text-cyan mb-3">Metode Pembayaran</h3>
                        <p class="text-muted mb-3">Pilih salah satu metode pembayaran non-tunai di bawah untuk verifikasi instan.</p>
                        
                        <div class="payment-method-selector">
                            <div class="method-card" data-method="QRIS">
                                <div class="method-icon">📱</div>
                                <div class="method-name">QRIS</div>
                            </div>
                            <div class="method-card" data-method="Transfer Bank">
                                <div class="method-icon">🏦</div>
                                <div class="method-name">Transfer Bank</div>
                            </div>
                            <div class="method-card" data-method="E-Wallet">
                                <div class="method-icon">💳</div>
                                <div class="method-name">E-Wallet</div>
                            </div>
                        </div>

                        <!-- Dummy payment details (loaded visually depending on selection) -->
                        <div id="payment-qris-details" class="payment-details-box" style="display: none;">
                            <h4 class="text-gold">Scan QRIS Untuk Bayar</h4>
                            <p class="text-muted" style="font-size:0.85rem; margin-top:5px;">Mendukung GoPay, OVO, Dana, LinkAja, & Mobile Banking.</p>
                            <div class="qr-code-img">
                                <!-- Draw a simulated QR Code using SVG -->
                                <svg viewBox="0 0 100 100" width="150" height="150">
                                    <rect width="100" height="100" fill="#fff"/>
                                    <!-- Outlines borders of QR Code -->
                                    <rect x="5" y="5" width="25" height="25" fill="#000"/>
                                    <rect x="10" y="10" width="15" height="15" fill="#fff"/>
                                    <rect x="13" y="13" width="9" height="9" fill="#000"/>
                                    
                                    <rect x="70" y="5" width="25" height="25" fill="#000"/>
                                    <rect x="75" y="10" width="15" height="15" fill="#fff"/>
                                    <rect x="78" y="13" width="9" height="9" fill="#000"/>
                                    
                                    <rect x="5" y="70" width="25" height="25" fill="#000"/>
                                    <rect x="10" y="75" width="15" height="15" fill="#fff"/>
                                    <rect x="13" y="78" width="9" height="9" fill="#000"/>
                                    <!-- Scattered blocks representing data bytes -->
                                    <rect x="40" y="15" width="15" height="10" fill="#000"/>
                                    <rect x="50" y="40" width="25" height="15" fill="#000"/>
                                    <rect x="40" y="70" width="20" height="20" fill="#000"/>
                                    <rect x="75" y="45" width="10" height="20" fill="#000"/>
                                    <rect x="80" y="80" width="10" height="10" fill="#000"/>
                                </svg>
                            </div>
                            <p class="text-cyan">Pindai QR di atas menggunakan aplikasi e-wallet Anda.</p>
                        </div>

                        <div id="payment-tf-details" class="payment-details-box" style="display: none;">
                            <h4 class="text-gold">Transfer Bank Manual</h4>
                            <p class="text-muted" style="margin-top: 8px;">Silakan transfer nominal pas ke rekening resmi kami berikut:</p>
                            <p style="font-size: 1.2rem; margin: 10px 0; color: #fff;">
                                <strong>Bank Mandiri: 123-45678-901</strong><br>
                                <span style="font-size: 0.95rem;" class="text-muted">a/n PT Rental PS Indonesia</span>
                            </p>
                            <p class="text-cyan" style="font-size:0.85rem;">Transaksi diverifikasi otomatis setelah transfer berhasil.</p>
                        </div>

                        <div id="payment-ewallet-details" class="payment-details-box" style="display: none;">
                            <h4 class="text-gold">Nomor E-Wallet Kami</h4>
                            <p class="text-muted" style="margin-top: 8px;">Kirim saldo sesuai total ke nomor e-wallet Gopay/OVO/Dana berikut:</p>
                            <p style="font-size: 1.2rem; margin: 10px 0; color: #fff;">
                                <strong>Gopay / OVO / Dana: 0898-7654-321</strong><br>
                                <span style="font-size: 0.95rem;" class="text-muted">a/n Admin Rental PS</span>
                            </p>
                        </div>
                    </div>

                    <!-- Terms checkbox & Submit Button -->
                    <div class="card">
                        <div class="form-group">
                            <label class="checkbox-group">
                                <input type="checkbox" id="terms_check" name="terms_check" required>
                                <span style="font-size: 0.9rem;">
                                    Saya menyetujui <span class="text-cyan" style="cursor:pointer; text-decoration: underline;">Syarat dan Ketentuan</span> yang berlaku. Saya akan hadir 10 menit sebelum jam mulai rental, dan bertanggung jawab menjaga kelayakan fasilitas console & room.
                                </span>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-lg" style="width: 100%; margin-top: 15px;">Bayar Sekarang (Simulasi)</button>
                    </div>
                </div>

                <!-- Right Column: Final Order Summary Receipt -->
                <div class="booking-sidebar">
                    <div class="card" style="border-color: var(--primary); background: rgba(20, 12, 40, 0.85);">
                        <h3 class="summary-title text-purple" style="border-color: var(--primary);">Checkout Summary</h3>
                        
                        <table style="width: 100%; border-spacing: 0 10px; border-collapse: separate; font-size: 0.95rem;">
                            <tr>
                                <td class="text-muted">Console</td>
                                <td style="text-align: right; font-weight: 600;"><?= sanitize($console['name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Room Type</td>
                                <td style="text-align: right; font-weight: 600;"><?= sanitize($room['name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Game Utama</td>
                                <td style="text-align: right; font-weight: 600; max-width: 150px;"><?= sanitize($game['name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Pemain</td>
                                <td style="text-align: right; font-weight: 600;"><?= $b_data['people_count'] ?> Orang</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tanggal</td>
                                <td style="text-align: right; font-weight: 600;"><?= date('d-m-Y', strtotime($b_data['booking_date'])) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jam Mulai</td>
                                <td style="text-align: right; font-weight: 600;"><?= date('H:i', strtotime($b_data['start_time'])) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Durasi Sewa</td>
                                <td style="text-align: right; font-weight: 600;"><?= $b_data['duration'] ?> Jam</td>
                            </tr>
                        </table>

                        <div style="margin-top: 15px; border-top: 1px dashed rgba(157,78,221,0.2); padding-top: 15px;">
                            <h4 class="text-cyan mb-2" style="font-size:1.05rem;">Rincian Transaksi</h4>
                            
                            <ul class="summary-list">
                                <li class="summary-row">
                                    <span class="text-muted">Total Room (<?= $b_data['duration'] ?> jam x <?= format_rupiah($room['price_per_hour']) ?>)</span>
                                    <span><?= format_rupiah($b_data['room_total']) ?></span>
                                </li>
                                
                                <li class="summary-row">
                                    <span class="text-muted">Total Makanan & Minuman</span>
                                    <span><?= format_rupiah($b_data['food_total']) ?></span>
                                </li>

                                <!-- Food items ordered breakdown list -->
                                <?php if (!empty($food_items)): ?>
                                    <ul class="summary-food-list">
                                        <?php foreach ($food_items as $f): ?>
                                            <li>• <?= sanitize($f['name']) ?> x<?= $f['qty'] ?> (<?= format_rupiah($f['subtotal']) ?>)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if (floatval($b_data['discount']) > 0): ?>
                                    <li class="summary-row" style="color: #2ec4b6;">
                                        <span>Potongan Diskon (10%):</span>
                                        <span>-<?= format_rupiah($b_data['discount']) ?></span>
                                    </li>
                                <?php endif; ?>

                                <li class="summary-row">
                                    <span class="text-muted">Service Fee:</span>
                                    <span><?= format_rupiah($b_data['service_fee']) ?></span>
                                </li>

                                <li class="summary-row total-row" style="color: var(--cyan); border-color: var(--primary);">
                                    <span>Grand Total:</span>
                                    <span><?= format_rupiah($b_data['grand_total']) ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <span>Rental PS Booking System</span>. All rights reserved.</p>
        </div>
    </footer>

    <!-- JS Autofill bindings -->
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const autofillCheck = document.getElementById('autofill_account');
            const inputName = document.getElementById('customer_name');
            const inputPhone = document.getElementById('customer_phone');

            // Values from PHP Session
            const sessionName = <?= json_encode($b_data['customer_name'] ?? $_SESSION['user_name']) ?>;
            const sessionPhone = <?= json_encode($b_data['customer_phone'] ?? $_SESSION['user_phone']) ?>;

            if (autofillCheck) {
                autofillCheck.addEventListener('change', () => {
                    if (autofillCheck.checked) {
                        inputName.value = sessionName;
                        inputPhone.value = sessionPhone;
                    } else {
                        inputName.value = '';
                        inputPhone.value = '';
                    }
                });
            }
        });
    </script>
</body>
</html>

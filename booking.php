<?php
/**
 * Multi-Step Booking Page (Step 1-4)
 */
require_once __DIR__ . '/config/database.php';

// Check login
check_login();

// Reset booking session if requested
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    unset($_SESSION['booking_data']);
}

// Initialize session structure if not present
if (!isset($_SESSION['booking_data'])) {
    $_SESSION['booking_data'] = [
        'console_id'   => 0,
        'room_id'      => 0,
        'game_id'      => 0,
        'people_count' => 1,
        'booking_date' => '',
        'start_time'   => '',
        'duration'     => 1,
        'foods'        => [],
        'promo_code'   => '',
        'room_total'   => 0.00,
        'food_total'   => 0.00,
        'discount'     => 0.00,
        'service_fee'  => 0.00,
        'grand_total'  => 0.00
    ];
}

$b_data = $_SESSION['booking_data'];

try {
    // 1. Fetch consoles
    $consoles_stmt = $db->query("SELECT * FROM consoles ORDER BY id ASC");
    $consoles = $consoles_stmt->fetchAll();

    // 2. Fetch rooms
    $rooms_stmt = $db->query("SELECT * FROM rooms ORDER BY id ASC");
    $rooms = $rooms_stmt->fetchAll();

    // 3. Fetch foods
    $foods_stmt = $db->query("SELECT * FROM foods ORDER BY category, name ASC");
    $foods = $foods_stmt->fetchAll();

    // 4. Load initial games if console already chosen in session
    $initial_games = [];
    if ($b_data['console_id'] > 0) {
        $games_stmt = $db->prepare("SELECT id, name FROM games WHERE console_id = :console_id ORDER BY name ASC");
        $games_stmt->execute([':console_id' => $b_data['console_id']]);
        $initial_games = $games_stmt->fetchAll();
    }

    // Load active names for sidebar summary if set in session
    $selected_console_name = '-';
    $selected_room_name = '-';
    $selected_game_name = '-';

    if ($b_data['console_id'] > 0) {
        foreach ($consoles as $c) {
            if ($c['id'] == $b_data['console_id']) {
                $selected_console_name = $c['name'];
                break;
            }
        }
    }
    if ($b_data['room_id'] > 0) {
        foreach ($rooms as $r) {
            if ($r['id'] == $b_data['room_id']) {
                $selected_room_name = $r['name'] . ' (' . format_rupiah($r['price_per_hour']) . '/jam)';
                break;
            }
        }
    }
    if ($b_data['game_id'] > 0 && !empty($initial_games)) {
        foreach ($initial_games as $g) {
            if ($g['id'] == $b_data['game_id']) {
                $selected_game_name = $g['name'];
                break;
            }
        }
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Room - Rental PS Booking System</title>
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

    <!-- Main Booking Step Section -->
    <main class="container" style="padding: 40px 20px; flex-grow: 1;">
        
        <!-- Multi-Step Progress Indicators -->
        <div class="booking-progress-container">
            <ul class="progress-steps">
                <li class="step-item active" data-step="1">
                    <div class="step-node">1</div>
                    <div class="step-label">Consoles</div>
                </li>
                <li class="step-item" data-step="2">
                    <div class="step-node">2</div>
                    <div class="step-label">Room & Game</div>
                </li>
                <li class="step-item" data-step="3">
                    <div class="step-node">3</div>
                    <div class="step-label">Date & Time</div>
                </li>
                <li class="step-item" data-step="4">
                    <div class="step-node">4</div>
                    <div class="step-label">Food & Drinks</div>
                </li>
            </ul>
        </div>

        <form id="booking-wizard-form" autocomplete="off" onsubmit="return false;">
            
            <input type="hidden" id="selected_console_id" name="console_id" value="<?= $b_data['console_id'] ?: '' ?>">
            <input type="hidden" id="selected_room_id" name="room_id" value="<?= $b_data['room_id'] ?: '' ?>">

            <div class="booking-grid">
                
                <!-- Left Side Panel Contents -->
                <div class="booking-panel-container">
                    
                    <!-- STEP 1: CONSOLE SELECTION -->
                    <div id="step-panel-1" class="booking-panel card" style="display: block;">
                        <h3 class="text-cyan mb-3">Step 1: Pilih Console Game</h3>
                        <p class="text-muted mb-4">Pilih console favorit yang ingin Anda mainkan hari ini. Game pilihan akan disesuaikan dengan console ini.</p>
                        
                        <div class="selection-grid">
                            <?php foreach ($consoles as $c): ?>
                                <?php $is_sel = ($c['id'] == $b_data['console_id']) ? 'selected' : ''; ?>
                                <div class="card select-card console-card <?= $is_sel ?>" 
                                     data-console-id="<?= $c['id'] ?>" 
                                     data-console-name="<?= sanitize($c['name']) ?>">
                                    <div class="card-img-wrapper">
                                        <?php if ($c['id'] == 1): ?>
                                            <img src="https://images.unsplash.com/photo-1578301978693-85fa9c0320b9?q=80&w=350&auto=format&fit=crop" alt="Nintendo Switch" class="card-img">
                                        <?php elseif ($c['id'] == 2): ?>
                                            <img src="https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?q=80&w=350&auto=format&fit=crop" alt="PS4" class="card-img">
                                        <?php else: ?>
                                            <img src="https://images.unsplash.com/photo-1606813907291-d86efa9b94db?q=80&w=350&auto=format&fit=crop" alt="PS5" class="card-img">
                                        <?php endif; ?>
                                    </div>
                                    <h4 style="margin-bottom: 8px;"><?= sanitize($c['name']) ?></h4>
                                    <p class="card-desc"><?= sanitize($c['description']) ?></p>
                                    <button type="button" class="btn btn-outline btn-sm" style="width:100%; pointer-events: none;">Pilih</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- STEP 2: ROOM & GAME SELECTION -->
                    <div id="step-panel-2" class="booking-panel card" style="display: none;">
                        <h3 class="text-cyan mb-3">Step 2: Pilih Tipe Ruangan & Game</h3>
                        <p class="text-muted mb-4">Pilih tipe room sesuai kapasitas rombongan Anda, masukkan jumlah pemain, dan pilih game utama.</p>
                        
                        <div class="selection-grid mb-4">
                            <?php foreach ($rooms as $r): ?>
                                <?php $is_sel = ($r['id'] == $b_data['room_id']) ? 'selected' : ''; ?>
                                <div class="card select-card room-card <?= $is_sel ?>" 
                                     data-room-id="<?= $r['id'] ?>" 
                                     data-room-name="<?= sanitize($r['name']) ?>"
                                     data-room-price="<?= $r['price_per_hour'] ?>"
                                     data-max-people="<?= $r['max_people'] ?>">
                                    <div class="card-img-wrapper">
                                        <?php if ($r['id'] == 1): ?>
                                            <img src="https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=350&auto=format&fit=crop" alt="Reguler" class="card-img">
                                        <?php elseif ($r['id'] == 2): ?>
                                            <img src="https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=350&auto=format&fit=crop" alt="VIP" class="card-img">
                                        <?php else: ?>
                                            <img src="https://images.unsplash.com/photo-1600861195091-690c92f1d2cc?q=80&w=350&auto=format&fit=crop" alt="VIP Luxury" class="card-img">
                                        <?php endif; ?>
                                    </div>
                                    <h4 style="margin-bottom: 5px;"><?= sanitize($r['name']) ?></h4>
                                    <p class="card-desc"><?= sanitize($r['description']) ?></p>
                                    <p class="text-muted mb-3" style="font-size:0.9rem;">Maksimal: <strong><?= $r['max_people'] ?> orang</strong></p>
                                    <div class="card-action">
                                        <span class="card-price text-cyan"><?= format_rupiah($r['price_per_hour']) ?><span style="font-size:0.85rem; font-weight:normal;" class="text-muted">/jam</span></span>
                                        <button type="button" class="btn btn-outline btn-sm" style="pointer-events: none;">Pilih</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="detail-grid">
                            <div class="form-group">
                                <label for="people_count" class="form-label">Jumlah Pemain</label>
                                <input type="number" id="people_count" name="people_count" class="form-control" 
                                       min="1" max="4" value="<?= $b_data['people_count'] ?: 1 ?>" required>
                                <small class="text-muted">Minimal 1 pemain. Maksimal kapasitas disesuaikan dengan tipe room.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="game_id" class="form-label">Pilih Game Utama</label>
                                <select id="game_id" name="game_id" class="form-control" required>
                                    <option value="">-- Pilih Game Utama --</option>
                                    <?php foreach ($initial_games as $ig): ?>
                                        <option value="<?= $ig['id'] ?>" <?= ($ig['id'] == $b_data['game_id']) ? 'selected' : '' ?>>
                                            <?= sanitize($ig['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Game utama wajib dipilih.</small>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: DATE & TIME SELECTION -->
                    <div id="step-panel-3" class="booking-panel card" style="display: none;">
                        <h3 class="text-cyan mb-3">Step 3: Tanggal & Waktu Bermain</h3>
                        <p class="text-muted mb-4">Tentukan jadwal bermain Anda. Jam operasional rental kami adalah pukul 10:00 s/d 23:00.</p>
                        
                        <div class="datetime-container">
                            <div class="form-group">
                                <label for="booking_date" class="form-label">Tanggal Booking</label>
                                <input type="date" id="booking_date" name="booking_date" class="form-control" 
                                       value="<?= $b_data['booking_date'] ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="start_time" class="form-label">Jam Mulai</label>
                                <input type="time" id="start_time" name="start_time" class="form-control" 
                                       value="<?= $b_data['start_time'] ?>" required>
                                <small class="text-muted">Jam operasional: 10:00 - 23:00 WIB.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="duration" class="form-label">Durasi Main (Jam)</label>
                            <input type="number" id="duration" name="duration" class="form-control" 
                                   min="1" max="12" value="<?= $b_data['duration'] ?: 1 ?>" required>
                            <small class="text-muted">Sewa minimal 1 jam, maksimal 12 jam.</small>
                        </div>

                        <!-- Real-time availability conflict checker status box -->
                        <div id="availability-status" style="margin-top: 25px; min-height: 40px;">
                            <!-- AJAX status loads here -->
                        </div>
                    </div>

                    <!-- STEP 4: FOODS & DRINKS OPTIONAL SELECTION -->
                    <div id="step-panel-4" class="booking-panel card" style="display: none;">
                        <h3 class="text-cyan mb-3">Step 4: Pesan Makanan & Minuman (Opsional)</h3>
                        <p class="text-muted mb-4">Tambahkan snack dan minuman segar untuk menemani bermain Anda. Langkah ini opsional, Anda bisa langsung melompati ke pembayaran.</p>
                        
                        <div class="food-tabs">
                            <button type="button" class="food-tab-btn active" data-category="all">Semua Menu</button>
                            <button type="button" class="food-tab-btn" data-category="Basic Drinks">Basic Drinks</button>
                            <button type="button" class="food-tab-btn" data-category="Coffee">Coffee</button>
                            <button type="button" class="food-tab-btn" data-category="Tea">Tea</button>
                            <button type="button" class="food-tab-btn" data-category="Snacks">Snacks</button>
                            <button type="button" class="food-tab-btn" data-category="Instant Noodles">Instant Noodles</button>
                            <button type="button" class="food-tab-btn" data-category="Others">Others</button>
                        </div>

                        <div style="max-height: 400px; overflow-y: auto; border: 1px solid rgba(157, 78, 221, 0.2); border-radius: 6px;">
                            <?php foreach ($foods as $food): ?>
                                <?php 
                                    $qty = 0;
                                    if (isset($b_data['foods'][$food['id']])) {
                                        $qty = intval($b_data['foods'][$food['id']]);
                                    }
                                ?>
                                <div class="food-item" data-category="<?= sanitize($food['category']) ?>" data-food-id="<?= $food['id'] ?>">
                                    <div class="food-img-wrapper">
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
                                        <div class="food-price"><?= format_rupiah($food['price']) ?></div>
                                    </div>
                                    <div class="food-qty-selector">
                                        <button type="button" class="qty-btn qty-minus">-</button>
                                        <span class="qty-val"><?= $qty ?></span>
                                        <button type="button" class="qty-btn qty-add">+</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Steps Back & Next Navigation -->
                    <div style="margin-top: 25px; display: flex; gap: 15px;">
                        <button type="button" id="btn-prev" class="btn btn-outline" style="display: none;">Sebelumnya</button>
                        <button type="button" id="btn-next" class="btn btn-primary" data-conflict="false" style="flex-grow: 1;">Next Step</button>
                    </div>

                </div>

                <!-- Right Side Real-time Summary Card -->
                <div class="booking-sidebar">
                    <div class="card summary-card" style="border-color: var(--primary);">
                        <h3 class="summary-title text-cyan">Booking Summary</h3>
                        
                        <ul class="summary-list">
                            <li class="summary-row">
                                <span class="text-muted">Console:</span>
                                <span id="summary-console" style="font-weight:600;"><?= sanitize($selected_console_name) ?></span>
                            </li>
                            <li class="summary-row">
                                <span class="text-muted">Room:</span>
                                <span id="summary-room" style="font-weight:600;"><?= sanitize($selected_room_name) ?></span>
                            </li>
                            <li class="summary-row">
                                <span class="text-muted">Game Utama:</span>
                                <span id="summary-game" style="font-weight:600; text-align: right; max-width: 180px;"><?= sanitize($selected_game_name) ?></span>
                            </li>
                            <li class="summary-row">
                                <span class="text-muted">Tanggal Main:</span>
                                <span id="summary-date" style="font-weight:600;"><?= $b_data['booking_date'] ?: '-' ?></span>
                            </li>
                            <li class="summary-row">
                                <span class="text-muted">Jam & Durasi:</span>
                                <span id="summary-time" style="font-weight:600;"><?= $b_data['start_time'] ? ($b_data['start_time'] . ' (' . $b_data['duration'] . ' Jam)') : '-' ?></span>
                            </li>
                        </ul>

                        <div style="margin-top: 15px; border-top: 1px dashed rgba(157,78,221,0.2); padding-top: 15px;">
                            <h4 class="text-purple mb-3" style="font-size: 1.1rem;">Rincian Harga</h4>
                            
                            <!-- Promo Input box -->
                            <div class="promo-container">
                                <input type="text" id="promo_code" class="form-control form-control-sm" placeholder="Kode Promo" value="<?= sanitize($b_data['promo_code']) ?>">
                                <button type="button" id="btn-apply-promo" class="btn btn-secondary btn-sm" style="padding: 10px 15px;">Apply</button>
                            </div>
                            <small class="text-muted" style="display:block; margin-bottom:15px;">Gunakan kode <strong>OPENINGYUK</strong> untuk diskon 10%.</small>

                            <ul class="summary-list">
                                <li class="summary-row">
                                    <span class="text-muted">Biaya Room:</span>
                                    <span id="summary-room-total"><?= format_rupiah($b_data['room_total']) ?></span>
                                </li>
                                <li class="summary-row">
                                    <span class="text-muted">Makanan & Minuman:</span>
                                    <span id="summary-food-total"><?= format_rupiah($b_data['food_total']) ?></span>
                                </li>
                                <ul id="summary-food-list" class="summary-food-list">
                                    <!-- AJAX will render selected food items here -->
                                </ul>
                                <li class="summary-row" style="color: #2ec4b6;">
                                    <span>Diskon:</span>
                                    <span id="summary-discount">-<?= format_rupiah($b_data['discount']) ?></span>
                                </li>
                                <li class="summary-row">
                                    <span class="text-muted">Service Fee:</span>
                                    <span><?= format_rupiah($b_data['service_fee']) ?></span>
                                </li>
                                <li class="summary-row total-row">
                                    <span>Grand Total:</span>
                                    <span id="summary-grand-total"><?= format_rupiah($b_data['grand_total']) ?></span>
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

    <!-- JS Scripts -->
    <script src="assets/js/script.js"></script>
</body>
</html>

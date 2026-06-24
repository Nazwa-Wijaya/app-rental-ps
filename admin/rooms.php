<?php
/**
 * Admin - Manage Rooms (CRUD)
 */
require_once dirname(__DIR__) . '/config/database.php';

$error = '';
$success = '';

// Handle Delete Action
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success = 'Room berhasil dihapus!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus room: ' . $e->getMessage();
    }
}

// Handle Add / Edit Submission
$edit_mode = false;
$edit_id = 0;
$edit_name = '';
$edit_max_people = 2;
$edit_price_per_hour = 25000;
$edit_description = '';
$edit_image = 'assets/img/room-regular.jpg';

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    
    $stmt_fetch = $db->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt_fetch->execute([$edit_id]);
    $r_edit = $stmt_fetch->fetch();
    if ($r_edit) {
        $edit_name = $r_edit['name'];
        $edit_max_people = intval($r_edit['max_people']);
        $edit_price_per_hour = floatval($r_edit['price_per_hour']);
        $edit_description = $r_edit['description'];
        $edit_image = $r_edit['image'];
    } else {
        $edit_mode = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(sanitize($_POST['name']));
    $max_people = intval($_POST['max_people']);
    $price_per_hour = floatval($_POST['price_per_hour']);
    $description = trim(sanitize($_POST['description']));
    $image = trim(sanitize($_POST['image']));
    
    if (empty($name) || $max_people <= 0 || $price_per_hour <= 0) {
        $error = 'Harap isi semua kolom dengan benar. Kapasitas dan harga harus bernilai positif.';
    } else {
        try {
            if (isset($_POST['action_edit'])) {
                // Update
                $r_id = intval($_POST['room_id']);
                $stmt_up = $db->prepare("
                    UPDATE rooms 
                    SET name = :name, max_people = :max_people, price_per_hour = :price_per_hour, 
                        description = :description, image = :image 
                    WHERE id = :id
                ");
                $stmt_up->execute([
                    ':name'           => $name,
                    ':max_people'     => $max_people,
                    ':price_per_hour' => $price_per_hour,
                    ':description'    => $description,
                    ':image'          => $image,
                    ':id'             => $r_id
                ]);
                $_SESSION['action_success'] = 'Ruangan berhasil diperbarui!';
                header("Location: rooms.php");
                exit;
            } else {
                // Insert
                $stmt_in = $db->prepare("
                    INSERT INTO rooms (name, max_people, price_per_hour, description, image) 
                    VALUES (:name, :max_people, :price_per_hour, :description, :image)
                ");
                $stmt_in->execute([
                    ':name'           => $name,
                    ':max_people'     => $max_people,
                    ':price_per_hour' => $price_per_hour,
                    ':description'    => $description,
                    ':image'          => $image
                ]);
                $success = 'Ruangan baru berhasil ditambahkan!';
            }
        } catch (PDOException $e) {
            $error = 'Kesalahan saat menyimpan data ruangan: ' . $e->getMessage();
        }
    }
}

// Flash messages from redirect
if (isset($_SESSION['action_success'])) {
    $success = $_SESSION['action_success'];
    unset($_SESSION['action_success']);
}

// Fetch all rooms for table listing
try {
    $rooms = $db->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Room - Rental PS Booking System</title>
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
                    <h2>Manajemen Room Rental</h2>
                    <p class="text-muted">Kelola kapasitas pemain, harga rental per jam, dan aset visual room.</p>
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

            <div class="payment-grid" style="grid-template-columns: 360px 1fr;">
                
                <!-- Left: Form Card (Add/Edit) -->
                <div>
                    <div class="card">
                        <h3 class="text-cyan mb-3"><?= $edit_mode ? 'Edit Ruangan' : 'Tambah Ruangan Baru' ?></h3>
                        
                        <form action="rooms.php" method="POST">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="action_edit" value="1">
                                <input type="hidden" name="room_id" value="<?= $edit_id ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="name" class="form-label">Nama Ruangan</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: VIP Premium" required value="<?= sanitize($edit_mode ? $edit_name : '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="max_people" class="form-label">Kapasitas Maksimal (Orang)</label>
                                <input type="number" id="max_people" name="max_people" class="form-control" placeholder="Maksimal orang" min="1" required value="<?= $edit_mode ? $edit_max_people : 2 ?>">
                            </div>

                            <div class="form-group">
                                <label for="price_per_hour" class="form-label">Harga per Jam (Rp)</label>
                                <input type="number" id="price_per_hour" name="price_per_hour" class="form-control" placeholder="Harga dalam Rp" min="1000" step="1000" required value="<?= $edit_mode ? $edit_price_per_hour : 25000 ?>">
                            </div>

                            <div class="form-group">
                                <label for="description" class="form-label">Deskripsi Fasilitas</label>
                                <textarea id="description" name="description" class="form-control" rows="3" placeholder="Fasilitas AC, TV Size, Sofa..."><?= sanitize($edit_mode ? $edit_description : '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="image" class="form-label">Path Gambar Asset</label>
                                <input type="text" id="image" name="image" class="form-control" placeholder="assets/img/room-vip.jpg" required value="<?= sanitize($edit_mode ? $edit_image : 'assets/img/room-regular.jpg') ?>">
                            </div>

                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; margin-top: 10px;">
                                <?= $edit_mode ? 'Simpan Perubahan' : 'Tambah Ruangan' ?>
                            </button>

                            <?php if ($edit_mode): ?>
                                <a href="rooms.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 8px; text-align: center; display: block;">Batal Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Right: Listing Table -->
                <div>
                    <div class="card" style="padding: 25px;">
                        <h3 class="text-purple mb-3">Daftar Ruangan Rental</h3>
                        
                        <div class="table-responsive">
                            <table class="custom-table" style="background: transparent;">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">ID</th>
                                        <th>Tipe Room</th>
                                        <th style="text-align: center;">Maks Orang</th>
                                        <th>Harga per Jam</th>
                                        <th>Deskripsi / Fasilitas</th>
                                        <th style="text-align: center; width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rooms as $r): ?>
                                        <tr>
                                            <td><?= $r['id'] ?></td>
                                            <td><strong><?= sanitize($r['name']) ?></strong></td>
                                            <td style="text-align: center;"><span class="badge badge-pending" style="color:#fff; border-color:#fff; background:rgba(255,255,255,0.05);"><?= $r['max_people'] ?> Orang</span></td>
                                            <td class="text-gold" style="font-weight:600;"><?= format_rupiah($r['price_per_hour']) ?></td>
                                            <td style="max-width: 250px; font-size: 0.85rem;" class="text-muted"><?= sanitize($r['description']) ?></td>
                                            <td style="text-align: center;">
                                                <a href="rooms.php?edit=<?= $r['id'] ?>" class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 0.8rem; margin-right: 4px;">Edit</a>
                                                <a href="rooms.php?delete=<?= $r['id'] ?>" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.8rem;" onclick="return confirm('Apakah Anda yakin ingin menghapus ruangan ini?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>

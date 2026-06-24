<?php
/**
 * Admin - Manage Consoles (CRUD)
 */
require_once dirname(__DIR__) . '/config/database.php';

$error = '';
$success = '';

// Handle Delete Action
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM consoles WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success = 'Console berhasil dihapus!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus console: ' . $e->getMessage();
    }
}

// Handle Add / Edit Submission
$edit_mode = false;
$edit_id = 0;
$edit_name = '';
$edit_description = '';
$edit_image = 'assets/img/console-switch.jpg';

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    
    $stmt_fetch = $db->prepare("SELECT * FROM consoles WHERE id = ?");
    $stmt_fetch->execute([$edit_id]);
    $c_edit = $stmt_fetch->fetch();
    if ($c_edit) {
        $edit_name = $c_edit['name'];
        $edit_description = $c_edit['description'];
        $edit_image = $c_edit['image'];
    } else {
        $edit_mode = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(sanitize($_POST['name']));
    $description = trim(sanitize($_POST['description']));
    $image = trim(sanitize($_POST['image'])); // Text path for assets simplification
    
    if (empty($name)) {
        $error = 'Nama console tidak boleh kosong.';
    } else {
        try {
            if (isset($_POST['action_edit'])) {
                // Update
                $c_id = intval($_POST['console_id']);
                $stmt_up = $db->prepare("
                    UPDATE consoles 
                    SET name = :name, description = :description, image = :image 
                    WHERE id = :id
                ");
                $stmt_up->execute([
                    ':name'        => $name,
                    ':description' => $description,
                    ':image'       => $image,
                    ':id'          => $c_id
                ]);
                $_SESSION['action_success'] = 'Console berhasil diperbarui!';
                header("Location: consoles.php");
                exit;
            } else {
                // Insert
                $stmt_in = $db->prepare("
                    INSERT INTO consoles (name, description, image) 
                    VALUES (:name, :description, :image)
                ");
                $stmt_in->execute([
                    ':name'        => $name,
                    ':description' => $description,
                    ':image'       => $image
                ]);
                $success = 'Console baru berhasil ditambahkan!';
            }
        } catch (PDOException $e) {
            $error = 'Kesalahan saat menyimpan data: ' . $e->getMessage();
        }
    }
}

// Flash messages from redirect
if (isset($_SESSION['action_success'])) {
    $success = $_SESSION['action_success'];
    unset($_SESSION['action_success']);
}

// Fetch all consoles for the listing table
try {
    $consoles = $db->query("SELECT * FROM consoles ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Console - Rental PS Booking System</title>
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
                    <h2>Manajemen Console Game</h2>
                    <p class="text-muted">Tambah, ubah, dan hapus master data console yang disewakan.</p>
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
                        <h3 class="text-cyan mb-3"><?= $edit_mode ? 'Edit Console' : 'Tambah Console Baru' ?></h3>
                        
                        <form action="consoles.php" method="POST">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="action_edit" value="1">
                                <input type="hidden" name="console_id" value="<?= $edit_id ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="name" class="form-label">Nama Console</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: PlayStation 5" required value="<?= sanitize($edit_mode ? $edit_name : '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea id="description" name="description" class="form-control" rows="3" placeholder="Masukkan spesifikasi singkat..."><?= sanitize($edit_mode ? $edit_description : '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="image" class="form-label">Path Gambar Asset</label>
                                <input type="text" id="image" name="image" class="form-control" placeholder="assets/img/console-ps5.jpg" required value="<?= sanitize($edit_mode ? $edit_image : 'assets/img/console-ps5.jpg') ?>">
                            </div>

                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; margin-top: 10px;">
                                <?= $edit_mode ? 'Simpan Perubahan' : 'Tambah Console' ?>
                            </button>

                            <?php if ($edit_mode): ?>
                                <a href="consoles.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 8px; text-align: center; display: block;">Batal Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Right: Listing Table -->
                <div>
                    <div class="card" style="padding: 25px;">
                        <h3 class="text-purple mb-3">Daftar Console Aktif</h3>
                        
                        <div class="table-responsive">
                            <table class="custom-table" style="background: transparent;">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">ID</th>
                                        <th>Nama Console</th>
                                        <th>Deskripsi</th>
                                        <th>Asset Path</th>
                                        <th style="text-align: center; width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consoles as $c): ?>
                                        <tr>
                                            <td><?= $c['id'] ?></td>
                                            <td><strong><?= sanitize($c['name']) ?></strong></td>
                                            <td style="max-width: 250px; font-size: 0.85rem;" class="text-muted"><?= sanitize($c['description']) ?></td>
                                            <td style="font-size: 0.85rem; font-family: monospace;" class="text-cyan"><?= sanitize($c['image']) ?></td>
                                            <td style="text-align: center;">
                                                <a href="consoles.php?edit=<?= $c['id'] ?>" class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 0.8rem; margin-right: 4px;">Edit</a>
                                                <a href="consoles.php?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.8rem;" onclick="return confirm('Apakah Anda yakin ingin menghapus console ini? Semua game terkait juga akan terhapus.');">Delete</a>
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

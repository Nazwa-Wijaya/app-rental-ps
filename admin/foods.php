<?php
/**
 * Admin - Manage Food & Drinks (CRUD)
 */
require_once dirname(__DIR__) . '/config/database.php';

$error = '';
$success = '';

// Handle Delete Action
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM foods WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success = 'Menu makanan/minuman berhasil dihapus!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus menu: ' . $e->getMessage();
    }
}

// Handle Add / Edit Submission
$edit_mode = false;
$edit_id = 0;
$edit_name = '';
$edit_category = '';
$edit_price = 10000;
$edit_description = '';
$edit_image = 'assets/img/food-aqua.jpg';

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    
    $stmt_fetch = $db->prepare("SELECT * FROM foods WHERE id = ?");
    $stmt_fetch->execute([$edit_id]);
    $f_edit = $stmt_fetch->fetch();
    if ($f_edit) {
        $edit_name = $f_edit['name'];
        $edit_category = $f_edit['category'];
        $edit_price = floatval($f_edit['price']);
        $edit_description = $f_edit['description'];
        $edit_image = $f_edit['image'];
    } else {
        $edit_mode = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(sanitize($_POST['name']));
    $category = sanitize($_POST['category']);
    $price = floatval($_POST['price']);
    $description = trim(sanitize($_POST['description']));
    $image = trim(sanitize($_POST['image']));
    
    $categories = ['Basic Drinks', 'Coffee', 'Tea', 'Snacks', 'Instant Noodles', 'Others'];
    
    if (empty($name) || !in_array($category, $categories) || $price <= 0) {
        $error = 'Harap lengkapi semua kolom dengan benar. Harga harus bernilai positif.';
    } else {
        try {
            if (isset($_POST['action_edit'])) {
                // Update
                $f_id = intval($_POST['food_id']);
                $stmt_up = $db->prepare("
                    UPDATE foods 
                    SET name = :name, category = :category, price = :price, 
                        description = :description, image = :image 
                    WHERE id = :id
                ");
                $stmt_up->execute([
                    ':name'        => $name,
                    ':category'    => $category,
                    ':price'       => $price,
                    ':description' => $description,
                    ':image'       => $image,
                    ':id'          => $f_id
                ]);
                $_SESSION['action_success'] = 'Menu berhasil diperbarui!';
                header("Location: foods.php");
                exit;
            } else {
                // Insert
                $stmt_in = $db->prepare("
                    INSERT INTO foods (name, category, price, description, image) 
                    VALUES (:name, :category, :price, :description, :image)
                ");
                $stmt_in->execute([
                    ':name'        => $name,
                    ':category'    => $category,
                    ':price'       => $price,
                    ':description' => $description,
                    ':image'       => $image
                ]);
                $success = 'Menu baru berhasil ditambahkan!';
            }
        } catch (PDOException $e) {
            $error = 'Kesalahan saat menyimpan data menu: ' . $e->getMessage();
        }
    }
}

// Flash messages from redirect
if (isset($_SESSION['action_success'])) {
    $success = $_SESSION['action_success'];
    unset($_SESSION['action_success']);
}

// Fetch all foods
try {
    $foods = $db->query("SELECT * FROM foods ORDER BY category ASC, name ASC")->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Food & Drinks - Rental PS Booking System</title>
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
                    <h2>Manajemen Food & Drinks</h2>
                    <p class="text-muted">Kelola persediaan menu camilan, mi instan, dan minuman segar.</p>
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
                        <h3 class="text-cyan mb-3"><?= $edit_mode ? 'Edit Menu' : 'Tambah Menu Baru' ?></h3>
                        
                        <form action="foods.php" method="POST">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="action_edit" value="1">
                                <input type="hidden" name="food_id" value="<?= $edit_id ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="name" class="form-label">Nama Menu</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: French Fries XL" required value="<?= sanitize($edit_mode ? $edit_name : '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="category" class="form-label">Kategori</label>
                                <select id="category" name="category" class="form-control" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php 
                                    $cats = ['Basic Drinks', 'Coffee', 'Tea', 'Snacks', 'Instant Noodles', 'Others'];
                                    foreach ($cats as $cat) {
                                        $sel = ($cat === $edit_category) ? 'selected' : '';
                                        echo "<option value=\"$cat\" $sel>$cat</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="price" class="form-label">Harga Jual (Rp)</label>
                                <input type="number" id="price" name="price" class="form-control" placeholder="Contoh: 12000" min="500" step="500" required value="<?= $edit_mode ? $edit_price : 10000 ?>">
                            </div>

                            <div class="form-group">
                                <label for="description" class="form-label">Deskripsi Menu</label>
                                <textarea id="description" name="description" class="form-control" rows="3" placeholder="Deskripsi rasa atau ukuran porsi..."><?= sanitize($edit_mode ? $edit_description : '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="image" class="form-label">Path Gambar Asset</label>
                                <input type="text" id="image" name="image" class="form-control" placeholder="assets/img/food-chips.jpg" required value="<?= sanitize($edit_mode ? $edit_image : 'assets/img/food-cola.jpg') ?>">
                            </div>

                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; margin-top: 10px;">
                                <?= $edit_mode ? 'Simpan Perubahan' : 'Tambah Menu' ?>
                            </button>

                            <?php if ($edit_mode): ?>
                                <a href="foods.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 8px; text-align: center; display: block;">Batal Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Right: Listing Table -->
                <div>
                    <div class="card" style="padding: 25px;">
                        <h3 class="text-purple mb-3">Daftar Menu Corner</h3>
                        
                        <div class="table-responsive">
                            <table class="custom-table" style="background: transparent;">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">ID</th>
                                        <th>Nama Item</th>
                                        <th>Kategori</th>
                                        <th>Harga</th>
                                        <th>Deskripsi</th>
                                        <th style="text-align: center; width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($foods as $f): ?>
                                        <tr>
                                            <td><?= $f['id'] ?></td>
                                            <td><strong><?= sanitize($f['name']) ?></strong></td>
                                            <td><span class="badge badge-pending" style="color:#fff; border-color:var(--primary); background:rgba(157,78,221,0.05);"><?= sanitize($f['category']) ?></span></td>
                                            <td class="text-gold" style="font-weight:600;"><?= format_rupiah($f['price']) ?></td>
                                            <td style="max-width: 250px; font-size: 0.85rem;" class="text-muted"><?= sanitize($f['description']) ?></td>
                                            <td style="text-align: center;">
                                                <a href="foods.php?edit=<?= $f['id'] ?>" class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 0.8rem; margin-right: 4px;">Edit</a>
                                                <a href="foods.php?delete=<?= $f['id'] ?>" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.8rem;" onclick="return confirm('Apakah Anda yakin ingin menghapus menu ini?');">Delete</a>
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

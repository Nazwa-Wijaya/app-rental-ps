<?php
/**
 * Admin - Manage Games (CRUD)
 */
require_once dirname(__DIR__) . '/config/database.php';

$error = '';
$success = '';

// Handle Delete Action
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM games WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success = 'Game berhasil dihapus!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus game: ' . $e->getMessage();
    }
}

// Fetch all consoles for select dropdown
try {
    $consoles = $db->query("SELECT id, name FROM consoles ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

// Handle Add / Edit Submission
$edit_mode = false;
$edit_id = 0;
$edit_name = '';
$edit_console_id = 0;

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    
    $stmt_fetch = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt_fetch->execute([$edit_id]);
    $g_edit = $stmt_fetch->fetch();
    if ($g_edit) {
        $edit_name = $g_edit['name'];
        $edit_console_id = intval($g_edit['console_id']);
    } else {
        $edit_mode = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(sanitize($_POST['name']));
    $console_id = intval($_POST['console_id']);
    
    if (empty($name) || $console_id <= 0) {
        $error = 'Harap isi nama game dan pilih console yang sesuai.';
    } else {
        try {
            if (isset($_POST['action_edit'])) {
                // Update
                $g_id = intval($_POST['game_id']);
                $stmt_up = $db->prepare("UPDATE games SET name = :name, console_id = :console_id WHERE id = :id");
                $stmt_up->execute([
                    ':name'       => $name,
                    ':console_id' => $console_id,
                    ':id'         => $g_id
                ]);
                $_SESSION['action_success'] = 'Game berhasil diperbarui!';
                header("Location: games.php");
                exit;
            } else {
                // Insert
                $stmt_in = $db->prepare("INSERT INTO games (name, console_id) VALUES (:name, :console_id)");
                $stmt_in->execute([
                    ':name'       => $name,
                    ':console_id' => $console_id
                ]);
                $success = 'Game baru berhasil ditambahkan!';
            }
        } catch (PDOException $e) {
            $error = 'Kesalahan saat menyimpan data game: ' . $e->getMessage();
        }
    }
}

// Flash messages from redirect
if (isset($_SESSION['action_success'])) {
    $success = $_SESSION['action_success'];
    unset($_SESSION['action_success']);
}

// Fetch all games with console name
try {
    $games = $db->query("
        SELECT g.*, c.name as console_name 
        FROM games g 
        JOIN consoles c ON g.console_id = c.id 
        ORDER BY c.name ASC, g.name ASC
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
    <title>Kelola Game - Rental PS Booking System</title>
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
                    <h2>Manajemen Library Game</h2>
                    <p class="text-muted">Kelola koleksi judul game berdasarkan masing-masing console.</p>
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
                        <h3 class="text-cyan mb-3"><?= $edit_mode ? 'Edit Game' : 'Tambah Game Baru' ?></h3>
                        
                        <form action="games.php" method="POST">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="action_edit" value="1">
                                <input type="hidden" name="game_id" value="<?= $edit_id ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="console_id" class="form-label">Pilih Console</label>
                                <select id="console_id" name="console_id" class="form-control" required>
                                    <option value="">-- Pilih Console --</option>
                                    <?php foreach ($consoles as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($c['id'] == $edit_console_id) ? 'selected' : '' ?>>
                                            <?= sanitize($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="name" class="form-label">Judul Game</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: GTA VI / FIFA 25" required value="<?= sanitize($edit_mode ? $edit_name : '') ?>">
                            </div>

                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; margin-top: 10px;">
                                <?= $edit_mode ? 'Simpan Perubahan' : 'Tambah Game' ?>
                            </button>

                            <?php if ($edit_mode): ?>
                                <a href="games.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 8px; text-align: center; display: block;">Batal Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Right: Listing Table -->
                <div>
                    <div class="card" style="padding: 25px;">
                        <h3 class="text-purple mb-3">Daftar Game Tersedia</h3>
                        
                        <div class="table-responsive">
                            <table class="custom-table" style="background: transparent;">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">ID</th>
                                        <th>Judul Game</th>
                                        <th>Console</th>
                                        <th style="text-align: center; width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($games as $g): ?>
                                        <tr>
                                            <td><?= $g['id'] ?></td>
                                            <td><strong><?= sanitize($g['name']) ?></strong></td>
                                            <td><span class="badge badge-paid" style="background:rgba(46,196,182,0.05); color:#2ec4b6; border-color:#2ec4b6;"><?= sanitize($g['console_name']) ?></span></td>
                                            <td style="text-align: center;">
                                                <a href="games.php?edit=<?= $g['id'] ?>" class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 0.8rem; margin-right: 4px;">Edit</a>
                                                <a href="games.php?delete=<?= $g['id'] ?>" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.8rem;" onclick="return confirm('Apakah Anda yakin ingin menghapus game ini?');">Delete</a>
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

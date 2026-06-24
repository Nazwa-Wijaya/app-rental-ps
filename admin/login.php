<?php
/**
 * Admin Login Page
 */
require_once dirname(__DIR__) . '/config/database.php';

// Redirect if already logged in as admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(sanitize($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Harap isi email dan password admin.';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND role = 'admin'");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Initialize admin sessions
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['admin_logged_in'] = true;

                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'Email atau password admin salah, atau Anda bukan administrator.';
            }
        } catch (PDOException $e) {
            $error = 'Kesalahan sistem login admin: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Rental PS Booking System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Sticky Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="../index.php" class="logo">RENTAL<span>PS</span> <span>(ADMIN)</span></a>
            <ul class="nav-links">
                <li class="nav-item"><a href="../index.php">Kembali Ke Website</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Auth Section -->
    <main class="auth-wrapper">
        <div class="container">
            <div class="card auth-card" style="border-color: #ffc107;">
                <h1 class="auth-title" style="color: #ffc107;">Admin Portal</h1>
                <p class="auth-subtitle">Masuk ke panel manajemen administrator</p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <strong>Error!</strong> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Admin</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="admin@gmail.com" required value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password Admin</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password admin" required>
                    </div>

                    <button type="submit" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">Login Administrator</button>
                </form>

                <!-- Admin credentials demo notice -->
                <div style="margin-top: 25px; padding: 12px; background: rgba(255,193,7,0.1); border-radius: 6px; font-size: 0.85rem; border: 1px dashed rgba(255,193,7,0.3);">
                    <p style="color: var(--secondary); font-weight: bold; margin-bottom: 2px;">Default Admin Account:</p>
                    <p>• <strong>Email:</strong> admin@gmail.com</p>
                    <p>• <strong>Password:</strong> admin123</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <span>Rental PS Booking System Admin Panel</span>. All rights reserved.</p>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
</body>
</html>

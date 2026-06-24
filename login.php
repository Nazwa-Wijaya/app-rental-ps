<?php
/**
 * User Login Page
 */
require_once __DIR__ . '/config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';
$success = '';

// Check registration success alert from session
if (isset($_SESSION['reg_success'])) {
    $success = $_SESSION['reg_success'];
    unset($_SESSION['reg_success']);
}

// Check unauthorized access prompt (e.g. from booking.php)
if (isset($_SESSION['login_prompt'])) {
    $error = $_SESSION['login_prompt'];
    unset($_SESSION['login_prompt']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(sanitize($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Harap isi email dan password Anda.';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Initialize session vars
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_phone'] = $user['phone'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    $_SESSION['admin_logged_in'] = true;
                    header("Location: admin/dashboard.php");
                } else {
                    // Redirect to booking if they were trying to book, otherwise dashboard
                    if (isset($_SESSION['redirect_to_booking']) && $_SESSION['redirect_to_booking'] === true) {
                        unset($_SESSION['redirect_to_booking']);
                        header("Location: booking.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                }
                exit;
            } else {
                $error = 'Email atau password yang Anda masukkan salah.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem saat login: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rental PS Booking System</title>
    <meta name="description" content="Masuk ke akun Rental PS Booking System untuk melakukan pemesanan room Nintendo Switch, PS4, dan PS5 secara online.">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Sticky Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">RENTAL<span>PS</span></a>
            <ul class="nav-links">
                <li class="nav-item"><a href="index.php#home">Home</a></li>
                <li class="nav-item"><a href="index.php#rooms">Rooms</a></li>
                <li class="nav-item"><a href="index.php#food-drinks">Food & Drinks</a></li>
                <li class="nav-item"><a href="index.php#faq">FAQ</a></li>
                <li class="nav-item"><a href="register.php" class="btn btn-outline btn-sm">Daftar</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Login Section -->
    <main class="auth-wrapper">
        <div class="container">
            <div class="card auth-card">
                <h1 class="auth-title">Login Gamer</h1>
                <p class="auth-subtitle">Masuk untuk memesan room gaming Anda</p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <strong>Error!</strong> <?= $error ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <strong>Berhasil!</strong> <?= $success ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="contoh@gmail.com" required value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password Anda" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Login Sekarang</button>
                </form>

                <!-- Admin credentials demo notice -->
                <div style="margin-top: 25px; padding: 12px; background: rgba(255,193,7,0.1); border-radius: 6px; font-size: 0.85rem; border: 1px dashed rgba(255,193,7,0.3);">
                    <p class="text-center" style="color: var(--secondary); font-weight: bold; margin-bottom: 5px;">Akun Uji Coba Default:</p>
                    <p style="margin-bottom: 2px;">• <strong>User:</strong> user@gmail.com | <strong>Pass:</strong> user123</p>
                    <p>• <strong>Admin:</strong> admin@gmail.com | <strong>Pass:</strong> admin123</p>
                </div>

                <p class="text-center text-muted" style="margin-top: 25px; font-size: 0.95rem;">
                    Belum memiliki akun? <a href="register.php" class="text-cyan">Daftar sekarang</a>
                </p>
            </div>
        </div>
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

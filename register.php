<?php
/**
 * User Registration Page
 */
require_once __DIR__ . '/config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(sanitize($_POST['name']));
    $email = trim(sanitize($_POST['email']));
    $phone = trim(sanitize($_POST['phone']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validations
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = 'Harap isi semua field formulir.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus 6 karakter.';
    } else {
        try {
            // Check if email already registered
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $error = 'Email tersebut sudah terdaftar. Silakan gunakan email lain.';
            } else {
                // Hash the password securely using bcrypt
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insert_stmt = $db->prepare("
                    INSERT INTO users (name, email, phone, password, role) 
                    VALUES (:name, :email, :phone, :password, 'user')
                ");
                $insert_stmt->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':phone'    => $phone,
                    ':password' => $hashed_password
                ]);

                $_SESSION['reg_success'] = 'Registrasi berhasil! Silakan login menggunakan akun baru Anda.';
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem saat mendaftar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Rental PS Booking System</title>
    <meta name="description" content="Daftar akun baru di Rental PS Booking System dan nikmati pengalaman bermain Nintendo Switch, PS4, dan PS5 di room gaming modern.">
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
                <li class="nav-item"><a href="login.php" class="btn btn-primary btn-sm">Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Registration Section -->
    <main class="auth-wrapper">
        <div class="container">
            <div class="card auth-card">
                <h1 class="auth-title">Daftar Akun</h1>
                <p class="auth-subtitle">Gabung bersama komunitas gamer Rental PS Booking System</p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <strong>Error!</strong> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Masukkan nama lengkap Anda" required value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="contoh@gmail.com" required value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Nomor WhatsApp</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="081234567890" required value="<?= isset($_POST['phone']) ? sanitize($_POST['phone']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Ulangi password Anda" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Daftar Sekarang</button>
                </form>

                <p class="text-center text-muted" style="margin-top: 25px; font-size: 0.95rem;">
                    Sudah memiliki akun? <a href="login.php" class="text-cyan">Login di sini</a>
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

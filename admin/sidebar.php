<?php
/**
 * Shared Admin Sidebar Layout Component
 */
require_once dirname(__DIR__) . '/config/database.php';

// Secure page access
check_admin();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="admin-sidebar">
    <div class="admin-sidebar-logo">
        <a href="../index.php" class="logo">RENTAL<span>PS</span></a>
        <div style="font-size:0.75rem; text-transform:uppercase; color:var(--secondary); letter-spacing:1.5px; margin-top:5px; font-weight:bold;">Admin Control</div>
    </div>
    
    <ul class="admin-nav">
        <li class="admin-nav-item <?= ($current_page === 'dashboard.php') ? 'active' : '' ?>">
            <a href="dashboard.php">📊 Dashboard</a>
        </li>
        <li class="admin-nav-item <?= ($current_page === 'consoles.php') ? 'active' : '' ?>">
            <a href="consoles.php">🎮 Kelola Console</a>
        </li>
        <li class="admin-nav-item <?= ($current_page === 'rooms.php') ? 'active' : '' ?>">
            <a href="rooms.php">🚪 Kelola Room</a>
        </li>
        <li class="admin-nav-item <?= ($current_page === 'games.php') ? 'active' : '' ?>">
            <a href="games.php">🕹️ Kelola Game</a>
        </li>
        <li class="admin-nav-item <?= ($current_page === 'foods.php') ? 'active' : '' ?>">
            <a href="foods.php">🍔 Food & Drinks</a>
        </li>
        <li class="admin-nav-item <?= ($current_page === 'bookings.php') ? 'active' : '' ?>">
            <a href="bookings.php">📅 Daftar Booking</a>
        </li>
        
        <li class="admin-nav-item" style="margin-top: 40px; border-top: 1px solid rgba(157,78,221,0.2); padding-top: 20px;">
            <a href="../index.php">🌐 Lihat Website</a>
        </li>
        <li class="admin-nav-item">
            <a href="logout.php" style="color: #e63946;">🚪 Logout Admin</a>
        </li>
    </ul>
</div>
